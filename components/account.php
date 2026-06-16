<?php
session_start();
require_once 'db.php';

// Not logged in → redirect
if (empty($_SESSION['steam_id']) && empty($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

$page_title = 'Konto';
$welcome    = isset($_GET['welcome']);

$is_admin   = !empty($_SESSION['is_admin']);
$steam_id   = $_SESSION['steam_id'] ?? null;
$nickname   = $_SESSION['nickname'] ?? 'Gracz';

// ── Logout handler ─────────────────────────────────────────
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// ── Load full player data ──────────────────────────────────
$player      = null;
$fav_hero    = null;
$match_stats = null;
$recent      = [];

if (!$is_admin && $steam_id && $db_conn) {

    // Player profile
    $rows = db_query($db_conn,
        "SELECT STEAM_ID, NICKNAME, RANK, REGION,
                TO_CHAR(ACCOUNT_CREATED, 'YYYY-MM-DD') AS CREATED_DATE
         FROM Player
         WHERE STEAM_ID = :sid",
        [':sid' => $steam_id]
    );
    $player = $rows[0] ?? null;

    // Favourite hero (most played)
    $fav_rows = db_query($db_conn,
        "SELECT h.NAME AS HERO_NAME,
                h.PRIMARY_ATTRIBUTE,
                COUNT(hp.ID)            AS GAMES,
                SUM(CASE WHEN t.ID = m.WINNER_ID THEN 1 ELSE 0 END) AS WINS,
                ROUND(AVG(hp.KDA), 2)   AS AVG_KDA,
                ROUND(AVG(hp.NETTO), 0) AS AVG_NETTO
         FROM Hero_Played hp
         JOIN Hero h ON h.ID = hp.HERO_ID
         JOIN Team t
              ON (t.HP1=hp.ID OR t.HP2=hp.ID OR t.HP3=hp.ID
                  OR t.HP4=hp.ID OR t.HP5=hp.ID)
         JOIN Match_Game m
              ON (m.TEAM1_ID=t.ID OR m.TEAM2_ID=t.ID)
         WHERE hp.STEAM_ID = :sid
         GROUP BY h.NAME, h.PRIMARY_ATTRIBUTE
         ORDER BY GAMES DESC, AVG_KDA DESC
         FETCH FIRST 1 ROWS ONLY",
        [':sid' => $steam_id]
    );
    $fav_hero = $fav_rows[0] ?? null;

    // Overall stats
    $stats_rows = db_query($db_conn,
        "SELECT COUNT(hp.ID)  AS TOTAL_GAMES,
                SUM(CASE WHEN t.ID = m.WINNER_ID THEN 1 ELSE 0 END) AS TOTAL_WINS,
                ROUND(AVG(hp.KDA),  2)   AS AVG_KDA,
                ROUND(AVG(hp.NETTO), 0)  AS AVG_NETTO,
                SUM(hp.KILLS)    AS TOTAL_KILLS,
                SUM(hp.DEATHS)   AS TOTAL_DEATHS,
                SUM(hp.ASSISTS)  AS TOTAL_ASSISTS
         FROM Hero_Played hp
         JOIN Team t
              ON (t.HP1=hp.ID OR t.HP2=hp.ID OR t.HP3=hp.ID
                  OR t.HP4=hp.ID OR t.HP5=hp.ID)
         JOIN Match_Game m
              ON (m.TEAM1_ID=t.ID OR m.TEAM2_ID=t.ID)
         WHERE hp.STEAM_ID = :sid",
        [':sid' => $steam_id]
    );
    $match_stats = $stats_rows[0] ?? null;

    // Recent matches (last 5)
    $recent = db_query($db_conn,
        "SELECT
             TO_CHAR(m.MATCH_TIME,'DD.MM.YYYY HH24:MI') AS MATCH_DATE,
             m.ID AS MATCH_ID,
             h.NAME  AS HERO_NAME,
             hp.KILLS, hp.DEATHS, hp.ASSISTS,
             ROUND(hp.KDA,2) AS KDA,
             hp.NETTO,
             t.SIDE  AS MY_SIDE,
             tw.SIDE AS WINNER_SIDE,
             CASE WHEN t.ID = m.WINNER_ID THEN 'WIN' ELSE 'LOSS' END AS RESULT,
             CASE m.IS_RANKED WHEN 1 THEN 'Ranked' ELSE 'Normal' END AS GTYPE
         FROM Hero_Played hp
         JOIN Hero h ON h.ID = hp.HERO_ID
         JOIN Team t
              ON (t.HP1=hp.ID OR t.HP2=hp.ID OR t.HP3=hp.ID
                  OR t.HP4=hp.ID OR t.HP5=hp.ID)
         JOIN Match_Game m
              ON (m.TEAM1_ID=t.ID OR m.TEAM2_ID=t.ID)
         JOIN Team tw ON tw.ID = m.WINNER_ID
         WHERE hp.STEAM_ID = :sid
         ORDER BY m.MATCH_TIME DESC
         FETCH FIRST 5 ROWS ONLY",
        [':sid' => $steam_id]
    );
}

// ── Rank badge map ─────────────────────────────────────────
$rank_bc = [
    'Herald'=>'badge-herald','Guardian'=>'badge-guardian','Crusader'=>'badge-crusader',
    'Archon'=>'badge-archon','Legend'=>'badge-legend','Ancient'=>'badge-ancient',
    'Divine'=>'badge-divine','Immortal'=>'badge-immortal'
];
$attr_icon = ['Strength'=>'💪','Agility'=>'🏃','Intelligence'=>'🧠','Universal'=>'⚡'];

require_once 'header.php';
?>

<style>
    /* ── ACCOUNT PAGE ─────────────────────────────────────── */
    .account-grid {
      display: grid;
      grid-template-columns: 320px 1fr;
      gap: 1.5rem;
      align-items: start;
    }
    @media (max-width: 820px) {
      .account-grid { grid-template-columns: 1fr; }
    }

    /* Profile card */
    .profile-card {
      background: var(--bg1); border: 1px solid var(--border);
      border-top: 3px solid var(--red);
      overflow: hidden;
    }
    .profile-avatar {
      background: linear-gradient(135deg, var(--red-dim), var(--bg3));
      height: 120px; display: flex; align-items: center;
      justify-content: center; position: relative;
    }
    .profile-avatar-letter {
      font-family: var(--font-head); font-size: 3.5rem;
      font-weight: 900; color: rgba(255,255,255,0.15);
      text-transform: uppercase;
    }
    .profile-avatar-icon {
      position: absolute; font-size: 3.5rem;
    }
    .profile-body { padding: 1.4rem; }
    .profile-nick {
      font-family: var(--font-head); font-size: 1.5rem;
      font-weight: 700; color: #fff; letter-spacing: 0.04em;
      margin-bottom: 0.5rem;
    }
    .profile-meta {
      font-family: var(--font-mono); font-size: 0.75rem;
      color: var(--text-dim); line-height: 1.8;
    }
    .profile-meta span { color: var(--text); }
    .profile-divider {
      height: 1px; background: var(--border); margin: 1rem 0;
    }

    /* Admin card */
    .admin-profile-card {
      background: var(--bg1); border: 1px solid rgba(212,168,67,0.3);
      border-top: 3px solid var(--gold); padding: 2rem;
    }
    .admin-badge {
      display: inline-flex; align-items: center; gap: 8px;
      background: rgba(212,168,67,0.1); border: 1px solid rgba(212,168,67,0.3);
      padding: 0.5rem 1rem; font-family: var(--font-mono);
      font-size: 0.8rem; color: var(--gold); letter-spacing: 0.1em;
      text-transform: uppercase; margin-bottom: 1rem;
    }

    /* Stat blocks */
    .stat-block {
      background: var(--bg2); border: 1px solid var(--border);
      padding: 1rem 1.4rem; margin-bottom: 1rem;
    }
    .stat-block-title {
      font-family: var(--font-mono); font-size: 0.7rem;
      color: var(--red); letter-spacing: 0.12em;
      text-transform: uppercase; margin-bottom: 0.8rem;
    }
    .stats-grid {
      display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.8rem;
    }
    .stats-grid-2 {
      display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.8rem;
    }
    .stat-item { }
    .stat-item .val {
      font-family: var(--font-head); font-size: 1.5rem;
      font-weight: 700; color: #fff; line-height: 1;
    }
    .stat-item .val.red  { color: var(--red-bright); }
    .stat-item .val.gold { color: var(--gold); }
    .stat-item .val.grn  { color: #66bb6a; }
    .stat-item .lbl {
      font-family: var(--font-mono); font-size: 0.65rem;
      color: var(--text-muted); margin-top: 2px; letter-spacing: 0.08em;
    }

    /* Fav hero card */
    .fav-hero-card {
      background: linear-gradient(135deg, rgba(192,57,43,0.12), var(--bg2));
      border: 1px solid var(--red-dim); padding: 1.2rem 1.4rem;
      margin-bottom: 1rem;
    }
    .fav-label {
      font-family: var(--font-mono); font-size: 0.68rem;
      color: var(--red); letter-spacing: 0.12em; text-transform: uppercase;
      margin-bottom: 0.5rem;
    }
    .fav-name {
      font-family: var(--font-head); font-size: 1.6rem;
      font-weight: 700; color: #fff; letter-spacing: 0.04em;
    }
    .fav-attr {
      font-family: var(--font-mono); font-size: 0.78rem;
      color: var(--text-dim); margin-top: 0.2rem;
    }

    /* Match history */
    .match-row {
      display: grid;
      grid-template-columns: 60px 1fr 1fr 80px 80px 90px;
      gap: 0.5rem; align-items: center;
      padding: 0.6rem 1rem;
      border-bottom: 1px solid var(--border);
      transition: background 0.12s;
    }
    .match-row:hover { background: var(--bg2); }
    .match-row:last-child { border-bottom: none; }
    .match-result {
      font-family: var(--font-head); font-size: 0.9rem;
      font-weight: 700; letter-spacing: 0.06em; text-align: center;
      padding: 3px 0;
    }
    .match-result.WIN  { color: #66bb6a; }
    .match-result.LOSS { color: var(--red-bright); }

    .logout-btn {
      display: inline-flex; align-items: center; gap: 6px;
      background: transparent; border: 1px solid var(--border);
      color: var(--text-muted); font-family: var(--font-head);
      font-size: 0.85rem; font-weight: 600; letter-spacing: 0.06em;
      text-transform: uppercase; padding: 0.45rem 1.2rem;
      cursor: pointer; text-decoration: none;
      transition: all 0.18s; margin-top: 1rem;
      display: block; text-align: center;
    }
    .logout-btn:hover { border-color: var(--red); color: var(--red-bright); }

    /* Welcome banner */
    .welcome-flash {
      background: rgba(76,175,80,0.1); border-left: 4px solid #4caf50;
      padding: 0.8rem 1.2rem; margin-bottom: 1.5rem;
      font-family: var(--font-mono); font-size: 0.85rem; color: #a5d6a7;
    }
</style>

<div class="page-wrap">

  <?php if ($welcome): ?>
    <div class="welcome-flash">
      🎉 Witaj na pokładzie, <strong><?= htmlspecialchars($nickname) ?></strong>! Konto zostało pomyślnie utworzone. Ranga startowa: Herald.
    </div>
  <?php endif; ?>

  <!-- ══════════════════════════════════════════════ -->
  <?php if ($is_admin): ?>
  <!-- ADMIN VIEW -->
  <div class="page-banner" data-label="ADMIN" style="margin-bottom:1.5rem;">
    <div class="banner-tag">// Konto administracyjne</div>
    <h1 class="banner-title">Panel <span>Admina</span></h1>
    <div class="banner-divider"></div>
  </div>

  <div style="max-width: 600px;">
    <div class="admin-profile-card">
      <div class="admin-badge">🔐 Oracle DBA Access</div>
      <div style="font-family:var(--font-head); font-size:1.8rem; font-weight:700; color:var(--gold); margin-bottom:0.3rem;">
        C##dota_app
      </div>
      <div style="font-family:var(--font-mono); font-size:0.8rem; color:var(--text-muted); margin-bottom:1.5rem;">
        Zalogowany jako administrator Oracle Database
      </div>

      <div style="display:flex; flex-direction:column; gap:0.6rem; margin-bottom:1.5rem;">
        <a href="add_match.php" class="btn btn-red" style="text-align:center; padding:0.7rem;">
          ⚔️ Dodaj nowy mecz
        </a>
        <a href="matches.php" class="btn btn-outline" style="text-align:center; padding:0.7rem;">
          🏆 Przeglądaj mecze
        </a>
        <a href="heroes.php" class="btn btn-outline" style="text-align:center; padding:0.7rem;">
          ⚔️ Bohaterowie
        </a>
      </div>

      <div style="background:rgba(212,168,67,0.05); border:1px solid rgba(212,168,67,0.15); padding:0.8rem; font-family:var(--font-mono); font-size:0.72rem; color:var(--gold); margin-bottom:1rem;">
        ⚡ Uprawnienia: SELECT, INSERT, UPDATE, DELETE<br>
        ⚡ Tabele: Hero, Item, Player, Hero_Played, Team, Match_Game<br>
        ⚡ Sesja aktywna: <?= date('Y-m-d H:i') ?>
      </div>

      <a href="?logout=1" class="logout-btn">🚪 Wyloguj się</a>
    </div>
  </div>

  <?php else: ?>
  <!-- PLAYER VIEW -->
  <div class="page-banner" data-label="ACCOUNT" style="margin-bottom:1.5rem;">
    <h1 class="banner-title">Twoje <span>Konto</span></h1>
    <div class="banner-divider"></div>
  </div>

  <div class="account-grid">

    <!-- LEFT: PROFILE CARD ─────────────────────────── -->
    <div>
      <div class="profile-card">
        <div class="profile-avatar">
          <span class="profile-avatar-letter"><?= substr($nickname, 0, 1) ?></span>
          <span class="profile-avatar-icon">👤</span>
        </div>
        <div class="profile-body">
          <div class="profile-nick"><?= htmlspecialchars($nickname) ?></div>

          <?php if ($player): ?>
            <div style="margin-bottom:0.8rem;">
              <span class="badge <?= $rank_bc[$player['RANK']] ?? 'badge-herald' ?>">
                <?= htmlspecialchars($player['RANK']) ?>
              </span>
            </div>
            <div class="profile-meta">
              Steam ID: <span><?= htmlspecialchars($player['STEAM_ID']) ?></span><br>
              Region: <span><?= htmlspecialchars($player['REGION'] ?? 'EU') ?></span><br>
              Konto od: <span><?= htmlspecialchars($player['CREATED_DATE'] ?? '—') ?></span>
            </div>
          <?php endif; ?>

          <div class="profile-divider"></div>

          <!-- Fav hero -->
          <?php if ($fav_hero): ?>
            <div class="fav-label">⭐ Ulubiony bohater</div>
            <div class="fav-name">
              <?= $attr_icon[$fav_hero['PRIMARY_ATTRIBUTE']] ?? '⚡' ?>
              <?= htmlspecialchars($fav_hero['HERO_NAME']) ?>
            </div>
            <div class="fav-attr">
              <?= htmlspecialchars($fav_hero['PRIMARY_ATTRIBUTE']) ?>
              &nbsp;·&nbsp; <?= $fav_hero['GAMES'] ?> rozgrywek
              &nbsp;·&nbsp; KDA <?= $fav_hero['AVG_KDA'] ?>
            </div>
          <?php else: ?>
            <div style="font-family:var(--font-mono); font-size:0.78rem; color:var(--text-muted);">
              Brak rozgrywek — zagraj kilka meczy!
            </div>
          <?php endif; ?>

          <a href="?logout=1" class="logout-btn">🚪 Wyloguj się</a>
        </div>
      </div>
    </div>

    <!-- RIGHT: STATS + HISTORY ─────────────────────── -->
    <div>

      <!-- Overall stats -->
      <?php if ($match_stats && (int)$match_stats['TOTAL_GAMES'] > 0):
        $total   = (int)$match_stats['TOTAL_GAMES'];
        $wins    = (int)$match_stats['TOTAL_WINS'];
        $losses  = $total - $wins;
        $wr      = $total > 0 ? round($wins / $total * 100, 1) : 0;
      ?>
      <div class="stat-block">
        <div class="stat-block-title">// Statystyki ogólne</div>
        <div class="stats-grid" style="margin-bottom:0.8rem;">
          <div class="stat-item">
            <div class="val"><?= $total ?></div>
            <div class="lbl">Mecze łącznie</div>
          </div>
          <div class="stat-item">
            <div class="val grn"><?= $wins ?></div>
            <div class="lbl">Wygrane</div>
          </div>
          <div class="stat-item">
            <div class="val red"><?= $losses ?></div>
            <div class="lbl">Przegrane</div>
          </div>
        </div>
        <div class="stats-grid">
          <div class="stat-item">
            <div class="val gold"><?= $wr ?>%</div>
            <div class="lbl">Win Rate</div>
          </div>
          <div class="stat-item">
            <div class="val"><?= $match_stats['AVG_KDA'] ?? '—' ?></div>
            <div class="lbl">Avg KDA</div>
          </div>
          <div class="stat-item">
            <div class="val"><?= number_format((int)($match_stats['AVG_NETTO'] ?? 0)) ?></div>
            <div class="lbl">Avg Netto</div>
          </div>
        </div>

        <!-- KDA bar -->
        <?php
          $tk = (int)($match_stats['TOTAL_KILLS']   ?? 0);
          $td = (int)($match_stats['TOTAL_DEATHS']  ?? 0);
          $ta = (int)($match_stats['TOTAL_ASSISTS']  ?? 0);
        ?>
        <div style="margin-top:1rem; display:flex; gap:1.5rem;">
          <div class="stat-item">
            <div class="val" style="font-size:1.1rem; color:#81c784;"><?= $tk ?></div>
            <div class="lbl">Kills</div>
          </div>
          <div class="stat-item">
            <div class="val" style="font-size:1.1rem; color:var(--red-bright);"><?= $td ?></div>
            <div class="lbl">Deaths</div>
          </div>
          <div class="stat-item">
            <div class="val" style="font-size:1.1rem; color:#64b5f6;"><?= $ta ?></div>
            <div class="lbl">Assists</div>
          </div>
        </div>
      </div>

      <?php else: ?>
      <div class="stat-block">
        <div class="stat-block-title">// Statystyki ogólne</div>
        <div style="font-family:var(--font-mono); font-size:0.8rem; color:var(--text-muted); padding:1rem 0;">
          Brak rozegranych meczy w bazie.
        </div>
      </div>
      <?php endif; ?>

      <!-- Recent matches -->
      <div class="stat-block" style="padding:0;">
        <div style="padding:1rem 1.4rem 0.6rem;">
          <div class="stat-block-title" style="margin:0;">// Ostatnie mecze</div>
        </div>
        <div>
          <!-- Headers -->
          <div class="match-row" style="background:var(--bg3); border-bottom:2px solid var(--border);">
            <span style="font-family:var(--font-mono); font-size:0.62rem; color:var(--text-muted); letter-spacing:0.1em;">WYNIK</span>
            <span style="font-family:var(--font-mono); font-size:0.62rem; color:var(--text-muted); letter-spacing:0.1em;">BOHATER</span>
            <span style="font-family:var(--font-mono); font-size:0.62rem; color:var(--text-muted); letter-spacing:0.1em;">DATA</span>
            <span style="font-family:var(--font-mono); font-size:0.62rem; color:var(--text-muted); letter-spacing:0.1em;">K/D/A</span>
            <span style="font-family:var(--font-mono); font-size:0.62rem; color:var(--text-muted); letter-spacing:0.1em;">NETTO</span>
            <span style="font-family:var(--font-mono); font-size:0.62rem; color:var(--text-muted); letter-spacing:0.1em;">TRYB</span>
          </div>
          <?php if (empty($recent)): ?>
            <div style="padding:1.5rem; font-family:var(--font-mono); font-size:0.8rem; color:var(--text-muted); text-align:center;">
              Brak meczy do wyświetlenia
            </div>
          <?php else: foreach ($recent as $r): ?>
            <div class="match-row">
              <span class="match-result <?= $r['RESULT'] ?>"><?= $r['RESULT'] ?></span>
              <span style="font-family:var(--font-head); font-size:0.9rem; color:#fff; font-weight:600;">
                <?= htmlspecialchars($r['HERO_NAME']) ?>
              </span>
              <span style="font-family:var(--font-mono); font-size:0.72rem; color:var(--text-dim);">
                <?= htmlspecialchars($r['MATCH_DATE']) ?>
              </span>
              <span style="font-family:var(--font-mono); font-size:0.78rem;">
                <span style="color:#81c784;"><?= $r['KILLS'] ?></span>
                <span style="color:var(--text-muted);">/</span>
                <span style="color:var(--red-bright);"><?= $r['DEATHS'] ?></span>
                <span style="color:var(--text-muted);">/</span>
                <span style="color:#64b5f6;"><?= $r['ASSISTS'] ?></span>
              </span>
              <span style="font-family:var(--font-mono); font-size:0.78rem; color:var(--gold);">
                <?= number_format((int)$r['NETTO']) ?>g
              </span>
              <span>
                <span class="badge <?= $r['GTYPE']==='Ranked' ? 'badge-ranked' : 'badge-normal' ?>"
                      style="font-size:0.6rem;">
                  <?= $r['GTYPE'] ?>
                </span>
              </span>
            </div>
          <?php endforeach; endif; ?>
        </div>
        <?php if (!empty($recent)): ?>
          <div style="padding:0.6rem 1rem; text-align:right;">
            <a href="players.php?search=<?= urlencode($nickname) ?>"
               class="btn btn-outline" style="font-size:0.75rem;">
              Pełna historia →
            </a>
          </div>
        <?php endif; ?>
      </div>

    </div><!-- /right col -->
  </div><!-- /account-grid -->
  <br/>
  <hr style="border-color:var(--border); margin:2rem 0;">
  <?php 
      $user_sql1 = db_query($db_conn, 
        "SELECT * 
        FROM table(
            PlayerStatistics.QueryLastMatches(" . $player['STEAM_ID'] . ", 10, 0, 0, TO_DATE('2005-05-05'), TO_DATE('2027-05-05'))
            )");

            echo "<h2>Ostatnie mecze (procedura)</h2>";
            echo "<p> 10 ostatnich nierankingowych meczów gracza (procedura PlayerStatistics.QueryLastMatches)</p>";

        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr>
                <th>ID</th>
            </tr>";
        foreach ($user_sql1 as $row) 
        {
            echo "<tr>";
            echo "<td>{$row['ID']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<br/><hr style='border-color:var(--border); margin:2rem 0;'>";
        //////////////////////////////////////////////////////////////////////////////////
        $user_sql2 = db_query($db_conn,"SELECT 
(PlayerStatistics.PlayerWinRate(".$player['STEAM_ID'].", (SELECT id FROM Hero WHERE name=(SELECT PlayerStatistics.GetFavouriteHero(".$player['STEAM_ID'].").name AS Fav FROM DUAL)), 0, TO_DATE('2005-05-05'), TO_DATE('2027-05-05')) || '%' ) AS UWU
FROM dual");
echo "<h2>Winrate gracza z ulubionym bohaterem (funkcja) w okresie: 2005-2027 </h2>";
$user_sql2_1 = db_query($db_conn,"SELECT PlayerStatistics.GetFavouriteHero(".$player['STEAM_ID'].").name AS Fav FROM DUAL");
print_r($user_sql2_1[0]['FAV']);
        print_r($user_sql2[0]['UWU']);
                echo "<br/><hr style='border-color:var(--border); margin:2rem 0;'>";
        //////////////////////////////////////////////////////////////////////////////////
        $user_sql3 = db_query($db_conn,"SELECT steam_id, nickname, PlayerStatistics.GetFavouritePosition(steam_id) AS FAV FROM Player WHERE steam_id = " . $player['STEAM_ID']);
        echo "<h2>Favourite position gracza (funkcja)</h2>";
        print_r($user_sql3[0]['FAV']);
        echo "<br/><hr style='border-color:var(--border); margin:2rem 0;'>";
        //////////////////////////////////////////////////////////////////////////////////
        $user_sql4 = db_query($db_conn,"SELECT PlayerStatistics.AvgKda(".$player['STEAM_ID'].", 0, TO_DATE('2005-05-05'), TO_DATE('2027-05-05')) AS KDA FROM dual");
        echo "<h2>Średnie KDA gracza (funkcja) w okresie: 2005-2027 </h2>";
        print_r($user_sql4[0]['KDA']);
                echo "<br/><hr style='border-color:var(--border); margin:2rem 0;'>";
        //////////////////////////////////////////////////////////////////////////////////
                echo "<br/><hr style='border-color:var(--border); margin:2rem 0;'>";
        //////////////////////////////////////////////////////////////////////////////////

  ?>
  <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>

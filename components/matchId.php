<?php
require_once 'db.php';

$match_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$match_id) 
{
    header('Location: matches.php');
    exit;
}

// ── Load match header ──────────────────────────────────────
$match_info = [];
if ($db_conn) 
{
    $rows = db_query($db_conn,
        "SELECT m.ID,
                TO_CHAR(m.MATCH_TIME,'YYYY-MM-DD HH24:MI') AS MATCH_DT,
                t1.ID AS T1_ID, t1.SIDE AS T1_SIDE,
                t2.ID AS T2_ID, t2.SIDE AS T2_SIDE,
                tw.SIDE AS WINNER_SIDE,
                CASE m.IS_RANKED WHEN 1 THEN 'Ranked' ELSE 'Normal' END AS GTYPE
         FROM Match_Game m
         JOIN Team t1 ON t1.ID = m.TEAM1_ID
         JOIN Team t2 ON t2.ID = m.TEAM2_ID
         JOIN Team tw ON tw.ID = m.WINNER_ID
         WHERE m.ID = $match_id"
    );
    $match_info = $rows[0] ?? [];
}

if (empty($match_info)) 
{
    $page_title = 'Mecz nie znaleziony';
    require_once 'header.php';
    echo '<div class="page-wrap"><div class="alert alert-error">⚠ Mecz #' . $match_id . ' nie istnieje.</div>';
    echo '<a href="matches.php" class="btn btn-outline">← Powrót</a></div>';
    require_once 'footer.php';
    exit;
}

// ── Load players for one team (by team ID) ─────────────────
function load_team_players($conn, $team_id) {
    // hp1..hp5 are Hero_Played IDs stored in Team
    $team = db_query($conn,
        "SELECT hp1,hp2,hp3,hp4,hp5 FROM Team WHERE ID = $team_id"
    );
    if (empty($team)) return [];

    $hp_ids = array_values($team[0]); // [hp1,hp2,hp3,hp4,hp5]
    $id_list = implode(',', array_map('intval', $hp_ids));
    $result = db_query($conn,
        "SELECT
            hp.ID, hp.POSITION, hp.KILLS, hp.DEATHS, hp.ASSISTS, hp.NETTO, hp.KDA,
            h.NAME AS HERO_NAME, h.PRIMARY_ATTRIBUTE,
            p.NICKNAME, p.RANK, p.STEAM_ID,
            i1.NAME AS ITEM1, i2.NAME AS ITEM2, i3.NAME AS ITEM3,
            i4.NAME AS ITEM4, i5.NAME AS ITEM5, i6.NAME AS ITEM6
         FROM Hero_Played hp
         JOIN Hero   h  ON h.ID  = hp.HERO_ID
         JOIN Player p  ON p.STEAM_ID = hp.STEAM_ID
         LEFT JOIN Item i1 ON i1.ID = hp.SLOT1
         LEFT JOIN Item i2 ON i2.ID = hp.SLOT2
         LEFT JOIN Item i3 ON i3.ID = hp.SLOT3
         LEFT JOIN Item i4 ON i4.ID = hp.SLOT4
         LEFT JOIN Item i5 ON i5.ID = hp.SLOT5
         LEFT JOIN Item i6 ON i6.ID = hp.SLOT6
         WHERE hp.ID IN ($id_list)
         ORDER BY hp.POSITION"
    );
    return $result;
}

$team1_players = $db_conn ? load_team_players($db_conn, $match_info['T1_ID']) : [];
$team2_players = $db_conn ? load_team_players($db_conn, $match_info['T2_ID']) : [];

// Team totals helper
function team_totals($players) {
    return [
        'kills'   => array_sum(array_column($players, 'KILLS')),
        'deaths'  => array_sum(array_column($players, 'DEATHS')),
        'assists' => array_sum(array_column($players, 'ASSISTS')),
        'netto'   => array_sum(array_column($players, 'NETTO')),
    ];
}

$t1 = team_totals($team1_players);
$t2 = team_totals($team2_players);

$winner = $match_info['WINNER_SIDE'];
$t1_side = $match_info['T1_SIDE'];
$t2_side = $match_info['T2_SIDE'];

$attr_icon = [
  'Strength'=>'https://cdn.steamstatic.com/apps/dota2/images/dota_react/icons/hero_strength.png',
  'Agility'=>'https://cdn.steamstatic.com/apps/dota2/images/dota_react/icons/hero_agility.png',
  'Intelligence'=>'https://cdn.steamstatic.com/apps/dota2/images/dota_react/icons/hero_intelligence.png',
  'Universal'=>'https://cdn.steamstatic.com/apps/dota2/images/dota_react/icons/hero_universal.png'
];
$rank_bc   = [
    'Herald'=>'badge-herald','Guardian'=>'badge-guardian','Crusader'=>'badge-crusader',
    'Archon'=>'badge-archon','Legend'=>'badge-legend','Ancient'=>'badge-ancient',
    'Divine'=>'badge-divine','Immortal'=>'badge-immortal'
];
$pos_label = [1=>'Carry',2=>'Mid',3=>'Offlane',4=>'Soft Sup',5=>'Hard Sup'];

$page_title = "Mecz #{$match_id}";
require_once 'header.php';
?>

<style>
  .match-hero-banner {
    text-align: center;
    padding: 2rem 1rem 1.5rem;
    margin-bottom: 2rem;
    position: relative;
  }

  .match-result-title {
    font-family: var(--font-head);
    font-size: 2rem; font-weight: 900;
    letter-spacing: 0.12em; text-transform: uppercase;
    margin-bottom: 0.6rem;
  }

  .match-result-title.radiant { color: #4caf50; text-shadow: 0 0 30px rgba(76,175,80,0.5); }
  .match-result-title.dire    { color: var(--red-bright); text-shadow: 0 0 30px rgba(231,76,60,0.5); }

  .score-row {
    display: flex; align-items: center; justify-content: center;
    gap: 1.5rem; margin-bottom: 0.6rem;
  }

  .score-num {
    font-family: var(--font-head);
    font-size: 3.5rem; font-weight: 900; line-height: 1;
  }

  .score-num.radiant { color: #4caf50; }
  .score-num.dire    { color: var(--red-bright); }
  .score-sep { font-family: var(--font-mono); color: var(--text-muted); font-size: 1.1rem; }

  .match-meta {
    font-family: var(--font-mono); font-size: 0.8rem;
    color: var(--text-muted); letter-spacing: 0.08em;
  }

  /* Team panel */
  .team-panel {
    margin-bottom: 2rem;
    border: 1px solid var(--border);
    overflow: hidden;
  }

  .team-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 0.8rem 1.4rem;
    font-family: var(--font-head); font-size: 1.2rem;
    font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase;
  }

  .team-header.radiant {
    background: linear-gradient(90deg, rgba(76,175,80,0.18), transparent);
    border-bottom: 2px solid rgba(76,175,80,0.4);
    color: #66bb6a;
  }

  .team-header.dire {
    background: linear-gradient(90deg, rgba(192,57,43,0.18), transparent);
    border-bottom: 2px solid rgba(192,57,43,0.4);
    color: var(--red-bright);
  }

  .team-header .team-winner-badge {
    font-family: var(--font-mono); font-size: 0.72rem;
    padding: 3px 12px; border: 1px solid;
    letter-spacing: 0.12em;
  }

  .team-header.radiant .team-winner-badge { border-color:#4caf50; color:#4caf50; }
  .team-header.dire    .team-winner-badge { border-color:var(--red); color:var(--red-bright); }

  /* Scoreboard table */
  .score-table { width:100%; border-collapse: collapse; background: var(--bg1); }

  .score-table thead tr {
    background: var(--bg3);
    border-bottom: 1px solid var(--border);
  }

  .score-table th {
    padding: 0.55rem 0.8rem;
    font-family: var(--font-mono); font-size: 0.68rem;
    color: var(--text-muted); text-align: center;
    letter-spacing: 0.1em; text-transform: uppercase;
    white-space: nowrap;
  }

  .score-table th:first-child,
  .score-table th:nth-child(2) { text-align: left; }

  .score-table tbody tr {
    border-bottom: 1px solid rgba(30,42,58,0.6);
    transition: background 0.12s;
  }
  .score-table tbody tr:last-child { border-bottom: none; }
  .score-table tbody tr:hover { background: var(--bg2); }

  .score-table td {
    padding: 0.6rem 0.8rem;
    text-align: center; vertical-align: middle;
    font-size: 0.88rem; color: var(--text);
  }

  .score-table td:first-child,
  .score-table td:nth-child(2) { text-align: left; }

  /* Hero cell */
  .hero-cell { display:flex; align-items:center; gap:0.6rem; }
  .hero-attr-icon { font-size: 0.9rem; }
  .hero-name { font-family:var(--font-head); font-size:0.95rem; font-weight:600; color:#fff; white-space:nowrap; }
  .player-nick { font-size:0.75rem; color:var(--text-dim); margin-top:1px; }

  /* KDA */
  .kda-cell { font-family:var(--font-mono); white-space: nowrap; }
  .kda-sep  { color: var(--text-muted); }
  .kda-val  {  font-weight:600; font-size:0.8rem; }

  /* Netto */
  .netto-cell 
  { 
  font-family:var(--font-mono) ; 
  font-weight:600; 
  color: var(--gold)!important; 
  }

  /* Items */
  .items-cell { display:flex; gap:3px; flex-wrap:wrap; align-items:center; justify-content:center; }
  .item-slot {
    background: var(--bg3); border: 1px solid var(--border);
    padding: 2px 6px; font-family: var(--font-mono);
    font-size: 0.62rem; color: var(--text-dim);
    white-space: nowrap; max-width: 100px; overflow:hidden;
    text-overflow: ellipsis;
    transition: border-color 0.15s, color 0.15s;
  }
  .item-slot:hover { border-color: var(--red-dim); color: var(--text); }
  .item-slot.empty { opacity: 0.2; }

  /* Position badge */
  .pos-badge {
    display:inline-block; width:20px; height:20px; line-height:20px;
    text-align:center; border-radius:2px;
    font-family:var(--font-mono); font-size:0.72rem; font-weight:700;
    background: var(--bg3); color: var(--text-muted); border: 1px solid var(--border);
  }

  /* Totals row */
  .totals-row td { background: var(--bg3) !important; font-weight:700; border-top: 2px solid var(--border) !important; }
  .totals-row td.netto-cell { font-size: 1rem; }

  /* Back button */
  .back-bar { margin-bottom: 1.5rem; display:flex; align-items:center; gap:1rem; }
</style>

<div class="page-wrap">
  <!-- BACK -->
  <div class="back-bar">
    <a href="matches.php" class="btn btn-outline" style="font-size:0.82rem;">← Powrót do listy</a>
    <span style="font-family:var(--font-mono); font-size:0.75rem; color:var(--text-muted);">
      MECZ #<?= $match_id ?>  &bull;  <?= htmlspecialchars($match_info['MATCH_DT']) ?>
      &bull; <span class="badge <?= $match_info['GTYPE']==='Ranked' ? 'badge-ranked' : 'badge-normal' ?>"><?= $match_info['GTYPE'] ?></span>
    </span>
  </div>

  <?php if ($db_error): ?>
    <div class="alert alert-error">⚠ <?= htmlspecialchars($db_error) ?></div>
  <?php endif; ?>

  <!-- RESULT BANNER -->
  <div class="match-hero-banner">
    <div class="match-result-title <?= strtolower($winner) ?>">
      🏆 Wygrana <?= htmlspecialchars($winner === 'Radiant' ? 'Świetlistych' : 'Mrocznych') ?>
    </div>

    <div class="score-row">
      <?php
        // Radiant kills = their kills total, Dire kills = their kills total
        $radiant_pl = ($t1_side === 'Radiant') ? $team1_players : $team2_players;
        $dire_pl    = ($t1_side === 'Dire')    ? $team1_players : $team2_players;
        $radiant_t  = ($t1_side === 'Radiant') ? $t1 : $t2;
        $dire_t     = ($t1_side === 'Dire')    ? $t1 : $t2;
      ?>
      <span class="score-num radiant"><?= $radiant_t['kills'] ?></span>
      <span class="score-sep">&nbsp;&mdash;&nbsp;</span>
      <span class="score-num dire"><?= $dire_t['kills'] ?></span>
    </div>

    <div class="match-meta">
      <b style="color:white;"><?= htmlspecialchars($match_info['MATCH_DT']) ?> </b>
      &nbsp;&bull;&nbsp;
      <b style="color:white;"><?= htmlspecialchars($match_info['GTYPE']) ?></b>
    </div>
  </div>

  <!-- ── TEAM PANELS ──────────────────────────────────── -->
  <?php
  $teams = [
    ['side'=>'Radiant','players'=>$radiant_pl,'totals'=>$radiant_t,'class'=>'radiant','label'=>'Świetliści'],
    ['side'=>'Dire',   'players'=>$dire_pl,   'totals'=>$dire_t,   'class'=>'dire',   'label'=>'Mroczni'],
  ];

  foreach ($teams as $team):
    $won = ($winner === $team['side']);
  ?>
  <div class="team-panel">
    <div class="team-header <?= $team['class'] ?>">
      <span><?= $won ? '🏆 ' : '' ?><?= $team['label'] ?></span>
      <?php if ($won): ?>
        <span class="team-winner-badge">VICTORY</span>
      <?php endif; ?>
    </div>

    <table class="score-table">
      <thead>
        <tr>
          <th style="width:26px;">Poz</th>
          <th style="min-width:160px;">Bohater / Gracz</th>
          <th>K</th>
          <th>D</th>
          <th>A</th>
          <th>KDA</th>
          <th>Netto</th>
          <th style="min-width:280px;">Przedmioty</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($team['players'] as $p):
          
          $kda = $p['DEATHS'] > 0
            ? round(($p['KILLS'] + $p['ASSISTS']) / $p['DEATHS'], 2)
            : ($p['KILLS'] + $p['ASSISTS']);
          $attr = $p['PRIMARY_ATTRIBUTE'] ?? '';
          $items = [$p['ITEM1'],$p['ITEM2'],$p['ITEM3'],$p['ITEM4'],$p['ITEM5'],$p['ITEM6']];
        ?>
        <tr>
          <!-- Position -->
          <td>
            <span class="pos-badge" title="<?= $pos_label[$p['POSITION']] ?? '' ?>">
              <?= $p['POSITION'] ?>
            </span>
          </td>

          <!-- Hero + Player -->
          <td>
            <div class="hero-cell">
              <span class="hero-attr-icon" title="<?= htmlspecialchars($attr) ?>">
                <img class="attr-img-valve" src="<?= $attr_icon[$attr] ?? '⚡' ?>" alt="attr_img_error">
              </span>
              <div>
                <div class="hero-name">
                  <?php 
                    $hero_name = strtolower($p['HERO_NAME'] ?? 'Unknown');
                    $hero_name = str_replace(' ', '-', $hero_name);
                    $hero_name = preg_replace("/'/", "", $hero_name);
                  ?>
                  <img style="margin-right:4px; vertical-align:middle; width:100px;"
                       src="https://pl.dotabuff.com/assets/heroes/<?= $hero_name ?>.jpg"
                       alt="hero_img_error"
                       title="<?= htmlspecialchars($p['HERO_NAME'] ?? 'Unknown') ?>">
                </div>
               </div>
                <div style="display:flex; align-items:center; gap:5px;">
                  <span class="player-nick"><?= htmlspecialchars($p['NICKNAME']) ?></span>
                  <span class="badge <?= $rank_bc[$p['RANK']] ?? 'badge-herald' ?>" style="font-size:0.6rem; padding:1px 5px;">
                    <?= htmlspecialchars($p['RANK']) ?>
                  </span>
                </div>
              </div>
            </div>
          </td>

          <!-- K / D / A -->
          <td><span class="kda-k" style="font-family:var(--font-mono); font-weight:700;"><?= $p['KILLS'] ?></span></td>
          <td><span class="kda-d" style="font-family:var(--font-mono); font-weight:700;"><?= $p['DEATHS'] ?></span></td>
          <td><span class="kda-a" style="font-family:var(--font-mono); font-weight:700;"><?= $p['ASSISTS'] ?></span></td>

          <!-- KDA -->
          <td>
            <span class="kda-val"><?= number_format($kda, 2) ?></span>
          </td>

          <!-- Netto -->
          <td class="netto-cell"><?= number_format($p['NETTO']) ?>g</td>

          <!-- Items -->
          <td>
            <div class="items-cell">
              <?php foreach ($items as $item): ?>
                <?php 
                    $item_name = strtolower($item ?? '');
                    $item_name = str_replace(' ', '-', $item_name);
                    $item_name = preg_replace("/'/", "", $item_name);
                  ?>
                <?php if ($item): ?>
                  <img style="margin-right:4px; vertical-align:middle; width:54px;"
                       src="https://pl.dotabuff.com/assets/items/<?= $item_name ?>.jpg"
                       alt="item_img_error"
                       title="<?= htmlspecialchars($item) ?>">
                <?php else: ?>
                  <span class="item-slot empty">—</span>
                <?php endif; ?>
              <?php endforeach; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>

        <!-- TOTALS ROW -->
        <tr class="totals-row">
          <td></td>
          <td style="font-family:var(--font-mono); font-size:0.75rem; color:var(--text-muted);">RAZEM</td>
          <td><span class="kda-k" style="font-family:var(--font-mono); font-size:1rem;"><?= $team['totals']['kills'] ?></span></td>
          <td><span class="kda-d" style="font-family:var(--font-mono); font-size:1rem;"><?= $team['totals']['deaths'] ?></span></td>
          <td><span class="kda-a" style="font-family:var(--font-mono); font-size:1rem;"><?= $team['totals']['assists'] ?></span></td>
          <td></td>
          <td class="netto-cell" style="font-size:1rem;"><?= number_format($team['totals']['netto']) ?>g</td>
          <td></td>
        </tr>
      </tbody>
    </table>
  </div>
  <?php endforeach; ?>

  <!-- SIDE BY SIDE SUMMARY -->
  <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-top:1rem;">

    <?php foreach ([['Świetliści','Radiant',$radiant_t], ['Mroczni','Dire',$dire_t]] as [$label,$side,$tot]): ?>
    <div style="
      background:var(--bg2); border:1px solid var(--border);
      border-top: 3px solid <?= $side==='Radiant' ? '#4caf50' : 'var(--red)' ?>;
      padding:1.2rem;
    ">
      <div style="
        font-family:var(--font-head); font-size:1rem; font-weight:700;
        color: <?= $side==='Radiant' ? '#66bb6a' : 'var(--red-bright)' ?>;
        text-transform:uppercase; letter-spacing:0.08em; margin-bottom:1rem;
      "><?= $label ?></div>

      <?php foreach (['kills'=>'Zabójstwa','deaths'=>'Śmierci','assists'=>'Asysty','netto'=>'Łączne Netto'] as $k=>$lbl): ?>
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
        <span style="font-family:var(--font-mono); font-size:0.75rem; color:var(--text-muted);"><?= $lbl ?></span>
        <span style="font-family:var(--font-head); font-size:1.1rem; font-weight:700; color:#fff;">
          <?= $k==='netto' ? number_format($tot[$k]).'g' : $tot[$k] ?>
        </span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

  </div>

  <div style="margin-top:1.5rem; text-align:center;">
    <a href="matches.php" class="btn btn-outline">← Wróć do listy meczy</a>
  </div>

</div>

<?php require_once 'footer.php'; ?>

<?php
require_once 'components/db.php';
$page_title = 'Dashboard';
require_once 'components/header.php';
?>

<div class="page-wrap">
  <div class="page-banner" data-label="ANALYTICS">
    <div class="banner-tag">Dashboard</div>
    <h1 class="banner-title">Dota<span>2</span> Analytics Hub</h1>
    <p class="banner-sub">Statystyki rozgrywek, bohaterów, przedmiotów i graczy — w jednym miejscu.</p>
    <div class="banner-divider"></div>
  </div>

  <?php if ($db_error): ?>
    <div class="alert alert-error">⚠ Błąd połączenia z bazą Oracle: <?= htmlspecialchars($db_error) ?></div>
  <?php else: ?>
    <div class="alert alert-ok">✓ Połączono z Oracle Database (C##dota_app @ localhost/ORCL)</div>
  <?php endif; ?>

  <!-- STAT CARDS -->
  <div class="stat-row">
    <?php
      $counts = [
        ['label' => 'Heroes',  'sql' => 'SELECT COUNT(*) FROM SYS.Hero',       'icon' => '⚔️',  'cls' => 'red'],
        ['label' => 'Items',   'sql' => 'SELECT COUNT(*) FROM SYS.Item',        'icon' => '🗡️', 'cls' => ''],
        ['label' => 'Players', 'sql' => 'SELECT COUNT(*) FROM SYS.Player',      'icon' => '👤',  'cls' => 'gold'],
        ['label' => 'Matches', 'sql' => 'SELECT COUNT(*) FROM SYS.Match_Game',  'icon' => '🏆',  'cls' => ''],
      ];
      foreach ($counts as $c):
        $val = $db_conn ? db_scalar($db_conn, $c['sql']) : '—';
    ?>
      <div class="stat-card">
        <div class="stat-label"><?= $c['icon'] ?> <?= $c['label'] ?></div>
        <div class="stat-value <?= $c['cls'] ?>"><?= $val ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- QUICK LINKS GRID -->
  <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:1rem; margin-bottom:2.5rem;">

    <?php
    $tiles = [
      ['href'=>'components/heroes.php',  'icon'=>'⚔️',  'title'=>'Heroes',  'desc'=>'Przeglądaj bohaterów wg atrybutu, pick rate i win rate. Śledź wpływ patchy.'],
      ['href'=>'components/items.php',   'icon'=>'🗡️', 'title'=>'Items',   'desc'=>'Katalog wszystkich przedmiotów. Sprawdź najpopularniejsze buildy na każdą pozycję.'],
      ['href'=>'components/players.php', 'icon'=>'👤',  'title'=>'Players', 'desc'=>'Wyszukaj gracza po nicku lub Steam ID. Pełna historia rangi i KDA.'],
      ['href'=>'components/matches.php', 'icon'=>'🏆',  'title'=>'Matches', 'desc'=>'Szczegółowe logi meczy — Radiant vs Dire, czas trwania, składy drużyn.'],
    ];
    foreach ($tiles as $t): ?>
    <a href="<?= $t['href'] ?>" style="text-decoration:none;">
      <div style="
        background: var(--bg2);
        border: 1px solid var(--border);
        border-top: 3px solid var(--red-dim);
        padding: 1.6rem;
        transition: all 0.2s;
        cursor: pointer;
      " onmouseover="this.style.borderTopColor='var(--red-bright)'; this.style.transform='translateY(-3px)';"
         onmouseout="this.style.borderTopColor='var(--red-dim)'; this.style.transform='translateY(0)';">
        <div style="font-size:2rem; margin-bottom:0.8rem;"><?= $t['icon'] ?></div>
        <div style="font-family: var(--font-head); font-size:1.4rem; font-weight:700; color:#fff; margin-bottom:0.4rem; letter-spacing:0.04em;">
          <?= $t['title'] ?>
        </div>
        <div style="color: var(--text-dim); font-size:0.88rem; line-height:1.5;">
          <?= $t['desc'] ?>
        </div>
        <div style="margin-top:1rem; font-family: var(--font-mono); font-size:0.75rem; color: var(--red); letter-spacing:0.1em;">
          OTWÓRZ →
        </div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- RECENT MATCHES PREVIEW -->
  <?php if ($db_conn): ?>
  <h2 style="font-family:var(--font-head); font-size:1.3rem; font-weight:700; color:#fff; letter-spacing:0.06em; text-transform:uppercase; margin-bottom:1rem; border-left:3px solid var(--red); padding-left:0.75rem;">
    Ostatnie Mecze
  </h2>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Data</th>
          <th>Radiant</th>
          <th>Dire</th>
          <th>Zwycięzca</th>
          <th>Typ</th>
        </tr>
      </thead>
      <tbody>
        <?php
          $matches = db_query($db_conn,
            "SELECT m.ID, TO_CHAR(m.MATCH_TIME,'YYYY-MM-DD HH24:MI') AS MATCH_DATE,
                    t1.SIDE AS SIDE1, t2.SIDE AS SIDE2, tw.SIDE AS WINNER,
                    CASE m.IS_RANKED WHEN 1 THEN 'Ranked' ELSE 'Normal' END AS GTYPE
             FROM SYS.Match_Game m
             JOIN SYS.Team t1 ON t1.ID = m.TEAM1_ID
             JOIN SYS.Team t2 ON t2.ID = m.TEAM2_ID
             JOIN SYS.Team tw ON tw.ID = m.WINNER_ID
             ORDER BY m.ID DESC
             FETCH FIRST 10 ROWS ONLY"
          );
          if (empty($matches)):
        ?>
          <tr><td colspan="6"><div class="empty-state"><div class="empty-icon">🏆</div><p>Brak zarejestrowanych meczy</p></div></td></tr>
        <?php else: foreach ($matches as $m): ?>
          <tr>
            <td class="td-mono">#<?= $m['ID'] ?></td>
            <td class="td-mono"><?= $m['MATCH_DATE'] ?></td>
            <td style="color:#4caf50; font-weight:600;"><?= htmlspecialchars($m['SIDE1']) ?></td>
            <td style="color:var(--red-bright); font-weight:600;"><?= htmlspecialchars($m['SIDE2']) ?></td>
            <td class="td-bold"><?= htmlspecialchars($m['WINNER']) ?></td>
            <td>
              <span class="badge <?= $m['GTYPE']==='Ranked' ? 'badge-ranked' : 'badge-normal' ?>">
                <?= $m['GTYPE'] ?>
              </span>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <div style="margin-top:0.8rem; text-align:right;">
    <a href="matches.php" class="btn btn-outline" style="font-size:0.82rem;">Wszystkie mecze →</a>
  </div>
  <?php endif; ?>

</div>
<?php require_once 'components/footer.php'; ?>

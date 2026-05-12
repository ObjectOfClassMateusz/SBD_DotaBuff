<?php
require_once 'db.php';
$page_title = 'Matches';

$filter_type   = isset($_GET['type'])   ? $_GET['type']   : '';   // Ranked / Normal
$filter_winner = isset($_GET['winner']) ? $_GET['winner'] : '';   // Radiant / Dire
$page_num  = max(1, (int)($_GET['page'] ?? 1));
$per_page  = 20;
$offset    = ($page_num - 1) * $per_page;

$where = [];
if ($filter_type === 'Ranked') $where[] = "m.IS_RANKED = 1";
if ($filter_type === 'Normal') $where[] = "m.IS_RANKED = 0";
if ($filter_winner)            $where[] = "tw.SIDE = '" . addslashes($filter_winner) . "'";

$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

require_once 'header.php';
?>

<div class="page-wrap">

  <div class="page-banner" data-label="MATCHES">
    <div class="banner-tag">// Historia meczy</div>
    <h1 class="banner-title">Match <span>History</span></h1>
    <p class="banner-sub">Przeglądaj wszystkie rozegrane mecze, filtruj po typie i zwycięzcy.</p>
    <div class="banner-divider"></div>
  </div>

  <?php if ($db_error): ?>
    <div class="alert alert-error">⚠ <?= htmlspecialchars($db_error) ?></div>
  <?php endif; ?>

  <!-- FILTERS -->
  <div class="filter-bar" style="margin-bottom:1.5rem;">
    <div style="display:flex; gap:0.4rem; flex-wrap:wrap;">
      <span style="font-family:var(--font-mono); font-size:0.75rem; color:var(--text-muted); line-height:2.2; margin-right:0.3rem;">TYP:</span>
      <?php foreach (['', 'Ranked', 'Normal'] as $t): ?>
        <a href="?type=<?= urlencode($t) ?>&winner=<?= urlencode($filter_winner) ?>"
           class="btn <?= $filter_type===$t ? 'btn-red' : 'btn-outline' ?>" style="font-size:0.8rem; padding:0.45rem 1rem;">
          <?= $t ?: 'Wszystkie' ?>
        </a>
      <?php endforeach; ?>

      <span style="font-family:var(--font-mono); font-size:0.75rem; color:var(--text-muted); line-height:2.2; margin-left:1rem; margin-right:0.3rem;">ZWYCIĘZCA:</span>
      <?php foreach (['', 'Radiant', 'Dire'] as $w): ?>
        <a href="?type=<?= urlencode($filter_type) ?>&winner=<?= urlencode($w) ?>"
           class="btn <?= $filter_winner===$w ? 'btn-red' : 'btn-outline' ?>" style="font-size:0.8rem; padding:0.45rem 1rem;">
          <?= $w ?: 'Wszyscy' ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if ($db_conn):
    $count_sql = "SELECT COUNT(*) FROM SYS.Match_Game m
                  JOIN SYS.Team t1 ON t1.ID=m.TEAM1_ID
                  JOIN SYS.Team t2 ON t2.ID=m.TEAM2_ID
                  JOIN SYS.Team tw ON tw.ID=m.WINNER_ID
                  $where_sql";
    $total = (int)db_scalar($db_conn, $count_sql);
    $total_pages = max(1, ceil($total / $per_page));

    $matches = db_query($db_conn,
      "SELECT * FROM (
         SELECT m.ID,
                TO_CHAR(m.MATCH_TIME,'YYYY-MM-DD') AS MATCH_DATE,
                TO_CHAR(m.MATCH_TIME,'HH24:MI')    AS MATCH_TIME2,
                t1.SIDE AS SIDE1, t2.SIDE AS SIDE2, tw.SIDE AS WINNER,
                CASE m.IS_RANKED WHEN 1 THEN 'Ranked' ELSE 'Normal' END AS GTYPE,
                ROWNUM AS RN
         FROM SYS.Match_Game m
         JOIN SYS.Team t1 ON t1.ID=m.TEAM1_ID
         JOIN SYS.Team t2 ON t2.ID=m.TEAM2_ID
         JOIN SYS.Team tw ON tw.ID=m.WINNER_ID
         $where_sql
         ORDER BY m.ID DESC
       ) WHERE RN > $offset AND RN <= " . ($offset + $per_page)
    );
  ?>

  <!-- STAT CARDS -->
  <div class="stat-row" style="grid-template-columns:repeat(3,1fr); margin-bottom:1.5rem;">
    <div class="stat-card">
      <div class="stat-label">🏆 Mecze</div>
      <div class="stat-value red"><?= $total ?></div>
    </div>
    <?php
      $ranked_cnt = (int)db_scalar($db_conn, "SELECT COUNT(*) FROM SYS.Match_Game WHERE IS_RANKED=1");
      $normal_cnt = (int)db_scalar($db_conn, "SELECT COUNT(*) FROM SYS.Match_Game WHERE IS_RANKED=0");
    ?>
    <div class="stat-card">
      <div class="stat-label">🎖️ Ranked</div>
      <div class="stat-value"><?= $ranked_cnt ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">🎮 Normal</div>
      <div class="stat-value"><?= $normal_cnt ?></div>
    </div>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Data</th>
          <th>Godzina</th>
          <th style="color:#4caf50;">Radiant</th>
          <th style="color:var(--red-bright);">Dire</th>
          <th>Zwycięzca</th>
          <th>Typ</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($matches)): ?>
          <tr><td colspan="7">
            <div class="empty-state">
              <div class="empty-icon">🏆</div>
              <p>Brak meczy spełniających kryteria</p>
            </div>
          </td></tr>
        <?php else: foreach ($matches as $m): ?>
          <tr>
            <td class="td-mono">#<?= $m['ID'] ?></td>
            <td class="td-mono"><?= htmlspecialchars($m['MATCH_DATE']) ?></td>
            <td class="td-mono"><?= htmlspecialchars($m['MATCH_TIME2']) ?></td>
            <td style="color:#4caf50; font-weight:600;">
              <?= htmlspecialchars($m['SIDE1']) === 'Radiant' ? '✓ Radiant' : htmlspecialchars($m['SIDE1']) ?>
            </td>
            <td style="color:var(--red-bright); font-weight:600;">
              <?= htmlspecialchars($m['SIDE2']) === 'Dire' ? '✓ Dire' : htmlspecialchars($m['SIDE2']) ?>
            </td>
            <td>
              <?php $w = htmlspecialchars($m['WINNER']); ?>
              <span style="
                font-family:var(--font-head); font-weight:700; font-size:0.95rem;
                color: <?= $w==='Radiant' ? '#4caf50' : 'var(--red-bright)' ?>;
              ">🏆 <?= $w ?></span>
            </td>
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

  <div style="display:flex; justify-content:space-between; align-items:center; margin-top:0.8rem; flex-wrap:wrap; gap:0.5rem;">
    <span style="font-family:var(--font-mono); font-size:0.75rem; color:var(--text-muted);">
      Strona <?= $page_num ?> z <?= $total_pages ?> (<?= $total ?> meczy łącznie)
    </span>

    <?php if ($total_pages > 1): ?>
    <div style="display:flex; gap:0.4rem;">
      <?php if ($page_num > 1): ?>
        <a href="?page=<?= $page_num-1 ?>&type=<?= urlencode($filter_type) ?>&winner=<?= urlencode($filter_winner) ?>"
           class="btn btn-outline" style="font-size:0.8rem;">← Poprzednia</a>
      <?php endif; ?>
      <?php if ($page_num < $total_pages): ?>
        <a href="?page=<?= $page_num+1 ?>&type=<?= urlencode($filter_type) ?>&winner=<?= urlencode($filter_winner) ?>"
           class="btn btn-outline" style="font-size:0.8rem;">Następna →</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>

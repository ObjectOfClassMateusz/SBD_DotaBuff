<?php
require_once 'includes/db.php';
$page_title = 'Players';

$filter_nick = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_rank = isset($_GET['rank'])   ? $_GET['rank']   : '';

$ranks = ['Herald','Guardian','Crusader','Archon','Legend','Ancient','Divine','Immortal'];

$where = [];
if ($filter_nick) $where[] = "UPPER(NICKNAME) LIKE UPPER('%" . addslashes($filter_nick) . "%')";
if ($filter_rank) $where[] = "RANK = '" . addslashes($filter_rank) . "'";
$sql = "SELECT * FROM SYS.Player";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY CASE RANK
    WHEN 'Immortal' THEN 1 WHEN 'Divine'   THEN 2 WHEN 'Ancient'  THEN 3
    WHEN 'Legend'   THEN 4 WHEN 'Archon'   THEN 5 WHEN 'Crusader' THEN 6
    WHEN 'Guardian' THEN 7 ELSE 8 END, NICKNAME";

require_once 'includes/header.php';

$rank_badges = [
  'Herald'   => 'badge-herald',
  'Guardian' => 'badge-guardian',
  'Crusader' => 'badge-crusader',
  'Archon'   => 'badge-archon',
  'Legend'   => 'badge-legend',
  'Ancient'  => 'badge-ancient',
  'Divine'   => 'badge-divine',
  'Immortal' => 'badge-immortal',
];
?>

<div class="page-wrap">

  <div class="page-banner" data-label="PLAYERS">
    <div class="banner-tag">// Baza graczy</div>
    <h1 class="banner-title">Player <span>Lookup</span></h1>
    <p class="banner-sub">Wyszukaj gracza po nicku lub Steam ID. Filtruj po randze.</p>
    <div class="banner-divider"></div>
  </div>

  <?php if ($db_error): ?>
    <div class="alert alert-error">⚠ <?= htmlspecialchars($db_error) ?></div>
  <?php endif; ?>

  <!-- RANK QUICK FILTERS -->
  <div style="display:flex; gap:0.4rem; flex-wrap:wrap; margin-bottom:1.2rem;">
    <a href="players.php<?= $filter_nick ? '?search='.urlencode($filter_nick) : '' ?>"
       class="btn <?= !$filter_rank ? 'btn-red' : 'btn-outline' ?>" style="font-size:0.8rem;">Wszystkie</a>
    <?php foreach ($ranks as $r): ?>
      <a href="?rank=<?= urlencode($r) ?><?= $filter_nick ? '&search='.urlencode($filter_nick) : '' ?>"
         class="btn btn-outline" style="font-size:0.8rem; <?= $filter_rank===$r ? 'border-color:var(--red); color:var(--red-bright);' : '' ?>">
        <?= $r ?>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="filter-bar">
    <form method="GET" style="display:flex; gap:0.6rem; flex-wrap:wrap;">
      <?php if ($filter_rank): ?>
        <input type="hidden" name="rank" value="<?= htmlspecialchars($filter_rank) ?>">
      <?php endif; ?>
      <input type="text" name="search" class="filter-input"
             placeholder="🔍 Nick lub Steam ID..." value="<?= htmlspecialchars($filter_nick) ?>">
      <button class="btn btn-red" type="submit">Szukaj</button>
      <?php if ($filter_nick || $filter_rank): ?>
        <a href="players.php" class="btn btn-outline">✕ Wyczyść</a>
      <?php endif; ?>
    </form>
  </div>

  <?php if ($db_conn):
    $players = db_query($db_conn, $sql);

    // Count per rank
    $rank_counts = [];
    foreach ($players as $p) {
        $r = $p['RANK'];
        $rank_counts[$r] = ($rank_counts[$r] ?? 0) + 1;
    }
  ?>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Steam ID</th>
          <th class="sortable">Nick</th>
          <th>Ranga</th>
          <th>Data konta</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($players)): ?>
          <tr><td colspan="4">
            <div class="empty-state">
              <div class="empty-icon">👤</div>
              <p>Nie znaleziono graczy</p>
            </div>
          </td></tr>
        <?php else: foreach ($players as $p):
          $rank = $p['RANK'];
          $bc   = $rank_badges[$rank] ?? 'badge-herald';
        ?>
          <tr>
            <td class="td-mono"><?= htmlspecialchars($p['STEAM_ID']) ?></td>
            <td class="td-bold"><?= htmlspecialchars($p['NICKNAME']) ?></td>
            <td><span class="badge <?= $bc ?>"><?= htmlspecialchars($rank) ?></span></td>
            <td class="td-mono"><?= htmlspecialchars($p['ACCOUNT_CREATED']) ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <div style="margin-top:0.6rem; font-family:var(--font-mono); font-size:0.75rem; color:var(--text-muted);">
    Znaleziono <?= count($players) ?> graczy
  </div>

  <!-- RANK DISTRIBUTION -->
  <?php if (!$filter_rank && !empty($players)): ?>
  <div style="margin-top:2rem;">
    <h2 style="font-family:var(--font-head); font-size:1.1rem; font-weight:700; color:#fff; letter-spacing:0.08em; text-transform:uppercase; margin-bottom:1rem; border-left:3px solid var(--red); padding-left:0.75rem;">
      Rozkład rang
    </h2>
    <div style="display:flex; flex-direction:column; gap:0.5rem;">
      <?php
        $max_c = max(array_values($rank_counts) ?: [1]);
        foreach ($ranks as $r):
          $c = $rank_counts[$r] ?? 0;
          $pct = $max_c > 0 ? round($c / $max_c * 100) : 0;
          $bc  = $rank_badges[$r];
      ?>
        <div style="display:flex; align-items:center; gap:1rem;">
          <span class="badge <?= $bc ?>" style="width:90px; text-align:center;"><?= $r ?></span>
          <div style="flex:1; height:8px; background:var(--bg3); position:relative;">
            <div style="height:100%; width:<?= $pct ?>%; background:var(--red-dim); transition:width 0.5s;"></div>
          </div>
          <span style="font-family:var(--font-mono); font-size:0.8rem; color:var(--text-dim); width:30px; text-align:right;"><?= $c ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>

<?php
require_once 'db.php';
$page_title = 'Items';

$filter_name = isset($_GET['search']) ? trim($_GET['search']) : '';
$page_num    = max(1, (int)($_GET['page'] ?? 1));
$per_page    = 50;
$offset      = ($page_num - 1) * $per_page;

$search_cond = $filter_name
  ? "WHERE UPPER(NAME) LIKE UPPER('%" . addslashes($filter_name) . "%')"
  : "";

require_once 'header.php';
?>

<div class="page-wrap">

  <div class="page-banner" data-label="ITEMS">
    <div class="banner-tag">Katalog przedmiotów</div>
    <h1 class="banner-title">Item <span>Database</span></h1>
    <p class="banner-sub">Wszystkie przedmioty dostępne w Dota 2 — od bazowych po legendarne.</p>
    <div class="banner-divider"></div>
  </div>

  <?php if ($db_error): ?>
    <div class="alert alert-error">⚠ <?= htmlspecialchars($db_error) ?></div>
  <?php endif; ?>
  <div class="filter-bar">
    <form method="GET" style="display:flex; gap:0.6rem; flex-wrap:wrap;">
      <input type="text" name="search" class="filter-input"
             placeholder="🔍 Szukaj przedmiotu..." value="<?= htmlspecialchars($filter_name) ?>">
      <button class="btn btn-red" type="submit">Szukaj</button>
      <?php if ($filter_name): ?>
        <a href="items.php" class="btn btn-outline">✕ Wyczyść</a>
      <?php endif; ?>
    </form>
  </div>

  <?php if ($db_conn):
    $total = db_scalar($db_conn, "SELECT COUNT(*) FROM SYS.Item $search_cond");
    $total_pages = max(1, ceil($total / $per_page));

    $items = db_query($db_conn,
      "SELECT * FROM (
         SELECT i.*, ROWNUM AS RN FROM SYS.Item i $search_cond ORDER BY id
       ) WHERE RN > $offset AND RN <= " . ($offset + $per_page)
    );
  ?>

  <div class="stat-row" style="grid-template-columns: repeat(3, 1fr);">
    <div class="stat-card">
      <div class="stat-label">🗡️ Total Items</div>
      <div class="stat-value red"><?= $total ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">📄 Strona</div>
      <div class="stat-value"><?= $page_num ?> / <?= $total_pages ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">📦 Na stronie</div>
      <div class="stat-value"><?= count($items) ?></div>
    </div>
  </div>

  <!-- GRID VIEW -->
  <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:0.6rem; margin-bottom:1.5rem;">
    <?php if (empty($items)): ?>
      <div class="empty-state" style="grid-column:1/-1;">
        <div class="empty-icon">🗡️</div>
        <p>Brak przedmiotów spełniających kryteria</p>
      </div>
    <?php else: foreach ($items as $item): ?>
      <div style="
        background: var(--bg2); border: 1px solid var(--border);
        padding: 0.8rem 1rem; display:flex; align-items:center; gap:0.6rem;
        transition: border-color 0.15s;
      " onmouseover="this.style.borderColor='var(--red-dim)'"
         onmouseout="this.style.borderColor='var(--border)'">
        <span style="color:var(--red-dim); font-family:var(--font-mono); font-size:0.7rem; flex-shrink:0;">
          #<?= $item['ID'] ?>
        </span>
        <span style="font-size:0.88rem; color:var(--text);">
          <?= htmlspecialchars($item['NAME']) ?>
        </span>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <!-- PAGINATION -->
  <?php if ($total_pages > 1): ?>
  <div style="display:flex; gap:0.4rem; flex-wrap:wrap; align-items:center; justify-content:center;">
    <?php if ($page_num > 1): ?>
      <a href="?page=<?= $page_num-1 ?><?= $filter_name ? '&search='.urlencode($filter_name) : '' ?>"
         class="btn btn-outline">← Poprzednia</a>
    <?php endif; ?>

    <?php for ($p = max(1,$page_num-3); $p <= min($total_pages,$page_num+3); $p++): ?>
      <a href="?page=<?= $p ?><?= $filter_name ? '&search='.urlencode($filter_name) : '' ?>"
         class="btn <?= $p===$page_num ? 'btn-red' : 'btn-outline' ?>"
         style="min-width:42px; justify-content:center;"><?= $p ?></a>
    <?php endfor; ?>

    <?php if ($page_num < $total_pages): ?>
      <a href="?page=<?= $page_num+1 ?><?= $filter_name ? '&search='.urlencode($filter_name) : '' ?>"
         class="btn btn-outline">Następna →</a>
    <?php endif; ?>
  </div>
  
  <?php endif; ?>

  <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>

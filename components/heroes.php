<?php
require_once 'db.php';
$page_title = 'Heroes';

$filter_attr = isset($_GET['attr'])   ? $_GET['attr']   : '';
$filter_name = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$where = [];
$sql = "SELECT * FROM SYS.Hero";
if ($filter_attr) $where[] = "primary_attribute = '" . addslashes($filter_attr) . "'";
if ($filter_name) $where[] = "UPPER(name) LIKE UPPER('%" . addslashes($filter_name) . "%')";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY primary_attribute, name";

require_once 'header.php';

$attr_map = [
  'Strength'     => ['badge-str', 'STR', 'https://cdn.steamstatic.com/apps/dota2/images/dota_react/icons/hero_strength.png'],
  'Agility'      => ['badge-agi', 'AGI', 'https://cdn.steamstatic.com/apps/dota2/images/dota_react/icons/hero_agility.png'],
  'Intelligence' => ['badge-int', 'INT', 'https://cdn.steamstatic.com/apps/dota2/images/dota_react/icons/hero_intelligence.png'],
  'Universal'    => ['badge-uni', 'UNI', 'https://cdn.steamstatic.com/apps/dota2/images/dota_react/icons/hero_universal.png'],
];
?>

<div class="page-wrap">
  <div class="page-banner" data-label="HEROES">
    <div class="banner-tag">Baza bohaterów</div>
    <h1 class="banner-title">Hero <span>Explorer</span></h1>
    <p class="banner-sub">Filtruj bohaterów według atrybutu, nazwy i roli.</p>
    <div class="banner-divider"></div>
  </div>

  <?php if ($db_error): ?>
    <div class="alert alert-error">⚠ <?= htmlspecialchars($db_error) ?></div>
  <?php endif; ?>

  <div style="display:flex; gap:0.5rem; flex-wrap:wrap; margin-bottom:1.5rem;">
    <a href="heroes.php" class="btn <?= !$filter_attr ? 'btn-red' : 'btn-outline' ?>">
      Wszyscy
    </a>
    <?php 
      foreach ($attr_map as $attr => [$cls,$short,$ico]): 
    ?>
    <a href="?attr=<?= urlencode($attr) ?><?= $filter_name ? '&search='.urlencode($filter_name) : '' ?>"
       class="btn <?= $filter_attr===$attr ? 'btn-red' : 'btn-outline' ?>">
       <img class="attr-img-valve" src="<?= $ico ?>" alt="attr_img_error">
       <?= $short ?>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- SEARCH -->
  <div class="filter-bar">
    <form method="GET" style="display:flex; gap:0.6rem; flex-wrap:wrap;">
      <?php if ($filter_attr): ?>
        <input type="hidden" name="attr" value="<?= htmlspecialchars($filter_attr) ?>">
      <?php endif; ?>
      <input type="text" name="search" class="filter-input"
             placeholder="🔍 Szukaj bohatera..." value="<?= htmlspecialchars($filter_name) ?>">
      <button class="btn btn-red" type="submit">Szukaj</button>
      <?php if ($filter_name || $filter_attr): ?>
        <a href="heroes.php" class="btn btn-outline">✕ Wyczyść</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- TABLE -->
  <?php if ($db_conn): ?>
    <?php $heroes = db_query($db_conn, $sql); ?>

    <!-- STAT BAR -->
    <div class="stat-row" style="grid-template-columns: repeat(4, 1fr); margin-bottom:1.5rem;">
      <?php foreach ($attr_map as $attr => [$cls,$short,$ico]):
        $cnt = count(array_filter($heroes, fn($h) => $h['PRIMARY_ATTRIBUTE'] === $attr));
      ?>
        <div class="stat-card">
          <div class="stat-label"><img class="attr-img-valve" src="<?= $ico ?>" alt="attr_img_error"> <?= $short ?></div>
          <div class="stat-value <?= $cls === 'badge-str' ? 'red' : ($cls === 'badge-uni' ? 'gold' : '') ?>"><?= $cnt ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th class="sortable">Bohater</th>
            <th>Atrybut</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($heroes)): ?>
            <tr><td colspan="3">
              <div class="empty-state">
                <div class="empty-icon">⚔️</div>
                <p>Brak bohaterów spełniających kryteria</p>
              </div>
            </td></tr>
          <?php else: foreach ($heroes as $i => $h):
            $attr = $h['PRIMARY_ATTRIBUTE'];
            [$cls,$short,$ico] = $attr_map[$attr] ?? ['badge-uni','UNI','⚡'];
          ?>
            <tr>
              <td class="td-mono"><?= $h['ID'] ?></td>
              <td class="td-bold"><?= htmlspecialchars($h['NAME']) ?></td>
              <td><span class="badge <?= $cls ?>"><img class="attr-img-valve" src="<?= $ico ?>" alt="attr_img_error"> <?= $short ?></span></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <div style="margin-top:0.6rem; font-family:var(--font-mono); font-size:0.75rem; color:var(--text-muted);">
      Znaleziono <?= count($heroes) ?> bohaterów
    </div>

  <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>


<?php


//    $stmt = oci_parse($db_conn, "SELECT * FROM SYS.Hero");
//    oci_execute($stmt);

//    while ($row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_NULLS)) {
//        echo "ID: " . $row['ID'] . 
//             ", Name: " . $row['NAME'] . 
//             ", primary_attribute: " . $row['PRIMARY_ATTRIBUTE'] . "<br>";
//    }
//}
//?>
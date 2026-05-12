<?php
  $current_page = basename( htmlspecialchars($_SERVER['PHP_SELF']), '.php');
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dota2 Analytics <?= isset($page_title) ? '| '.$page_title : '' ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=Exo+2:wght@300;400;600;800&family=Share+Tech+Mono&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./css/style.css">
  <link rel="stylesheet" href="./components/css/style.css">
</head>
<body>

<nav>
  <div class="nav-inner">
    <a href="/DotaOracleApp/index.php" class="nav-logo">
      <div class="nav-logo-icon">D2</div>
      <span class="nav-logo-text">Dota<span>2</span> Analytics</span>
    </a>
    <ul class="nav-links">
      <li><a href="/DotaOracleApp/components/heroes.php" class="<?= $current_page==='heroes' ? 'active' : '' ?>">
        <span class="nav-icon">⚔️</span> Heroes
      </a></li>
      <li><a href="/DotaOracleApp/components//items.php" class="<?= $current_page==='items' ? 'active' : '' ?>">
        <span class="nav-icon">🗡️</span> Items
      </a></li>
      <li><a href="/DotaOracleApp/components//players.php" class="<?= $current_page==='players' ? 'active' : '' ?>">
        <span class="nav-icon">👤</span> Players
      </a></li>
      <li><a href="/DotaOracleApp/components//matches.php" class="<?= $current_page==='matches' ? 'active' : '' ?>">
        <span class="nav-icon">🏆</span> Matches
      </a></li>
    </ul>
  </div>
</nav>

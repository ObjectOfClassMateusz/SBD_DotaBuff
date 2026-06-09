<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$__logged_in  = !empty($_SESSION['steam_id']) || !empty($_SESSION['is_admin']);
$__is_admin   = !empty($_SESSION['is_admin']);
$__nick       = htmlspecialchars($_SESSION['nickname'] ?? '');
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

<?php if ($__is_admin): ?>
      <li><a href="/DotaOracleApp/components//add_match.php" class="<?= $current_page==='add_match' ? 'active' : '' ?>"
             style="color:var(--red-bright);">
        <span class="nav-icon">➕</span> Dodaj Mecz
      </a></li>
      <?php endif; ?>
    </ul>
    <div style="margin-left:auto; display:flex; align-items:center; gap:0.5rem;">
      <?php if ($__logged_in): ?>
        <a href="/DotaOracleApp/components//account.php" style="
          display:flex; align-items:center; gap:7px;
          text-decoration:none; color:var(--text-dim);
          font-family:var(--font-mono); font-size:0.78rem;
          letter-spacing:0.06em;
          padding: 0 10px; height:var(--nav-h);
          transition: color 0.18s;
        " onmouseover="this.style.color='var(--text)'"
           onmouseout="this.style.color='var(--text-dim)'">
          👤 <?= $__nick ?>
        </a>
        <a href="/DotaOracleApp/components//account.php?logout=1" style="
          font-family:var(--font-mono); font-size:0.72rem;
          color:var(--text-muted); text-decoration:none;
          border:1px solid var(--border); padding:4px 10px;
          transition:all 0.18s;
        " onmouseover="this.style.borderColor='var(--red)'; this.style.color='var(--red-bright)'"`
           onmouseout="this.style.borderColor='var(--border)'; this.style.color='var(--text-muted)'">
          WYLOGUJ
        </a>
      <?php else: ?>
        <a href="/DotaOracleApp/components//login.php" class="btn btn-outline" style="
          font-size:0.8rem; padding:0.4rem 1rem; height:auto;
          color:var(--text-dim); border-color:var(--border-hot);
        ">🔑 Zaloguj</a>
        <a href="/DotaOracleApp/components//register.php" class="btn btn-red" style="font-size:0.8rem; padding:0.4rem 1rem; height:auto;">
          ➕ Rejestracja
        </a>
      <?php endif; ?>
    </ul>
  </div>
</nav>

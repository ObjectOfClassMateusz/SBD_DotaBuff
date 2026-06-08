<?php
require_once 'components/db.php';
$page_title = 'Account';

if (session_status() !== PHP_SESSION_ACTIVE) 
{
    session_start();
}

if (!isset($_SESSION['user'])) {
    header('Location: /DotaOracleApp/login.php');
    exit;
}

$user = $_SESSION['user'];
$error_msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header('Location: /DotaOracleApp/login.php');
    exit;
}

$fav_hero = null;
if ($db_conn) {
    $stmt = oci_parse($db_conn,
        "SELECT h.NAME AS HERO_NAME, COUNT(*) AS PLAY_COUNT\n" .
        "FROM SYS.Hero_Played hp\n" .
        "JOIN SYS.Hero h ON h.ID = hp.HERO_ID\n" .
        "WHERE hp.STEAM_ID = :steam_id\n" .
        "GROUP BY h.NAME\n" .
        "ORDER BY COUNT(*) DESC, h.NAME\n" .
        "FETCH FIRST 1 ROWS ONLY"
    );
    oci_bind_by_name($stmt, ':steam_id', $user['steam_id']);
    if (oci_execute($stmt)) {
        $row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_NULLS);
        if ($row && !empty($row['HERO_NAME'])) {
            $fav_hero = $row['HERO_NAME'];
        }
    } else {
        $e = oci_error($stmt);
        $error_msg = $e['message'] ?? 'Nie udało się pobrać ulubionego bohatera.';
    }
}

require_once 'components/header.php';
?>

<div class="page-wrap">
  <div class="page-banner" data-label="ACCOUNT">
    <div class="banner-tag">Konto</div>
    <h1 class="banner-title">Witaj, <?= htmlspecialchars($user['nickname']) ?></h1>
    <p class="banner-sub">Twoje konto jest zalogowane jako <strong>Herald</strong> i możesz dodawać mecze.</p>
    <div class="banner-divider"></div>
  </div>

  <?php if ($error_msg): ?>
    <div class="alert alert-error">⚠ <?= htmlspecialchars($error_msg) ?></div>
  <?php endif; ?>

  <div style="display:grid; gap:1.25rem; max-width:720px;">
    <div class="form-section" style="padding:1.5rem; background:var(--bg2); border:1px solid var(--border);">
      <div class="section-label">Dane użytkownika</div>
      <p><strong>NickName:</strong> <?= htmlspecialchars($user['nickname']) ?></p>
      <p><strong>STEAM_ID:</strong> <?= htmlspecialchars($user['steam_id']) ?></p>
      <p><strong>Region:</strong> <?= htmlspecialchars($user['region']) ?></p>
      <p><strong>Ranga:</strong> <?= htmlspecialchars($user['rank']) ?></p>
      <p><strong>Ulubiony bohater:</strong>
        <?= $fav_hero ? htmlspecialchars($fav_hero) : '<span style="color:var(--text-muted);">brak danych</span>' ?>
      </p>
    </div>

    <div class="submit-bar" style="justify-content:flex-start; gap:1rem;">
      <a href="components/add_match.php" class="btn btn-red">Przejdź do add_match</a>
      <form method="POST" style="margin:0;">
        <button type="submit" name="logout" class="btn btn-outline">Wyloguj</button>
      </form>
    </div>
  </div>
</div>

<?php require_once 'components/footer.php'; ?>
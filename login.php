<?php
require_once 'components/db.php';
$page_title = 'Logowanie';
$success_msg = null;
$error_msg = null;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$next = '/DotaOracleApp/account.php';
if (!empty($_GET['next']) && str_starts_with($_GET['next'], '/DotaOracleApp/')) {
    $next = $_GET['next'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db_conn) {
    $nickname = trim($_POST['nickname'] ?? '');
    $steam_id = trim($_POST['steam_id'] ?? '');

    if ($nickname === '' || $steam_id === '') {
        $error_msg = 'Wpisz NickName i STEAM_ID.';
    } elseif (!ctype_digit($steam_id)) {
        $error_msg = 'STEAM_ID musi zawierać tylko cyfry.';
    } else {
        $sql = "SELECT STEAM_ID, NICKNAME, REGION, RANK FROM SYS.Player WHERE LOWER(NICKNAME)=LOWER(:nick) AND STEAM_ID = :steam";
        $stmt = oci_parse($db_conn, $sql);
        oci_bind_by_name($stmt, ':nick', $nickname);
        oci_bind_by_name($stmt, ':steam', $steam_id);
        oci_execute($stmt);
        $row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_NULLS);

        if ($row) {
            $_SESSION['user'] = [
                'steam_id' => $row['STEAM_ID'],
                'nickname' => $row['NICKNAME'],
                'region'   => $row['REGION'],
                'rank'     => $row['RANK'],
            ];
            $_SESSION['logged_in_at'] = time();
            header('Location: ' . $next);
            exit;
        }

        $error_msg = 'Nieprawidłowy NickName lub STEAM_ID.';
    }
}

require_once 'components/header.php';
?>

<div class="page-wrap">
  <div class="page-banner" data-label="LOGIN">
    <div class="banner-tag">Logowanie</div>
    <h1 class="banner-title">Zaloguj się</h1>
    <p class="banner-sub">Użyj NickName i STEAM_ID, aby wejść do panelu.</p>
    <div class="banner-divider"></div>
  </div>

  <?php if ($success_msg): ?>
    <div class="alert alert-ok"><?= htmlspecialchars($success_msg) ?></div>
  <?php endif; ?>
  <?php if ($error_msg): ?>
    <div class="alert alert-error">⚠ <?= htmlspecialchars($error_msg) ?></div>
  <?php endif; ?>

  <div style="max-width:520px;">
    <form method="POST" autocomplete="off">
      <div class="form-section">
        <div class="section-label">Dane logowania</div>

        <label class="field-label">NickName</label>
        <input type="text" name="nickname" maxlength="64" required value="<?= htmlspecialchars($_POST['nickname'] ?? '') ?>">

        <label class="field-label" style="margin-top:1rem;">STEAM_ID</label>
        <input type="text" name="steam_id" maxlength="17" required value="<?= htmlspecialchars($_POST['steam_id'] ?? '') ?>">

        <p style="color:var(--text-muted); font-size:0.9rem; margin-top:0.75rem;">
          Po zalogowaniu uzyskasz dostęp do <strong>add_match.php</strong> i panelu konta.
        </p>
      </div>

      <div class="submit-bar">
        <button type="submit" class="btn btn-red">Zaloguj</button>
        <a href="register.php" class="btn btn-outline">Zarejestruj</a>
      </div>
    </form>
  </div>
</div>

<?php require_once 'components/footer.php'; ?>
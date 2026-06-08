<?php
require_once 'components/db.php';
$page_title = 'Rejestracja';
$regions = ['CHINA','RUSSIA','N-AMERICA','S-AMERICA','W-EUROPE','E-EUROPE','FILIPINO'];
$success_msg = null;
$error_msg = null;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db_conn) {
    $nickname = trim($_POST['nickname'] ?? '');
    $region = strtoupper(trim($_POST['region'] ?? ''));

    if ($nickname === '') {
        $error_msg = 'Wpisz NickName.';
    } elseif (!in_array($region, $regions, true)) {
        $error_msg = 'Wybierz poprawny region.';
    } else {
        try {
            $existing = db_scalar($db_conn,
                "SELECT COUNT(*) FROM SYS.Player WHERE LOWER(NICKNAME) = LOWER(:nick)",
                ['nick' => $nickname]
            );
            if ($existing > 0) {
                throw new Exception('Taki NickName jest już zajęty.');
            }

            $next_id = db_scalar($db_conn,
                "SELECT NVL(MAX(STEAM_ID), 76561198000000000) + 1 FROM SYS.Player"
            );
            $steam_id = (int)$next_id;
            $rank = 'Herald';

            $stmt = oci_parse($db_conn,
                "INSERT INTO SYS.Player (STEAM_ID, NICKNAME, REGION, ACCOUNT_CREATED, RANK)\n" .
                "VALUES (:steam_id, :nickname, :region, SYSDATE, :rank)"
            );
            oci_bind_by_name($stmt, ':steam_id', $steam_id);
            oci_bind_by_name($stmt, ':nickname', $nickname);
            oci_bind_by_name($stmt, ':region', $region);
            oci_bind_by_name($stmt, ':rank', $rank);
            if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
                $e = oci_error($stmt);
                throw new Exception($e['message'] ?? 'Błąd zapisu w bazie danych.');
            }
            oci_commit($db_conn);
            $success_msg = $steam_id;
        } catch (Exception $e) {
            oci_rollback($db_conn);
            $error_msg = $e->getMessage();
        }
    }
}

require_once 'components/header.php';
?>

<div class="page-wrap">
  <div class="page-banner" data-label="LOGIN">
    <div class="banner-tag">Rejestracja</div>
    <h1 class="banner-title">Utwórz konto</h1>
    <p class="banner-sub">NickName, region oraz automatycznie przydzielane STEAM_ID.</p>
    <div class="banner-divider"></div>
  </div>

  <?php if ($success_msg): ?>
    <div class="alert alert-ok">
      ✓ Zarejestrowano! Twoje STEAM_ID: <strong><?= htmlspecialchars($success_msg) ?></strong>.
      &nbsp;<a href="login.php" style="color:#66bb6a; text-decoration:underline;">Zaloguj się teraz →</a>
    </div>
  <?php endif; ?>
  <?php if ($error_msg): ?>
    <div class="alert alert-error">⚠ <?= htmlspecialchars($error_msg) ?></div>
  <?php endif; ?>

  <div style="max-width:520px;">
    <form method="POST" autocomplete="off">
      <div class="form-section">
        <div class="section-label">Dane konta</div>

        <label class="field-label">NickName</label>
        <input type="text" name="nickname" maxlength="64" required value="<?= htmlspecialchars($_POST['nickname'] ?? '') ?>">

        <label class="field-label" style="margin-top:1rem;">Region</label>
        <select name="region" required>
          <option value="">— Wybierz region —</option>
          <?php foreach ($regions as $reg): ?>
            <option value="<?= $reg ?>" <?= (($_POST['region'] ?? '') === $reg) ? 'selected' : '' ?>><?= $reg ?></option>
          <?php endforeach; ?>
        </select>

        <p style="color:var(--text-muted); font-size:0.9rem; margin-top:0.75rem;">
          STEAM_ID zostanie utworzone automatycznie, a Twoja ranga zacznie się od <strong>Herald</strong>.
        </p>
      </div>

      <div class="submit-bar">
        <button type="submit" class="btn btn-red">Zarejestruj</button>
        <a href="login.php" class="btn btn-outline">Mam już konto</a>
      </div>
    </form>
  </div>
</div>

<?php require_once 'components/footer.php'; ?>
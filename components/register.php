<?php
session_start();
require_once 'db.php';

if (!empty($_SESSION['steam_id'])) {
    header('Location: account.php');
    exit;
}

$page_title = 'Rejestracja';
$errors  = [];

$regions = ['CHINA','RUSSIA','N-AMERICA','S-AMERICA','W-EUROPE','E-EUROPE','FILIPINO'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db_conn) {

    $nickname  = trim($_POST['nickname']  ?? '');
    $region    = trim($_POST['region']    ?? 'W-EUROPE');
    $password  = $_POST['password']       ?? '';
    $password2 = $_POST['password2']      ?? '';

    if (strlen($nickname) < 3 || strlen($nickname) > 64)
        $errors[] = 'Nick musi mieć od 3 do 64 znaków.';
    if (!preg_match('/^[a-zA-Z0-9_\-\.ąćęłńóśźżĄĆĘŁŃÓŚŹŻ ]+$/u', $nickname))
        $errors[] = 'Nick zawiera niedozwolone znaki.';
    if (!in_array($region, $regions))
        $errors[] = 'Nieprawidłowy region.';
    if (strlen($password) < 6)
        $errors[] = 'Hasło musi mieć minimum 6 znaków.';
    if ($password !== $password2)
        $errors[] = 'Hasła nie są identyczne.';

    // Sprawdź unikalność w C##app_admin.Player (tabela główna)
    if (empty($errors)) {
        $check = db_query($db_conn,
            "SELECT COUNT(*) AS CNT FROM C##app_admin.Player WHERE UPPER(NICKNAME) = UPPER(:nick)",
            [':nick' => $nickname]
        );
        if ((int)($check[0]['CNT'] ?? 0) > 0)
            $errors[] = 'Nick "' . htmlspecialchars($nickname) . '" jest już zajęty.';
    }

    if (empty($errors)) {
        $new_steam_id = 76561190000000000 + mt_rand(1000000, 9999999);

        // Sprawdź kolizję ID
        $id_check = db_query($db_conn,
            "SELECT COUNT(*) AS CNT FROM C##app_admin.Player WHERE STEAM_ID = :sid",
            [':sid' => $new_steam_id]
        );
        if ((int)($id_check[0]['CNT'] ?? 0) > 0)
            $new_steam_id += mt_rand(1, 99999);

        $pw_hash = password_hash($password, PASSWORD_BCRYPT);

        // INSERT do C##app_admin.Player — jawny schemat
        $stmt = oci_parse($db_conn,
            "INSERT INTO C##app_admin.Player
                 (STEAM_ID, NICKNAME, REGION, ACCOUNT_CREATED, RANK, PASSWORD_HASH)
             VALUES (:sid, :nick, :region, SYSDATE, 'Herald', :pwhash)"
        );
        oci_bind_by_name($stmt, ':sid',    $new_steam_id);
        oci_bind_by_name($stmt, ':nick',   $nickname);
        oci_bind_by_name($stmt, ':region', $region);
        oci_bind_by_name($stmt, ':pwhash', $pw_hash);

        $result = oci_execute($stmt);

        if ($result) {
            // Również wstaw do C##dota_app.Player żeby sesja działała bez schematu
            $stmt2 = oci_parse($db_conn,
                "INSERT INTO C##dota_app.Player
                     (STEAM_ID, NICKNAME, REGION, ACCOUNT_CREATED, RANK, PASSWORD_HASH)
                 VALUES (:sid, :nick, :region, SYSDATE, 'Herald', :pwhash)"
            );
            oci_bind_by_name($stmt2, ':sid',    $new_steam_id);
            oci_bind_by_name($stmt2, ':nick',   $nickname);
            oci_bind_by_name($stmt2, ':region', $region);
            oci_bind_by_name($stmt2, ':pwhash', $pw_hash);
            oci_execute($stmt2); // ignoruj błąd jeśli już istnieje
            oci_commit($db_conn);
            $_SESSION['steam_id'] = $new_steam_id;
            $_SESSION['nickname'] = $nickname;
            $_SESSION['rank']     = 'Herald';
            $_SESSION['region']   = $region;
            $_SESSION['is_admin'] = false;
            header('Location: account.php?welcome=1');
            exit;
        } else {
            $e = oci_error($stmt);
            $errors[] = 'Błąd bazy: ' . ($e['message'] ?? 'nieznany błąd');
            oci_rollback($db_conn);
        }
    }
}

require_once 'header.php';
?>

<style>
  .auth-wrap { max-width: 520px; margin: 3rem auto; }
  .auth-card {
    background: var(--bg1); border: 1px solid var(--border);
    border-top: 3px solid var(--red); padding: 2.4rem 2rem;
  }
  .auth-title {
    font-family: var(--font-head); font-size: 1.8rem; font-weight: 700;
    color: #fff; letter-spacing: 0.06em; text-transform: uppercase; margin-bottom: 0.3rem;
  }
  .auth-sub {
    font-family: var(--font-mono); font-size: 0.75rem;
    color: var(--text-muted); margin-bottom: 1.8rem; letter-spacing: 0.08em;
  }
  .form-group { margin-bottom: 1.2rem; }
  .form-group label {
    display: block; font-family: var(--font-mono); font-size: 0.7rem;
    color: var(--text-dim); letter-spacing: 0.12em; text-transform: uppercase; margin-bottom: 5px;
  }
  .form-group input, .form-group select {
    width: 100%; background: var(--bg2); border: 1px solid var(--border);
    color: var(--text); font-family: var(--font-body); font-size: 0.95rem;
    padding: 0.65rem 0.9rem; outline: none; transition: border-color 0.18s;
    appearance: none; -webkit-appearance: none;
  }
  .form-group input:focus, .form-group select:focus { border-color: var(--red); }
  .form-group input::placeholder { color: var(--text-muted); }
  .field-hint { font-family: var(--font-mono); font-size: 0.68rem; color: var(--text-muted); margin-top: 4px; }
  .info-badge {
    display: flex; align-items: center; gap: 8px;
    background: var(--bg3); border: 1px solid var(--border);
    padding: 0.6rem 0.9rem; font-family: var(--font-mono); font-size: 0.8rem; color: var(--text-muted);
  }
  .info-badge .badge-val { color: var(--gold); }
  .auth-errors {
    background: rgba(192,57,43,0.1); border-left: 3px solid var(--red);
    padding: 0.8rem 1rem; margin-bottom: 1.4rem;
  }
  .auth-errors li {
    font-family: var(--font-mono); font-size: 0.8rem;
    color: #ff8a80; margin-bottom: 4px; list-style: none;
  }
  .auth-errors li::before { content: '⚠ '; }
  .auth-footer {
    margin-top: 1.4rem; text-align: center;
    font-family: var(--font-mono); font-size: 0.78rem; color: var(--text-muted);
  }
  .auth-footer a { color: var(--red-bright); text-decoration: none; }
  .auth-footer a:hover { text-decoration: underline; }
  .rank-badge-herald {
    display: inline-block; padding: 2px 12px;
    border: 1px solid #616161; color: #9e9e9e;
    font-family: var(--font-mono); font-size: 0.72rem; letter-spacing: 0.1em;
  }
</style>

<div class="page-wrap">
  <div class="auth-wrap">

    <div class="page-banner" data-label="REGISTER" style="margin-bottom:1.5rem;">
      <h1 class="banner-title">Rejestracja <span>Gracza</span></h1>
      <div class="banner-divider"></div>
    </div>

    <?php if (!empty($errors)): ?>
      <ul class="auth-errors">
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <div class="auth-card">
      <div class="auth-title">Utwórz konto</div>
      <div class="auth-sub">// konto zapisywane do C##app_admin · ranga startowa: Herald</div>

      <form method="POST" autocomplete="off">

        <div class="form-group">
          <label>Nick (nazwa gracza)</label>
          <input type="text" name="nickname" maxlength="64" required
                 value="<?= htmlspecialchars($_POST['nickname'] ?? '') ?>">
          <div class="field-hint">3–64 znaki</div>
        </div>

        <div class="form-group">
          <label>Region</label>
          <select name="region">
            <?php foreach ($regions as $r): ?>
              <option value="<?= $r ?>" <?= ($_POST['region'] ?? 'W-EUROPE') === $r ? 'selected' : '' ?>>
                <?= $r ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Steam ID</label>
          <div class="info-badge">
            <span>🔑</span>
            <span>Generowane automatycznie po rejestracji</span>
            <span class="badge-val">76561190XXXXXXX</span>
          </div>
        </div>

        <div class="form-group">
          <label>Ranga startowa</label>
          <div class="info-badge">
            <span>🛡️</span>
            <span>Przypisana automatycznie:</span>
            <span class="rank-badge-herald">HERALD</span>
          </div>
        </div>

        <div class="form-group">
          <label>Hasło</label>
          <input type="password" name="password" minlength="6" required placeholder="Minimum 6 znaków">
        </div>

        <div class="form-group">
          <label>Powtórz hasło</label>
          <input type="password" name="password2" minlength="6" required placeholder="Wpisz hasło ponownie">
        </div>

        <button type="submit" class="btn btn-red"
                style="width:100%; padding:0.75rem; font-size:1rem; margin-top:0.5rem;">
          ⚔️ Zarejestruj się
        </button>
      </form>

      <div class="auth-footer">
        Masz już konto? <a href="login.php">Zaloguj się →</a>
      </div>
    </div>

  </div>
</div>

<?php require_once 'footer.php'; ?>

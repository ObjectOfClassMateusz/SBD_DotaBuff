<?php
session_start();
require_once 'db.php';

// Already logged in → go to account
if (!empty($_SESSION['steam_id'])) {
    header('Location: account.php');
    exit;
}

$page_title = 'Rejestracja';
$errors  = [];
$success = false;

$regions = ['CHINA','RUSSIA','N-AMERICA','S-AMERICA','W-EUROPE','E-EUROPE','FILIPINO'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db_conn) {

    // ── Sanitize & validate inputs ─────────────────────────
    $nickname = trim($_POST['nickname'] ?? '');
    $region   = trim($_POST['region']   ?? 'EU');
    $password = $_POST['password']      ?? '';
    $password2= $_POST['password2']     ?? '';

    // Validation
    if (strlen($nickname) < 3 || strlen($nickname) > 64) {
        $errors[] = 'Nick musi mieć od 3 do 64 znaków.';
    }
    if (!in_array($region, $regions)) {
        $errors[] = 'Nieprawidłowy region.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Hasło musi mieć minimum 6 znaków.';
    }
    if ($password !== $password2) {
        $errors[] = 'Hasła nie są identyczne.';
    }

    // Check nickname uniqueness — BIND VARIABLE (SQL Injection safe)
    if (empty($errors)) {
        $check = db_query($db_conn,
            "SELECT COUNT(*) AS CNT FROM Player WHERE UPPER(NICKNAME) = UPPER(:nick)",
            [':nick' => $nickname]
        );
        if ((int)($check[0]['CNT'] ?? 0) > 0) {
            $errors[] = 'Nick "' . htmlspecialchars($nickname) . '" jest już zajęty.';
        }
    }

    // ── Insert new player ──────────────────────────────────
    if (empty($errors)) {
        // Generate unique SteamID-like numeric ID (timestamp + random)
        $new_steam_id = 76561190000000000 + (int)(microtime(true) * 1000) % 9999999;

        // Hash password with PHP's bcrypt
        $pw_hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = oci_parse($db_conn,
            "INSERT INTO Player
                 (STEAM_ID, NICKNAME, ACCOUNT_CREATED, RANK, REGION, PASSWORD_HASH)
             VALUES
                 (:sid, :nick, SYSDATE, 'Herald', :region, :pwhash)"
        );
        oci_bind_by_name($stmt, ':sid',    $new_steam_id);
        oci_bind_by_name($stmt, ':nick',   $nickname);
        oci_bind_by_name($stmt, ':region', $region);
        oci_bind_by_name($stmt, ':pwhash', $pw_hash);

        $result = oci_execute($stmt);

        if ($result) {
            oci_commit($db_conn);

            // Auto-login after registration
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
.auth-wrap {
  max-width: 520px; margin: 3rem auto;
}
.auth-card {
  background: var(--bg1);
  border: 1px solid var(--border);
  border-top: 3px solid var(--red);
  padding: 2.4rem 2rem;
}
.auth-title {
  font-family: var(--font-head);
  font-size: 1.8rem; font-weight: 700;
  color: #fff; letter-spacing: 0.06em;
  text-transform: uppercase;
  margin-bottom: 0.3rem;
}
.auth-sub {
  font-family: var(--font-mono);
  font-size: 0.75rem; color: var(--text-muted);
  margin-bottom: 1.8rem; letter-spacing: 0.08em;
}
.form-group { margin-bottom: 1.2rem; }
.form-group label {
  display: block;
  font-family: var(--font-mono); font-size: 0.7rem;
  color: var(--text-dim); letter-spacing: 0.12em;
  text-transform: uppercase; margin-bottom: 5px;
}
.form-group input,
.form-group select {
  width: 100%; background: var(--bg2);
  border: 1px solid var(--border); color: var(--text);
  font-family: var(--font-body); font-size: 0.95rem;
  padding: 0.65rem 0.9rem; outline: none;
  transition: border-color 0.18s;
  appearance: none; -webkit-appearance: none;
}
.form-group input:focus,
.form-group select:focus { border-color: var(--red); }
.form-group input::placeholder { color: var(--text-muted); }

.field-hint {
  font-family: var(--font-mono); font-size: 0.68rem;
  color: var(--text-muted); margin-top: 4px;
}
.steam-badge {
  display: flex; align-items: center; gap: 8px;
  background: var(--bg3); border: 1px solid var(--border);
  padding: 0.6rem 0.9rem;
  font-family: var(--font-mono); font-size: 0.8rem;
  color: var(--text-muted);
}
.steam-badge .badge-val { color: var(--gold); }

.auth-errors {
  background: rgba(192,57,43,0.1);
  border-left: 3px solid var(--red);
  padding: 0.8rem 1rem; margin-bottom: 1.4rem;
}
.auth-errors li {
  font-family: var(--font-mono); font-size: 0.8rem;
  color: #ff8a80; margin-bottom: 4px; list-style: none;
}
.auth-errors li::before { content: '⚠ '; }

.auth-footer {
  margin-top: 1.4rem; text-align: center;
  font-family: var(--font-mono); font-size: 0.78rem;
  color: var(--text-muted);
}
.auth-footer a { color: var(--red-bright); text-decoration: none; }
.auth-footer a:hover { text-decoration: underline; }

.rank-badge-herald {
  display: inline-block; padding: 2px 12px;
  border: 1px solid #616161; color: #9e9e9e;
  font-family: var(--font-mono); font-size: 0.72rem;
  letter-spacing: 0.1em;
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

      <form method="POST" autocomplete="off">

        <!-- Nickname -->
        <div class="form-group">
          <label>Nick (nazwa gracza)</label>
          <input type="text" name="nickname" maxlength="64" required
                 placeholder="np. NightStalker_PL"
                 value="<?= htmlspecialchars($_POST['nickname'] ?? '') ?>">
          <div class="field-hint">Tylko litery, cyfry, _, -, . (3–64 znaki)</div>
        </div>

        <!-- Region -->
        <div class="form-group">
          <label>Region</label>
          <select name="region">
            <?php foreach ($regions as $r): ?>
              <option value="<?= $r ?>" <?= ($_POST['region'] ?? 'EU') === $r ? 'selected' : '' ?>>
                <?= $r ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Steam ID info -->
        <div class="form-group">
          <label>Steam ID</label>
          <div class="steam-badge">
            <span>🔑</span>
            <span>Generowane automatycznie po rejestracji</span>
            <span class="badge-val">76561190XXXXXXXXX</span>
          </div>
        </div>

        <!-- Rank info -->
        <div class="form-group">
          <label>Ranga startowa</label>
          <div class="steam-badge">
            <span>🛡️</span>
            <span>Ranga przypisana automatycznie:</span>
            <span class="rank-badge-herald">HERALD</span>
          </div>
        </div>

        <!-- Password -->
        <div class="form-group">
          <label>Hasło</label>
          <input type="password" name="password" minlength="6" required
                 placeholder="Minimum 6 znaków">
        </div>

        <!-- Password confirm -->
        <div class="form-group">
          <label>Powtórz hasło</label>
          <input type="password" name="password2" minlength="6" required
                 placeholder="Wpisz hasło ponownie">
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

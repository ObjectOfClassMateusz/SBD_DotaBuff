<?php
session_start();
require_once 'db.php';

// Already logged in
if (!empty($_SESSION['steam_id']) || !empty($_SESSION['is_admin'])) {
    header('Location: account.php');
    exit;
}

$page_title = 'Logowanie';
$error_player = null;
$error_admin  = null;


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type'])
    && $_POST['form_type'] === 'player' && $db_conn)
{
    $nickname  = trim($_POST['nickname']  ?? '');
    $steam_raw = trim($_POST['steam_id']  ?? '');
    $password  = $_POST['password']       ?? '';

    // Validate — no raw string concatenation, all bind variables
    if (empty($nickname) || empty($steam_raw) || empty($password)) {
        $error_player = 'Wypełnij wszystkie pola.';
    } else {
        $steam_id = (int) preg_replace('/\D/', '', $steam_raw); // strip non-digits

        // Fetch player using bind variables — SQL Injection safe
        $rows = db_query($db_conn,
            "SELECT STEAM_ID, NICKNAME, RANK, REGION, PASSWORD_HASH
             FROM Player
             WHERE STEAM_ID = :sid
               AND UPPER(NICKNAME) = UPPER(:nick)",
            [':sid' => $steam_id, ':nick' => $nickname]
        );

        if (empty($rows)) {
            $error_player = 'Nie znaleziono gracza o podanym nicku i Steam ID.';
        } else {
            $player = $rows[0];

            // Verify password
            if (!password_verify($password, $player['PASSWORD_HASH'] ?? '')) {
                $error_player = 'Nieprawidłowe hasło.';
            } else {
                // ✅ Login success
                session_regenerate_id(true);
                $_SESSION['steam_id'] = (int)$player['STEAM_ID'];
                $_SESSION['nickname'] = $player['NICKNAME'];
                $_SESSION['rank']     = $player['RANK'];
                $_SESSION['region']   = $player['REGION'] ?? 'EU';
                $_SESSION['is_admin'] = false;

                header('Location: account.php');
                exit;
            }
        }
    }
}

// ── ADMIN (C##dota_app) LOGIN ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type'])
    && $_POST['form_type'] === 'admin')
{
    $admin_user = trim($_POST['admin_user'] ?? '');
    $admin_pass = $_POST['admin_pass']      ?? '';

    // Only allow the dota_app credential — never pass raw to OCI without check
    $allowed_user = 'C##dota_app';
    $allowed_pass = 'qwerty123'; // same as db.php

    if (strtolower($admin_user) !== strtolower($allowed_user)
        || $admin_pass !== $allowed_pass)
    {
        $error_admin = 'Nieprawidłowe dane konta C##dota_app.';
    } else {
        // Try to actually connect to verify credentials
        $test = oci_connect($admin_user, $admin_pass, 'localhost/ORCL');
        if (!$test) {
            $e = oci_error();
            $error_admin = 'Błąd połączenia Oracle: ' . ($e['message'] ?? '');
        } else {
            oci_close($test);
            session_regenerate_id(true);
            $_SESSION['is_admin']    = true;
            $_SESSION['admin_user']  = $allowed_user;
            $_SESSION['nickname']    = 'C##dota_app';
            $_SESSION['steam_id']    = null;

            header('Location: account.php');
            exit;
        }
    }
}

require_once 'header.php';
?>

<style>
.login-wrap {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
  max-width: 900px;
  margin: 3rem auto;
  align-items: start;
}
@media (max-width: 680px) {
  .login-wrap { grid-template-columns: 1fr; }
}

.auth-card {
  background: var(--bg1);
  border: 1px solid var(--border);
  padding: 2rem 1.8rem;
  position: relative;
}
.auth-card.player-card { border-top: 3px solid var(--red); }
.auth-card.admin-card  { border-top: 3px solid var(--gold); }

.card-badge {
  font-family: var(--font-mono); font-size: 0.68rem;
  letter-spacing: 0.14em; text-transform: uppercase;
  margin-bottom: 0.4rem;
}
.card-badge.player { color: var(--red); }
.card-badge.admin  { color: var(--gold); }

.auth-title {
  font-family: var(--font-head); font-size: 1.5rem;
  font-weight: 700; color: #fff; letter-spacing: 0.04em;
  text-transform: uppercase; margin-bottom: 0.25rem;
}
.auth-sub {
  font-family: var(--font-mono); font-size: 0.72rem;
  color: var(--text-muted); margin-bottom: 1.6rem;
}

.divider-line {
  display: flex; align-items: center; gap: 1rem;
  margin: 2rem 0;
}
.divider-line::before,
.divider-line::after {
  content: ''; flex: 1; height: 1px;
  background: var(--border);
}
.divider-line span {
  font-family: var(--font-mono); font-size: 0.7rem;
  color: var(--text-muted); white-space: nowrap;
}

/* Shared form field styles */
.form-group { margin-bottom: 1.1rem; }
.form-group label {
  display: block; font-family: var(--font-mono);
  font-size: 0.68rem; color: var(--text-dim);
  letter-spacing: 0.12em; text-transform: uppercase;
  margin-bottom: 4px;
}
.form-group input {
  width: 100%; background: var(--bg2);
  border: 1px solid var(--border); color: var(--text);
  font-family: var(--font-body); font-size: 0.9rem;
  padding: 0.6rem 0.9rem; outline: none;
  transition: border-color 0.18s;
}
.form-group input:focus { border-color: var(--red); }
.admin-card .form-group input:focus { border-color: var(--gold); }
.form-group input::placeholder { color: var(--text-muted); }

.auth-error {
  background: rgba(192,57,43,0.1);
  border-left: 3px solid var(--red);
  padding: 0.65rem 0.9rem; margin-bottom: 1.2rem;
  font-family: var(--font-mono); font-size: 0.8rem;
  color: #ff8a80;
}
.auth-error::before { content: '⚠ '; }

.btn-gold {
  background: var(--gold); color: var(--bg0);
  font-family: var(--font-head); font-size: 0.95rem;
  font-weight: 700; letter-spacing: 0.06em;
  text-transform: uppercase; border: none;
  width: 100%; padding: 0.7rem;
  cursor: pointer; transition: background 0.18s;
}
.btn-gold:hover { background: #e8c060; }

.auth-footer {
  margin-top: 1.2rem; text-align: center;
  font-family: var(--font-mono); font-size: 0.75rem;
  color: var(--text-muted);
}
.auth-footer a { color: var(--red-bright); text-decoration: none; }
.auth-footer a:hover { text-decoration: underline; }

.admin-warning {
  background: rgba(212,168,67,0.07);
  border: 1px solid rgba(212,168,67,0.2);
  padding: 0.6rem 0.9rem; margin-bottom: 1.2rem;
  font-family: var(--font-mono); font-size: 0.72rem;
  color: var(--gold);
}
.admin-warning::before { content: '🔐 '; }

/* eye toggle */
.pw-wrap { position: relative; }
.pw-wrap input { padding-right: 2.5rem; }
.pw-toggle {
  position: absolute; right: 10px; top: 50%;
  transform: translateY(-50%);
  background: none; border: none; cursor: pointer;
  color: var(--text-muted); font-size: 1rem; padding: 0;
  transition: color 0.15s;
}
.pw-toggle:hover { color: var(--text); }
</style>

<div class="page-wrap">

  <div style="max-width:900px; margin:0 auto;">
    <div class="page-banner" data-label="LOGIN" style="margin-bottom:2rem;">
      <div class="banner-tag">// Dostęp do systemu</div>
      <h1 class="banner-title">Logowanie <span>Dota2</span> Analytics</h1>
      <div class="banner-divider"></div>
    </div>
  </div>

  <div class="login-wrap">

    <!-- ══ PANEL 1: PLAYER LOGIN ═══════════════════════════ -->
    <div class="auth-card player-card">
      <div class="card-badge player">// Konto gracza</div>
      <div class="auth-title">Logowanie</div>
      <div class="auth-sub">Zaloguj się nickiem i Steam ID</div>

      <?php if ($error_player): ?>
        <div class="auth-error"><?= htmlspecialchars($error_player) ?></div>
      <?php endif; ?>

      <form method="POST" autocomplete="off">
        <input type="hidden" name="form_type" value="player">

        <div class="form-group">
          <label>Nickname</label>
          <input type="text" name="nickname" required
                 placeholder="Twój nick w grze"
                 value="<?= htmlspecialchars($_POST['nickname'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label>Steam ID</label>
          <input type="text" name="steam_id" required
                 placeholder="np. 76561198000555001"
                 value="<?= htmlspecialchars($_POST['steam_id'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label>Hasło</label>
          <div class="pw-wrap">
            <input type="password" name="password" id="pw_player" required
                   placeholder="Twoje hasło">
            <button type="button" class="pw-toggle" onclick="togglePw('pw_player',this)">👁</button>
          </div>
        </div>

        <button type="submit" class="btn btn-red"
                style="width:100%; padding:0.7rem; font-size:0.95rem; margin-top:0.3rem;">
          ⚔️ Zaloguj się
        </button>
      </form>

      <div class="auth-footer">
        Nie masz konta? <a href="register.php">Zarejestruj się →</a>
      </div>
    </div>

    <!-- ══ PANEL 2: C##DOTA_APP ADMIN LOGIN ════════════════ -->
    <div class="auth-card admin-card">
      <div class="card-badge admin">// Konto administracyjne Oracle</div>
      <div class="auth-title">Panel Admina</div>
      <div class="auth-sub">Dostęp do zarządzania meczami</div>

      <div class="admin-warning">
        Panel tylko dla użytkownika C##dota_app.<br>
        Daje dostęp do: Dodaj Mecz i zarządzania DB.
      </div>

      <?php if ($error_admin): ?>
        <div class="auth-error"><?= htmlspecialchars($error_admin) ?></div>
      <?php endif; ?>

      <form method="POST" autocomplete="off">
        <input type="hidden" name="form_type" value="admin">

        <div class="form-group">
          <label>Użytkownik Oracle</label>
          <input type="text" name="admin_user" required
                 placeholder="C##dota_app"
                 value="<?= htmlspecialchars($_POST['admin_user'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label>Hasło Oracle</label>
          <div class="pw-wrap">
            <input type="password" name="admin_pass" id="pw_admin" required
                   placeholder="Hasło do Oracle">
            <button type="button" class="pw-toggle" onclick="togglePw('pw_admin',this)">👁</button>
          </div>
        </div>

        <button type="submit" class="btn-gold" style="margin-top:0.3rem;">
          🔐 Zaloguj jako Admin
        </button>
      </form>

      <div class="auth-footer" style="margin-top:1rem;">
        <span style="color:var(--gold); opacity:0.6;">
          Sesja admina daje dostęp do Dodaj Mecz
        </span>
      </div>
    </div>

  </div><!-- /.login-wrap -->
</div>

<script>
function togglePw(id, btn) {
  const inp = document.getElementById(id);
  if (inp.type === 'password') {
    inp.type = 'text';
    btn.textContent = '🙈';
  } else {
    inp.type = 'password';
    btn.textContent = '👁';
  }
}
</script>

<?php require_once 'footer.php'; ?>

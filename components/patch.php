<?php
require_once 'db.php';
$page_title = 'Patch Notes';

// ── Dane z paczki GeneralInfo ──────────────────────────────
$latest_patch_id  = null;
$all_patches      = [];
$hero_changes     = [];
$item_changes     = [];
$patch_balance    = null;
$selected_patch   = null;
$db_error_local   = null;

if ($db_conn) {
    try {
        // Lista wszystkich patchy
        $all_patches = db_query($db_conn,
            "SELECT ID, VERSION_STRING,
                    TO_CHAR(RELEASE_DATE,'YYYY-MM-DD') AS REL_DATE
             FROM Patch_Info
             ORDER BY RELEASE_DATE DESC"
        );

        // Aktualnie wybrany patch (z GET lub najnowszy)
        $latest_id_row = db_query($db_conn,
            "SELECT GeneralInfo.GetLatestPatchId AS PID FROM DUAL"
        );
        $latest_patch_id = (int)($latest_id_row[0]['PID'] ?? 0);

        $selected_patch = isset($_GET['patch'])
            ? (int)$_GET['patch']
            : $latest_patch_id;

        // Info o wybranym patchu
        $patch_info_rows = db_query($db_conn,
            "SELECT VERSION_STRING, TO_CHAR(RELEASE_DATE,'DD MMMM YYYY','NLS_DATE_LANGUAGE=POLISH') AS REL_DATE
             FROM Patch_Info WHERE ID = :pid",
            [':pid' => $selected_patch]
        );
        $patch_info = $patch_info_rows[0] ?? null;

        // Zmiany bohaterów — PIPELINED function
        $hero_changes = db_query($db_conn,
            "SELECT VERSION_STRING, TARGET_NAME, CHANGE_FLAG, CHANGE_DESCRIPTION
             FROM TABLE(GeneralInfo.GetPatchHeroChanges(:pid))
             ORDER BY CHANGE_FLAG, TARGET_NAME",
            [':pid' => $selected_patch]
        );

        // Zmiany itemów — PIPELINED function
        $item_changes = db_query($db_conn,
            "SELECT VERSION_STRING, TARGET_NAME, CHANGE_FLAG, CHANGE_DESCRIPTION
             FROM TABLE(GeneralInfo.GetPatchItemChanges(:pid))
             ORDER BY CHANGE_FLAG, TARGET_NAME",
            [':pid' => $selected_patch]
        );

        // Balans patcha (buffy - nerfy)
        $balance_row = db_query($db_conn,
            "SELECT GeneralInfo.GetPatchBalance(:pid) AS BAL FROM DUAL",
            [':pid' => $selected_patch]
        );
        $patch_balance = (int)($balance_row[0]['BAL'] ?? 0);

    } catch (Exception $e) {
        $db_error_local = $e->getMessage();
    }
}

function group_by_flag($changes) {
    $out = [1 => [], 2 => [], 0 => []];
    foreach ($changes as $c) {
        $flag = (int)$c['CHANGE_FLAG'];
        $key  = $flag === 1 ? 1 : ($flag === 2 ? 2 : 0);
        $out[$key][] = $c;
    }
    return $out;
}

$hero_grouped = group_by_flag($hero_changes);
$item_grouped = group_by_flag($item_changes);

$buff_heroes  = count($hero_grouped[1]);
$nerf_heroes  = count($hero_grouped[2]);
$buff_items   = count($item_grouped[1]);
$nerf_items   = count($item_grouped[2]);
$total_buffs  = $buff_heroes + $buff_items;
$total_nerfs  = $nerf_heroes + $nerf_items;

require_once 'header.php';
?>

<style>
    /* ══ PATCH PAGE ════════════════════════════════════════════ */
    .patch-layout {
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 1.5rem;
    align-items: start;
    }
    @media (max-width: 860px) {
    .patch-layout { grid-template-columns: 1fr; }
    }

    /* Sidebar — patch list */
    .patch-sidebar {
    background: var(--bg1);
    border: 1px solid var(--border);
    overflow: hidden;
    position: sticky; top: calc(var(--nav-h) + 1rem);
    }
    .patch-sidebar-title {
    padding: 0.8rem 1.1rem;
    font-family: var(--font-mono); font-size: 0.68rem;
    letter-spacing: 0.14em; text-transform: uppercase;
    color: var(--red); background: var(--bg3);
    border-bottom: 1px solid var(--border);
    }
    .patch-list { list-style: none; }
    .patch-list li a {
    display: flex; align-items: center; justify-content: space-between;
    padding: 0.65rem 1.1rem;
    text-decoration: none; font-family: var(--font-mono);
    font-size: 0.8rem; color: var(--text-dim);
    border-bottom: 1px solid var(--border);
    transition: all 0.15s;
    }
    .patch-list li a:hover { background: var(--bg2); color: var(--text); }
    .patch-list li a.active {
    background: rgba(192,57,43,0.12);
    color: var(--red-bright);
    border-left: 3px solid var(--red-bright);
    }
    .patch-list li a .pver {
    font-weight: 700; font-size: 0.88rem;
    }
    .patch-list li a .pdate {
    font-size: 0.68rem; color: var(--text-muted);
    }
    .patch-list li a.active .pdate { color: rgba(231,76,60,0.7); }

    /* Main area */
    .patch-main { }

    /* Patch header card */
    .patch-hero-card {
    background: linear-gradient(135deg, rgba(192,57,43,0.1), var(--bg2));
    border: 1px solid var(--red-dim);
    padding: 1.8rem 2rem; margin-bottom: 1.5rem;
    position: relative; overflow: hidden;
    }
    .patch-hero-card::before {
    content: attr(data-version);
    position: absolute; right: -10px; top: -20px;
    font-family: var(--font-head); font-size: 7rem;
    font-weight: 900; color: rgba(192,57,43,0.06);
    letter-spacing: -0.04em; pointer-events: none;
    }
    .patch-version {
    font-family: var(--font-head); font-size: 2.4rem;
    font-weight: 900; color: #fff; letter-spacing: 0.04em;
    line-height: 1; margin-bottom: 0.3rem;
    }
    .patch-date {
    font-family: var(--font-mono); font-size: 0.78rem;
    color: var(--text-muted); text-transform: uppercase;
    letter-spacing: 0.1em; margin-bottom: 1.2rem;
    }
    .patch-balance-bar {
    display: flex; align-items: center; gap: 1rem;
    flex-wrap: wrap;
    }
    .balance-chip {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 14px; border: 1px solid;
    font-family: var(--font-mono); font-size: 0.78rem;
    letter-spacing: 0.08em; font-weight: 700;
    }
    .balance-chip.buff  { color: #66bb6a; border-color: rgba(76,175,80,0.4); background: rgba(76,175,80,0.08); }
    .balance-chip.nerf  { color: var(--red-bright); border-color: rgba(192,57,43,0.4); background: rgba(192,57,43,0.08); }
    .balance-chip.total { color: var(--gold); border-color: rgba(212,168,67,0.4); background: rgba(212,168,67,0.06); }

    .balance-indicator {
    font-family: var(--font-head); font-size: 1rem;
    font-weight: 700; letter-spacing: 0.06em;
    padding: 3px 16px; border: 1px solid;
    }
    .balance-indicator.pos { color: #66bb6a; border-color: #4caf50; background: rgba(76,175,80,0.1); }
    .balance-indicator.neg { color: var(--red-bright); border-color: var(--red); background: rgba(192,57,43,0.1); }
    .balance-indicator.neu { color: var(--text-dim); border-color: var(--border); background: transparent; }

    /* Section tabs */
    .section-tabs {
    display: flex; gap: 2px; margin-bottom: 1.2rem;
    }
    .tab-btn {
    padding: 0.5rem 1.4rem;
    font-family: var(--font-head); font-size: 0.9rem;
    font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase;
    border: 1px solid var(--border); background: var(--bg2);
    color: var(--text-dim); cursor: pointer; transition: all 0.15s;
    }
    .tab-btn:hover { border-color: var(--border-hot); color: var(--text); }
    .tab-btn.active { background: var(--red); border-color: var(--red); color: #fff; }

    /* Change section */
    .change-section { margin-bottom: 1.5rem; }
    .change-section-header {
    display: flex; align-items: center; gap: 0.8rem;
    padding: 0.6rem 1rem; margin-bottom: 0.4rem;
    font-family: var(--font-head); font-size: 1rem;
    font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase;
    }
    .change-section-header.buff {
    background: rgba(76,175,80,0.08);
    border-left: 4px solid #4caf50; color: #66bb6a;
    }
    .change-section-header.nerf {
    background: rgba(192,57,43,0.08);
    border-left: 4px solid var(--red-bright); color: var(--red-bright);
    }
    .change-section-header.neutral {
    background: rgba(255,255,255,0.03);
    border-left: 4px solid var(--text-muted); color: var(--text-muted);
    }
    .change-section-header .count-badge {
    font-family: var(--font-mono); font-size: 0.72rem;
    padding: 1px 8px; border: 1px solid currentColor;
    opacity: 0.8;
    }

    /* Change cards grid */
    .change-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 0.6rem;
    }
    .change-card {
    background: var(--bg1); border: 1px solid var(--border);
    padding: 0.8rem 1rem; transition: border-color 0.15s;
    position: relative;
    }
    .change-card:hover { border-color: var(--border-hot); }
    .change-card.buff  { border-left: 3px solid rgba(76,175,80,0.5); }
    .change-card.nerf  { border-left: 3px solid rgba(192,57,43,0.5); }
    .change-card.neutral { border-left: 3px solid var(--border); }

    .change-card .card-name {
    font-family: var(--font-head); font-size: 1rem;
    font-weight: 700; color: #fff; letter-spacing: 0.03em;
    margin-bottom: 0.35rem;
    }
    .change-card .card-flag {
    display: inline-block; font-family: var(--font-mono);
    font-size: 0.62rem; letter-spacing: 0.1em; text-transform: uppercase;
    padding: 1px 7px; border: 1px solid; margin-bottom: 0.4rem;
    }
    .card-flag.buff  { color: #66bb6a; border-color: rgba(76,175,80,0.5); }
    .card-flag.nerf  { color: var(--red-bright); border-color: rgba(192,57,43,0.5); }
    .card-flag.neutral { color: var(--text-muted); border-color: var(--border); }
    .change-card .card-desc {
    font-size: 0.82rem; color: var(--text-dim); line-height: 1.5;
    }

    /* Empty state */
    .empty-patch {
    text-align: center; padding: 3rem 2rem;
    font-family: var(--font-mono); font-size: 0.85rem;
    color: var(--text-muted);
    }
    .empty-patch .big { font-size: 2.5rem; margin-bottom: 0.5rem; opacity: 0.3; }

    /* Tab content */
    .tab-content { display: none; }
    .tab-content.active { display: block; }
</style>

<div class="page-wrap">

  <!-- BANNER -->
  <div class="page-banner" data-label="PATCH">
    <div class="banner-tag">// Aktualizacje gry</div>
    <h1 class="banner-title">Patch <span>Notes</span></h1>
    <p class="banner-sub">Zmiany bohaterów i przedmiotów — buffy, nerfy, poprawki balansowe.</p>
    <div class="banner-divider"></div>
  </div>

  <?php if ($db_error || $db_error_local): ?>
    <div class="alert alert-error">
      ⚠ <?= htmlspecialchars($db_error ?? $db_error_local) ?>
    </div>
  <?php endif; ?>

  <?php if ($db_conn): ?>
  <div class="patch-layout">

    <!-- ══ SIDEBAR — lista patchy ════════════════════════ -->
    <aside class="patch-sidebar">
      <div class="patch-sidebar-title">// Wersje patchy</div>
      <ul class="patch-list">
        <?php if (empty($all_patches)): ?>
          <li style="padding:1rem; font-family:var(--font-mono); font-size:0.75rem; color:var(--text-muted);">
            Brak danych w Patch_Info
          </li>
        <?php else: foreach ($all_patches as $p): ?>
          <li>
            <a href="?patch=<?= $p['ID'] ?>"
               class="<?= (int)$p['ID'] === $selected_patch ? 'active' : '' ?>">
              <span class="pver"><?= htmlspecialchars($p['VERSION_STRING']) ?></span>
              <span class="pdate"><?= htmlspecialchars($p['REL_DATE']) ?></span>
            </a>
          </li>
        <?php endforeach; endif; ?>
      </ul>
    </aside>

    <!-- ══ MAIN ══════════════════════════════════════════ -->
    <main class="patch-main">

      <?php if (!$patch_info): ?>
        <div class="empty-patch">
          <div class="big">📋</div>
          <p>Wybierz patch z listy po lewej stronie</p>
        </div>
      <?php else: ?>

      <!-- PATCH HERO CARD -->
      <div class="patch-hero-card" data-version="<?= htmlspecialchars($patch_info['VERSION_STRING']) ?>">
        <div class="patch-version">
          Patch <?= htmlspecialchars($patch_info['VERSION_STRING']) ?>
        </div>
        <div class="patch-date">
          📅 <?= htmlspecialchars($patch_info['REL_DATE'] ?? '—') ?>
          <?php if ($selected_patch == $latest_patch_id): ?>
            &nbsp;<span class="badge badge-ranked" style="font-size:0.65rem; vertical-align:middle;">NAJNOWSZY</span>
          <?php endif; ?>
        </div>

        <div class="patch-balance-bar">
          <span class="balance-chip buff">🟢 BUFF &nbsp;+<?= $total_buffs ?></span>
          <span class="balance-chip nerf">🔴 NERF &nbsp;−<?= $total_nerfs ?></span>
          <span class="balance-chip total">⚡ ŁĄCZNIE <?= count($hero_changes) + count($item_changes) ?></span>
          <?php
            $bal_class = $patch_balance > 0 ? 'pos' : ($patch_balance < 0 ? 'neg' : 'neu');
            $bal_label = $patch_balance > 0 ? 'BUFF-HEAVY' : ($patch_balance < 0 ? 'NERF-HEAVY' : 'ZBALANSOWANY');
          ?>
          <span class="balance-indicator <?= $bal_class ?>">
            <?= $bal_label ?> (<?= $patch_balance > 0 ? '+' : '' ?><?= $patch_balance ?>)
          </span>
        </div>
      </div>

      <!-- TABS: Heroes / Items -->
      <div class="section-tabs">
        <button class="tab-btn active" onclick="switchTab('heroes', this)">
          ⚔️ Bohaterowie
          <span style="font-size:0.75rem; opacity:0.7;">(<?= count($hero_changes) ?>)</span>
        </button>
        <button class="tab-btn" onclick="switchTab('items', this)">
          🗡️ Przedmioty
          <span style="font-size:0.75rem; opacity:0.7;">(<?= count($item_changes) ?>)</span>
        </button>
      </div>

      <!-- ── TAB: HEROES ──────────────────────────────── -->
      <div id="tab-heroes" class="tab-content active">
        <?php
          $flag_config = [
            1 => ['label'=>'🟢 Buffy',   'cls'=>'buff',    'icon'=>'⬆'],
            2 => ['label'=>'🔴 Nerfy',   'cls'=>'nerf',    'icon'=>'⬇'],
            0 => ['label'=>'⬜ Neutralne','cls'=>'neutral', 'icon'=>'→'],
          ];
          foreach ([1, 2, 0] as $flag):
            $entries = $hero_grouped[$flag] ?? [];
            if (empty($entries)) continue;
            $cfg = $flag_config[$flag];
        ?>
        <div class="change-section">
          <div class="change-section-header <?= $cfg['cls'] ?>">
            <?= $cfg['label'] ?>
            <span class="count-badge"><?= count($entries) ?></span>
          </div>
          <div class="change-grid">
            <?php foreach ($entries as $ch): ?>
            <div class="change-card <?= $cfg['cls'] ?>">
              <div class="card-name"><?= htmlspecialchars($ch['TARGET_NAME']) ?></div>
              <span class="card-flag <?= $cfg['cls'] ?>"><?= $cfg['icon'] ?> <?= $cfg['cls'] === 'buff' ? 'BUFF' : ($cfg['cls'] === 'nerf' ? 'NERF' : 'NEUTRAL') ?></span>
              <div class="card-desc">
                <?= htmlspecialchars($ch['CHANGE_DESCRIPTION'] ?? 'Brak opisu zmian') ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($hero_changes)): ?>
          <div class="empty-patch">
            <div class="big">⚔️</div>
            <p>Brak zmian bohaterów w tym patchu</p>
          </div>
        <?php endif; ?>
      </div>

      <!-- ── TAB: ITEMS ───────────────────────────────── -->
      <div id="tab-items" class="tab-content">
        <?php
          foreach ([1, 2, 0] as $flag):
            $entries = $item_grouped[$flag] ?? [];
            if (empty($entries)) continue;
            $cfg = $flag_config[$flag];
        ?>
        <div class="change-section">
          <div class="change-section-header <?= $cfg['cls'] ?>">
            <?= $cfg['label'] ?>
            <span class="count-badge"><?= count($entries) ?></span>
          </div>
          <div class="change-grid">
            <?php foreach ($entries as $ch): ?>
            <div class="change-card <?= $cfg['cls'] ?>">
              <div class="card-name"><?= htmlspecialchars($ch['TARGET_NAME']) ?></div>
              <span class="card-flag <?= $cfg['cls'] ?>"><?= $cfg['icon'] ?> <?= $cfg['cls'] === 'buff' ? 'BUFF' : ($cfg['cls'] === 'nerf' ? 'NERF' : 'NEUTRAL') ?></span>
              <div class="card-desc">
                <?= htmlspecialchars($ch['CHANGE_DESCRIPTION'] ?? 'Brak opisu zmian') ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($item_changes)): ?>
          <div class="empty-patch">
            <div class="big">🗡️</div>
            <p>Brak zmian przedmiotów w tym patchu</p>
          </div>
        <?php endif; ?>
      </div>

      <?php endif; // patch_info ?>
    </main>

  </div><!-- /.patch-layout -->
  <?php endif; // db_conn ?>

</div>

<script>
function switchTab(name, btn) {
  // Hide all tabs
  document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));

  // Show selected
  document.getElementById('tab-' + name).classList.add('active');
  btn.classList.add('active');
}
</script>

<?php require_once 'footer.php'; ?>

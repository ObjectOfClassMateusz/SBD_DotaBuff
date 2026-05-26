<?php
require_once 'db.php';
$page_title = 'Dodaj Mecz';

// ── Load reference data ────────────────────────────────────
$heroes  = $db_conn ? db_query($db_conn, "SELECT ID, NAME, PRIMARY_ATTRIBUTE FROM SYS.Hero ORDER BY NAME") : [];
$players = $db_conn ? db_query($db_conn, "SELECT STEAM_ID, NICKNAME, RANK FROM SYS.Player ORDER BY NICKNAME") : [];
$items   = $db_conn ? db_query($db_conn, "SELECT ID, NAME FROM SYS.Item ORDER BY NAME") : [];

// ── Process POST ───────────────────────────────────────────
$success_msg = null;
$error_msg   = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db_conn) {
    try {
        $mode      = $_POST['game_mode']    ?? 'Normal';   // Ranked / Normal
        $winner    = $_POST['winner_side']  ?? 'Radiant';  // Radiant / Dire
        $dur_h     = (int)($_POST['dur_h']  ?? 0);
        $dur_m     = (int)($_POST['dur_m']  ?? 40);
        $dur_s     = (int)($_POST['dur_s']  ?? 0);
        $match_dt  = $_POST['match_date']   ?? date('Y-m-d H:i');
        $is_ranked = $mode === 'Ranked' ? 1 : 0;

        // ── Insert 10 Hero_Played rows ─────────────────────
        $hp_ids = [];
        $sides  = ['radiant', 'dire'];

        foreach ($sides as $side) {
            for ($pos = 1; $pos <= 5; $pos++) {
                $pfx = $side . '_' . $pos;

                $steam_id  = (int)($_POST[$pfx . '_player']  ?? 0);
                $hero_id   = (int)($_POST[$pfx . '_hero']    ?? 0);
                $kills     = (int)($_POST[$pfx . '_kills']   ?? 0);
                $deaths    = (int)($_POST[$pfx . '_deaths']  ?? 0);
                $assists   = (int)($_POST[$pfx . '_assists'] ?? 0);
                $netto     = (int)($_POST[$pfx . '_netto']   ?? 0);
                $slot1     = (int)($_POST[$pfx . '_item1']   ?? 0) ?: 'NULL';
                $slot2     = (int)($_POST[$pfx . '_item2']   ?? 0) ?: 'NULL';
                $slot3     = (int)($_POST[$pfx . '_item3']   ?? 0) ?: 'NULL';
                $slot4     = (int)($_POST[$pfx . '_item4']   ?? 0) ?: 'NULL';
                $slot5     = (int)($_POST[$pfx . '_item5']   ?? 0) ?: 'NULL';
                $slot6     = (int)($_POST[$pfx . '_item6']   ?? 0) ?: 'NULL';

                if (!$steam_id || !$hero_id) {
                    throw new Exception("Brak gracza lub bohatera dla pozycji {$pos} ({$side})");
                }

                $sql = "INSERT INTO SYS.Hero_Played
                            (STEAM_ID, HERO_ID, POSITION, KILLS, DEATHS, ASSISTS, NETTO,
                             SLOT1, SLOT2, SLOT3, SLOT4, SLOT5, SLOT6)
                        VALUES
                            ($steam_id, $hero_id, $pos, $kills, $deaths, $assists, $netto,
                             $slot1, $slot2, $slot3, $slot4, $slot5, $slot6)";
                $stmt = oci_parse($db_conn, $sql);
                oci_execute($stmt, OCI_NO_AUTO_COMMIT);

                // Get the generated ID
                $id_row = db_query($db_conn,
                    "SELECT SYS.SEQ_HEROPLAYED_ID.CURRVAL AS CID FROM DUAL"
                );
                $hp_ids[$side][$pos] = (int)$id_row[0]['CID'];
            }
        }

        // ── Insert Radiant Team ────────────────────────────
        $r = $hp_ids['radiant'];
        $d = $hp_ids['dire'];

        $sql = "INSERT INTO SYS.Team (SIDE, HP1, HP2, HP3, HP4, HP5)
                VALUES ('Radiant', {$r[1]}, {$r[2]}, {$r[3]}, {$r[4]}, {$r[5]})";
        $stmt = oci_parse($db_conn, $sql);
        oci_execute($stmt, OCI_NO_AUTO_COMMIT);

        $rad_team = db_query($db_conn, "SELECT SYS.SEQ_TEAM_ID.CURRVAL AS TID FROM DUAL");
        $rad_team_id = (int)$rad_team[0]['TID'];

        // ── Insert Dire Team ───────────────────────────────
        $sql = "INSERT INTO SYS.Team (SIDE, HP1, HP2, HP3, HP4, HP5)
                VALUES ('Dire', {$d[1]}, {$d[2]}, {$d[3]}, {$d[4]}, {$d[5]})";
        $stmt = oci_parse($db_conn, $sql);
        oci_execute($stmt, OCI_NO_AUTO_COMMIT);

        $dire_team = db_query($db_conn, "SELECT SYS.SEQ_TEAM_ID.CURRVAL AS TID FROM DUAL");
        $dire_team_id = (int)$dire_team[0]['TID'];

        $winner_id = ($winner === 'Radiant') ? $rad_team_id : $dire_team_id;

        // ── Insert Match_Game ──────────────────────────────
        $sql = "INSERT INTO SYS.Match_Game (MATCH_TIME, TEAM1_ID, TEAM2_ID, WINNER_ID, IS_RANKED)
                VALUES (TO_TIMESTAMP('$match_dt','YYYY-MM-DD HH24:MI'),
                        $rad_team_id, $dire_team_id, $winner_id, $is_ranked)";
        $stmt = oci_parse($db_conn, $sql);
        oci_execute($stmt, OCI_NO_AUTO_COMMIT);

        $mid_row = db_query($db_conn, "SELECT SYS.SEQ_MATCH_ID.CURRVAL AS MID FROM DUAL");
        $new_match_id = (int)$mid_row[0]['MID'];

        oci_commit($db_conn);

        $success_msg = $new_match_id;

    } catch (Exception $e) {
        oci_rollback($db_conn);
        $error_msg = $e->getMessage();
    }
}

require_once 'header.php';

// ── Attr color map ─────────────────────────────────────────
$attr_class = [
    'Strength'     => 'str',
    'Agility'      => 'agi',
    'Intelligence' => 'int',
    'Universal'    => 'uni',
];
?>

<style>
/* ══════════════════════════════════════
   ADD MATCH PAGE EXTRAS
══════════════════════════════════════ */

/* SECTION DIVIDER */
.form-section {
  margin-bottom: 2rem;
}
.section-label {
  font-family: var(--font-mono);
  font-size: 0.72rem; letter-spacing: 0.15em;
  text-transform: uppercase; color: var(--red);
  margin-bottom: 0.5rem;
}

/* TEAM BLOCK */
.team-block {
  border: 1px solid var(--border);
  background: var(--bg1);
  overflow: hidden;
  margin-bottom: 1.5rem;
}
.team-block-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 0.8rem 1.2rem;
  font-family: var(--font-head); font-size: 1.1rem;
  font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase;
}
.team-block-header.radiant {
  background: linear-gradient(90deg, rgba(76,175,80,0.14), transparent);
  border-bottom: 2px solid rgba(76,175,80,0.3); color: #66bb6a;
}
.team-block-header.dire {
  background: linear-gradient(90deg, rgba(192,57,43,0.14), transparent);
  border-bottom: 2px solid rgba(192,57,43,0.3); color: var(--red-bright);
}
.team-block-header .pos-pills {
  display: flex; gap: 5px;
}
.pos-pill {
  width: 22px; height: 22px; border-radius: 2px;
  display: flex; align-items: center; justify-content: center;
  font-family: var(--font-mono); font-size: 0.7rem; font-weight: 700;
  background: var(--bg3); color: var(--text-muted); border: 1px solid var(--border);
}

/* PLAYER ROW */
.player-row {
  display: grid;
  grid-template-columns: 28px 1fr 1fr 70px 70px 70px 100px;
  gap: 6px;
  align-items: start;
  padding: 0.7rem 1rem;
  border-bottom: 1px solid var(--border);
  transition: background 0.12s;
}
.player-row:last-child { border-bottom: none; }
.player-row:hover { background: rgba(255,255,255,0.02); }

/* Items row below player */
.items-row {
  display: grid;
  grid-template-columns: 28px repeat(6, 1fr);
  gap: 6px;
  padding: 0 1rem 0.7rem;
  border-bottom: 1px solid var(--border);
  background: rgba(0,0,0,0.15);
}
.items-row:last-of-type { border-bottom: none; }
.items-label {
  font-family: var(--font-mono); font-size: 0.65rem;
  color: var(--text-muted); text-align: right;
  padding-top: 0.35rem; letter-spacing: 0.08em;
}

/* FORM FIELDS */
.field-label {
  display: block;
  font-family: var(--font-mono); font-size: 0.65rem;
  color: var(--text-muted); letter-spacing: 0.1em;
  text-transform: uppercase; margin-bottom: 3px;
}

select, input[type="number"], input[type="datetime-local"], input[type="text"] {
  width: 100%; background: var(--bg2);
  border: 1px solid var(--border);
  color: var(--text); font-family: var(--font-body);
  font-size: 0.85rem; padding: 0.45rem 0.6rem;
  outline: none; appearance: none;
  -webkit-appearance: none;
  transition: border-color 0.18s;
}
select:focus, input:focus { border-color: var(--red); }
select option { background: var(--bg2); }

/* hero select coloring */
select.hero-sel option[data-attr="str"] { color: #ef9a9a; }
select.hero-sel option[data-attr="agi"] { color: #a5d6a7; }
select.hero-sel option[data-attr="int"] { color: #90caf9; }
select.hero-sel option[data-attr="uni"] { color: #ffe082; }

/* pos number badge in row */
.pos-num {
  width: 28px; height: 28px;
  display: flex; align-items: center; justify-content: center;
  background: var(--bg3); border: 1px solid var(--border);
  font-family: var(--font-mono); font-size: 0.75rem;
  color: var(--text-muted); border-radius: 2px;
  flex-shrink: 0; margin-top: 20px;
}

/* GLOBAL MATCH INFO */
.match-meta-grid {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr auto auto;
  gap: 1rem; align-items: end;
  margin-bottom: 1.5rem;
}

.duration-group {
  display: flex; gap: 4px; align-items: end;
}
.duration-group > div { flex: 1; }

/* WINNER TOGGLE */
.winner-toggle {
  display: flex; gap: 0; overflow: hidden;
  border: 1px solid var(--border);
}
.winner-toggle input[type="radio"] { display: none; }
.winner-toggle label {
  flex: 1; text-align: center; padding: 0.45rem 0;
  font-family: var(--font-head); font-size: 0.9rem;
  font-weight: 700; letter-spacing: 0.06em;
  cursor: pointer; transition: all 0.18s;
  color: var(--text-muted); background: var(--bg2);
  border: none;
}
.winner-toggle label:first-of-type { border-right: 1px solid var(--border); }
#winner_radiant:checked ~ label[for="winner_radiant"] { background:rgba(76,175,80,0.18); color:#66bb6a; }
#winner_dire:checked    ~ label[for="winner_dire"]    { background:rgba(192,57,43,0.18); color:var(--red-bright); }

/* MODE TOGGLE */
.mode-toggle {
  display: flex; gap: 0; overflow: hidden;
  border: 1px solid var(--border);
}
.mode-toggle input[type="radio"] { display: none; }
.mode-toggle label {
  flex: 1; text-align: center; padding: 0.45rem 0;
  font-family: var(--font-head); font-size: 0.9rem; font-weight: 700;
  letter-spacing: 0.06em; cursor: pointer; transition: all 0.18s;
  color: var(--text-muted); background: var(--bg2);
}
.mode-toggle label:first-of-type { border-right: 1px solid var(--border); }
#mode_ranked:checked ~ label[for="mode_ranked"]  { background:rgba(192,57,43,0.15); color:var(--red-bright); }
#mode_normal:checked ~ label[for="mode_normal"]  { background:rgba(100,181,246,0.1); color:#64b5f6; }

/* SUBMIT */
.submit-bar {
  display: flex; align-items: center; gap: 1rem;
  padding: 1.5rem; background: var(--bg2);
  border: 1px solid var(--border);
  border-top: 3px solid var(--red);
  margin-top: 1rem;
}

/* Alert */
.alert-success {
  background: rgba(76,175,80,0.1); border-left: 4px solid #4caf50;
  padding: 1rem 1.4rem; font-family: var(--font-mono); font-size: 0.9rem;
  color: #a5d6a7; margin-bottom: 1.5rem;
}
.alert-error-box {
  background: rgba(192,57,43,0.1); border-left: 4px solid var(--red);
  padding: 1rem 1.4rem; font-family: var(--font-mono); font-size: 0.9rem;
  color: #ff8a80; margin-bottom: 1.5rem;
}

/* Column headers */
.col-headers {
  display: grid;
  grid-template-columns: 28px 1fr 1fr 70px 70px 70px 100px;
  gap: 6px; padding: 0.4rem 1rem;
  background: var(--bg3); border-bottom: 1px solid var(--border);
}
.col-headers span {
  font-family: var(--font-mono); font-size: 0.62rem;
  color: var(--text-muted); letter-spacing: 0.1em; text-transform: uppercase;
}

/* KDA preview */
.kda-preview {
  font-family: var(--font-mono); font-size: 0.75rem;
  color: var(--gold); text-align: center; margin-top: 2px;
}
</style>

<div class="page-wrap">

  <!-- BANNER -->
  <div class="page-banner" data-label="INSERT">
    <div class="banner-tag">// Nowy wpis</div>
    <h1 class="banner-title">Dodaj <span>Mecz</span></h1>
    <p class="banner-sub">Wypełnij skład obu drużyn, statystyki i parametry meczu.</p>
    <div class="banner-divider"></div>
  </div>

  <!-- STATUS MESSAGES -->
  <?php if ($db_error): ?>
    <div class="alert alert-error">⚠ Brak połączenia z bazą danych: <?= htmlspecialchars($db_error) ?></div>
  <?php endif; ?>

  <?php if ($success_msg): ?>
    <div class="alert-success">
      ✓ Mecz #<?= $success_msg ?> został zapisany pomyślnie!
      &nbsp; <a href="matchId.php?id=<?= $success_msg ?>" style="color:#66bb6a; text-decoration:underline;">Otwórz szczegóły →</a>
    </div>
  <?php endif; ?>

  <?php if ($error_msg): ?>
    <div class="alert-error-box">⚠ Błąd zapisu: <?= htmlspecialchars($error_msg) ?></div>
  <?php endif; ?>

  <form method="POST" id="matchForm">

    <!-- ── MATCH META ─────────────────────────────────── -->
    <div class="form-section">
      <div class="section-label">// Parametry meczu</div>

      <div class="match-meta-grid">

        <!-- Date/time -->
        <div>
          <label class="field-label">Data i godzina</label>
          <input type="datetime-local" name="match_date"
                 value="<?= date('Y-m-d\TH:i') ?>" required>
        </div>

        <!-- Duration -->
        <div>
          <label class="field-label">Czas trwania (hh:mm:ss)</label>
          <div class="duration-group">
            <div>
              <input type="number" name="dur_h" min="0" max="2" value="0" placeholder="hh"
                     style="text-align:center;" title="Godziny">
            </div>
            <div style="padding:0.45rem 2px; color:var(--text-muted); font-size:1.1rem; flex:0;">:</div>
            <div>
              <input type="number" name="dur_m" min="0" max="59" value="40" placeholder="mm"
                     style="text-align:center;" title="Minuty">
            </div>
            <div style="padding:0.45rem 2px; color:var(--text-muted); font-size:1.1rem; flex:0;">:</div>
            <div>
              <input type="number" name="dur_s" min="0" max="59" value="0" placeholder="ss"
                     style="text-align:center;" title="Sekundy">
            </div>
          </div>
        </div>

        <!-- Winner -->
        <div>
          <label class="field-label">Zwycięzca</label>
          <div class="winner-toggle">
            <input type="radio" id="winner_radiant" name="winner_side" value="Radiant" checked>
            <label for="winner_radiant">🟢 Radiant</label>
            <input type="radio" id="winner_dire" name="winner_side" value="Dire">
            <label for="winner_dire">🔴 Dire</label>
          </div>
        </div>

        <!-- Mode -->
        <div>
          <label class="field-label">Tryb gry</label>
          <div class="mode-toggle">
            <input type="radio" id="mode_ranked" name="game_mode" value="Ranked" checked>
            <label for="mode_ranked">🎖 Ranked</label>
            <input type="radio" id="mode_normal" name="game_mode" value="Normal">
            <label for="mode_normal">🎮 Normal</label>
          </div>
        </div>

      </div>
    </div>

    <!-- ── TEAMS ──────────────────────────────────────── -->
    <?php
    $teams = [
      ['key' => 'radiant', 'label' => '🌿 Świetliści (Radiant)', 'class' => 'radiant'],
      ['key' => 'dire',    'label' => '🔥 Mroczni (Dire)',        'class' => 'dire'],
    ];
    $pos_labels = [1=>'Carry',2=>'Mid',3=>'Offlane',4=>'Soft Sup',5=>'Hard Sup'];

    foreach ($teams as $team):
      $key = $team['key'];
    ?>
    <div class="form-section">
      <div class="section-label">// Skład drużyny — <?= $team['class'] === 'radiant' ? 'Świetliści' : 'Mroczni' ?></div>

      <div class="team-block">
        <div class="team-block-header <?= $team['class'] ?>">
          <span><?= $team['label'] ?></span>
          <div class="pos-pills">
            <?php for ($p=1;$p<=5;$p++): ?>
              <span class="pos-pill" title="<?= $pos_labels[$p] ?>"><?= $p ?></span>
            <?php endfor; ?>
          </div>
        </div>

        <!-- Column headers -->
        <div class="col-headers">
          <span>#</span>
          <span>Gracz</span>
          <span>Bohater</span>
          <span>Kills</span>
          <span>Deaths</span>
          <span>Assists</span>
          <span>Netto (gold)</span>
        </div>

        <!-- Player rows -->
        <?php for ($pos = 1; $pos <= 5; $pos++):
          $pfx = $key . '_' . $pos;
        ?>
        <div class="player-row">

          <!-- Position badge -->
          <div class="pos-num" title="<?= $pos_labels[$pos] ?>"><?= $pos ?></div>

          <!-- Player select -->
          <div>
            <label class="field-label"><?= $pos_labels[$pos] ?></label>
            <select name="<?= $pfx ?>_player" required>
              <option value="">— Wybierz gracza —</option>
              <?php foreach ($players as $pl): ?>
                <option value="<?= $pl['STEAM_ID'] ?>">
                  <?= htmlspecialchars($pl['NICKNAME']) ?> [<?= htmlspecialchars($pl['RANK']) ?>]
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Hero select -->
          <div>
            <label class="field-label">Bohater</label>
            <select name="<?= $pfx ?>_hero" class="hero-sel" required
                    onchange="updateHeroColor(this)">
              <option value="">— Wybierz bohatera —</option>
              <?php foreach ($heroes as $h):
                $attr_key = strtolower(substr($h['PRIMARY_ATTRIBUTE'],0,3));
              ?>
                <option value="<?= $h['ID'] ?>" data-attr="<?= $attr_key ?>">
                  <?= htmlspecialchars($h['NAME']) ?> (<?= $attr_key ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Kills -->
          <div>
            <label class="field-label">K</label>
            <input type="number" name="<?= $pfx ?>_kills" min="0" max="50" value="0"
                   id="<?= $pfx ?>_kills" onchange="updateKDA('<?= $pfx ?>')">
            <div class="kda-preview" id="<?= $pfx ?>_kda_preview">KDA —</div>
          </div>

          <!-- Deaths -->
          <div>
            <label class="field-label">D</label>
            <input type="number" name="<?= $pfx ?>_deaths" min="0" max="50" value="0"
                   id="<?= $pfx ?>_deaths" onchange="updateKDA('<?= $pfx ?>')">
          </div>

          <!-- Assists -->
          <div>
            <label class="field-label">A</label>
            <input type="number" name="<?= $pfx ?>_assists" min="0" max="50" value="0"
                   id="<?= $pfx ?>_assists" onchange="updateKDA('<?= $pfx ?>')">
          </div>

          <!-- Netto -->
          <div>
            <label class="field-label">Netto</label>
            <input type="number" name="<?= $pfx ?>_netto" min="0" max="100000"
                   value="12000" step="100">
          </div>
        </div>

        <!-- Items row -->
        <div class="items-row">
          <span class="items-label">items:</span>
          <?php for ($sl = 1; $sl <= 6; $sl++): ?>
          <div>
            <label class="field-label">Slot <?= $sl ?></label>
            <select name="<?= $pfx ?>_item<?= $sl ?>">
              <option value="0">— brak —</option>
              <?php foreach ($items as $it): ?>
                <option value="<?= $it['ID'] ?>"><?= htmlspecialchars($it['NAME']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endfor; ?>
        </div>

        <?php endfor; ?>
      </div>
    </div>
    <?php endforeach; ?>

    <!-- ── SUBMIT BAR ─────────────────────────────────── -->
    <div class="submit-bar">
      <button type="submit" class="btn btn-red" style="font-size:1rem; padding:0.7rem 2.5rem;">
        ⚔️ Zapisz Mecz
      </button>
      <button type="reset" class="btn btn-outline" onclick="return confirm('Wyczyścić formularz?')">
        ✕ Resetuj
      </button>
      <span style="font-family:var(--font-mono); font-size:0.75rem; color:var(--text-muted); margin-left:auto;">
        10 graczy · 60 przedmiotów · 1 mecz
      </span>
    </div>

  </form>

</div><!-- /.page-wrap -->

<script>
// ── KDA live preview ──────────────────────────────────────
function updateKDA(pfx) {
  const k = parseInt(document.getElementById(pfx+'_kills').value)   || 0;
  const d = parseInt(document.getElementById(pfx+'_deaths').value)  || 0;
  const a = parseInt(document.getElementById(pfx+'_assists').value) || 0;
  const kda = ((k + a) / Math.max(d, 1)).toFixed(2);
  const el = document.getElementById(pfx+'_kda_preview');
  if (el) el.textContent = 'KDA ' + kda;
}

// ── Hero select colour flash ──────────────────────────────
function updateHeroColor(sel) {
  const opt   = sel.options[sel.selectedIndex];
  const attr  = opt.dataset.attr || '';
  const colors = { str:'#ef9a9a', agi:'#a5d6a7', int:'#90caf9', uni:'#ffe082' };
  sel.style.borderColor = colors[attr] || 'var(--border)';
  sel.style.color       = colors[attr] || 'var(--text)';
}

// ── Duplicate player guard ────────────────────────────────
document.getElementById('matchForm').addEventListener('submit', function(e) {
  const selects = this.querySelectorAll('select[name$="_player"]');
  const vals = Array.from(selects).map(s => s.value).filter(v => v !== '');
  const unique = new Set(vals);
  if (unique.size < vals.length) {
    e.preventDefault();
    alert('⚠ Ten sam gracz pojawia się w formularzu więcej niż raz!\nKażdy gracz może wystąpić tylko w jednym slocie.');
    return;
  }
  if (vals.length < 10) {
    e.preventDefault();
    alert('⚠ Wybierz gracza i bohatera dla wszystkich 10 slotów!');
  }
});

// ── Auto-fill netto presets by position ──────────────────
document.querySelectorAll('select[name$="_player"]').forEach(sel => {
  const name = sel.name; // e.g. radiant_1_player
  const parts = name.split('_');
  const pos = parseInt(parts[1]);
  const presets = { 1:22000, 2:18000, 3:14000, 4:10000, 5:7500 };
  sel.addEventListener('change', function() {
    if (!this.value) return;
    const nettoName = parts[0]+'_'+parts[1]+'_netto';
    const nettoEl = document.querySelector('[name="'+nettoName+'"]');
    if (nettoEl && nettoEl.value == '12000') {
      nettoEl.value = presets[pos] || 12000;
    }
  });
});
</script>

<?php require_once 'footer.php'; ?>
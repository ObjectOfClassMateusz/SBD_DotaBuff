<?php
require_once 'db.php';
$page_title = 'Hero Statistics';

// ── Stałe dat (zakres obejmujący wszystkie mecze) ──────────
$DATE_START = "TO_DATE('2005-05-05','YYYY-MM-DD')";
$DATE_END   = "TO_DATE('2027-05-05','YYYY-MM-DD')";

// ── Filtry z GET ───────────────────────────────────────────
$active_tab  = $_GET['tab']   ?? 'winrate';       // winrate | pickrate | patch | trend | flag
$filter_rank = $_GET['rank']  ?? '';               // Herald..Immortal / ''
$filter_pos  = isset($_GET['pos']) && $_GET['pos'] !== '' ? (int)$_GET['pos'] : null;
$filter_attr = $_GET['attr']  ?? '';               // Strength/Agility/Intelligence/Universal / ''
$filter_patch= $_GET['patch'] ?? '';               // version_string np. 7.37c
$filter_flag = isset($_GET['flag']) ? (int)$_GET['flag'] : 1;  // 1=buff,2=nerf,0=neutral
$hero_search = trim($_GET['hero'] ?? '');

// ── Słowniki ───────────────────────────────────────────────
$ranks = ['','Herald','Guardian','Crusader','Archon','Legend','Ancient','Divine','Immortal'];
$positions = ['' => 'Wszystkie', '1'=>'Carry','2'=>'Mid','3'=>'Offlane','4'=>'Soft Sup','5'=>'Hard Sup'];
$attrs = [''=>'Wszystkie','Strength'=>'STR','Agility'=>'AGI','Intelligence'=>'INT','Universal'=>'UNI'];
$flags = [1=>'🟢 Buffy', 2=>'🔴 Nerfy', 0=>'⬜ Neutralne'];

// ── Dane ──────────────────────────────────────────────────
$data        = [];
$patches     = [];
$trend_data  = null;
$trend_hero  = '';
$hero_list   = [];

if ($db_conn) {
    // Zawsze pobierz listę patchy do selecta
    $patches = db_query($db_conn,
        "SELECT ID, VERSION_STRING FROM Patch_Info ORDER BY RELEASE_DATE DESC"
    );
    // Lista bohaterów do trend selecta
    $hero_list = db_query($db_conn,
        "SELECT ID, NAME FROM Hero ORDER BY NAME"
    );

    // Bind variables — NULL gdy pusty string
    $b_rank = $filter_rank ?: null;
    $b_pos  = $filter_pos;
    $b_attr = $filter_attr ?: null;

    // ── TAB: Win Rate ranking ─────────────────────────────
    if ($active_tab === 'winrate') {
        $data = db_query($db_conn,
            "SELECT ID, NAME, RATE AS WIN_RATE
             FROM TABLE(HeroStatistics.RankByWinRate(:rank, :pos, :attr, $DATE_START, $DATE_END))
             WHERE RATE > 0
             " . ($hero_search ? "AND UPPER(NAME) LIKE UPPER('%$hero_search%')" : "") . "
             ORDER BY RATE DESC",
            [':rank'=>$b_rank, ':pos'=>$b_pos, ':attr'=>$b_attr]
        );
    }

    // ── TAB: Pick Rate ranking ────────────────────────────
    elseif ($active_tab === 'pickrate') {
        $data = db_query($db_conn,
            "SELECT ID, NAME, RATE AS PICK_RATE
             FROM TABLE(HeroStatistics.RankByPickRate(:rank, :pos, :attr, $DATE_START, $DATE_END))
             WHERE RATE > 0
             " . ($hero_search ? "AND UPPER(NAME) LIKE UPPER('%$hero_search%')" : "") . "
             ORDER BY RATE DESC",
            [':rank'=>$b_rank, ':pos'=>$b_pos, ':attr'=>$b_attr]
        );
    }

    // ── TAB: Win Rate wszystkich bohaterów (podstawowy) ───
    elseif ($active_tab === 'winrate_all') {
        $data = db_query($db_conn,
            "SELECT h.ID, h.NAME,
                    ROUND(HeroStatistics.CalculateHeroWinRate(h.ID, $DATE_START, $DATE_END), 2) AS WIN_RATE,
                    ROUND(HeroStatistics.CalculateHeroPickRate(h.ID, $DATE_START, $DATE_END), 2) AS PICK_RATE
             FROM Hero h
             " . ($hero_search ? "WHERE UPPER(h.NAME) LIKE UPPER('%$hero_search%')" : "") . "
             ORDER BY WIN_RATE DESC"
        );
    }

    // ── TAB: Win Rate z filtrem Extended ─────────────────
    elseif ($active_tab === 'winrate_ext') {
        $data = db_query($db_conn,
            "SELECT h.ID, h.NAME,
                    ROUND(HeroStatistics.CalculateHeroWinRateExtended(h.ID, :rank, :pos, $DATE_START, $DATE_END), 2) AS WIN_RATE,
                    ROUND(HeroStatistics.CalculateHeroPickRateExtended(h.ID, :rank, :pos, $DATE_START, $DATE_END), 2) AS PICK_RATE
             FROM Hero h
             " . ($hero_search ? "WHERE UPPER(h.NAME) LIKE UPPER('%$hero_search%')" : "") . "
             ORDER BY WIN_RATE DESC",
            [':rank'=>$b_rank, ':pos'=>$b_pos]
        );
    }

    // ── TAB: Bohaterowie z flagą w patchu ─────────────────
    elseif ($active_tab === 'flag') {
        if ($filter_patch) {
            $patch_id_row = db_query($db_conn,
                "SELECT ID FROM Patch_Info WHERE VERSION_STRING = :ver",
                [':ver' => $filter_patch]
            );
            $pid = $patch_id_row[0]['ID'] ?? null;
            if ($pid) {
                $data = db_query($db_conn,
                    "SELECT ID, NAME, CHANGE_FLAG, CHANGE_DESCRIPTION
                     FROM TABLE(HeroStatistics.GetHerosWithFlag(:pid, :flag))",
                    [':pid' => $pid, ':flag' => $filter_flag]
                );
            }
        }
    }

    // ── TAB: Win Rate Delta w patchu ─────────────────────
    elseif ($active_tab === 'patch') {
        if ($filter_patch) {
            $data = db_query($db_conn,
                "SELECT
                    pc.TARGET_NAME AS NAME,
                    ROUND(HeroStatistics.CalculateWinRateInPatch(
                        pc.TARGET_ID,
                        (SELECT ID FROM Patch_Info WHERE VERSION_STRING = :ver)
                    ), 2) AS WIN_RATE_IN_PATCH,
                    ROUND(HeroStatistics.CalculateWinRateDelta(
                        pc.TARGET_ID,
                        (SELECT ID FROM Patch_Info WHERE VERSION_STRING = :ver)
                    ), 2) AS WIN_RATE_DELTA
                 FROM TABLE(GeneralInfo.GetPatchHeroChanges(
                        (SELECT ID FROM Patch_Info WHERE VERSION_STRING = :ver)
                 )) pc
                 ORDER BY WIN_RATE_DELTA DESC",
                [':ver' => $filter_patch]
            );
        }
    }

    // ── TAB: Win Rate Trend bohatera ──────────────────────
    elseif ($active_tab === 'trend') {
        $trend_hero_id = (int)($_GET['hero_id'] ?? 0);
        if ($trend_hero_id) {
            $hn = db_query($db_conn,"SELECT NAME FROM Hero WHERE ID=:id",[':id'=>$trend_hero_id]);
            $trend_hero = $hn[0]['NAME'] ?? '';

            $trend_row = db_query($db_conn,
                "SELECT t.WIN_RATE1, t.WIN_RATE2, t.WIN_RATE3, t.WIN_RATE4, t.WIN_RATE5
                 FROM (SELECT HeroStatistics.GetHeroWinRateTrend(:hid) AS T FROM DUAL) src,
                      TABLE(CAST(MULTISET(SELECT src.T FROM DUAL) AS SYS.ODCINUMBERLIST)) t",
                [':hid' => $trend_hero_id]
            );
            // GetHeroWinRateTrend zwraca obiekt — odpytaj bezpośrednio
            $raw = db_query($db_conn,
                "SELECT
                    ROUND(t.WIN_RATE1,2) W1, ROUND(t.WIN_RATE2,2) W2,
                    ROUND(t.WIN_RATE3,2) W3, ROUND(t.WIN_RATE4,2) W4,
                    ROUND(t.WIN_RATE5,2) W5
                 FROM (SELECT HeroStatistics.GetHeroWinRateTrend(:hid) t FROM DUAL) src
                 CROSS JOIN TABLE(CAST(MULTISET(SELECT src.t FROM DUAL) AS ODCITABTYP)) t",
                [':hid' => $trend_hero_id]
            );

            // Fallback — wywołaj jako SELECT na obiekcie
            if (empty($raw)) {
                $raw = db_query($db_conn,
                    "DECLARE
                         v HeroWinRateTrend;
                     BEGIN
                         v := HeroStatistics.GetHeroWinRateTrend(:hid);
                     END;",
                    [':hid' => $trend_hero_id]
                );
            }

            // Właściwe zapytanie przez kolumny obiektu
            $trend_raw = db_query($db_conn,
                "SELECT
                    (HeroStatistics.GetHeroWinRateTrend(:hid)).WIN_RATE1 AS W1,
                    (HeroStatistics.GetHeroWinRateTrend(:hid)).WIN_RATE2 AS W2,
                    (HeroStatistics.GetHeroWinRateTrend(:hid)).WIN_RATE3 AS W3,
                    (HeroStatistics.GetHeroWinRateTrend(:hid)).WIN_RATE4 AS W4,
                    (HeroStatistics.GetHeroWinRateTrend(:hid)).WIN_RATE5 AS W5
                 FROM DUAL",
                [':hid' => $trend_hero_id]
            );
            $trend_data = $trend_raw[0] ?? null;

            // Pobierz też ostatnie 5 patchy dla etykiet
            $patch_labels = db_query($db_conn,
                "SELECT VERSION_STRING FROM Patch_Info ORDER BY RELEASE_DATE DESC FETCH FIRST 5 ROWS ONLY"
            );
            $patch_labels = array_reverse(array_column($patch_labels, 'VERSION_STRING'));
        }
    }
}

require_once 'header.php';

// ── Helpers ────────────────────────────────────────────────
function rate_bar($pct, $color='var(--red)') {
    $w = min(100, max(0, (float)$pct));
    return "<div style='width:100%; height:6px; background:var(--bg3); border-radius:2px; overflow:hidden;'>
              <div style='width:{$w}%; height:100%; background:{$color}; transition:width .4s;'></div>
            </div>";
}
function delta_badge($delta) {
    $d = round((float)$delta, 2);
    if ($d > 0) return "<span style='color:#66bb6a; font-family:var(--font-mono); font-size:0.8rem;'>▲ +{$d}%</span>";
    if ($d < 0) return "<span style='color:var(--red-bright); font-family:var(--font-mono); font-size:0.8rem;'>▼ {$d}%</span>";
    return "<span style='color:var(--text-muted); font-family:var(--font-mono); font-size:0.8rem;'>– 0%</span>";
}
?>

<style>
/* ══ HERO STATS PAGE ═══════════════════════════════════════ */
.hs-layout {
  display: grid;
  grid-template-columns: 240px 1fr;
  gap: 1.5rem; align-items: start;
}
@media (max-width:820px) { .hs-layout { grid-template-columns:1fr; } }

/* Sidebar */
.hs-sidebar {
  background: var(--bg1); border: 1px solid var(--border);
  position: sticky; top: calc(var(--nav-h) + 1rem);
}
.hs-sidebar-title {
  padding: .7rem 1rem;
  font-family: var(--font-mono); font-size: .66rem;
  letter-spacing: .14em; text-transform: uppercase;
  color: var(--red); background: var(--bg3);
  border-bottom: 1px solid var(--border);
}
.tab-nav { list-style: none; }
.tab-nav li a {
  display: flex; align-items: center; gap: .6rem;
  padding: .65rem 1rem; text-decoration: none;
  font-family: var(--font-head); font-size: .88rem;
  font-weight: 600; letter-spacing: .04em; color: var(--text-dim);
  border-bottom: 1px solid var(--border); transition: all .15s;
}
.tab-nav li a:hover { background: var(--bg2); color: var(--text); }
.tab-nav li a.active { background: rgba(192,57,43,.12); color: var(--red-bright); border-left: 3px solid var(--red-bright); }
.tab-nav li a .tab-icon { font-size: 1rem; }
.tab-nav li a .tab-desc { font-family: var(--font-mono); font-size: .62rem; color: var(--text-muted); display: block; }

/* Filter bar */
.filter-strip {
  display: flex; align-items: end; gap: .6rem;
  flex-wrap: wrap; margin-bottom: 1.2rem;
  padding: 1rem; background: var(--bg2);
  border: 1px solid var(--border); border-left: 3px solid var(--red-dim);
}
.filter-strip label { font-family: var(--font-mono); font-size: .65rem; color: var(--text-muted); letter-spacing: .1em; text-transform: uppercase; display: block; margin-bottom: 3px; }
.filter-strip select, .filter-strip input[type="text"] {
  background: var(--bg3); border: 1px solid var(--border);
  color: var(--text); font-family: var(--font-body); font-size: .82rem;
  padding: .4rem .7rem; outline: none;
  appearance: none; -webkit-appearance: none;
  transition: border-color .18s;
}
.filter-strip select:focus, .filter-strip input:focus { border-color: var(--red); }

/* Data table */
.hs-table-wrap { background: var(--bg1); border: 1px solid var(--border); overflow-x: auto; }
.hs-table { width: 100%; border-collapse: collapse; }
.hs-table thead tr { background: var(--bg3); border-bottom: 2px solid var(--red-dim); }
.hs-table th {
  padding: .65rem 1rem; font-family: var(--font-mono);
  font-size: .66rem; letter-spacing: .1em; text-transform: uppercase;
  color: var(--text-muted); text-align: left; white-space: nowrap;
}
.hs-table tbody tr { border-bottom: 1px solid var(--border); transition: background .12s; }
.hs-table tbody tr:hover { background: var(--bg2); }
.hs-table td { padding: .6rem 1rem; font-size: .88rem; color: var(--text); vertical-align: middle; }
.hs-table .rank-num { font-family: var(--font-mono); color: var(--text-muted); font-size: .8rem; }
.hs-table .hero-name { font-family: var(--font-head); font-weight: 700; font-size: .95rem; color: #fff; }
.hs-table .rate-val { font-family: var(--font-mono); font-weight: 700; font-size: .95rem; color: var(--gold); }
.hs-table .rate-cell { min-width: 130px; }

/* Stat cards row */
.hs-stat-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px,1fr)); gap: .8rem; margin-bottom: 1.2rem; }
.hs-stat-card { background: var(--bg2); border: 1px solid var(--border); border-top: 2px solid var(--red-dim); padding: .9rem 1.1rem; }
.hs-stat-card .sv { font-family: var(--font-head); font-size: 1.8rem; font-weight: 700; color: #fff; line-height: 1; }
.hs-stat-card .sv.gold { color: var(--gold); }
.hs-stat-card .sv.grn  { color: #66bb6a; }
.hs-stat-card .sv.red  { color: var(--red-bright); }
.hs-stat-card .sl { font-family: var(--font-mono); font-size: .65rem; color: var(--text-muted); margin-top: 3px; letter-spacing: .08em; }

/* Trend chart */
.trend-wrap { background: var(--bg1); border: 1px solid var(--border); padding: 1.5rem; }
.trend-title { font-family: var(--font-head); font-size: 1rem; font-weight: 700; color: #fff; letter-spacing: .06em; text-transform: uppercase; margin-bottom: 1.2rem; }
.trend-chart { display: flex; align-items: flex-end; gap: 1.2rem; height: 160px; padding-bottom: .5rem; border-bottom: 1px solid var(--border); }
.trend-bar-wrap { flex: 1; display: flex; flex-direction: column; align-items: center; gap: .4rem; height: 100%; justify-content: flex-end; }
.trend-bar { width: 100%; background: var(--red-dim); border-top: 2px solid var(--red-bright); transition: height .5s; min-height: 4px; position: relative; }
.trend-bar:hover::after { content: attr(data-val) '%'; position: absolute; top: -22px; left: 50%; transform: translateX(-50%); font-family: var(--font-mono); font-size: .7rem; color: var(--gold); white-space: nowrap; }
.trend-bar-val { font-family: var(--font-mono); font-size: .75rem; font-weight: 700; color: var(--gold); }
.trend-bar-label { font-family: var(--font-mono); font-size: .68rem; color: var(--text-muted); margin-top: .4rem; }
.trend-empty { text-align: center; padding: 2rem; font-family: var(--font-mono); color: var(--text-muted); }

/* Flag cards */
.flag-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px,1fr)); gap: .7rem; }
.flag-card { background: var(--bg1); border: 1px solid var(--border); padding: .9rem 1.1rem; }
.flag-card.buff { border-left: 3px solid #4caf50; }
.flag-card.nerf { border-left: 3px solid var(--red-bright); }
.flag-card.neutral { border-left: 3px solid var(--text-muted); }
.flag-card .fc-name { font-family: var(--font-head); font-size: 1rem; font-weight: 700; color: #fff; margin-bottom: .3rem; }
.flag-card .fc-desc { font-size: .8rem; color: var(--text-dim); line-height: 1.5; }
.flag-badge { font-family: var(--font-mono); font-size: .62rem; letter-spacing: .1em; padding: 1px 8px; border: 1px solid; display: inline-block; margin-bottom: .4rem; }
.flag-badge.buff { color: #66bb6a; border-color: rgba(76,175,80,.5); }
.flag-badge.nerf { color: var(--red-bright); border-color: rgba(192,57,43,.5); }
.flag-badge.neutral { color: var(--text-muted); border-color: var(--border); }

/* Empty */
.hs-empty { text-align: center; padding: 3rem; font-family: var(--font-mono); color: var(--text-muted); }
.hs-empty .big { font-size: 2.5rem; opacity: .3; margin-bottom: .5rem; }

/* Section header */
.hs-section-head {
  font-family: var(--font-mono); font-size: .68rem;
  letter-spacing: .14em; text-transform: uppercase;
  color: var(--red); margin-bottom: .8rem;
  padding-bottom: .4rem; border-bottom: 1px solid var(--border);
}
</style>

<div class="page-wrap">

  <!-- BANNER -->
  <div class="page-banner" data-label="HEROES">
    <div class="banner-tag">// Statystyki bohaterów</div>
    <h1 class="banner-title">Hero <span>Statistics</span></h1>
    <p class="banner-sub">Win rate, pick rate, trendy, zmiany patchowe — z pakietu HeroStatistics.</p>
    <div class="banner-divider"></div>
  </div>

  <?php if ($db_error): ?>
    <div class="alert alert-error">⚠ <?= htmlspecialchars($db_error) ?></div>
  <?php endif; ?>

  <div class="hs-layout">

    <!-- ══ SIDEBAR ══════════════════════════════════════ -->
    <aside class="hs-sidebar">
      <div class="hs-sidebar-title">// Widoki</div>
      <ul class="tab-nav">
        <?php
        $tabs = [
          'winrate'     => ['🏆','Win Rate Ranking',   'RankByWinRate()'],
          'pickrate'    => ['📊','Pick Rate Ranking',   'RankByPickRate()'],
          'winrate_all' => ['⚔️', 'Win/Pick Rate — wszyscy','CalculateHeroWinRate()'],
          'winrate_ext' => ['🎯','Extended Ranking',    'Extended() z filtrem'],
          'patch'       => ['🔄','Delta Patcha',        'CalculateWinRateDelta()'],
          'flag'        => ['🚩','Flagowanie patcha',   'GetHerosWithFlag()'],
          'trend'       => ['📈','Trend Win Rate',      'GetHeroWinRateTrend()'],
        ];
        foreach ($tabs as $key => [$ico, $label, $fn]): ?>
          <li>
            <a href="?tab=<?= $key ?><?= $filter_patch ? '&patch='.urlencode($filter_patch) : '' ?>"
               class="<?= $active_tab === $key ? 'active' : '' ?>">
              <span class="tab-icon"><?= $ico ?></span>
              <span>
                <?= $label ?>
                <span class="tab-desc"><?= $fn ?></span>
              </span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </aside>

    <!-- ══ MAIN ═════════════════════════════════════════ -->
    <main>

      <?php
      // ─────────────────────────────────────────────────
      // TAB: Win Rate Ranking (RankByWinRate)
      // ─────────────────────────────────────────────────
      if ($active_tab === 'winrate' || $active_tab === 'pickrate'):
        $is_wr = $active_tab === 'winrate';
        $col_label = $is_wr ? 'Win Rate %' : 'Pick Rate %';
        $col_key   = $is_wr ? 'WIN_RATE' : 'PICK_RATE';
      ?>

      <div class="hs-section-head"><?= $is_wr ? '// RankByWinRate()' : '// RankByPickRate()' ?></div>

      <!-- Filter strip -->
      <form method="GET" class="filter-strip">
        <input type="hidden" name="tab" value="<?= $active_tab ?>">
        <div>
          <label>Ranga</label>
          <select name="rank">
            <?php foreach ($ranks as $r): ?>
              <option value="<?= $r ?>" <?= $filter_rank === $r ? 'selected' : '' ?>><?= $r ?: 'Wszystkie' ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Pozycja</label>
          <select name="pos">
            <?php foreach ($positions as $v => $l): ?>
              <option value="<?= $v ?>" <?= (string)$filter_pos === (string)$v ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Atrybut</label>
          <select name="attr">
            <?php foreach ($attrs as $v => $l): ?>
              <option value="<?= $v ?>" <?= $filter_attr === $v ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Szukaj bohatera</label>
          <input type="text" name="hero" value="<?= htmlspecialchars($hero_search) ?>" placeholder="np. Invoker" style="min-width:140px;">
        </div>
        <div style="align-self:end;">
          <button type="submit" class="btn btn-red" style="padding:.4rem 1.2rem;">Filtruj</button>
        </div>
        <?php if ($filter_rank || $filter_pos || $filter_attr || $hero_search): ?>
          <div style="align-self:end;">
            <a href="?tab=<?= $active_tab ?>" class="btn btn-outline" style="padding:.4rem 1rem;">✕</a>
          </div>
        <?php endif; ?>
      </form>

      <!-- Stat cards -->
      <?php if (!empty($data)):
        $top = $data[0]; $avg = round(array_sum(array_column($data, $col_key)) / count($data), 2);
      ?>
      <div class="hs-stat-row">
        <div class="hs-stat-card">
          <div class="sv gold"><?= count($data) ?></div>
          <div class="sl">Bohaterów</div>
        </div>
        <div class="hs-stat-card">
          <div class="sv grn"><?= round($top[$col_key] ?? 0, 1) ?>%</div>
          <div class="sl">#1 <?= htmlspecialchars($top['NAME']) ?></div>
        </div>
        <div class="hs-stat-card">
          <div class="sv"><?= $avg ?>%</div>
          <div class="sl">Średnia</div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Table -->
      <div class="hs-table-wrap">
        <table class="hs-table">
          <thead><tr>
            <th>#</th>
            <th>Bohater</th>
            <th><?= $col_label ?></th>
            <th style="min-width:160px;">Wykres</th>
          </tr></thead>
          <tbody>
            <?php if (empty($data)): ?>
              <tr><td colspan="4">
                <div class="hs-empty"><div class="big">⚔️</div><p>Brak danych dla wybranych filtrów</p></div>
              </td></tr>
            <?php else: foreach ($data as $i => $row): ?>
              <tr>
                <td class="rank-num"><?= $i+1 ?></td>
                <td class="hero-name"><?= htmlspecialchars($row['NAME']) ?></td>
                <td class="rate-val"><?= round($row[$col_key] ?? 0, 2) ?>%</td>
                <td class="rate-cell"><?= rate_bar($row[$col_key] ?? 0, $is_wr ? '#66bb6a' : 'var(--gold)') ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <?php
      // ─────────────────────────────────────────────────
      // TAB: Win Rate All + Pick Rate (Calculate*)
      // ─────────────────────────────────────────────────
      elseif ($active_tab === 'winrate_all' || $active_tab === 'winrate_ext'):
        $is_ext = $active_tab === 'winrate_ext';
      ?>

      <div class="hs-section-head">
        // <?= $is_ext ? 'CalculateHeroWinRateExtended() + CalculateHeroPickRateExtended()' : 'CalculateHeroWinRate() + CalculateHeroPickRate()' ?>
      </div>

      <form method="GET" class="filter-strip">
        <input type="hidden" name="tab" value="<?= $active_tab ?>">
        <?php if ($is_ext): ?>
        <div>
          <label>Ranga</label>
          <select name="rank">
            <?php foreach ($ranks as $r): ?>
              <option value="<?= $r ?>" <?= $filter_rank === $r ? 'selected' : '' ?>><?= $r ?: 'Wszystkie' ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Pozycja</label>
          <select name="pos">
            <?php foreach ($positions as $v => $l): ?>
              <option value="<?= $v ?>" <?= (string)$filter_pos === (string)$v ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>
        <div>
          <label>Szukaj</label>
          <input type="text" name="hero" value="<?= htmlspecialchars($hero_search) ?>" placeholder="Bohater..." style="min-width:140px;">
        </div>
        <div style="align-self:end;">
          <button type="submit" class="btn btn-red" style="padding:.4rem 1.2rem;">Filtruj</button>
        </div>
        <?php if ($filter_rank || $filter_pos || $hero_search): ?>
          <div style="align-self:end;"><a href="?tab=<?= $active_tab ?>" class="btn btn-outline" style="padding:.4rem 1rem;">✕</a></div>
        <?php endif; ?>
      </form>

      <div class="hs-table-wrap">
        <table class="hs-table">
          <thead><tr>
            <th>#</th><th>Bohater</th>
            <th>Win Rate %</th><th style="min-width:120px;"></th>
            <th>Pick Rate %</th><th style="min-width:120px;"></th>
          </tr></thead>
          <tbody>
            <?php if (empty($data)): ?>
              <tr><td colspan="6"><div class="hs-empty"><div class="big">⚔️</div><p>Brak danych</p></div></td></tr>
            <?php else: foreach ($data as $i => $row): ?>
              <tr>
                <td class="rank-num"><?= $i+1 ?></td>
                <td class="hero-name"><?= htmlspecialchars($row['NAME']) ?></td>
                <td class="rate-val"><?= $row['WIN_RATE'] ?? 0 ?>%</td>
                <td><?= rate_bar($row['WIN_RATE'] ?? 0, '#66bb6a') ?></td>
                <td class="rate-val" style="color:var(--gold);"><?= $row['PICK_RATE'] ?? 0 ?>%</td>
                <td><?= rate_bar($row['PICK_RATE'] ?? 0, 'var(--gold)') ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <?php
      // ─────────────────────────────────────────────────
      // TAB: Delta patcha (CalculateWinRateDelta)
      // ─────────────────────────────────────────────────
      elseif ($active_tab === 'patch'):
      ?>

      <div class="hs-section-head">// CalculateWinRateInPatch() + CalculateWinRateDelta()</div>

      <form method="GET" class="filter-strip">
        <input type="hidden" name="tab" value="patch">
        <div>
          <label>Wybierz patch</label>
          <select name="patch">
            <option value="">— Wybierz patch —</option>
            <?php foreach ($patches as $p): ?>
              <option value="<?= htmlspecialchars($p['VERSION_STRING']) ?>"
                      <?= $filter_patch === $p['VERSION_STRING'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['VERSION_STRING']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div style="align-self:end;">
          <button type="submit" class="btn btn-red" style="padding:.4rem 1.2rem;">Pokaż</button>
        </div>
      </form>

      <?php if (!$filter_patch): ?>
        <div class="hs-empty"><div class="big">🔄</div><p>Wybierz patch z listy powyżej</p></div>
      <?php elseif (empty($data)): ?>
        <div class="hs-empty"><div class="big">📋</div><p>Brak zmian bohaterów w tym patchu</p></div>
      <?php else: ?>
        <div class="hs-table-wrap">
          <table class="hs-table">
            <thead><tr>
              <th>#</th><th>Bohater</th>
              <th>Win Rate w patchu</th>
              <th>Delta (vs poprzedni patch)</th>
              <th></th>
            </tr></thead>
            <tbody>
              <?php
                usort($data, fn($a,$b) => $b['WIN_RATE_DELTA'] <=> $a['WIN_RATE_DELTA']);
                foreach ($data as $i => $row): ?>
                <tr>
                  <td class="rank-num"><?= $i+1 ?></td>
                  <td class="hero-name"><?= htmlspecialchars($row['NAME']) ?></td>
                  <td class="rate-val"><?= $row['WIN_RATE_IN_PATCH'] ?>%</td>
                  <td><?= delta_badge($row['WIN_RATE_DELTA']) ?></td>
                  <td style="min-width:100px;">
                    <?php $d = (float)$row['WIN_RATE_DELTA'];
                    $col = $d > 0 ? '#66bb6a' : ($d < 0 ? 'var(--red-bright)' : 'var(--text-muted)');
                    echo rate_bar(abs($d) * 3, $col); ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <?php
      // ─────────────────────────────────────────────────
      // TAB: GetHerosWithFlag
      // ─────────────────────────────────────────────────
      elseif ($active_tab === 'flag'):
      ?>

      <div class="hs-section-head">// GetHerosWithFlag(patchId, changeFlag)</div>

      <form method="GET" class="filter-strip">
        <input type="hidden" name="tab" value="flag">
        <div>
          <label>Patch</label>
          <select name="patch">
            <option value="">— Wybierz —</option>
            <?php foreach ($patches as $p): ?>
              <option value="<?= htmlspecialchars($p['VERSION_STRING']) ?>"
                      <?= $filter_patch === $p['VERSION_STRING'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['VERSION_STRING']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Flaga zmiany</label>
          <select name="flag">
            <?php foreach ($flags as $v => $l): ?>
              <option value="<?= $v ?>" <?= $filter_flag === $v ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div style="align-self:end;">
          <button type="submit" class="btn btn-red" style="padding:.4rem 1.2rem;">Pokaż</button>
        </div>
      </form>

      <?php
        $flag_cls = $filter_flag === 1 ? 'buff' : ($filter_flag === 2 ? 'nerf' : 'neutral');
        $flag_lbl = $flags[$filter_flag] ?? '';
      ?>

      <?php if (!$filter_patch): ?>
        <div class="hs-empty"><div class="big">🚩</div><p>Wybierz patch i flagę</p></div>
      <?php elseif (empty($data)): ?>
        <div class="hs-empty"><div class="big">🚩</div><p>Brak bohaterów z flagą <?= $flag_lbl ?> w tym patchu</p></div>
      <?php else: ?>
        <div class="hs-stat-row" style="grid-template-columns:repeat(2,1fr); max-width:300px; margin-bottom:1rem;">
          <div class="hs-stat-card">
            <div class="sv <?= $flag_cls === 'buff' ? 'grn' : ($flag_cls === 'nerf' ? 'red' : '') ?>"><?= count($data) ?></div>
            <div class="sl"><?= $flag_lbl ?> bohaterów</div>
          </div>
          <div class="hs-stat-card">
            <div class="sv"><?= htmlspecialchars($filter_patch) ?></div>
            <div class="sl">Patch</div>
          </div>
        </div>
        <div class="flag-grid">
          <?php foreach ($data as $row): ?>
            <div class="flag-card <?= $flag_cls ?>">
              <div class="fc-name"><?= htmlspecialchars($row['NAME']) ?></div>
              <span class="flag-badge <?= $flag_cls ?>"><?= $flag_lbl ?></span>
              <div class="fc-desc"><?= htmlspecialchars($row['CHANGE_DESCRIPTION'] ?? 'Brak opisu') ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php
      // ─────────────────────────────────────────────────
      // TAB: Win Rate Trend (GetHeroWinRateTrend)
      // ─────────────────────────────────────────────────
      elseif ($active_tab === 'trend'):
        $trend_hero_id = (int)($_GET['hero_id'] ?? 0);
      ?>

      <div class="hs-section-head">// GetHeroWinRateTrend(heroId) — 5 ostatnich patchy</div>

      <form method="GET" class="filter-strip">
        <input type="hidden" name="tab" value="trend">
        <div>
          <label>Wybierz bohatera</label>
          <select name="hero_id" style="min-width:200px;">
            <option value="">— Wybierz bohatera —</option>
            <?php foreach ($hero_list as $h): ?>
              <option value="<?= $h['ID'] ?>" <?= $trend_hero_id === (int)$h['ID'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($h['NAME']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div style="align-self:end;">
          <button type="submit" class="btn btn-red" style="padding:.4rem 1.2rem;">Pokaż trend</button>
        </div>
      </form>

      <?php if (!$trend_hero_id): ?>
        <div class="hs-empty"><div class="big">📈</div><p>Wybierz bohatera żeby zobaczyć trend win rate na przestrzeni 5 ostatnich patchy</p></div>
      <?php elseif (!$trend_data): ?>
        <div class="hs-empty"><div class="big">📈</div><p>Brak danych dla wybranego bohatera</p></div>
      <?php else:
        $wrs  = [$trend_data['W1']??0, $trend_data['W2']??0, $trend_data['W3']??0, $trend_data['W4']??0, $trend_data['W5']??0];
        $max_wr = max($wrs) ?: 100;
        $patch_labels = $patch_labels ?? ['P-4','P-3','P-2','P-1','Aktualny'];
        $avg_wr = round(array_sum($wrs) / 5, 2);
        $delta_total = round(($wrs[4] ?? 0) - ($wrs[0] ?? 0), 2);
      ?>
        <div class="hs-stat-row">
          <div class="hs-stat-card">
            <div class="sv gold"><?= $avg_wr ?>%</div>
            <div class="sl">Średni WR (5 patchy)</div>
          </div>
          <div class="hs-stat-card">
            <div class="sv <?= $delta_total >= 0 ? 'grn' : 'red' ?>"><?= $delta_total >= 0 ? '+' : '' ?><?= $delta_total ?>%</div>
            <div class="sl">Delta (stary→aktualny)</div>
          </div>
          <div class="hs-stat-card">
            <div class="sv"><?= round(max($wrs), 2) ?>%</div>
            <div class="sl">Szczytowy WR</div>
          </div>
        </div>

        <div class="trend-wrap">
          <div class="trend-title">📈 Win Rate trend — <?= htmlspecialchars($trend_hero) ?></div>
          <div class="trend-chart">
            <?php foreach ($wrs as $i => $wr):
              $h_pct = $max_wr > 0 ? round($wr / $max_wr * 100, 1) : 0;
              $col   = $wr >= 50 ? '#4caf50' : 'var(--red-dim)';
            ?>
            <div class="trend-bar-wrap">
              <div class="trend-bar-val"><?= round($wr, 1) ?>%</div>
              <div class="trend-bar" style="height:<?= $h_pct ?>%; background:<?= $col ?>; border-top-color:<?= $wr >= 50 ? '#66bb6a' : 'var(--red-bright)' ?>;" data-val="<?= round($wr,2) ?>"></div>
              <div class="trend-bar-label"><?= htmlspecialchars($patch_labels[$i] ?? "P$i") ?></div>
            </div>
            <?php endforeach; ?>
          </div>
          <div style="margin-top:.8rem; font-family:var(--font-mono); font-size:.72rem; color:var(--text-muted);">
            Zielony = WR ≥ 50% &nbsp;·&nbsp; Czerwony = WR &lt; 50%
          </div>
        </div>
      <?php endif; ?>

      <?php endif; // end tabs ?>

    </main>
  </div>
</div>

<?php require_once 'footer.php'; ?>
<?php
require_once __DIR__ . '/config.php';
$pdo = db();

$judul = get_setting($pdo, 'judul', 'Master Plan Desa');
$lokasi = get_setting($pdo, 'lokasi', '');
$periode = get_setting($pdo, 'periode', '');
$versi = get_setting($pdo, 'versi', '');
$slogan = get_setting($pdo, 'slogan', '');

$monitoring = $pdo->query('SELECT * FROM monitoring ORDER BY id ASC')->fetchAll();
$checklist = $pdo->query('SELECT * FROM checklist ORDER BY tahap,urutan,id')->fetchAll();
$quarters = $pdo->query('SELECT * FROM quarterly ORDER BY urutan,id')->fetchAll();
$pillars = $pdo->query('SELECT * FROM pillars ORDER BY urutan,id')->fetchAll();

$checkTotal = count($checklist);
$checkDone = 0;
foreach ($checklist as $r) { if ((int)$r['selesai'] === 1) $checkDone++; }
$checkPct = $checkTotal > 0 ? (int)round($checkDone * 100 / $checkTotal) : 0;

$groupedChecklist = [];
foreach ($checklist as $r) { $groupedChecklist[$r['tahap']][] = $r; }
$checklistBars = [];
foreach ($groupedChecklist as $tahap => $items) {
    $done = 0; foreach ($items as $i) { if ((int)$i['selesai'] === 1) $done++; }
    $tot = count($items);
    $checklistBars[] = ['label' => $tahap, 'done' => $done, 'total' => $tot, 'pct' => $tot ? (int)round($done*100/$tot) : 0];
}

$statusCounts = [];
foreach ($monitoring as $m) {
    $s = trim((string)($m['status'] ?? '')) ?: 'Belum dinilai';
    $statusCounts[$s] = ($statusCounts[$s] ?? 0) + 1;
}
$monitorTotal = count($monitoring);
$monitorDone = 0; $monitorWarn = 0; $monitorBad = 0; $monitorOk = 0; $monitorPending = 0;
foreach ($monitoring as $m) {
    $s = (string)($m['status'] ?? '');
    if (str_starts_with($s, 'Biru')) $monitorDone++;
    elseif (str_starts_with($s, 'Hijau')) $monitorOk++;
    elseif (str_starts_with($s, 'Kuning')) $monitorWarn++;
    elseif (str_starts_with($s, 'Merah')) $monitorBad++;
    else $monitorPending++;
}
$monitorProgress = $monitorTotal > 0 ? (int)round((($monitorDone + $monitorOk) * 100) / $monitorTotal) : 0;

function parse_pct($v): int {
    if (preg_match('/(\d{1,3})/', (string)$v, $m)) return max(0, min(100, (int)$m[1]));
    return 0;
}
$programBars = [];
foreach ($monitoring as $m) {
    $programBars[] = ['kode' => $m['kode'], 'label' => $m['program'], 'pct' => parse_pct($m['progres_fisik'] ?? '0'), 'status' => $m['status'] ?: 'Belum dinilai'];
}

$statusColors = ['Hijau · Sesuai' => '#176b45', 'Kuning · Waspada' => '#c18a2d', 'Merah · Bermasalah' => '#b94c42', 'Biru · Selesai' => '#286b9b', 'Abu-abu · Belum mulai' => '#8a9890', 'Belum dinilai' => '#8a9890'];
$statusOrder = ['Merah · Bermasalah', 'Kuning · Waspada', 'Hijau · Sesuai', 'Biru · Selesai', 'Abu-abu · Belum mulai', 'Belum dinilai'];
$donutData = [];
foreach ($statusOrder as $s) {
    if (isset($statusCounts[$s])) $donutData[] = ['label' => $s, 'value' => $statusCounts[$s], 'color' => $statusColors[$s] ?? '#8a9890'];
}
foreach ($statusCounts as $s => $c) {
    $exists = false; foreach ($donutData as $d) { if ($d['label'] === $s) $exists = true; }
    if (!$exists) $donutData[] = ['label' => $s, 'value' => $c, 'color' => $statusColors[$s] ?? '#8a9890'];
}

$infografis = [
    'checkPct' => $checkPct,
    'checkDone' => $checkDone,
    'checkTotal' => $checkTotal,
    'monitorTotal' => $monitorTotal,
    'monitorDone' => $monitorDone,
    'monitorOk' => $monitorOk,
    'monitorWarn' => $monitorWarn,
    'monitorBad' => $monitorBad,
    'monitorPending' => $monitorPending,
    'monitorProgress' => $monitorProgress,
    'pillarCount' => count($pillars),
    'quarterCount' => count($quarters),
    'statusDonut' => $donutData,
    'programBars' => $programBars,
    'checklistBars' => $checklistBars,
];
$json = json_encode($infografis, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Infografis Perkembangan Program - <?php echo h($judul); ?></title>
<style>
:root{--green:#176b45;--green2:#21845a;--mint:#eaf5ee;--ink:#18251f;--muted:#65736b;--line:#dce6df;--bg:#f4f7f4;--white:#fff;--gold:#c18a2d;--blue:#286b9b;--red:#b94c42}
*{box-sizing:border-box}body{margin:0;font:15px/1.6 system-ui,-apple-system,"Segoe UI",Arial,sans-serif;background:var(--bg);color:var(--ink)}
.wrap{max-width:1180px;margin:auto;padding:0 20px}
.header{background:linear-gradient(120deg,#103f2c,#176b45 60%,#2b8b61);color:#fff;padding:30px 0}
.eyebrow{text-transform:uppercase;letter-spacing:.12em;font-size:11px;font-weight:800;color:#c8e6d3}
h1{margin:8px 0 4px;font-size:clamp(24px,4vw,36px)}.sub{color:#e0eee5;margin:4px 0}.meta{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}.chip{border:1px solid #ffffff55;border-radius:99px;padding:4px 10px;font-size:12px}
main{padding:24px 0 50px}.section{margin-bottom:26px}
.grid{display:grid;grid-template-columns:repeat(12,1fr);gap:14px}
.card{background:var(--white);border:1px solid var(--line);border-radius:16px;padding:18px;box-shadow:0 8px 24px rgba(25,60,40,.06)}
.c3{grid-column:span 3}.c4{grid-column:span 4}.c6{grid-column:span 6}.c8{grid-column:span 8}.c12{grid-column:span 12}
h2{margin:0 0 12px;font-size:24px}h3{margin:0 0 10px;font-size:16px}.muted{color:var(--muted);font-size:13px}.label{font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;font-weight:700}
.topbar{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:14px;flex-wrap:wrap}
.btn{display:inline-block;background:#176b45;color:#fff;text-decoration:none;padding:8px 14px;border-radius:8px;font-weight:700;font-size:13px;border:0;cursor:pointer}.btn.sec{background:#e8f1eb;color:#176b45}
.stat-big{font-size:44px;font-weight:900;color:var(--green);line-height:1;letter-spacing:-.03em}
.progress{height:14px;background:#e6ece7;border-radius:99px;overflow:hidden;margin:8px 0}.bar{height:100%;width:0;background:linear-gradient(90deg,#176b45,#2b8b61);transition:width 1.2s ease}
.bar.blue{background:linear-gradient(90deg,#286b9b,#3d82b4)}.bar.gold{background:linear-gradient(90deg,#c18a2d,#d9a94e)}.bar.red{background:linear-gradient(90deg,#b94c42,#cf6a5f)}
.hbar{margin:12px 0}.hbar-head{display:flex;justify-content:space-between;gap:10px;font-size:13px;margin-bottom:5px}
.hbar-track{height:16px;background:#e6ece7;border-radius:99px;overflow:hidden}.hbar-fill{height:100%;width:0;border-radius:99px;background:linear-gradient(90deg,#176b45,#2b8b61);transition:width 1.2s ease}
.donut-wrap{display:flex;gap:18px;align-items:center;flex-wrap:wrap}
.donut{width:180px;height:180px;border-radius:50%;background:conic-gradient(#e6ece7 0 360deg);display:grid;place-items:center;position:relative;transition:--a 1s}
.donut::after{content:"";position:absolute;width:110px;height:110px;border-radius:50%;background:#fff}
.donut-center{position:relative;z-index:1;text-align:center}.donut-center b{font-size:26px}.donut-center span{display:block;font-size:11px;color:var(--muted)}
.legend{display:flex;flex-direction:column;gap:8px;flex:1;min-width:220px}.legend-item{display:flex;align-items:center;gap:8px;font-size:13px}.legend-item .dot{width:14px;height:14px;border-radius:4px;flex:0 0 14px}.legend-item b{margin-left:auto}
.kpi-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px}
.kpi{background:#f7faf7;border:1px solid var(--line);border-radius:12px;padding:12px;text-align:center}.kpi .num{font-size:28px;font-weight:850}.kpi .lbl{font-size:11px;color:var(--muted)}
.stack-bar{display:flex;height:30px;border-radius:15px;overflow:hidden;background:#e6ece7;margin:10px 0}.stack-seg{width:0;transition:width 1.2s ease;height:100%}
.checklist-item{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #eef3ef;font-size:13px}.checklist-item:last-child{border-bottom:0}
.badge{display:inline-block;padding:3px 8px;border-radius:99px;font-size:11px;font-weight:700}
@media(max-width:900px){.c3,.c4,.c6{grid-column:span 6}.c8{grid-column:span 12}}
@media(max-width:560px){.c3,.c4,.c6,.c8,.c12{grid-column:span 12}}
@media print{.no-print{display:none!important}body{background:#fff}.card{break-inside:avoid;box-shadow:none}.header{print-color-adjust:exact;-webkit-print-color-adjust:exact}}
</style>
</head>
<body>
<div class="header"><div class="wrap">
  <div class="eyebrow">Infografis Perkembangan Program</div>
  <h1><?php echo h($judul); ?></h1>
  <?php if ($slogan): ?><div class="sub"><b><?php echo h($slogan); ?></b></div><?php endif; ?>
  <div class="meta">
    <?php if ($lokasi): ?><span class="chip"><?php echo h($lokasi); ?></span><?php endif; ?>
    <?php if ($periode): ?><span class="chip">Periode: <?php echo h($periode); ?></span><?php endif; ?>
    <?php if ($versi): ?><span class="chip"><?php echo h($versi); ?></span><?php endif; ?>
  </div>
</div></div>

<main class="wrap">
  <div class="topbar no-print">
    <div class="muted">Visual interaktif berbasis CSS &amp; JavaScript — diperbarui otomatis dari data sistem.</div>
    <div style="display:flex;gap:8px">
      <a class="btn sec" href="index.php">← Publik</a>
      <a class="btn sec" href="laporan.php">Laporan</a>
      <button class="btn" onclick="window.print()">Cetak</button>
    </div>
  </div>

  <section class="section">
    <h2>Angka Kunci</h2>
    <div class="grid">
      <div class="card c3"><div class="label">Progres Checklist</div><div class="stat-big counter" data-target="<?php echo $checkPct; ?>">0</div><div class="muted">% selesai (<?php echo $checkDone; ?>/<?php echo $checkTotal; ?>)</div></div>
      <div class="card c3"><div class="label">Monitoring On-track</div><div class="stat-big counter" data-target="<?php echo $monitorProgress; ?>">0</div><div class="muted">% kegiatan Hijau/Biru</div></div>
      <div class="card c3"><div class="label">Total Kegiatan</div><div class="stat-big counter" data-target="<?php echo $monitorTotal; ?>">0</div><div class="muted">program terdaftar</div></div>
      <div class="card c3"><div class="label">Pilar Program</div><div class="stat-big counter" data-target="<?php echo count($pillars); ?>">0</div><div class="muted">bidang fokus</div></div>
    </div>
  </section>

  <section class="section">
    <h2>Ringkasan Visual</h2>
    <div class="grid">
      <div class="card c6">
        <h3>Komposisi Status Kegiatan (Donut)</h3>
        <div class="donut-wrap">
          <div class="donut" id="statusDonut">
            <div class="donut-center"><b id="donutTotal"><?php echo $monitorTotal; ?></b><span>kegiatan</span></div>
          </div>
          <div class="legend" id="statusLegend"></div>
        </div>
      </div>
      <div class="card c6">
        <h3>Checklist 90 Hari (Progress Bar)</h3>
        <div class="label">Keseluruhan</div>
        <div class="progress"><div class="bar" id="overallBar" data-pct="<?php echo $checkPct; ?>"></div></div>
        <div id="checklistBars"></div>
      </div>
      <div class="card c12">
        <h3>Progres Fisik per Kegiatan</h3>
        <div id="programBars"></div>
      </div>
      <div class="card c12">
        <h3>Stacked Bar — Kondisi Monitoring</h3>
        <div class="stack-bar" id="stackBar"></div>
        <div class="legend" id="stackLegend" style="flex-direction:row;flex-wrap:wrap;min-width:0"></div>
      </div>
      <div class="card c12">
        <h3>Detail KPI Status</h3>
        <div class="kpi-row">
          <div class="kpi"><div class="num" style="color:#286b9b" data-counter="<?php echo $monitorDone; ?>">0</div><div class="lbl">Selesai (Biru)</div></div>
          <div class="kpi"><div class="num" style="color:#176b45" data-counter="<?php echo $monitorOk; ?>">0</div><div class="lbl">Sesuai (Hijau)</div></div>
          <div class="kpi"><div class="num" style="color:#c18a2d" data-counter="<?php echo $monitorWarn; ?>">0</div><div class="lbl">Waspada (Kuning)</div></div>
          <div class="kpi"><div class="num" style="color:#b94c42" data-counter="<?php echo $monitorBad; ?>">0</div><div class="lbl">Bermasalah (Merah)</div></div>
          <div class="kpi"><div class="num" style="color:#65736b" data-counter="<?php echo $monitorPending; ?>">0</div><div class="lbl">Belum mulai/dinilai</div></div>
        </div>
      </div>
    </div>
  </section>

  <div class="muted" style="text-align:center">Infografis ini hanya membaca data yang diinput admin — tidak ada modifikasi dari halaman ini.</div>
</main>

<script>
const INFO = <?php echo $json; ?>;

// Animated counters
document.querySelectorAll('.counter,.kpi .num[data-counter]').forEach(el => {
  const target = Number(el.dataset.target ?? el.dataset.counter ?? 0);
  const isPct = el.classList.contains('counter');
  let cur = 0;
  const steps = 60;
  const step = Math.max(1, Math.ceil(target / steps));
  const t = setInterval(() => {
    cur = Math.min(target, cur + step);
    el.textContent = isPct ? String(cur) : String(cur);
    if (cur >= target) clearInterval(t);
  }, 20);
});

// Animate bars on view
const bars = [document.getElementById('overallBar'), ...document.querySelectorAll('.hbar-fill[data-pct]')].filter(Boolean);
const io = new IntersectionObserver(entries => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      const el = entry.target;
      el.style.width = (el.dataset.pct || 0) + '%';
      io.unobserve(el);
    }
  });
}, {threshold: 0.2});

// Overall bar
const overall = document.getElementById('overallBar');
if (overall) io.observe(overall);

// Program bars
const programContainer = document.getElementById('programBars');
(INFO.programBars || []).forEach(item => {
  const row = document.createElement('div');
  row.className = 'hbar';
  row.innerHTML = `<div class="hbar-head"><span><b>${escapeHtml(item.kode)}</b> — ${escapeHtml(item.label)}</span><span><b>${item.pct}%</b></span></div>
    <div class="hbar-track"><div class="hbar-fill" data-pct="${item.pct}"></div></div>`;
  programContainer.appendChild(row);
  io.observe(row.querySelector('.hbar-fill'));
});

// Checklist bars
const checklistContainer = document.getElementById('checklistBars');
(INFO.checklistBars || []).forEach(item => {
  const row = document.createElement('div');
  row.className = 'hbar';
  row.innerHTML = `<div class="hbar-head"><span>${escapeHtml(item.label)}</span><span><b>${item.pct}%</b> <span class="muted">(${item.done}/${item.total})</span></span></div>
    <div class="hbar-track"><div class="hbar-fill" data-pct="${item.pct}"></div></div>`;
  checklistContainer.appendChild(row);
  io.observe(row.querySelector('.hbar-fill'));
});

// Donut chart via conic-gradient
const donut = document.getElementById('statusDonut');
const legend = document.getElementById('statusLegend');
const data = INFO.statusDonut || [];
const total = data.reduce((a, b) => a + b.value, 0);
if (donut && data.length) {
  let acc = 0;
  const stops = data.map(d => {
    const start = (acc / total) * 360;
    acc += d.value;
    const end = (acc / total) * 360;
    return `${d.color} ${start.toFixed(1)}deg ${end.toFixed(1)}deg`;
  });
  donut.style.background = `conic-gradient(${stops.join(', ')})`;
  data.forEach(d => {
    const item = document.createElement('div');
    item.className = 'legend-item';
    const pct = Math.round((d.value / total) * 100);
    item.innerHTML = `<span class="dot" style="background:${d.color}"></span><span>${escapeHtml(d.label)}</span><b>${d.value} · ${pct}%</b>`;
    legend.appendChild(item);
  });
}

// Stacked bar
const stack = document.getElementById('stackBar');
const stackLegend = document.getElementById('stackLegend');
if (stack && data.length) {
  data.forEach(d => {
    const pct = (d.value / total) * 100;
    const seg = document.createElement('div');
    seg.className = 'stack-seg';
    seg.style.background = d.color;
    seg.title = `${d.label}: ${d.value}`;
    stack.appendChild(seg);
    requestAnimationFrame(() => { seg.style.width = pct + '%'; });
    const item = document.createElement('span');
    item.innerHTML = `<i class="dot" style="background:${d.color};display:inline-block;width:12px;height:12px;border-radius:3px;margin-right:5px"></i>${escapeHtml(d.label)}: <b>${d.value}</b>`;
    item.style.display = 'inline-flex'; item.style.alignItems = 'center'; item.style.fontSize = '12px';
    stackLegend.appendChild(item);
  });
}

function escapeHtml(str) {
  return String(str).replace(/[&<>'"]/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s]));
}
</script>
</body>
</html>
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

$checkTotal = count($checklist);
$checkDone = 0;
foreach ($checklist as $r) { if ((int)$r['selesai'] === 1) $checkDone++; }
$checkPct = $checkTotal > 0 ? (int)round($checkDone * 100 / $checkTotal) : 0;

$groupedChecklist = [];
foreach ($checklist as $r) { $groupedChecklist[$r['tahap']][] = $r; }

$statusCounts = [];
$byStatus = [];
foreach ($monitoring as $m) {
    $s = trim((string)($m['status'] ?? '')) ?: 'Belum dinilai';
    $statusCounts[$s] = ($statusCounts[$s] ?? 0) + 1;
    $byStatus[$s][] = $m;
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

function status_class(string $s): string {
    if (str_starts_with($s, 'Hijau')) return 'st-green';
    if (str_starts_with($s, 'Kuning')) return 'st-yellow';
    if (str_starts_with($s, 'Merah')) return 'st-red';
    if (str_starts_with($s, 'Biru')) return 'st-blue';
    return 'st-gray';
}
function nl_to_list(?string $text): array {
    $text = trim((string)$text);
    if ($text === '') return [];
    $parts = preg_split('/\r?\n|;/', $text);
    $out = [];
    foreach ($parts as $p) { $p = trim($p, " \t\n\r\0\x0B-•"); if ($p !== '') $out[] = $p; }
    return $out;
}
$generated = date('d-m-Y H:i');
$statusOrder = ['Merah · Bermasalah', 'Kuning · Waspada', 'Hijau · Sesuai', 'Biru · Selesai', 'Abu-abu · Belum mulai', 'Belum dinilai'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan Progress - <?php echo h($judul); ?></title>
<style>
:root{--green:#176b45;--green2:#21845a;--mint:#eaf5ee;--ink:#18251f;--muted:#65736b;--line:#dce6df;--bg:#f4f7f4;--white:#fff;--gold:#c18a2d;--blue:#286b9b;--red:#b94c42}
*{box-sizing:border-box}body{margin:0;font:15px/1.6 system-ui,-apple-system,"Segoe UI",Arial,sans-serif;background:var(--bg);color:var(--ink)}
.wrap{max-width:1100px;margin:auto;padding:0 20px}
.header{background:linear-gradient(120deg,#103f2c,#176b45 60%,#2b8b61);color:#fff;padding:28px 0}
.eyebrow{text-transform:uppercase;letter-spacing:.12em;font-size:11px;font-weight:800;color:#c8e6d3}
h1{margin:6px 0 4px;font-size:clamp(24px,4vw,36px);line-height:1.15}
.sub{color:#e0eee5;margin:4px 0}.meta{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
.chip{border:1px solid #ffffff55;border-radius:99px;padding:4px 10px;font-size:12px;color:#f0faf3}
main{padding:22px 0 40px}
.topbar{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:14px;flex-wrap:wrap}
.btn{display:inline-block;background:#176b45;color:#fff;text-decoration:none;padding:8px 12px;border-radius:8px;font-weight:700;font-size:13px;border:0;cursor:pointer}
.btn.sec{background:#e8f1eb;color:#176b45}
.grid{display:grid;grid-template-columns:repeat(12,1fr);gap:12px}
.card{background:var(--white);border:1px solid var(--line);border-radius:14px;padding:16px;margin-bottom:12px}
.c3{grid-column:span 3}.c4{grid-column:span 4}.c6{grid-column:span 6}.c8{grid-column:span 8}.c12{grid-column:span 12}
h2{margin:0 0 10px;font-size:22px}h3{margin:0 0 8px;font-size:16px}.muted{color:var(--muted);font-size:13px}.label{font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;font-weight:700}
.stat{font-size:32px;font-weight:850;color:var(--green);letter-spacing:-.03em;line-height:1.1}
.progress{height:14px;background:#e6ece7;border-radius:99px;overflow:hidden;margin:8px 0}.bar{height:100%;background:linear-gradient(90deg,#176b45,#2b8b61)}
.progress.sm{height:8px;margin:6px 0}
.badge{display:inline-block;padding:4px 9px;border-radius:99px;font-size:11px;font-weight:700;white-space:nowrap}
.st-green{background:#e7f5ec;color:#176b45}.st-yellow{background:#fbf1df;color:#8a601b}.st-red{background:#f9e9e7;color:#a33e35}.st-blue{background:#e8f1f8;color:#286b9b}.st-gray{background:#edf0ee;color:#58645d}
.kpi{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;margin-top:10px}
.kpi .item{background:#f7faf7;border:1px solid var(--line);border-radius:10px;padding:10px;text-align:center}
.kpi .num{font-size:24px;font-weight:800}.kpi .lbl{font-size:12px;color:var(--muted)}
.tablewrap{overflow:auto;border:1px solid var(--line);border-radius:12px;background:#fff}
table{width:100%;border-collapse:collapse;min-width:700px;font-size:13px}th,td{padding:10px;border-top:1px solid #e8eee9;text-align:left;vertical-align:top}
th{background:#edf4ef;color:#345b45;font-size:11px;text-transform:uppercase;letter-spacing:.05em;border-top:0}
ul.clean{margin:6px 0 0;padding-left:18px}.clean li{margin:4px 0;font-size:13px}
.check-item{display:flex;gap:8px;align-items:flex-start;padding:6px 0;border-bottom:1px solid #eef3ef;font-size:13px}
.check-item:last-child{border-bottom:0}.mark{font-weight:800;width:18px;flex:0 0 18px}.done{color:#176b45}.todo{color:#9aa7a0}
.highlight{border-left:4px solid #ccc;padding-left:12px;margin:10px 0}
.highlight.red{border-left-color:var(--red);background:#fdf5f4;padding:10px 12px;border-radius:0 10px 10px 0}
.highlight.yellow{border-left-color:var(--gold);background:#fdf8ef;padding:10px 12px;border-radius:0 10px 10px 0}
.highlight.green{border-left-color:var(--green);background:#f3f9f5;padding:10px 12px;border-radius:0 10px 10px 0}
.section{margin-bottom:22px}
.summary-box{background:#edf5ef;border:1px solid #d5e7da;border-radius:12px;padding:14px;color:#294b38}
@media(max-width:800px){.c3,.c4{grid-column:span 6}.c6,.c8{grid-column:span 12}}
@media(max-width:520px){.c3,.c4,.c6,.c8,.c12{grid-column:span 12}}
@media print{.no-print{display:none!important}body{background:#fff}.card{break-inside:avoid;box-shadow:none}.header{print-color-adjust:exact;-webkit-print-color-adjust:exact}}
</style>
</head>
<body>
<div class="header"><div class="wrap">
  <div class="eyebrow">Laporan Progress Publik</div>
  <h1><?php echo h($judul); ?></h1>
  <?php if ($slogan): ?><div class="sub"><b><?php echo h($slogan); ?></b></div><?php endif; ?>
  <div class="meta">
    <?php if ($lokasi): ?><span class="chip"><?php echo h($lokasi); ?></span><?php endif; ?>
    <?php if ($periode): ?><span class="chip">Periode: <?php echo h($periode); ?></span><?php endif; ?>
    <?php if ($versi): ?><span class="chip"><?php echo h($versi); ?></span><?php endif; ?>
    <span class="chip">Dibuat: <?php echo h($generated); ?></span>
  </div>
</div></div>

<main class="wrap">
  <div class="topbar no-print">
    <div class="muted">Ringkasan progres pelaksanaan rencana desa, disajikan agar mudah dibaca publik.</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <a class="btn sec" href="index.php">← Kembali ke Halaman Publik</a>
      <a class="btn sec" href="index.php#kritik-saran">Kritik & Saran</a>
      <button class="btn" onclick="window.print()">Cetak Laporan</button>
    </div>
  </div>

  <section class="section">
    <div class="summary-box">
      <h3 style="margin-top:0">Ringkasan Cepat</h3>
      <ul class="clean" style="margin:0">
        <li><b>Checklist 90 Hari:</b> <?php echo $checkDone; ?> dari <?php echo $checkTotal; ?> item selesai (<?php echo $checkPct; ?>%).</li>
        <li><b>Monitoring Kegiatan:</b> <?php echo $monitorTotal; ?> kegiatan tercatat — Selesai: <?php echo $monitorDone; ?>, Sesuai: <?php echo $monitorOk; ?>, Waspada: <?php echo $monitorWarn; ?>, Bermasalah: <?php echo $monitorBad; ?>, Belum dinilai/mulai: <?php echo $monitorPending; ?>.</li>
        <li><b>Perhatian utama:</b> <?php echo $monitorBad > 0 ? $monitorBad.' kegiatan berstatus bermasalah perlu tindak lanjut.' : ($monitorWarn > 0 ? $monitorWarn.' kegiatan berstatus waspada perlu dipantau.' : 'Tidak ada kegiatan berstatus bermasalah/waspada saat ini.'); ?></li>
      </ul>
    </div>
  </section>

  <section class="section">
    <h2>Statistik Utama</h2>
    <div class="grid">
      <div class="card c6">
        <div class="label">Progres Checklist 90 Hari</div>
        <div class="stat"><?php echo $checkPct; ?>%</div>
        <div class="progress"><div class="bar" style="width:<?php echo $checkPct; ?>%"></div></div>
        <div class="muted"><?php echo $checkDone; ?> selesai · <?php echo max(0,$checkTotal-$checkDone); ?> belum selesai · Total <?php echo $checkTotal; ?> item</div>
      </div>
      <div class="card c6">
        <div class="label">Kondisi Monitoring Kegiatan</div>
        <div class="stat"><?php echo $monitorTotal; ?> <span style="font-size:16px;font-weight:700;color:#65736b">kegiatan</span></div>
        <div class="progress"><div class="bar" style="width:<?php echo $monitorProgress; ?>%"></div></div>
        <div class="muted"><?php echo $monitorProgress; ?>% dalam kondisi sesuai/selesai (Hijau/Biru)</div>
      </div>
    </div>
    <div class="kpi">
      <div class="item"><div class="num" style="color:#286b9b"><?php echo $monitorDone; ?></div><div class="lbl">Selesai (Biru)</div></div>
      <div class="item"><div class="num" style="color:#176b45"><?php echo $monitorOk; ?></div><div class="lbl">Sesuai (Hijau)</div></div>
      <div class="item"><div class="num" style="color:#c18a2d"><?php echo $monitorWarn; ?></div><div class="lbl">Waspada (Kuning)</div></div>
      <div class="item"><div class="num" style="color:#b94c42"><?php echo $monitorBad; ?></div><div class="lbl">Bermasalah (Merah)</div></div>
      <div class="item"><div class="num" style="color:#65736b"><?php echo $monitorPending; ?></div><div class="lbl">Belum Mulai/Dinilai</div></div>
    </div>
  </section>

  <?php if ($statusCounts): ?>
  <section class="section">
    <h2>Distribusi Status</h2>
    <div class="card">
      <?php foreach ($statusCounts as $s => $c): $pct = $monitorTotal ? (int)round($c*100/$monitorTotal) : 0; ?>
        <div style="margin:10px 0">
          <div style="display:flex;justify-content:space-between;align-items:center;gap:8px"><span class="badge <?php echo status_class($s); ?>"><?php echo h($s); ?></span><span class="muted"><b><?php echo $c; ?></b> kegiatan (<?php echo $pct; ?>%)</span></div>
          <div class="progress sm"><div class="bar" style="width:<?php echo $pct; ?>%;background:<?php echo str_starts_with($s,'Hijau')?'#176b45':(str_starts_with($s,'Kuning')?'#c18a2d':(str_starts_with($s,'Merah')?'#b94c42':(str_starts_with($s,'Biru')?'#286b9b':'#8a9890'))); ?>"></div></div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if (!empty($byStatus['Merah · Bermasalah']) || !empty($byStatus['Kuning · Waspada'])): ?>
  <section class="section">
    <h2>Perlu Perhatian</h2>
    <?php if (!empty($byStatus['Merah · Bermasalah'])): ?>
      <div class="highlight red"><b>Bermasalah (<?php echo count($byStatus['Merah · Bermasalah']); ?>)</b>
        <ul class="clean"><?php foreach ($byStatus['Merah · Bermasalah'] as $m): ?><li><b><?php echo h($m['kode']); ?></b> — <?php echo h($m['program']); ?><?php if(!empty($m['tindak_lanjut'])): ?>. <i>Tindak lanjut:</i> <?php echo h($m['tindak_lanjut']); ?><?php endif; ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>
    <?php if (!empty($byStatus['Kuning · Waspada'])): ?>
      <div class="highlight yellow"><b>Waspada (<?php echo count($byStatus['Kuning · Waspada']); ?>)</b>
        <ul class="clean"><?php foreach ($byStatus['Kuning · Waspada'] as $m): ?><li><b><?php echo h($m['kode']); ?></b> — <?php echo h($m['program']); ?><?php if(!empty($m['tindak_lanjut'])): ?>. <i>Tindak lanjut:</i> <?php echo h($m['tindak_lanjut']); ?><?php endif; ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <section class="section">
    <h2>Daftar Progres Kegiatan (Monitoring)</h2>
    <?php if ($monitoring): ?>
      <div class="card" style="padding:0;overflow:hidden"><div class="tablewrap" style="border:0;border-radius:0">
        <table>
          <thead><tr><th>Kode</th><th>Program/Kegiatan</th><th>Lokasi</th><th>PIC</th><th>Target</th><th>Progres Fisik</th><th>Status</th><th>Tindak Lanjut</th></tr></thead>
          <tbody>
          <?php foreach ($monitoring as $m): ?>
            <tr>
              <td><b><?php echo h($m['kode']); ?></b></td>
              <td><?php echo h($m['program']); ?></td>
              <td><?php echo h($m['lokasi']); ?></td>
              <td><?php echo h($m['pic']); ?></td>
              <td><?php echo h($m['target']); ?></td>
              <td><?php echo h($m['progres_fisik']); ?></td>
              <td><span class="badge <?php echo status_class($m['status'] ?? ''); ?>"><?php echo h($m['status'] ?: 'Belum dinilai'); ?></span></td>
              <td><?php echo nl2br(h($m['tindak_lanjut'] ?? '')); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div></div>
    <?php else: ?><div class="card muted">Belum ada data monitoring.</div><?php endif; ?>
  </section>

  <section class="section">
    <h2>Progres Checklist 90 Hari</h2>
    <div class="card" style="margin-bottom:12px">
      <div style="display:flex;justify-content:space-between;align-items:center"><b>Keseluruhan</b><b style="color:var(--green)"><?php echo $checkPct; ?>% (<?php echo $checkDone; ?>/<?php echo $checkTotal; ?>)</b></div>
      <div class="progress"><div class="bar" style="width:<?php echo $checkPct; ?>%"></div></div>
    </div>
    <?php if ($groupedChecklist): ?>
      <div class="grid">
      <?php foreach ($groupedChecklist as $tahap => $items): $done = 0; foreach($items as $i){ if((int)$i['selesai']===1) $done++; } $tot = count($items); $pct = $tot? (int)round($done*100/$tot):0; ?>
        <div class="card c6">
          <h3 style="margin-bottom:4px"><?php echo h($tahap); ?></h3>
          <div class="muted" style="margin-bottom:6px"><?php echo $done; ?> dari <?php echo $tot; ?> selesai (<?php echo $pct; ?>%)</div>
          <div class="progress sm"><div class="bar" style="width:<?php echo $pct; ?>%"></div></div>
          <?php foreach ($items as $it): $ok = (int)$it['selesai'] === 1; ?>
            <div class="check-item"><span class="mark <?php echo $ok?'done':'todo'; ?>"><?php echo $ok ? '✓' : '○'; ?></span><span><?php echo h($it['item']); ?></span></div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
      </div>
    <?php else: ?><div class="card muted">Belum ada item checklist.</div><?php endif; ?>
  </section>

  <?php if ($quarters): ?>
  <section class="section">
    <h2>Target Kuartal (Rencana)</h2>
    <div class="grid">
      <?php foreach ($quarters as $q): $tg = nl_to_list($q['target'] ?? ''); ?>
        <div class="card c6">
          <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:6px"><h3 style="margin:0"><?php echo h($q['kuartal']); ?></h3><span class="badge st-blue"><?php echo h($q['fokus']); ?></span></div>
          <?php if ($tg): ?><ul class="clean"><?php foreach ($tg as $t): ?><li><?php echo h($t); ?></li><?php endforeach; ?></ul><?php endif; ?>
          <?php if (!empty($q['output'])): ?><div class="muted" style="margin-top:8px;border-top:1px solid #e8eee9;padding-top:6px"><b>Output:</b> <?php echo h($q['output']); ?></div><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <div class="muted" style="text-align:center;padding:10px 0">Sumber data: sistem pengelolaan perencanaan desa. Untuk rencana & detail lengkap lihat <a href="index.php">halaman publik utama</a>.</div>
</main>
</body>
</html>
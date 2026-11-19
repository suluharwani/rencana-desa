<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/layout.php';
require_login();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = post('action');
    if ($a === 'update_status') {
        $pdo->prepare('UPDATE feedback SET status=? WHERE id=?')->execute([post('status'), (int)post('id')]);
        flash_set('Status kritik/saran diperbarui.');
    } elseif ($a === 'delete') {
        $pdo->prepare('DELETE FROM feedback WHERE id=?')->execute([(int)post('id')]);
        flash_set('Kritik/saran dihapus.');
    } elseif ($a === 'delete_all') {
        $pdo->exec('DELETE FROM feedback');
        flash_set('Semua kritik/saran dihapus.');
    }
    header('Location: feedback.php');
    exit;
}

$rows = $pdo->query('SELECT * FROM feedback ORDER BY created_at DESC, id DESC')->fetchAll();
$statuses = ['Baru', 'Dibaca', 'Ditindaklanjuti', 'Selesai'];
$surveyCount = 0;
$categoryCounts = [];
$planCounts = [];
$statusCounts = array_fill_keys($statuses, 0);
$surveyMonths = [];
foreach ($rows as $row) {
    $status = in_array($row['status'], $statuses, true) ? $row['status'] : 'Baru';
    $statusCounts[$status]++;
    if (!str_starts_with((string)$row['jenis'], 'UMKM:')) continue;

    $surveyCount++;
    $category = trim(substr((string)$row['jenis'], strlen('UMKM:')));
    if ($category !== '') $categoryCounts[$category] = ($categoryCounts[$category] ?? 0) + 1;
    if (preg_match('/^Rencana yang didukung:\s*(.+)$/mu', (string)$row['pesan'], $match)) {
        $plan = trim($match[1]);
        if ($plan !== '') $planCounts[$plan] = ($planCounts[$plan] ?? 0) + 1;
    }
    $month = date('Y-m', strtotime((string)$row['created_at']));
    $surveyMonths[$month] = ($surveyMonths[$month] ?? 0) + 1;
}
arsort($categoryCounts);
arsort($planCounts);
$maxCategory = max($categoryCounts ?: [0]);
$maxPlan = max($planCounts ?: [0]);
$monthLabels = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("first day of -$i month"));
    $monthLabels[$month] = date('M Y', strtotime($month . '-01'));
}
$maxMonth = max(array_map(static fn($month) => $surveyMonths[$month] ?? 0, array_keys($monthLabels)) ?: [0]);
page_start('Masukan & Survei UMKM', 'feedback');
?>
<style>
.stats-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:14px}.stat-card{background:#fff;border:1px solid var(--line);border-radius:12px;padding:15px}.stat-label{font-size:12px;color:var(--muted);font-weight:700}.stat-value{font-size:28px;line-height:1.2;font-weight:800;color:#176b45;margin-top:4px}.charts-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.chart-card{background:#fff;border:1px solid var(--line);border-radius:12px;padding:16px;margin-bottom:14px}.chart-card h3{margin-bottom:3px}.bar-list{display:grid;gap:11px;margin-top:16px}.bar-row{display:grid;grid-template-columns:minmax(130px,1fr) minmax(80px,2fr) 32px;align-items:center;gap:9px}.bar-name{font-size:12px;line-height:1.35}.bar-track{height:11px;background:#edf2ee;border-radius:99px;overflow:hidden}.bar-fill{height:100%;background:#258155;border-radius:99px;min-width:0}.bar-count{text-align:right;font-size:12px;font-weight:800}.month-chart{height:160px;display:flex;align-items:flex-end;gap:9px;margin-top:16px;border-bottom:1px solid #dce6df}.month-col{flex:1;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;gap:5px;min-width:0}.month-value{font-size:11px;font-weight:700;color:#365543}.month-bar{width:min(34px,70%);height:var(--height);min-height:3px;background:#258155;border-radius:6px 6px 0 0}.month-label{font-size:10px;color:var(--muted);text-align:center;white-space:nowrap}.empty-chart{padding:20px 0;color:var(--muted);font-size:13px}.status-chips{display:flex;flex-wrap:wrap;gap:7px;margin-top:12px}.status-chip{padding:5px 9px;border-radius:99px;background:#edf4ef;font-size:11px;color:#345140;font-weight:700}
@media(max-width:900px){.stats-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.charts-grid{grid-template-columns:1fr}}
@media(max-width:500px){.bar-row{grid-template-columns:minmax(105px,1fr) minmax(50px,1fr) 24px;gap:6px}.chart-card{padding:13px}.month-chart{gap:4px}.month-label{font-size:9px}}
</style>
<div class="top">
  <h2>Masukan Publik &amp; Survei UMKM</h2>
  <?php if ($rows): ?>
  <form method="post"><button class="btn danger" name="action" value="delete_all" onclick="return confirm('Hapus semua kritik/saran?')">Hapus Semua</button></form>
  <?php endif; ?>
</div>
<div class="stats-grid" aria-label="Ringkasan tanggapan warga">
  <div class="stat-card"><div class="stat-label">TOTAL MASUKAN</div><div class="stat-value"><?php echo count($rows); ?></div></div>
  <div class="stat-card"><div class="stat-label">SURVEI UMKM</div><div class="stat-value"><?php echo $surveyCount; ?></div></div>
  <div class="stat-card"><div class="stat-label">MASIH BARU</div><div class="stat-value"><?php echo $statusCounts['Baru']; ?></div></div>
  <div class="stat-card"><div class="stat-label">SUDAH DITINDAKLANJUTI / SELESAI</div><div class="stat-value"><?php echo $statusCounts['Ditindaklanjuti'] + $statusCounts['Selesai']; ?></div></div>
</div>
<div class="charts-grid">
  <section class="chart-card" aria-label="Sebaran jenis usaha UMKM"><h3>Jenis usaha warga</h3><div class="muted">Jumlah jawaban berdasarkan kategori usaha</div>
    <?php if ($categoryCounts): ?><div class="bar-list"><?php foreach ($categoryCounts as $name => $count): ?><div class="bar-row"><div class="bar-name"><?php echo h($name); ?></div><div class="bar-track"><div class="bar-fill" style="width:<?php echo (int)round($count / $maxCategory * 100); ?>%"></div></div><div class="bar-count"><?php echo (int)$count; ?></div></div><?php endforeach; ?></div><?php else: ?><div class="empty-chart">Belum ada jawaban survei UMKM untuk diringkas.</div><?php endif; ?>
  </section>
  <section class="chart-card" aria-label="Rencana UMKM yang didukung"><h3>Rencana yang didukung</h3><div class="muted">Pilihan warga pada survei UMKM</div>
    <?php if ($planCounts): ?><div class="bar-list"><?php foreach ($planCounts as $name => $count): ?><div class="bar-row"><div class="bar-name"><?php echo h($name); ?></div><div class="bar-track"><div class="bar-fill" style="width:<?php echo (int)round($count / $maxPlan * 100); ?>%"></div></div><div class="bar-count"><?php echo (int)$count; ?></div></div><?php endforeach; ?></div><?php else: ?><div class="empty-chart">Belum ada pilihan rencana dari survei UMKM.</div><?php endif; ?>
  </section>
  <section class="chart-card" aria-label="Tren survei UMKM enam bulan terakhir"><h3>Tren survei UMKM</h3><div class="muted">Jawaban masuk per bulan · 6 bulan terakhir</div>
    <?php if ($surveyCount): ?><div class="month-chart"><?php foreach ($monthLabels as $month => $label): $count = $surveyMonths[$month] ?? 0; $height = $maxMonth > 0 && $count > 0 ? max(8, (int)round($count / $maxMonth * 100)) : 2; ?><div class="month-col" title="<?php echo h($label . ': ' . $count . ' jawaban'); ?>"><span class="month-value"><?php echo $count ?: ''; ?></span><div class="month-bar" style="--height:<?php echo $height; ?>%"></div><span class="month-label"><?php echo h($label); ?></span></div><?php endforeach; ?></div><?php else: ?><div class="empty-chart">Grafik akan terisi setelah warga mengirim survei.</div><?php endif; ?>
  </section>
  <section class="chart-card" aria-label="Status tindak lanjut masukan"><h3>Status tindak lanjut</h3><div class="muted">Perkembangan semua masukan warga</div><div class="status-chips"><?php foreach ($statuses as $status): ?><span class="status-chip"><?php echo h($status); ?>: <?php echo $statusCounts[$status]; ?></span><?php endforeach; ?></div></section>
</div>
<div class="card"><div class="muted">Masukan umum dan jawaban survei UMKM dari halaman publik. Jenis usaha tersimpan pada kolom Jenis; pilihan rencana dan usulan ada pada kolom Pesan. Total: <b><?php echo count($rows); ?></b></div></div>
<?php foreach ($rows as $r): ?>
<div class="card">
  <form method="post">
    <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
    <div class="grid">
      <div class="col-3"><label>Waktu</label><div><?php echo h($r['created_at']); ?></div></div>
      <div class="col-3"><label>Jenis</label><div><?php echo h($r['jenis']); ?></div></div>
      <div class="col-3"><label>Nama</label><div><?php echo h($r['nama'] ?: '-'); ?></div></div>
      <div class="col-3"><label>Kontak</label><div><?php echo h($r['kontak'] ?: '-'); ?></div></div>
      <div class="col-12"><label>Pesan</label><div style="white-space:pre-wrap;background:#f7faf7;border:1px solid #e3ebe5;border-radius:8px;padding:10px"><?php echo h($r['pesan']); ?></div></div>
      <div class="col-4"><label>Status</label>
        <select name="status"><?php foreach ($statuses as $s): ?><option <?php echo $r['status']===$s?'selected':''; ?>><?php echo h($s); ?></option><?php endforeach; ?></select>
      </div>
      <div class="col-8 row-actions" style="align-items:flex-end">
        <button class="btn" name="action" value="update_status">Simpan Status</button>
        <button class="btn danger" name="action" value="delete" onclick="return confirm('Hapus masukan ini?')">Hapus</button>
      </div>
    </div>
  </form>
</div>
<?php endforeach; if (!$rows): ?><div class="card muted">Belum ada kritik atau saran.</div><?php endif; ?>
<?php page_end(); ?>

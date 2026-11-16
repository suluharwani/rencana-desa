<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/layout.php';
require_login();
$pdo = db();

$counts = [];
foreach ([
    'baseline' => 'Baseline', 'baseline_tasks' => 'Tugas Baseline', 'pillars' => 'Pilar',
    'roadmap' => 'Roadmap', 'year1_months' => 'Bulan Tahun 1', 'monitoring' => 'Monitoring',
    'checklist' => 'Checklist'
] as $t => $label) {
    $counts[$label] = (int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
}

$judul = get_setting($pdo, 'judul');
$periode = get_setting($pdo, 'periode');
$lokasi = get_setting($pdo, 'lokasi');
page_start('Dashboard', 'dashboard');
?>
<div class="top"><div><h2>Dashboard</h2><div class="muted"><?php echo h($judul); ?> · <?php echo h($lokasi); ?> · <?php echo h($periode); ?></div></div></div>
<div class="grid">
  <div class="card col-12"><h3>Ringkasan Data</h3>
    <div class="grid">
      <?php foreach ($counts as $label => $c): ?>
        <div class="col-3"><div class="card" style="margin:0"><div class="muted"><?php echo h($label); ?></div><div style="font-size:26px;font-weight:800;color:#176b45"><?php echo $c; ?></div></div></div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card col-12"><h3>Navigasi Pengelolaan</h3>
    <p class="muted">Gunakan menu di samping untuk mengelola tiap bagian per halaman.</p>
    <div class="row-actions">
      <a class="btn" href="profil.php">Profil & Visi</a>
      <a class="btn" href="baseline.php">Baseline</a>
      <a class="btn" href="pilar.php">Pilar</a>
      <a class="btn" href="monitoring.php">Monitoring</a>
      <a class="btn" href="checklist.php">Checklist</a>
    </div>
  </div>
</div>
<?php page_end(); ?>
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
page_start('Masukan & Survei UMKM', 'feedback');
?>
<div class="top">
  <h2>Masukan Publik &amp; Survei UMKM</h2>
  <?php if ($rows): ?>
  <form method="post"><button class="btn danger" name="action" value="delete_all" onclick="return confirm('Hapus semua kritik/saran?')">Hapus Semua</button></form>
  <?php endif; ?>
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

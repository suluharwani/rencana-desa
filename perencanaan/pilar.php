<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/layout.php';
require_login();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = post('action');
    if ($a === 'add') {
        $pdo->prepare('INSERT INTO pillars(kode,nama,deskripsi,indikator,urutan) VALUES(?,?,?,?,?)')
            ->execute([post('kode'), post('nama'), post('deskripsi'), post('indikator'), (int)post('urutan','0')]);
        flash_set('Pilar ditambahkan.');
    } elseif ($a === 'update') {
        $pdo->prepare('UPDATE pillars SET kode=?,nama=?,deskripsi=?,indikator=?,urutan=? WHERE id=?')
            ->execute([post('kode'), post('nama'), post('deskripsi'), post('indikator'), (int)post('urutan','0'), (int)post('id')]);
        flash_set('Pilar diperbarui.');
    } elseif ($a === 'delete') {
        $pdo->prepare('DELETE FROM pillars WHERE id=?')->execute([(int)post('id')]);
        flash_set('Pilar dihapus.');
    }
    header('Location: pilar.php'); exit;
}
$rows = $pdo->query('SELECT * FROM pillars ORDER BY urutan,id')->fetchAll();
page_start('Pilar & Indikator', 'pilar');
?>
<div class="top"><h2>Pilar Program & Indikator</h2></div>
<div class="card"><h3>Tambah Pilar</h3>
<form method="post" class="grid">
  <input type="hidden" name="action" value="add">
  <div class="col-3"><label>Kode</label><input type="text" name="kode" placeholder="mis. 1"></div>
  <div class="col-3"><label>Urutan</label><input type="number" name="urutan" value="0"></div>
  <div class="col-6"><label>Nama Pilar</label><input type="text" name="nama" required></div>
  <div class="col-6"><label>Deskripsi Program</label><textarea name="deskripsi"></textarea></div>
  <div class="col-6"><label>Indikator (pisahkan baris/;)</label><textarea name="indikator"></textarea></div>
  <div class="col-12"><button class="btn" type="submit">Tambah</button></div>
</form></div>
<?php foreach ($rows as $r): ?>
<div class="card">
  <form method="post" class="grid">
    <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
    <div class="col-3"><label>Kode</label><input type="text" name="kode" value="<?php echo h($r['kode']); ?>"></div>
    <div class="col-3"><label>Urutan</label><input type="number" name="urutan" value="<?php echo (int)$r['urutan']; ?>"></div>
    <div class="col-6"><label>Nama</label><input type="text" name="nama" value="<?php echo h($r['nama']); ?>"></div>
    <div class="col-6"><label>Deskripsi</label><textarea name="deskripsi"><?php echo h($r['deskripsi']); ?></textarea></div>
    <div class="col-6"><label>Indikator</label><textarea name="indikator"><?php echo h($r['indikator']); ?></textarea></div>
    <div class="col-12 row-actions">
      <button class="btn" name="action" value="update">Simpan</button>
      <button class="btn danger" name="action" value="delete" onclick="return confirm('Hapus pilar ini?')">Hapus</button>
    </div>
  </form>
</div>
<?php endforeach; if (!$rows): ?><div class="card muted">Belum ada pilar.</div><?php endif; ?>
<?php page_end(); ?>
<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/layout.php';
require_login();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = post('action');
    if ($a === 'add') {
        $pdo->prepare('INSERT INTO notes(judul,isi,urutan) VALUES(?,?,?)')
            ->execute([post('judul'), post('isi'), (int)post('urutan','0')]);
        flash_set('Catatan ditambahkan.');
    } elseif ($a === 'update') {
        $pdo->prepare('UPDATE notes SET judul=?, isi=?, urutan=? WHERE id=?')
            ->execute([post('judul'), post('isi'), (int)post('urutan','0'), (int)post('id')]);
        flash_set('Catatan diperbarui.');
    } elseif ($a === 'delete') {
        $pdo->prepare('DELETE FROM notes WHERE id=?')->execute([(int)post('id')]);
        flash_set('Catatan dihapus.');
    }
    header('Location: catatan.php'); exit;
}
$rows = $pdo->query('SELECT * FROM notes ORDER BY urutan,id')->fetchAll();
page_start('Catatan', 'catatan');
?>
<div class="top"><h2>Catatan Kebijakan & Dokumen</h2></div>
<div class="card"><h3>Tambah Catatan (Input)</h3>
<form method="post" class="grid">
  <input type="hidden" name="action" value="add">
  <div class="col-8"><label>Judul</label><input type="text" name="judul" required></div>
  <div class="col-4"><label>Urutan</label><input type="number" name="urutan" value="0"></div>
  <div class="col-12"><label>Isi Catatan</label><textarea name="isi" style="min-height:120px"></textarea></div>
  <div class="col-12"><button class="btn" type="submit">Tambah</button></div>
</form></div>
<?php foreach ($rows as $r): ?>
<div class="card"><form method="post" class="grid">
  <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
  <div class="col-8"><label>Judul</label><input type="text" name="judul" value="<?php echo h($r['judul']); ?>"></div>
  <div class="col-4"><label>Urutan</label><input type="number" name="urutan" value="<?php echo (int)$r['urutan']; ?>"></div>
  <div class="col-12"><label>Isi</label><textarea name="isi" style="min-height:120px"><?php echo h($r['isi']); ?></textarea></div>
  <div class="col-12 row-actions"><button class="btn" name="action" value="update">Simpan</button>
    <button class="btn danger" name="action" value="delete" onclick="return confirm('Hapus catatan ini?')">Hapus</button></div>
</form></div>
<?php endforeach; if(!$rows): ?><div class="card muted">Belum ada catatan.</div><?php endif; ?>
<?php page_end(); ?>
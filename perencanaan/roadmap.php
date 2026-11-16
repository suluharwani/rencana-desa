<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/layout.php';
require_login();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = post('action');
    if ($a === 'add') {
        $pdo->prepare('INSERT INTO roadmap(periode,judul,poin,urutan) VALUES(?,?,?,?)')
            ->execute([post('periode'), post('judul'), post('poin'), (int)post('urutan','0')]);
        flash_set('Fase roadmap ditambahkan.');
    } elseif ($a === 'update') {
        $pdo->prepare('UPDATE roadmap SET periode=?,judul=?,poin=?,urutan=? WHERE id=?')
            ->execute([post('periode'), post('judul'), post('poin'), (int)post('urutan','0'), (int)post('id')]);
        flash_set('Fase roadmap diperbarui.');
    } elseif ($a === 'delete') {
        $pdo->prepare('DELETE FROM roadmap WHERE id=?')->execute([(int)post('id')]);
        flash_set('Fase dihapus.');
    }
    header('Location: roadmap.php'); exit;
}
$rows = $pdo->query('SELECT * FROM roadmap ORDER BY urutan,id')->fetchAll();
page_start('Roadmap', 'roadmap');
?>
<div class="top"><h2>Roadmap Strategis</h2></div>
<div class="card"><h3>Tambah Fase</h3>
<form method="post" class="grid">
  <input type="hidden" name="action" value="add">
  <div class="col-4"><label>Periode</label><input type="text" name="periode" placeholder="Tahun 1 / Lintas tahun" required></div>
  <div class="col-4"><label>Judul Fase</label><input type="text" name="judul" required></div>
  <div class="col-4"><label>Urutan</label><input type="number" name="urutan" value="0"></div>
  <div class="col-12"><label>Poin-poin (satu per baris)</label><textarea name="poin"></textarea></div>
  <div class="col-12"><button class="btn" type="submit">Tambah</button></div>
</form></div>
<?php foreach ($rows as $r): ?>
<div class="card"><form method="post" class="grid">
  <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
  <div class="col-4"><label>Periode</label><input type="text" name="periode" value="<?php echo h($r['periode']); ?>"></div>
  <div class="col-4"><label>Judul</label><input type="text" name="judul" value="<?php echo h($r['judul']); ?>"></div>
  <div class="col-4"><label>Urutan</label><input type="number" name="urutan" value="<?php echo (int)$r['urutan']; ?>"></div>
  <div class="col-12"><label>Poin</label><textarea name="poin"><?php echo h($r['poin']); ?></textarea></div>
  <div class="col-12 row-actions"><button class="btn" name="action" value="update">Simpan</button>
  <button class="btn danger" name="action" value="delete" onclick="return confirm('Hapus?')">Hapus</button></div>
</form></div>
<?php endforeach; if (!$rows): ?><div class="card muted">Belum ada fase roadmap.</div><?php endif; ?>
<?php page_end(); ?>
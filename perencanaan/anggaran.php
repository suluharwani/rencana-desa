<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/layout.php';
require_login();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = post('action');
    if ($a === 'add') {
        $pdo->prepare('INSERT INTO budget_notes(kategori,item,urutan) VALUES(?,?,?)')
            ->execute([post('kategori'), post('item'), (int)post('urutan','0')]);
        flash_set('Catatan anggaran ditambahkan.');
    } elseif ($a === 'update') {
        $pdo->prepare('UPDATE budget_notes SET kategori=?, item=?, urutan=? WHERE id=?')
            ->execute([post('kategori'), post('item'), (int)post('urutan','0'), (int)post('id')]);
        flash_set('Catatan anggaran diperbarui.');
    } elseif ($a === 'delete') {
        $pdo->prepare('DELETE FROM budget_notes WHERE id=?')->execute([(int)post('id')]);
        flash_set('Catatan anggaran dihapus.');
    }
    header('Location: anggaran.php'); exit;
}
$rows = $pdo->query('SELECT * FROM budget_notes ORDER BY kategori,urutan,id')->fetchAll();
page_start('Anggaran', 'anggaran');
?>
<div class="top"><h2>Strategi & Catatan Anggaran</h2></div>
<div class="card"><h3>Tambah Catatan/Item Anggaran (Input)</h3>
<form method="post" class="grid">
  <input type="hidden" name="action" value="add">
  <div class="col-4"><label>Kategori</label><input type="text" name="kategori" placeholder="Urutan penganggaran / Sumber / Catatan khusus" required></div>
  <div class="col-6"><label>Item/Catatan</label><input type="text" name="item" required></div>
  <div class="col-2"><label>Urutan</label><input type="number" name="urutan" value="0"></div>
  <div class="col-12"><button class="btn" type="submit">Tambah</button></div>
</form></div>
<div class="card"><h3>Daftar Catatan Anggaran (Input/Edit)</h3>
<table><tr><th>Urut</th><th>Kategori</th><th>Item/Catatan</th><th>Aksi</th></tr>
<?php foreach ($rows as $r): ?><tr><form method="post">
  <td style="width:70px"><input type="number" name="urutan" value="<?php echo (int)$r['urutan']; ?>"></td>
  <td><input type="text" name="kategori" value="<?php echo h($r['kategori']); ?>"></td>
  <td><textarea name="item"><?php echo h($r['item']); ?></textarea></td>
  <td class="row-actions">
    <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
    <button class="btn sm" name="action" value="update">Simpan</button>
    <button class="btn sm danger" name="action" value="delete" onclick="return confirm('Hapus?')">Hapus</button>
  </td>
</form></tr><?php endforeach; if(!$rows): ?><tr><td colspan="4" class="muted">Belum ada catatan anggaran.</td></tr><?php endif; ?></table>
</div>
<?php page_end(); ?>
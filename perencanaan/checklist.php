<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/layout.php';
require_login();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = post('action');
    if ($a === 'add') {
        $pdo->prepare('INSERT INTO checklist(tahap,item,selesai,urutan) VALUES(?,?,?,?)')
            ->execute([post('tahap'), post('item'), isset($_POST['selesai']) ? 1 : 0, (int)post('urutan','0')]);
        flash_set('Item checklist ditambahkan.');
    } elseif ($a === 'update') {
        $pdo->prepare('UPDATE checklist SET tahap=?, item=?, selesai=?, urutan=? WHERE id=?')
            ->execute([post('tahap'), post('item'), isset($_POST['selesai']) ? 1 : 0, (int)post('urutan','0'), (int)post('id')]);
        flash_set('Item checklist diperbarui.');
    } elseif ($a === 'toggle') {
        $pdo->prepare('UPDATE checklist SET selesai = 1 - selesai WHERE id=?')->execute([(int)post('id')]);
        flash_set('Status checklist diubah.');
    } elseif ($a === 'delete') {
        $pdo->prepare('DELETE FROM checklist WHERE id=?')->execute([(int)post('id')]);
        flash_set('Item checklist dihapus.');
    } elseif ($a === 'reset_all') {
        $pdo->exec('UPDATE checklist SET selesai=0');
        flash_set('Semua checklist direset.');
    }
    header('Location: checklist.php'); exit;
}

$rows = $pdo->query('SELECT * FROM checklist ORDER BY tahap,urutan,id')->fetchAll();
$grouped = [];
foreach ($rows as $r) { $grouped[$r['tahap']][] = $r; }
page_start('Checklist 90 Hari', 'checklist');
?>
<div class="top"><h2>Checklist 90 Hari Pertama</h2>
  <form method="post"><button class="btn sec" name="action" value="reset_all" onclick="return confirm('Reset semua centang?')">Reset Semua Centang</button></form>
</div>
<div class="card"><h3>Tambah Item Checklist (Input)</h3>
<form method="post" class="grid">
  <input type="hidden" name="action" value="add">
  <div class="col-4"><label>Tahap</label><input type="text" name="tahap" placeholder="Hari 1-30 / Pra-pelantikan" required></div>
  <div class="col-6"><label>Item Kegiatan</label><input type="text" name="item" required></div>
  <div class="col-2"><label>Urutan</label><input type="number" name="urutan" value="0"></div>
  <div class="col-12"><label><input type="checkbox" name="selesai" style="width:auto"> Tandai selesai</label> &nbsp; <button class="btn" type="submit">Tambah</button></div>
</form></div>

<div class="card"><h3>Daftar Checklist (Input/Edit & Centang)</h3>
<table><tr><th>Urut</th><th>Tahap</th><th>Item</th><th>Selesai</th><th>Aksi</th></tr>
<?php foreach ($rows as $r): ?><tr><form method="post">
  <td style="width:70px"><input type="number" name="urutan" value="<?php echo (int)$r['urutan']; ?>"></td>
  <td><input type="text" name="tahap" value="<?php echo h($r['tahap']); ?>"></td>
  <td><textarea name="item"><?php echo h($r['item']); ?></textarea></td>
  <td style="text-align:center"><input type="checkbox" name="selesai" style="width:auto" <?php echo $r['selesai']?'checked':''; ?>></td>
  <td class="row-actions">
    <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
    <button class="btn sm" name="action" value="update">Simpan</button>
    <button class="btn sm danger" name="action" value="delete" onclick="return confirm('Hapus?')">Hapus</button>
  </td>
</form></tr><?php endforeach; if(!$rows): ?><tr><td colspan="5" class="muted">Belum ada checklist.</td></tr><?php endif; ?></table>
</div>

<?php if ($grouped): ?>
<div class="card"><h3>Ringkasan per Tahap</h3>
<?php foreach ($grouped as $tahap => $items): $done = count(array_filter($items, fn($i)=>$i['selesai'])); ?>
  <p><b><?php echo h($tahap); ?></b>: <?php echo $done; ?>/<?php echo count($items); ?> selesai</p>
<?php endforeach; ?></div>
<?php endif; ?>
<?php page_end(); ?>
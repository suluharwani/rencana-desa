<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/layout.php';
require_login();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = post('action');
    if ($action === 'add_stat') {
        $stmt = $pdo->prepare('INSERT INTO baseline(label,nilai,keterangan,urutan) VALUES(?,?,?,?)');
        $stmt->execute([post('label'), post('nilai'), post('keterangan'), (int)post('urutan', '0')]);
        flash_set('Data baseline ditambahkan.');
    } elseif ($action === 'update_stat') {
        $stmt = $pdo->prepare('UPDATE baseline SET label=?, nilai=?, keterangan=?, urutan=? WHERE id=?');
        $stmt->execute([post('label'), post('nilai'), post('keterangan'), (int)post('urutan','0'), (int)post('id')]);
        flash_set('Data baseline diperbarui.');
    } elseif ($action === 'delete_stat') {
        $pdo->prepare('DELETE FROM baseline WHERE id=?')->execute([(int)post('id')]);
        flash_set('Data baseline dihapus.');
    } elseif ($action === 'add_task') {
        $stmt = $pdo->prepare('INSERT INTO baseline_tasks(kelompok,item,urutan) VALUES(?,?,?)');
        $stmt->execute([post('kelompok'), post('item'), (int)post('urutan','0')]);
        flash_set('Tugas verifikasi ditambahkan.');
    } elseif ($action === 'update_task') {
        $stmt = $pdo->prepare('UPDATE baseline_tasks SET kelompok=?, item=?, urutan=? WHERE id=?');
        $stmt->execute([post('kelompok'), post('item'), (int)post('urutan','0'), (int)post('id')]);
        flash_set('Tugas verifikasi diperbarui.');
    } elseif ($action === 'delete_task') {
        $pdo->prepare('DELETE FROM baseline_tasks WHERE id=?')->execute([(int)post('id')]);
        flash_set('Tugas verifikasi dihapus.');
    }
    header('Location: baseline.php'); exit;
}

$stats = $pdo->query('SELECT * FROM baseline ORDER BY urutan,id')->fetchAll();
$tasks = $pdo->query('SELECT * FROM baseline_tasks ORDER BY kelompok,urutan,id')->fetchAll();
page_start('Baseline', 'baseline');
?>
<div class="top"><h2>Baseline & Verifikasi Awal</h2></div>
<div class="card">
  <h3>Tambah Data Baseline</h3>
  <form method="post" class="inline-form">
    <input type="hidden" name="action" value="add_stat">
    <div><label>Label</label><input type="text" name="label" required></div>
    <div><label>Nilai</label><input type="text" name="nilai" required></div>
    <div><label>Keterangan</label><input type="text" name="keterangan"></div>
    <div style="max-width:100px"><label>Urutan</label><input type="number" name="urutan" value="0"></div>
    <div style="flex:0"><label>&nbsp;</label><button class="btn" type="submit">Tambah</button></div>
  </form>
</div>
<div class="card">
  <h3>Data Baseline (Input/Edit)</h3>
  <table><tr><th>Urut</th><th>Label</th><th>Nilai</th><th>Keterangan</th><th>Aksi</th></tr>
  <?php foreach ($stats as $r): ?>
    <tr><form method="post">
      <td style="width:70px"><input type="number" name="urutan" value="<?php echo (int)$r['urutan']; ?>"></td>
      <td><input type="text" name="label" value="<?php echo h($r['label']); ?>"></td>
      <td><input type="text" name="nilai" value="<?php echo h($r['nilai']); ?>"></td>
      <td><input type="text" name="keterangan" value="<?php echo h($r['keterangan']); ?>"></td>
      <td class="row-actions">
        <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
        <button class="btn sm" name="action" value="update_stat">Simpan</button>
        <button class="btn sm danger" name="action" value="delete_stat" onclick="return confirm('Hapus data ini?')">Hapus</button>
      </td>
    </form></tr>
  <?php endforeach; if (!$stats): ?><tr><td colspan="5" class="muted">Belum ada data.</td></tr><?php endif; ?>
  </table>
</div>
<div class="card">
  <h3>Tambah Tugas Verifikasi 90 Hari</h3>
  <form method="post" class="inline-form">
    <input type="hidden" name="action" value="add_task">
    <div><label>Kelompok/Bidang</label><input type="text" name="kelompok" required></div>
    <div style="flex:2"><label>Item yang diverifikasi</label><input type="text" name="item" required></div>
    <div style="max-width:100px"><label>Urutan</label><input type="number" name="urutan" value="0"></div>
    <div style="flex:0"><label>&nbsp;</label><button class="btn" type="submit">Tambah</button></div>
  </form>
</div>
<div class="card">
  <h3>Daftar Tugas Verifikasi (Input/Edit)</h3>
  <table><tr><th>Urut</th><th>Kelompok</th><th>Item</th><th>Aksi</th></tr>
  <?php foreach ($tasks as $r): ?>
    <tr><form method="post">
      <td style="width:70px"><input type="number" name="urutan" value="<?php echo (int)$r['urutan']; ?>"></td>
      <td><input type="text" name="kelompok" value="<?php echo h($r['kelompok']); ?>"></td>
      <td><textarea name="item"><?php echo h($r['item']); ?></textarea></td>
      <td class="row-actions">
        <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
        <button class="btn sm" name="action" value="update_task">Simpan</button>
        <button class="btn sm danger" name="action" value="delete_task" onclick="return confirm('Hapus?')">Hapus</button>
      </td>
    </form></tr>
  <?php endforeach; if (!$tasks): ?><tr><td colspan="4" class="muted">Belum ada data.</td></tr><?php endif; ?>
  </table>
</div>
<?php page_end(); ?>
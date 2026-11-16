<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/layout.php';
require_login();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = post('action');
    if ($a === 'add_month') {
        $pdo->prepare('INSERT INTO year1_months(bulan,judul,kegiatan,urutan) VALUES(?,?,?,?)')
            ->execute([post('bulan'), post('judul'), post('kegiatan'), (int)post('urutan','0')]);
        flash_set('Bulan ditambahkan.');
    } elseif ($a === 'update_month') {
        $pdo->prepare('UPDATE year1_months SET bulan=?,judul=?,kegiatan=?,urutan=? WHERE id=?')
            ->execute([post('bulan'), post('judul'), post('kegiatan'), (int)post('urutan','0'), (int)post('id')]);
        flash_set('Bulan diperbarui.');
    } elseif ($a === 'delete_month') {
        $pdo->prepare('DELETE FROM year1_months WHERE id=?')->execute([(int)post('id')]);
        flash_set('Bulan dihapus.');
    } elseif ($a === 'add_routine') {
        $pdo->prepare('INSERT INTO routines(kegiatan,frekuensi,bukti,urutan) VALUES(?,?,?,?)')
            ->execute([post('kegiatan'), post('frekuensi'), post('bukti'), (int)post('urutan','0')]);
        flash_set('Agenda rutin ditambahkan.');
    } elseif ($a === 'update_routine') {
        $pdo->prepare('UPDATE routines SET kegiatan=?,frekuensi=?,bukti=?,urutan=? WHERE id=?')
            ->execute([post('kegiatan'), post('frekuensi'), post('bukti'), (int)post('urutan','0'), (int)post('id')]);
        flash_set('Agenda rutin diperbarui.');
    } elseif ($a === 'delete_routine') {
        $pdo->prepare('DELETE FROM routines WHERE id=?')->execute([(int)post('id')]);
        flash_set('Agenda rutin dihapus.');
    } elseif ($a === 'add_q') {
        $pdo->prepare('INSERT INTO quarterly(kuartal,fokus,target,output,urutan) VALUES(?,?,?,?,?)')
            ->execute([post('kuartal'), post('fokus'), post('target'), post('output'), (int)post('urutan','0')]);
        flash_set('Target kuartal ditambahkan.');
    } elseif ($a === 'update_q') {
        $pdo->prepare('UPDATE quarterly SET kuartal=?,fokus=?,target=?,output=?,urutan=? WHERE id=?')
            ->execute([post('kuartal'), post('fokus'), post('target'), post('output'), (int)post('urutan','0'), (int)post('id')]);
        flash_set('Target kuartal diperbarui.');
    } elseif ($a === 'delete_q') {
        $pdo->prepare('DELETE FROM quarterly WHERE id=?')->execute([(int)post('id')]);
        flash_set('Target kuartal dihapus.');
    } elseif ($a === 'add_sem') {
        $pdo->prepare('INSERT INTO semester_eval(bidang,semester1,semester2,urutan) VALUES(?,?,?,?)')
            ->execute([post('bidang'), post('semester1'), post('semester2'), (int)post('urutan','0')]);
        flash_set('Evaluasi semester ditambahkan.');
    } elseif ($a === 'update_sem') {
        $pdo->prepare('UPDATE semester_eval SET bidang=?,semester1=?,semester2=?,urutan=? WHERE id=?')
            ->execute([post('bidang'), post('semester1'), post('semester2'), (int)post('urutan','0'), (int)post('id')]);
        flash_set('Evaluasi semester diperbarui.');
    } elseif ($a === 'delete_sem') {
        $pdo->prepare('DELETE FROM semester_eval WHERE id=?')->execute([(int)post('id')]);
        flash_set('Evaluasi semester dihapus.');
    }
    header('Location: tahun1.php'); exit;
}

$months = $pdo->query('SELECT * FROM year1_months ORDER BY urutan,id')->fetchAll();
$routines = $pdo->query('SELECT * FROM routines ORDER BY urutan,id')->fetchAll();
$quarters = $pdo->query('SELECT * FROM quarterly ORDER BY urutan,id')->fetchAll();
$sems = $pdo->query('SELECT * FROM semester_eval ORDER BY urutan,id')->fetchAll();
page_start('Tahun Pertama', 'tahun1');
?>
<div class="top"><h2>Rencana Kerja Tahun Pertama</h2></div>

<div class="card"><h3>Tambah Kalender Bulan</h3>
<form method="post" class="grid"><input type="hidden" name="action" value="add_month">
  <div class="col-3"><label>Bulan</label><input type="text" name="bulan" placeholder="Bulan 1" required></div>
  <div class="col-5"><label>Judul</label><input type="text" name="judul" required></div>
  <div class="col-4"><label>Urutan</label><input type="number" name="urutan" value="0"></div>
  <div class="col-12"><label>Kegiatan</label><textarea name="kegiatan"></textarea></div>
  <div class="col-12"><button class="btn">Tambah</button></div>
</form></div>
<div class="card"><h3>Kalender 12 Bulan (Input/Edit)</h3><table><tr><th>Urut</th><th>Bulan</th><th>Judul</th><th>Kegiatan</th><th>Aksi</th></tr>
<?php foreach ($months as $r): ?><tr><form method="post">
  <td style="width:60px"><input type="number" name="urutan" value="<?php echo (int)$r['urutan']; ?>"></td>
  <td><input type="text" name="bulan" value="<?php echo h($r['bulan']); ?>"></td>
  <td><input type="text" name="judul" value="<?php echo h($r['judul']); ?>"></td>
  <td><textarea name="kegiatan"><?php echo h($r['kegiatan']); ?></textarea></td>
  <td class="row-actions"><input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
    <button class="btn sm" name="action" value="update_month">Simpan</button>
    <button class="btn sm danger" name="action" value="delete_month" onclick="return confirm('Hapus?')">Hapus</button></td>
</form></tr><?php endforeach; if(!$months): ?><tr><td colspan="5" class="muted">Belum ada data.</td></tr><?php endif; ?></table></div>

<div class="card"><h3>Tambah Agenda Rutin</h3>
<form method="post" class="grid"><input type="hidden" name="action" value="add_routine">
  <div class="col-4"><label>Kegiatan</label><input type="text" name="kegiatan" required></div>
  <div class="col-3"><label>Frekuensi</label><input type="text" name="frekuensi" required></div>
  <div class="col-3"><label>Bukti minimal</label><input type="text" name="bukti"></div>
  <div class="col-2"><label>Urutan</label><input type="number" name="urutan" value="0"></div>
  <div class="col-12"><button class="btn">Tambah</button></div>
</form></div>
<div class="card"><h3>Agenda Rutin (Input/Edit)</h3><table><tr><th>Urut</th><th>Kegiatan</th><th>Frekuensi</th><th>Bukti</th><th>Aksi</th></tr>
<?php foreach ($routines as $r): ?><tr><form method="post">
  <td style="width:60px"><input type="number" name="urutan" value="<?php echo (int)$r['urutan']; ?>"></td>
  <td><input type="text" name="kegiatan" value="<?php echo h($r['kegiatan']); ?>"></td>
  <td><input type="text" name="frekuensi" value="<?php echo h($r['frekuensi']); ?>"></td>
  <td><textarea name="bukti"><?php echo h($r['bukti']); ?></textarea></td>
  <td class="row-actions"><input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
    <button class="btn sm" name="action" value="update_routine">Simpan</button>
    <button class="btn sm danger" name="action" value="delete_routine" onclick="return confirm('Hapus?')">Hapus</button></td>
</form></tr><?php endforeach; if(!$routines): ?><tr><td colspan="5" class="muted">Belum ada data.</td></tr><?php endif; ?></table></div>

<div class="card"><h3>Tambah Target Kuartal</h3>
<form method="post" class="grid"><input type="hidden" name="action" value="add_q">
  <div class="col-3"><label>Kuartal</label><input type="text" name="kuartal" placeholder="Q1" required></div>
  <div class="col-3"><label>Fokus</label><input type="text" name="fokus"></div>
  <div class="col-4"><label>Output</label><input type="text" name="output"></div>
  <div class="col-2"><label>Urutan</label><input type="number" name="urutan" value="0"></div>
  <div class="col-12"><label>Target (satu per baris)</label><textarea name="target"></textarea></div>
  <div class="col-12"><button class="btn">Tambah</button></div>
</form></div>
<div class="card"><h3>Target Kuartal (Input/Edit)</h3>
<?php foreach ($quarters as $r): ?><form method="post" class="card" style="background:#f9fbf9"><div class="grid">
  <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
  <div class="col-3"><label>Kuartal</label><input type="text" name="kuartal" value="<?php echo h($r['kuartal']); ?>"></div>
  <div class="col-3"><label>Fokus</label><input type="text" name="fokus" value="<?php echo h($r['fokus']); ?>"></div>
  <div class="col-4"><label>Output</label><input type="text" name="output" value="<?php echo h($r['output']); ?>"></div>
  <div class="col-2"><label>Urutan</label><input type="number" name="urutan" value="<?php echo (int)$r['urutan']; ?>"></div>
  <div class="col-12"><label>Target</label><textarea name="target"><?php echo h($r['target']); ?></textarea></div>
  <div class="col-12 row-actions"><button class="btn sm" name="action" value="update_q">Simpan</button>
    <button class="btn sm danger" name="action" value="delete_q" onclick="return confirm('Hapus?')">Hapus</button></div>
</div></form><?php endforeach; if(!$quarters): ?><div class="muted">Belum ada data.</div><?php endif; ?></div>

<div class="card"><h3>Tambah Evaluasi Semester</h3>
<form method="post" class="grid"><input type="hidden" name="action" value="add_sem">
  <div class="col-4"><label>Bidang</label><input type="text" name="bidang" required></div>
  <div class="col-4"><label>Semester 1</label><textarea name="semester1"></textarea></div>
  <div class="col-4"><label>Semester 2</label><textarea name="semester2"></textarea></div>
  <div class="col-12"><label>Urutan</label><input type="number" name="urutan" value="0" style="max-width:120px"> <button class="btn">Tambah</button></div>
</form></div>
<div class="card"><h3>Evaluasi Semester (Input/Edit)</h3><table><tr><th>Urut</th><th>Bidang</th><th>Semester 1</th><th>Semester 2</th><th>Aksi</th></tr>
<?php foreach ($sems as $r): ?><tr><form method="post">
  <td style="width:60px"><input type="number" name="urutan" value="<?php echo (int)$r['urutan']; ?>"></td>
  <td><input type="text" name="bidang" value="<?php echo h($r['bidang']); ?>"></td>
  <td><textarea name="semester1"><?php echo h($r['semester1']); ?></textarea></td>
  <td><textarea name="semester2"><?php echo h($r['semester2']); ?></textarea></td>
  <td class="row-actions"><input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
    <button class="btn sm" name="action" value="update_sem">Simpan</button>
    <button class="btn sm danger" name="action" value="delete_sem" onclick="return confirm('Hapus?')">Hapus</button></td>
</form></tr><?php endforeach; if(!$sems): ?><tr><td colspan="5" class="muted">Belum ada data.</td></tr><?php endif; ?></table></div>
<?php page_end(); ?>
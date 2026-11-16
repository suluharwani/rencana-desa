<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/layout.php';
require_login();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = post('action');
    $fields = ['kode','program','lokasi','pic','target','anggaran','progres_fisik','realisasi_biaya','status','bukti','tindak_lanjut'];
    if ($a === 'add' || $a === 'update') {
        $data = [];
        foreach ($fields as $f) $data[$f] = post($f);
        if ($a === 'add') {
            $pdo->prepare('INSERT INTO monitoring(kode,program,lokasi,pic,target,anggaran,progres_fisik,realisasi_biaya,status,bukti,tindak_lanjut) VALUES(?,?,?,?,?,?,?,?,?,?,?)')
                ->execute(array_values($data));
            flash_set('Data monitoring ditambahkan.');
        } else {
            $data['id'] = (int)post('id');
            $pdo->prepare('UPDATE monitoring SET kode=?,program=?,lokasi=?,pic=?,target=?,anggaran=?,progres_fisik=?,realisasi_biaya=?,status=?,bukti=?,tindak_lanjut=? WHERE id=?')
                ->execute(array_values($data));
            flash_set('Data monitoring diperbarui.');
        }
    } elseif ($a === 'delete') {
        $pdo->prepare('DELETE FROM monitoring WHERE id=?')->execute([(int)post('id')]);
        flash_set('Data monitoring dihapus.');
    }
    header('Location: monitoring.php'); exit;
}

$rows = $pdo->query('SELECT * FROM monitoring ORDER BY id DESC')->fetchAll();
$statuses = ['Belum dinilai','Hijau · Sesuai','Kuning · Waspada','Merah · Bermasalah','Biru · Selesai','Abu-abu · Belum mulai'];
page_start('Monitoring', 'monitoring');
?>
<div class="top"><h2>Register Monitoring & Evaluasi</h2></div>
<div class="card"><h3>Tambah Program/Kegiatan (Input)</h3>
<form method="post" class="grid">
  <input type="hidden" name="action" value="add">
  <div class="col-3"><label>Kode</label><input type="text" name="kode" required></div>
  <div class="col-3"><label>Status</label><select name="status"><?php foreach($statuses as $s): ?><option><?php echo h($s); ?></option><?php endforeach; ?></select></div>
  <div class="col-6"><label>Program/Kegiatan</label><input type="text" name="program" required></div>
  <div class="col-4"><label>Lokasi</label><input type="text" name="lokasi"></div>
  <div class="col-4"><label>PIC</label><input type="text" name="pic"></div>
  <div class="col-4"><label>Target</label><input type="text" name="target"></div>
  <div class="col-4"><label>Anggaran</label><input type="text" name="anggaran"></div>
  <div class="col-4"><label>Progres Fisik</label><input type="text" name="progres_fisik"></div>
  <div class="col-4"><label>Realisasi Biaya</label><input type="text" name="realisasi_biaya"></div>
  <div class="col-6"><label>Bukti</label><textarea name="bukti"></textarea></div>
  <div class="col-6"><label>Tindak Lanjut</label><textarea name="tindak_lanjut"></textarea></div>
  <div class="col-12"><button class="btn" type="submit">Tambah</button></div>
</form></div>

<?php foreach ($rows as $r): ?>
<div class="card"><form method="post" class="grid">
  <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
  <div class="col-3"><label>Kode</label><input type="text" name="kode" value="<?php echo h($r['kode']); ?>"></div>
  <div class="col-3"><label>Status</label><select name="status"><?php foreach($statuses as $s): ?><option <?php echo $r['status']===$s?'selected':''; ?>><?php echo h($s); ?></option><?php endforeach; ?></select></div>
  <div class="col-6"><label>Program/Kegiatan</label><input type="text" name="program" value="<?php echo h($r['program']); ?>"></div>
  <div class="col-4"><label>Lokasi</label><input type="text" name="lokasi" value="<?php echo h($r['lokasi']); ?>"></div>
  <div class="col-4"><label>PIC</label><input type="text" name="pic" value="<?php echo h($r['pic']); ?>"></div>
  <div class="col-4"><label>Target</label><input type="text" name="target" value="<?php echo h($r['target']); ?>"></div>
  <div class="col-4"><label>Anggaran</label><input type="text" name="anggaran" value="<?php echo h($r['anggaran']); ?>"></div>
  <div class="col-4"><label>Progres Fisik</label><input type="text" name="progres_fisik" value="<?php echo h($r['progres_fisik']); ?>"></div>
  <div class="col-4"><label>Realisasi Biaya</label><input type="text" name="realisasi_biaya" value="<?php echo h($r['realisasi_biaya']); ?>"></div>
  <div class="col-6"><label>Bukti</label><textarea name="bukti"><?php echo h($r['bukti']); ?></textarea></div>
  <div class="col-6"><label>Tindak Lanjut</label><textarea name="tindak_lanjut"><?php echo h($r['tindak_lanjut']); ?></textarea></div>
  <div class="col-12 row-actions"><button class="btn" name="action" value="update">Simpan</button>
    <button class="btn danger" name="action" value="delete" onclick="return confirm('Hapus data ini?')">Hapus</button></div>
</form></div>
<?php endforeach; if (!$rows): ?><div class="card muted">Belum ada data monitoring.</div><?php endif; ?>
<?php page_end(); ?>
<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/layout.php';
require_login();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['judul','subjudul','lokasi','periode','versi','slogan','visi'] as $k) {
        set_setting($pdo, $k, post($k));
    }
    flash_set('Profil & visi berhasil disimpan.');
    header('Location: profil.php'); exit;
}

$fields = [];
foreach (['judul','subjudul','lokasi','periode','versi','slogan','visi'] as $k) {
    $fields[$k] = get_setting($pdo, $k);
}
page_start('Profil & Visi', 'profil');
?>
<div class="top"><h2>Profil & Visi</h2></div>
<div class="card">
  <form method="post">
    <div class="grid">
      <div class="col-6"><label>Judul</label><input type="text" name="judul" value="<?php echo h($fields['judul']); ?>"></div>
      <div class="col-6"><label>Subjudul</label><input type="text" name="subjudul" value="<?php echo h($fields['subjudul']); ?>"></div>
      <div class="col-4"><label>Lokasi</label><input type="text" name="lokasi" value="<?php echo h($fields['lokasi']); ?>"></div>
      <div class="col-4"><label>Periode</label><input type="text" name="periode" value="<?php echo h($fields['periode']); ?>"></div>
      <div class="col-4"><label>Versi</label><input type="text" name="versi" value="<?php echo h($fields['versi']); ?>"></div>
      <div class="col-12"><label>Slogan</label><input type="text" name="slogan" value="<?php echo h($fields['slogan']); ?>"></div>
      <div class="col-12"><label>Visi Operasional</label><textarea name="visi"><?php echo h($fields['visi']); ?></textarea></div>
    </div>
    <p style="margin-top:12px"><button class="btn" type="submit">Simpan</button></p>
  </form>
</div>
<?php page_end(); ?>
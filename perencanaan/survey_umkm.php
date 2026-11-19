<?php
require_once __DIR__ . '/config.php';

$pdo = db();
$categories = [
    'Kuliner' => 'Makanan dan minuman',
    'Pertanian' => 'Pertanian, peternakan, dan perikanan',
    'Olahan' => 'Olahan hasil pertanian',
    'Kerajinan' => 'Kerajinan dan produk rumah tangga',
    'Perdagangan' => 'Warung dan perdagangan',
    'Jasa' => 'Jasa',
    'Fashion' => 'Fashion dan konveksi',
    'Lainnya' => 'Lainnya',
];
$plans = [
    'Pasar rakyat dan kewirausahaan pemuda',
    'Menghubungkan UMKM dengan pasar dan mitra lokal',
    'Kemitraan dan pengolahan hasil pertanian, termasuk kajian pengeringan jagung',
    'Semua rencana yang berkaitan dengan UMKM',
    'Belum menentukan / usulan lain',
];
$form = [
    'nama' => trim((string)($_POST['nama'] ?? '')),
    'usaha' => trim((string)($_POST['usaha'] ?? '')),
    'kategori' => trim((string)($_POST['kategori'] ?? '')),
    'rencana' => trim((string)($_POST['rencana'] ?? '')),
    'saran' => trim((string)($_POST['saran'] ?? '')),
];
$error = '';
$sent = isset($_GET['sent']);

if (empty($_SESSION['survey_umkm_csrf'])) {
    $_SESSION['survey_umkm_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string)($_POST['csrf_token'] ?? '');
    $captcha = trim((string)($_POST['captcha'] ?? ''));
    $expectedCaptcha = $_SESSION['survey_umkm_captcha'] ?? null;
    if (!hash_equals((string)$_SESSION['survey_umkm_csrf'], $token)) {
        $error = 'Form kedaluwarsa. Muat ulang halaman lalu kirim kembali.';
    } elseif ($form['usaha'] === '' || $form['saran'] === '') {
        $error = 'Nama UMKM/produk serta saran atau usulan perlu diisi.';
    } elseif (!isset($categories[$form['kategori']])) {
        $error = 'Pilih jenis UMKM terlebih dahulu.';
    } elseif (!in_array($form['rencana'], $plans, true)) {
        $error = 'Pilih rencana UMKM yang didukung.';
    } elseif ($expectedCaptcha === null || $captcha === '' || !ctype_digit($captcha) || (int)$captcha !== (int)$expectedCaptcha) {
        $error = 'Jawaban penjumlahan belum tepat. Silakan coba lagi.';
    } else {
        $message = "Nama UMKM/produk: {$form['usaha']}\nRencana yang didukung: {$form['rencana']}\nSaran dan usulan: {$form['saran']}";
        $pdo->prepare('INSERT INTO feedback(nama,kontak,jenis,pesan,status) VALUES(?,?,?,?,?)')
            ->execute([
                mb_substr($form['nama'], 0, 100),
                '',
                'UMKM: ' . $categories[$form['kategori']],
                mb_substr($message, 0, 4000),
                'Baru',
            ]);
        unset($_SESSION['survey_umkm_captcha']);
        $_SESSION['survey_umkm_csrf'] = bin2hex(random_bytes(32));
        header('Location: survey_umkm.php?sent=1');
        exit;
    }
}

$captchaA = random_int(1, 9);
$captchaB = random_int(1, 9);
$_SESSION['survey_umkm_captcha'] = $captchaA + $captchaB;
$captchaQuestion = $captchaA . ' + ' . $captchaB;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#123c2b">
<title>Survei UMKM Desa Penganten</title>
<style>
:root{--green:#176b45;--green-dark:#123c2b;--mint:#eaf5ee;--ink:#18251f;--muted:#65736b;--line:#dce6df;--bg:#f4f7f4;--white:#fff;--gold:#c18a2d}
*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:var(--bg);color:var(--ink);font:15px/1.6 system-ui,-apple-system,"Segoe UI",Arial,sans-serif}a{color:var(--green)}.wrap{width:min(980px,100% - 36px);margin:auto}
.site-nav{background:#fff;border-bottom:1px solid var(--line)}.site-nav .wrap{display:flex;align-items:center;justify-content:space-between;gap:12px;min-height:54px}.brand{font-size:12px;font-weight:850;letter-spacing:.04em;color:var(--green-dark);text-decoration:none}.back{font-size:13px;font-weight:700;text-decoration:none}
.hero{padding:28px 0 22px;background:linear-gradient(120deg,#103f2c,#176b45 58%,#2b8b61);color:#fff}.hero h1{margin:0;font-size:clamp(25px,5vw,38px);line-height:1.12;letter-spacing:-.03em}.hero p{margin:10px 0 0;color:#e4f1e8;max-width:700px}.kicker{text-transform:uppercase;letter-spacing:.12em;font-size:11px;color:#cce6d5;font-weight:850;margin-bottom:7px}
main{padding:24px 0 52px}.intro{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(380px,1fr);gap:16px;align-items:stretch}.intro-copy,.survey-card{background:var(--white);border:1px solid var(--line);border-radius:18px;overflow:hidden;box-shadow:0 8px 26px #14382308}.intro-copy{padding:22px}.intro-copy h2{margin:0 0 7px;font-size:21px}.intro-copy p{margin:0;color:var(--muted);font-size:14px}.intro-copy .note{margin-top:13px;padding:11px 12px;background:#f3f8f4;border-radius:11px;font-size:12px;color:#43594a}.intro-copy img{display:block;width:100%;height:auto;object-fit:contain;border-radius:12px;margin-bottom:15px}
.survey-card{padding:22px;margin-top:16px}.section-head{display:flex;justify-content:space-between;align-items:flex-end;gap:14px;margin-bottom:16px}.section-head h2{font-size:21px;margin:2px 0 0}.section-head p{font-size:13px;color:var(--muted);margin:0;max-width:440px}.form-grid{display:grid;grid-template-columns:minmax(0,1fr);gap:14px}.field{min-width:0}.field.wide{grid-column:1/-1}label{display:block;font-size:13px;font-weight:800;margin-bottom:5px;color:#284633}.hint{font-size:11px;color:var(--muted);margin:4px 0 0}input,select,textarea{width:100%;border:1px solid #cbd9cf;border-radius:10px;background:#fff;color:var(--ink);font:inherit;padding:11px 12px}input:focus,select:focus,textarea:focus{outline:3px solid #b7d7c2;border-color:var(--green)}textarea{min-height:130px;resize:vertical}.required{color:#b94c42}.privacy{margin:15px 0;padding:12px 14px;background:#f3f8f4;border-radius:11px;color:#4e6255;font-size:12px}.captcha-row{display:grid;grid-template-columns:minmax(0,1fr) 180px;align-items:end;gap:12px}.submit-row{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:18px}.btn{display:inline-flex;align-items:center;justify-content:center;min-height:46px;border:0;border-radius:10px;padding:10px 17px;background:var(--green);color:#fff;text-decoration:none;font:inherit;font-size:14px;font-weight:800;cursor:pointer}.btn:hover{background:#105538}.small{font-size:12px;color:var(--muted)}.alert{padding:12px 14px;border-radius:11px;margin-bottom:14px;font-size:14px}.alert.ok{background:#e7f5ec;color:#1b5e3b;border:1px solid #cfe8d8}.alert.err{background:#fdeceb;color:#8a332d;border:1px solid #f3c9c5}
@media(max-width:900px){.intro{grid-template-columns:1fr}}@media(max-width:700px){.wrap{width:min(100% - 28px,600px)}.intro{grid-template-columns:1fr}.intro-copy{padding:17px}.intro-copy img{max-height:none;object-fit:contain}.survey-card{padding:17px}.section-head{display:block}.section-head p{margin-top:5px}.form-grid{grid-template-columns:1fr;gap:12px}.field.wide{grid-column:auto}.captcha-row{grid-template-columns:minmax(0,1fr) 130px}.submit-row{align-items:stretch;flex-direction:column}.submit-row .btn{width:100%}}
@media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}}
</style>
</head>
<body>
<div class="site-nav"><div class="wrap"><a class="brand" href="index.php">RENCANA DESA · PENGANTEN</a><a class="back" href="index.php">← Kembali ke halaman utama</a></div></div>
<header class="hero"><div class="wrap"><div class="kicker">Pendataan usaha warga</div><h1>Survei UMKM Desa Penganten</h1><p>Ceritakan usaha warga dan rencana pengembangan UMKM yang paling dibutuhkan. Masukan akan membantu menyusun prioritas bersama.</p></div></header>
<main class="wrap">
  <section class="intro" aria-label="Tentang survei">
    <div class="intro-copy"><img src="assets/umkm.jpeg" alt="Ajakan warga Desa Penganten berbagi informasi produk UMKM dan kebutuhan pemasaran" width="1152" height="1536" fetchpriority="high"><h2>Suara pelaku UMKM penting</h2><p>Isi nama usaha atau produk, pilih jenis usaha, lalu tandai rencana yang paling didukung. Tambahkan saran agar kebutuhan pelatihan, pemasaran, dan pengembangan usaha dapat dipahami.</p><div class="note"><b>Privasi:</b> Nama warga bersifat opsional. Jawaban survei hanya dibaca pengelola untuk merangkum kebutuhan UMKM dan tidak ditampilkan kepada pengunjung.</div></div>
    <div class="survey-card" id="form-survei">
      <?php if ($sent): ?><div class="alert ok" role="status"><b>Terima kasih.</b> Jawaban survei UMKM Anda sudah tersimpan.</div><?php endif; ?>
      <?php if ($error !== ''): ?><div class="alert err" role="alert"><?php echo h($error); ?></div><?php endif; ?>
      <div class="section-head"><div><div class="kicker" style="color:var(--green)">Form survei</div><h2>Data usaha dan usulan</h2></div><p>Isian bertanda <span class="required">*</span> perlu dilengkapi.</p></div>
      <form method="post" action="survey_umkm.php#form-survei">
        <input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['survey_umkm_csrf']); ?>">
        <div class="form-grid">
          <div class="field"><label for="nama">Nama warga / pemilik <span class="small">(opsional)</span></label><input id="nama" type="text" name="nama" maxlength="100" autocomplete="name" value="<?php echo h($form['nama']); ?>" placeholder="Nama Anda"></div>
          <div class="field"><label for="usaha">Nama UMKM atau produk <span class="required">*</span></label><input id="usaha" type="text" name="usaha" maxlength="150" required value="<?php echo h($form['usaha']); ?>" placeholder="Contoh: Keripik pisang Bu Sari"></div>
          <div class="field"><label for="kategori">Jenis UMKM <span class="required">*</span></label><select id="kategori" name="kategori" required><option value="">Pilih jenis usaha</option><?php foreach ($categories as $key => $label): ?><option value="<?php echo h($key); ?>" <?php echo $form['kategori'] === $key ? 'selected' : ''; ?>><?php echo h($label); ?></option><?php endforeach; ?></select></div>
          <div class="field"><label for="rencana">Rencana UMKM yang didukung <span class="required">*</span></label><select id="rencana" name="rencana" required><option value="">Pilih rencana</option><?php foreach ($plans as $plan): ?><option value="<?php echo h($plan); ?>" <?php echo $form['rencana'] === $plan ? 'selected' : ''; ?>><?php echo h($plan); ?></option><?php endforeach; ?></select><p class="hint">Pilihan mengikuti rencana kewirausahaan, pasar rakyat, kemitraan, dan hasil pertanian yang tercatat di halaman utama.</p></div>
          <div class="field wide"><label for="saran">Saran dan usulan <span class="required">*</span></label><textarea id="saran" name="saran" maxlength="3000" required placeholder="Ceritakan dukungan, pelatihan, akses pasar, atau kebutuhan lain yang diharapkan."><?php echo h($form['saran']); ?></textarea></div>
          <div class="field wide captcha-row"><div><label for="captcha">Keamanan: berapa <?php echo h($captchaQuestion); ?>? <span class="required">*</span></label><p class="hint">Jawab penjumlahan sederhana untuk mengirim survei.</p></div><input id="captcha" type="text" name="captcha" inputmode="numeric" pattern="[0-9]*" maxlength="2" required autocomplete="off" placeholder="Jawaban"></div>
        </div>
        <div class="submit-row"><button class="btn" type="submit">Kirim jawaban survei</button><span class="small">Masukan akan ditinjau oleh pengelola desa.</span></div>
      </form>
    </div>
  </section>
</main>
</body>
</html>

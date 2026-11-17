<?php
require_once __DIR__ . '/config.php';
$pdo = db();

$judul = get_setting($pdo, 'judul', 'Master Plan Desa');
$subjudul = get_setting($pdo, 'subjudul', '');
$lokasi = get_setting($pdo, 'lokasi', '');
$periode = get_setting($pdo, 'periode', '');
$versi = get_setting($pdo, 'versi', '');
$slogan = get_setting($pdo, 'slogan', '');
$visi = get_setting($pdo, 'visi', '');
$prinsip = get_setting($pdo, 'prinsip', '');
$tujuan = get_setting($pdo, 'tujuan', '');

$baseline = $pdo->query('SELECT * FROM baseline ORDER BY urutan,id')->fetchAll();
$baseline_tasks = $pdo->query('SELECT * FROM baseline_tasks ORDER BY urutan,id')->fetchAll();
$pillars = $pdo->query('SELECT * FROM pillars ORDER BY urutan,id')->fetchAll();
$roadmap = $pdo->query('SELECT * FROM roadmap ORDER BY urutan,id')->fetchAll();
$year1 = $pdo->query('SELECT * FROM year1_months ORDER BY urutan,id')->fetchAll();
$routines = $pdo->query('SELECT * FROM routines ORDER BY urutan,id')->fetchAll();
$quarters = $pdo->query('SELECT * FROM quarterly ORDER BY urutan,id')->fetchAll();
$semesters = $pdo->query('SELECT * FROM semester_eval ORDER BY urutan,id')->fetchAll();
$monitoring = $pdo->query('SELECT * FROM monitoring ORDER BY id ASC')->fetchAll();
$budgets = $pdo->query('SELECT * FROM budget_notes ORDER BY kategori,urutan,id')->fetchAll();
$checklist = $pdo->query('SELECT * FROM checklist ORDER BY tahap,urutan,id')->fetchAll();
$notes = $pdo->query('SELECT * FROM notes ORDER BY urutan,id')->fetchAll();

$fb_msg = ''; $fb_err = '';
$expected_captcha = $_SESSION['captcha_answer'] ?? null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'feedback') {
    $nama = trim((string)($_POST['nama'] ?? ''));
    $kontak = trim((string)($_POST['kontak'] ?? ''));
    $jenis = trim((string)($_POST['jenis'] ?? 'Saran'));
    $pesan = trim((string)($_POST['pesan'] ?? ''));
    $captcha = trim((string)($_POST['captcha'] ?? ''));
    $allowed = ['Kritik', 'Saran', 'Lainnya'];
    if (!in_array($jenis, $allowed, true)) $jenis = 'Saran';
    if ($pesan === '') {
        $fb_err = 'Pesan kritik/saran tidak boleh kosong.';
    } elseif ($expected_captcha === null || $captcha === '' || !ctype_digit($captcha) || (int)$captcha !== (int)$expected_captcha) {
        $fb_err = 'Jawaban captcha penjumlahan salah. Silakan coba lagi.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO feedback (nama, kontak, jenis, pesan, status) VALUES (?,?,?,?,?)');
        $stmt->execute([mb_substr($nama,0,100), mb_substr($kontak,0,100), $jenis, $pesan, 'Baru']);
        unset($_SESSION['captcha_answer']);
        $self = strtok($_SERVER['REQUEST_URI'] ?? 'index.php', '?') ?: 'index.php';
        header('Location: ' . $self . '?sent=1#kritik-saran');
        exit;
    }
}
if (isset($_GET['sent'])) $fb_msg = 'Terima kasih, kritik/saran Anda telah dikirim.';
$ca = rand(1, 9); $cb = rand(1, 9);
$_SESSION['captcha_answer'] = $ca + $cb;
$captcha_q = $ca . ' + ' . $cb;

$checkTotal = count($checklist);
$checkDone = 0;
foreach ($checklist as $r) { if ((int)$r['selesai'] === 1) $checkDone++; }
$checkPct = $checkTotal > 0 ? (int)round($checkDone * 100 / $checkTotal) : 0;
$baselineIsVerified = count(array_filter($baseline, static function (array $row): bool {
    $description = strtolower((string)($row['keterangan'] ?? ''));
    return str_contains($description, 'verifikasi') || str_contains($description, 'tervalidasi');
})) === count($baseline) && count($baseline) > 0;
$checklistStarted = $checkDone > 0;
$monitorStarted = count(array_filter($monitoring, static function (array $row): bool {
    return !str_starts_with((string)($row['status'] ?? ''), 'Belum dinilai');
})) > 0;

$groupedChecklist = [];
foreach ($checklist as $r) { $groupedChecklist[$r['tahap']][] = $r; }

$statusCounts = [];
foreach ($monitoring as $m) {
    $s = trim((string)($m['status'] ?? '')) ?: 'Belum dinilai';
    $statusCounts[$s] = ($statusCounts[$s] ?? 0) + 1;
}
$monitorTotal = count($monitoring);
$monitorDone = 0; $monitorWarn = 0; $monitorBad = 0; $monitorOk = 0;
foreach ($monitoring as $m) {
    $s = (string)($m['status'] ?? '');
    if (str_starts_with($s, 'Biru')) $monitorDone++;
    elseif (str_starts_with($s, 'Hijau')) $monitorOk++;
    elseif (str_starts_with($s, 'Kuning')) $monitorWarn++;
    elseif (str_starts_with($s, 'Merah')) $monitorBad++;
}

$groupedBudget = [];
foreach ($budgets as $b) { $groupedBudget[$b['kategori']][] = $b; }

$groupedBaselineTasks = [];
foreach ($baseline_tasks as $t) { $groupedBaselineTasks[$t['kelompok']][] = $t; }

$pillarVisuals = [
    '1' => ['src' => 'assets/program/pertanian.webp', 'alt' => 'Ilustrasi petani dan sawah untuk program pertanian'],
    '2' => ['src' => 'assets/program/pembangunan.webp', 'alt' => 'Ilustrasi warga bermusyawarah tentang pembangunan desa'],
    '3' => ['src' => 'assets/program/pemuda.webp', 'alt' => 'Ilustrasi pemuda desa berolahraga'],
    '4' => ['src' => 'assets/program/pendidikan.webp', 'alt' => 'Ilustrasi pelajar desa menuju sekolah'],
    '5' => ['src' => 'assets/program/digital.webp', 'alt' => 'Ilustrasi warga menggunakan layanan digital desa'],
    '6' => ['src' => 'assets/program/mobil-siaga.webp', 'alt' => 'Ilustrasi layanan mobil siaga kesehatan'],
];

function status_class(string $s): string {
    if (str_starts_with($s, 'Hijau')) return 'st-green';
    if (str_starts_with($s, 'Kuning')) return 'st-yellow';
    if (str_starts_with($s, 'Merah')) return 'st-red';
    if (str_starts_with($s, 'Biru')) return 'st-blue';
    return 'st-gray';
}
function nl_to_list(?string $text): array {
    $text = trim((string)$text);
    if ($text === '') return [];
    $parts = preg_split('/\r?\n|;/', $text);
    $out = [];
    foreach ($parts as $p) { $p = trim($p, " \t\n\r\0\x0B-•"); if ($p !== '') $out[] = $p; }
    return $out;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo h($judul); ?> - Progress Publik</title>
<style>
:root{--green:#176b45;--green2:#21845a;--mint:#eaf5ee;--ink:#18251f;--muted:#65736b;--line:#dce6df;--bg:#f4f7f4;--white:#fff;--gold:#c18a2d;--blue:#286b9b;--red:#b94c42}
*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;font:15px/1.6 system-ui,-apple-system,"Segoe UI",Arial,sans-serif;background:var(--bg);color:var(--ink)}
a{color:var(--green)}.wrap{max-width:1180px;margin:auto;padding:0 20px}
.hero{background:linear-gradient(120deg,#103f2c,#176b45 58%,#2b8b61);color:#fff;padding:44px 0 34px}
.eyebrow{text-transform:uppercase;letter-spacing:.14em;font-size:11px;font-weight:800;color:#c8e6d3}
.hero h1{margin:10px 0;font-size:clamp(30px,5vw,50px);line-height:1.1;letter-spacing:-.03em}
.hero p{margin:8px 0;color:#e0eee5;max-width:760px;font-size:16px}
.meta{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}.chip{border:1px solid #ffffff55;border-radius:99px;padding:5px 11px;font-size:12px;color:#f0faf3}
nav{position:sticky;top:0;z-index:20;background:#ffffffee;backdrop-filter:blur(10px);border-bottom:1px solid var(--line)}
.navinner{display:flex;gap:4px;overflow:auto;padding:8px 0;white-space:nowrap}
nav a{text-decoration:none;font-size:12px;font-weight:700;color:#52645a;padding:7px 10px;border-radius:8px}nav a:hover{background:var(--mint);color:var(--green)}
main{padding:26px 0 50px}.section{margin-bottom:30px;scroll-margin-top:70px}
.kicker{font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:var(--green2);font-weight:800}
h2{margin:4px 0 8px;font-size:24px;letter-spacing:-.02em}h3{margin:0 0 8px;font-size:16px}
.lead{color:var(--muted);margin:0 0 12px}
.grid{display:grid;grid-template-columns:repeat(12,1fr);gap:12px}
.card{background:var(--white);border:1px solid var(--line);border-radius:14px;padding:16px}
.c3{grid-column:span 3}.c4{grid-column:span 4}.c6{grid-column:span 6}.c8{grid-column:span 8}.c12{grid-column:span 12}
.stat{font-size:30px;font-weight:850;color:var(--green);letter-spacing:-.03em}.label{font-size:12px;color:var(--muted)}
.muted{color:var(--muted);font-size:13px}
.progress{height:12px;background:#e6ece7;border-radius:99px;overflow:hidden;margin:8px 0}.bar{height:100%;background:linear-gradient(90deg,#176b45,#2b8b61)}
.progress.sm{height:8px;margin:6px 0}
.tablewrap{overflow:auto;border:1px solid var(--line);border-radius:12px;background:#fff}
table{width:100%;border-collapse:collapse;min-width:680px;font-size:13px}th,td{padding:10px;border-top:1px solid #e8eee9;text-align:left;vertical-align:top}
th{background:#edf4ef;color:#345b45;font-size:11px;text-transform:uppercase;letter-spacing:.05em;border-top:0}
.badge{display:inline-block;padding:3px 8px;border-radius:99px;font-size:11px;font-weight:700;white-space:nowrap}
.st-green{background:#e7f5ec;color:#176b45}.st-yellow{background:#fbf1df;color:#8a601b}.st-red{background:#f9e9e7;color:#a33e35}.st-blue{background:#e8f1f8;color:#286b9b}.st-gray{background:#edf0ee;color:#58645d}
ul.clean{margin:6px 0 0;padding-left:18px}.clean li{margin:4px 0;font-size:13px}
.pill{display:flex;gap:10px;align-items:flex-start}.ico{width:34px;height:34px;border-radius:10px;background:var(--mint);display:grid;place-items:center;font-weight:800;color:var(--green);flex:0 0 34px}
.flow{display:flex;flex-wrap:wrap;gap:8px;align-items:center}.flow span{background:#edf5ef;border:1px solid #d5e7da;border-radius:8px;padding:6px 10px;font-size:12px;font-weight:700}.flow i{font-style:normal;color:var(--green);font-weight:900}
.timeline{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px}
.phase{border:1px solid var(--line);border-top:4px solid var(--green2);border-radius:0 0 12px 12px;padding:14px;background:#fff}
.phase .period{font-size:11px;color:var(--muted);font-weight:800;text-transform:uppercase}
.months{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px}
.month{background:#fff;border:1px solid var(--line);border-radius:12px;padding:12px}.month b{display:block;color:var(--green);margin-bottom:4px}
.check-item{display:flex;gap:8px;align-items:flex-start;padding:6px 0;border-bottom:1px solid #eef3ef;font-size:13px}
.check-item:last-child{border-bottom:0}.mark{font-weight:800;width:18px;flex:0 0 18px}.done{color:#176b45}.todo{color:#9aa7a0}
.callout{background:#edf5ef;border-left:4px solid var(--green2);padding:14px 16px;border-radius:0 12px 12px 0;color:#315542}
.topbar{display:flex;justify-content:space-between;align-items:center;gap:10px;margin:10px 0 18px}
.btn{display:inline-block;background:#176b45;color:#fff;text-decoration:none;padding:8px 13px;border-radius:8px;font-weight:700;font-size:13px}.btn.sec{background:#e8f1eb;color:#176b45}
.note-box{background:#fff;border:1px solid var(--line);border-radius:12px;padding:14px;margin-bottom:10px}
.form-group{margin-bottom:10px}label.f{display:block;font-weight:700;font-size:13px;margin-bottom:4px}
input[type=text],input[type=email],select,textarea{width:100%;padding:9px 10px;border:1px solid #ccd9cf;border-radius:8px;font:inherit;background:#fff}
textarea{min-height:110px;resize:vertical}
.alert{padding:10px 12px;border-radius:8px;margin-bottom:12px;font-size:14px}.alert.ok{background:#e7f5ec;color:#1b5e3b;border:1px solid #cfe8d8}.alert.err{background:#fdeceb;color:#8a332d;border:1px solid #f3c9c5}
@media(max-width:900px){.c3,.c4{grid-column:span 6}.c8,.c6{grid-column:span 12}}
@media(max-width:560px){.c3,.c4,.c6,.c8,.c12{grid-column:span 12}.wrap{padding:0 14px}.hero{padding:34px 0 26px}}
.hero{position:relative;overflow:hidden}.hero:after{content:"";position:absolute;width:320px;height:320px;border:1px solid #ffffff22;border-radius:50%;right:-60px;top:-150px;box-shadow:0 0 0 34px #ffffff0d,0 0 0 70px #ffffff08;pointer-events:none}.hero .wrap{position:relative;z-index:1}
.navinner{scrollbar-width:thin}.navinner a{flex:none}.navinner a:first-child,.navinner a:nth-child(2){background:var(--mint);color:var(--green)}.navinner a:hover,.navinner a:focus-visible{outline:2px solid #8fbba0;outline-offset:1px}
.intro{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(220px,.65fr);gap:14px;margin:18px 0 28px}.intro-main{background:#fff;border:1px solid var(--line);border-radius:16px;padding:20px}.intro-main h2{font-size:22px}.intro-side{background:#e7f2e9;border-radius:16px;padding:18px;color:#244b35}.intro-side b{display:block;margin-bottom:6px}.intro-side p{margin:0;font-size:13px}
.section-head{display:flex;justify-content:space-between;align-items:end;gap:16px;margin-bottom:12px}.section-head .lead{margin:0;max-width:560px}
.metric{position:relative;overflow:hidden}.metric:after{content:"";position:absolute;width:90px;height:90px;border-radius:50%;background:#eaf5ee;right:-28px;top:-35px}.metric .label,.metric .stat,.metric .progress,.metric .muted{position:relative;z-index:1}.metric .stat{font-size:34px}.metric.featured{background:linear-gradient(135deg,#eaf5ee,#fff)}
.status-explain{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;margin-top:10px}.status-key{padding:10px;border:1px solid var(--line);border-radius:10px;background:#fff;font-size:12px}.status-key b{display:block;margin-bottom:3px}
.filters{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin:0 0 12px}.filters button{appearance:none;border:1px solid var(--line);border-radius:99px;background:#fff;color:#42564a;padding:7px 12px;font:inherit;font-size:12px;font-weight:700;cursor:pointer}.filters button[aria-pressed="true"]{background:#176b45;border-color:#176b45;color:#fff}.filters input{max-width:270px;margin-left:auto}.program-card{grid-column:span 4;display:flex;flex-direction:column;gap:10px;transition:transform .18s ease,box-shadow .18s ease}.program-card:hover{transform:translateY(-2px);box-shadow:0 12px 28px #193c2812}.program-top{display:flex;align-items:flex-start;gap:10px}.program-top .ico{width:40px;height:40px;flex-basis:40px;font-size:16px}.program-desc{color:#52645a;font-size:13px}.program-outcome{margin-top:auto;padding-top:10px;border-top:1px solid #e8eee9}.program-outcome b{display:block;color:#176b45;font-size:11px;text-transform:uppercase;letter-spacing:.06em}.status-note{background:#fff7e7;border:1px solid #f0dfb8;color:#72531d;border-radius:12px;padding:12px 14px;font-size:13px}
.checklist-summary{display:flex;gap:16px;align-items:center}.checklist-ring{width:84px;height:84px;border-radius:50%;display:grid;place-items:center;flex:0 0 84px;background:conic-gradient(var(--green) calc(var(--pct)*1%),#e6ece7 0);position:relative}.checklist-ring:after{content:"";position:absolute;inset:8px;border-radius:50%;background:#fff}.checklist-ring b{position:relative;z-index:1;color:var(--green);font-size:19px}.feedback-card{background:linear-gradient(145deg,#fff,#f5faf6)}
.simple-intro{margin:18px 0 20px}.simple-intro .intro-main{padding:22px}.simple-intro .intro-main h2{margin-top:6px}.summary-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.summary-grid .card{min-height:142px}.quick-links{display:flex;gap:8px;flex-wrap:wrap;margin:14px 0}.quick-links a{font-size:13px}.detail-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.detail-card{background:#fff;border:1px solid var(--line);border-radius:16px;overflow:hidden}.detail-card summary{cursor:pointer;list-style:none;padding:17px;display:flex;align-items:center;justify-content:space-between;gap:12px;font-weight:800;color:#234b36;background:linear-gradient(120deg,#fff,#f4f9f5)}.detail-card summary::-webkit-details-marker{display:none}.detail-card summary:after{content:"Lihat";padding:6px 10px;border-radius:99px;background:#eaf5ee;color:var(--green);font-size:11px;flex:0 0 auto}.detail-card[open] summary:after{content:"Tutup"}.detail-body{padding:16px}.detail-body .section{margin:0;padding-top:0}.detail-body .section>h2{font-size:20px}.detail-body .lead{margin-bottom:10px}.detail-body .card{margin-bottom:8px}.detail-body .timeline,.detail-body .months{grid-template-columns:repeat(auto-fill,minmax(190px,1fr))}.detail-body .tablewrap{margin-top:8px}.detail-body .filters{margin-top:10px}.detail-list .wide{grid-column:1/-1}.detail-card[open] summary{border-bottom:1px solid #e8eee9}.small-note{font-size:12px;color:var(--muted);margin:0}
.roadmap-visual{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin:16px 0}.roadmap-step{background:#fff;border:1px solid var(--line);border-radius:14px;padding:14px;position:relative;overflow:hidden}.roadmap-step:before{content:"";position:absolute;left:0;right:0;top:0;height:4px;background:linear-gradient(90deg,#176b45,#9fca76)}.roadmap-step .num{font-size:11px;font-weight:800;color:var(--green)}.roadmap-step b{display:block;margin:4px 0}.roadmap-step span{font-size:12px;color:var(--muted)}.program-card{cursor:pointer;border-top:4px solid #71aa65}.program-card:nth-child(3n+2){border-top-color:#e2b44e}.program-card:nth-child(3n){border-top-color:#69a8a0}.program-open{margin-top:auto;color:var(--green);font-size:12px;font-weight:800}.detail-modal{position:fixed;inset:0;z-index:80;background:#10281dcc;display:none;align-items:center;justify-content:center;padding:20px}.detail-modal[aria-hidden="false"]{display:flex}.modal-panel{background:#f7faf7;border-radius:20px;width:min(900px,100%);max-height:min(86vh,900px);overflow:auto;box-shadow:0 25px 80px #07190f66}.modal-head{position:sticky;top:0;background:#fff;border-bottom:1px solid var(--line);padding:16px 20px;display:flex;justify-content:space-between;align-items:center;gap:12px;z-index:2}.modal-head h2{margin:0;font-size:20px}.modal-close{border:0;border-radius:50%;width:38px;height:38px;background:#eaf5ee;color:var(--green);font-size:22px;cursor:pointer}.modal-content{padding:20px}.modal-content .section{margin-bottom:20px}.info-links{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}.info-tile{border:1px solid var(--line);border-radius:14px;background:#fff;padding:16px}.info-tile b{display:block;margin-bottom:5px;color:#234b36}.info-tile span{font-size:12px;color:var(--muted)}
.program-card{padding:0;overflow:hidden;gap:0}.program-card img{display:block;width:100%;height:auto;aspect-ratio:auto;object-fit:contain;background:#fff}.program-copy{padding:12px 15px 15px;display:flex;flex-direction:column;gap:7px;flex:1}
@media(max-width:700px){.intro{grid-template-columns:1fr}.status-explain{grid-template-columns:repeat(2,minmax(0,1fr))}.program-card{grid-column:span 6}.section-head{align-items:flex-start;flex-direction:column}.filters input{max-width:none;width:100%;margin-left:0}}
@media(max-width:900px){.roadmap-visual{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:700px){.summary-grid{grid-template-columns:1fr}.detail-list{grid-template-columns:1fr}.summary-grid .card{min-height:unset}.info-links{grid-template-columns:1fr}.modal-content{padding:14px}.detail-modal{padding:8px}}
@media(max-width:560px){.program-card{grid-column:span 12}.checklist-summary{align-items:flex-start}}
@media(max-width:640px){
  html,body{max-width:100%;overflow-x:hidden}
  .wrap{width:100%;padding-left:14px;padding-right:14px}
  .hero{padding:28px 0 22px}.hero h1{font-size:clamp(28px,9vw,38px);line-height:1.08}.hero p{font-size:15px}
  .eyebrow{font-size:10px;letter-spacing:.1em}.meta{gap:6px}.chip{font-size:11px;padding:5px 9px}
  .navinner{padding:7px 0;gap:6px;overscroll-behavior-x:contain;-webkit-overflow-scrolling:touch}.navinner a{padding:10px 11px;min-height:42px;display:flex;align-items:center}
  main{padding-top:16px}.topbar{align-items:stretch;flex-direction:column;margin:4px 0 14px}.topbar>div:last-child{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px!important}.topbar .btn{text-align:center;min-height:44px;display:flex;align-items:center;justify-content:center}
  .simple-intro .intro-main{padding:18px}.intro-main h2{font-size:21px}.lead{font-size:14px}
  .grid{grid-template-columns:repeat(12,minmax(0,1fr));gap:9px}.grid>*{min-width:0}.c3,.c4,.c6,.c8,.c12{grid-column:span 12}
  .summary-grid{gap:8px}.summary-grid .card{padding:14px;min-height:0}.summary-grid .stat{font-size:25px!important}
  h2{font-size:21px}.section-head{gap:4px}.roadmap-visual{grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.roadmap-step{padding:12px}.roadmap-step b{font-size:14px}.roadmap-step span{font-size:11px}
  .program-card{grid-column:span 12!important;min-height:108px;padding:0!important;border-radius:14px}.program-card h3{font-size:16px}.program-desc{font-size:13px}
  .detail-list{grid-template-columns:minmax(0,1fr);gap:8px}.detail-card summary{min-height:56px;padding:13px 14px;font-size:14px}.detail-card summary:after{font-size:10px}
  .detail-modal{padding:8px;align-items:flex-end}.modal-panel{width:100%;max-height:92vh;max-height:92dvh;border-radius:20px 20px 12px 12px}.modal-head{padding:12px 16px}.modal-content{padding:16px}.modal-content h3{font-size:18px}
  input[type=text],input[type=email],input[type=search],select,textarea{font-size:16px;padding:11px 12px}.btn{min-height:44px;display:inline-flex;align-items:center;justify-content:center}
  .tablewrap{max-width:100%}.tablewrap table{min-width:620px}.phase,.month{padding:12px}
}
@media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}*,*:before,*:after{animation-duration:.01ms!important;transition-duration:.01ms!important}}
@media print{nav,.topbar,.filters,.btn,form{display:none!important}.hero{padding:20px 0}.section{break-inside:avoid}.card{box-shadow:none}}
</style>
</head>
<body>
<header class="hero"><div class="wrap">
  <div class="eyebrow">Rencana Desa · Ringkasan untuk Warga</div>
  <h1><?php echo h($judul); ?></h1>
  <?php if ($subjudul): ?><p><?php echo h($subjudul); ?></p><?php endif; ?>
  <?php if ($slogan): ?><p><b><?php echo h($slogan); ?></b></p><?php endif; ?>
  <div class="meta">
    <?php if ($lokasi): ?><span class="chip"><?php echo h($lokasi); ?></span><?php endif; ?>
    <?php if ($periode): ?><span class="chip">Periode: <?php echo h($periode); ?></span><?php endif; ?>
    <?php if ($versi): ?><span class="chip"><?php echo h($versi); ?></span><?php endif; ?>
    <span class="chip">Bahan rencana · perlu dibahas bersama</span>
  </div>
</div></header>

<nav><div class="wrap navinner">
  <a href="#ringkasan">Ringkasan</a><a href="#detail-ringkas">Program</a><a href="#detail-tahapan">Tahapan</a><a href="#detail-data">Data desa</a><a href="#detail-masukan">Sampaikan usul</a>
</div></nav>

<main class="wrap">
  <div class="topbar">
    <div class="muted">Kenali arah rencana, lihat hal yang sudah tercatat, lalu sampaikan usul untuk musyawarah.</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <a class="btn sec" href="infografis.php">Infografis</a>
      <a class="btn sec" href="laporan.php">Laporan Progress</a>
      <a class="btn" href="login.php">Login Admin</a>
    </div>
  </div>

  <section class="intro simple-intro" id="ringkasan" aria-label="Ringkasan rencana"><div class="intro-main"><div class="kicker">Rencana desa · versi ringkas</div><h2>Apa yang ingin diperbaiki?</h2><p class="lead">Rencana ini mengusulkan perbaikan pertanian, pembangunan yang merata, dukungan untuk pemuda dan pendidikan, serta pelayanan desa yang lebih mudah. Pilih bagian di bawah jika ingin membaca rinciannya.</p><p class="small-note">Ini bahan rencana, bukan janji anggaran atau dokumen yang sudah disahkan.</p><div class="quick-links"><a class="btn" href="#detail-ringkas">Lihat bidang program</a><a class="btn sec" href="#detail-masukan">Sampaikan usul</a></div></div></section>

  <section class="section" id="statistik">
    <div class="section-head"><div><div class="kicker">Gambaran singkat</div><h2>Ringkasan rencana</h2></div></div>
    <div class="grid summary-grid">
      <div class="card c4 metric"><div class="label">Fokus rencana</div><div class="stat"><?php echo count($pillars); ?> bidang</div><div class="muted">Usulan yang bisa dibahas bersama warga.</div></div>
      <div class="card c4 metric"><div class="label">Persiapan</div><div class="stat" style="font-size:24px"><?php echo $checklistStarted ? $checkDone . ' langkah' : 'Belum dimulai'; ?></div><div class="muted">Dari <?php echo $checkTotal; ?> langkah awal yang tercatat.</div></div>
      <div class="card c4 metric"><div class="label">Pelaksanaan</div><div class="stat" style="font-size:24px"><?php echo $monitorStarted ? 'Ada pembaruan' : 'Belum dinilai'; ?></div><div class="muted">Status perlu diperbarui setelah kegiatan diverifikasi.</div></div>
    </div>
  </section>

  <section class="section" id="detail-ringkas"><div class="section-head"><div><div class="kicker">Peta rencana</div><h2>Perjalanan menuju desa yang lebih kuat</h2></div><p class="lead">Pilih kartu untuk melihat hal yang direncanakan pada tiap bidang.</p></div><div class="roadmap-visual"><div class="roadmap-step"><span class="num">TAHAP 1 · AWALI</span><b>Dengar & petakan</b><span>Kenali kebutuhan warga dan kondisi tiap dusun.</span></div><div class="roadmap-step"><span class="num">TAHAP 2 · SIAPKAN</span><b>Susun prioritas</b><span>Pilih usulan bersama dan siapkan langkahnya.</span></div><div class="roadmap-step"><span class="num">TAHAP 3 · KERJAKAN</span><b>Jalankan bertahap</b><span>Laksanakan program sesuai hasil musyawarah.</span></div><div class="roadmap-step"><span class="num">TAHAP 4 · TINJAU</span><b>Lihat hasil</b><span>Periksa manfaat dan perbaiki yang belum berjalan.</span></div></div>
    <div class="section-head" style="margin-top:26px"><div><div class="kicker">Pilih bidang</div><h2>Rencana yang dekat dengan kebutuhan warga</h2></div></div>
    <div class="grid">
      <?php foreach ($pillars as $p): $inds = nl_to_list($p['indikator'] ?? ''); ?>
      <button type="button" class="card program-card" data-title="<?php echo h($p['nama']); ?>" data-description="<?php echo h($p['deskripsi']); ?>" data-indicators="<?php echo h(json_encode($inds, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT)); ?>" style="text-align:left;font:inherit"><?php if (isset($pillarVisuals[(string)$p['kode']])): ?><img src="<?php echo h($pillarVisuals[(string)$p['kode']]['src']); ?>" alt="<?php echo h($pillarVisuals[(string)$p['kode']]['alt']); ?>" loading="lazy" decoding="async" width="900" height="818"><?php endif; ?><span class="program-copy"><span class="program-top"><span class="ico"><?php echo h($p['kode'] ?: '•'); ?></span><span><span class="label">Bidang <?php echo h((string)$p['kode']); ?></span><h3><?php echo h($p['nama']); ?></h3></span></span><span class="program-desc"><?php echo h(mb_strimwidth($p['deskripsi'], 0, 105, '…', 'UTF-8')); ?></span><span class="program-open">Lihat rencana →</span></span></button>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="section" aria-labelledby="detail-heading"><div class="section-head"><div><div class="kicker">Informasi tambahan</div><h2 id="detail-heading">Jelajahi rincian bila diperlukan</h2></div><p class="lead">Informasi dikelompokkan agar mudah dicari.</p></div>
    <div class="detail-list">

  <details class="detail-card wide" id="detail-visi"><summary>Arah dan tujuan desa</summary><div class="detail-body"><section class="section" id="arah">
    <div class="kicker">01 · Tujuan bersama</div><h2>Arah pembangunan desa</h2>
    <div class="grid">
      <?php if ($visi): ?><div class="card c8"><h3>Visi Operasional</h3><p style="font-size:16px;color:#294b38;margin:0"><?php echo nl2br(h($visi)); ?></p></div><?php endif; ?>
      <div class="card c4">
        <h3>Tujuan Utama</h3>
        <?php $tgs = nl_to_list($tujuan); if ($tgs): ?><ul class="clean"><?php foreach ($tgs as $t): ?><li><?php echo h($t); ?></li><?php endforeach; ?></ul><?php else: ?><div class="muted">Belum diisi.</div><?php endif; ?>
      </div>
      <div class="card c12">
        <h3>Prinsip Pelaksanaan</h3>
        <?php $prs = nl_to_list($prinsip); if ($prs): ?><div class="flow"><?php foreach ($prs as $i=>$p): if($i>0): ?><i>→</i><?php endif; ?><span><?php echo h($p); ?></span><?php endforeach; ?></div><?php else: ?><div class="muted">Belum diisi.</div><?php endif; ?>
      </div>
    </div>
  </section></div></details>

  <details class="detail-card" id="detail-data"><summary>Data kondisi desa</summary><div class="detail-body"><section class="section" id="baseline">
    <div class="kicker">02 · Mengenali kondisi desa</div><h2>Data yang menjadi titik awal</h2>
    <p class="lead">Angka historis membantu melihat gambaran awal. Pastikan tahun dan sumber data selalu terbaca, lalu perbarui bersama data resmi terbaru.</p>
    <?php if (!$baselineIsVerified): ?><div class="status-note" style="margin-bottom:12px"><b>Perlu diperiksa kembali.</b> Data awal di bawah masih mencantumkan riwayat lama atau belum menunjukkan tanda validasi. Gunakan sebagai petunjuk untuk pendataan, bukan angka terkini.</div><?php endif; ?>
    <?php if ($baseline): ?><div class="grid" style="margin-bottom:12px">
      <?php foreach ($baseline as $b): ?><div class="card c3"><div class="label"><?php echo h($b['label']); ?></div><div class="stat" style="font-size:26px"><?php echo h($b['nilai']); ?></div><div class="muted"><?php echo h($b['keterangan']); ?></div></div><?php endforeach; ?>
    </div><?php endif; ?>
    <?php if ($groupedBaselineTasks): ?><div class="card"><h3>Data yang Harus Diverifikasi</h3><div class="grid">
      <?php foreach ($groupedBaselineTasks as $kel => $items): ?><div class="c4"><b style="font-size:13px;color:#345b45"><?php echo h($kel); ?></b><ul class="clean"><?php foreach ($items as $it): ?><li><?php echo h($it['item']); ?></li><?php endforeach; ?></ul></div><?php endforeach; ?>
    </div></div><?php endif; ?>
  </section></div></details>

  <?php if ($roadmap): ?>
  <details class="detail-card" id="detail-tahapan"><summary>Tahapan rencana beberapa tahun</summary><div class="detail-body"><section class="section" id="roadmap">
    <div class="kicker">04 · Urutan perubahan</div><h2>Tahapan rencana beberapa tahun</h2><p class="lead">Setiap tahap menyiapkan langkah berikutnya. Waktu pelaksanaan tetap menyesuaikan hasil musyawarah dan dokumen resmi.</p>
    <div class="timeline">
      <?php foreach ($roadmap as $r): $pts = nl_to_list($r['poin'] ?? ''); ?>
        <div class="phase"><div class="period"><?php echo h($r['periode']); ?></div><h3><?php echo h($r['judul']); ?></h3><?php if ($pts): ?><ul class="clean"><?php foreach ($pts as $pt): ?><li><?php echo h($pt); ?></li><?php endforeach; ?></ul><?php endif; ?></div>
      <?php endforeach; ?>
    </div>
  </section></div></details>
  <?php endif; ?>

  <?php if ($year1 || $routines): ?>
  <details class="detail-card" id="detail-tahun1"><summary>Rencana tahun pertama</summary><div class="detail-body"><section class="section" id="tahun1">
    <div class="kicker">05 · Mulai dari langkah dekat</div><h2>Rencana tahun pertama</h2>
    <?php if ($year1): ?><h3>Kalender 12 Bulan</h3><div class="months" style="margin-bottom:14px"><?php foreach ($year1 as $m): ?><div class="month"><b><?php echo h($m['bulan']); ?> · <?php echo h($m['judul']); ?></b><div class="muted"><?php echo nl2br(h($m['kegiatan'])); ?></div></div><?php endforeach; ?></div><?php endif; ?>
    <?php if ($routines): ?><div class="card"><h3>Agenda Rutin</h3><div class="tablewrap"><table><thead><tr><th>Kegiatan</th><th>Frekuensi</th><th>Bukti Minimal</th></tr></thead><tbody><?php foreach ($routines as $r): ?><tr><td><?php echo h($r['kegiatan']); ?></td><td><?php echo h($r['frekuensi']); ?></td><td><?php echo h($r['bukti']); ?></td></tr><?php endforeach; ?></tbody></table></div></div><?php endif; ?>
  </section></div></details>
  <?php endif; ?>

  <?php if ($quarters): ?>
  <details class="detail-card" id="detail-kuartal"><summary>Target per tiga bulan</summary><div class="detail-body"><section class="section" id="kuartal">
    <div class="kicker">06 · Pemeriksaan tiap tiga bulan</div><h2>Apa yang ingin dicapai tiap tahap</h2>
    <div class="grid">
      <?php foreach ($quarters as $q): $tg = nl_to_list($q['target'] ?? ''); ?>
        <div class="card c6"><div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px"><h3 style="margin:0"><?php echo h($q['kuartal']); ?></h3><span class="badge st-blue"><?php echo h($q['fokus']); ?></span></div>
        <?php if ($tg): ?><ul class="clean"><?php foreach ($tg as $t): ?><li><?php echo h($t); ?></li><?php endforeach; ?></ul><?php endif; ?>
        <?php if (!empty($q['output'])): ?><div class="muted" style="margin-top:8px;border-top:1px solid #e8eee9;padding-top:6px"><b>Output:</b> <?php echo h($q['output']); ?></div><?php endif; ?></div>
      <?php endforeach; ?>
    </div>
  </section></div></details>
  <?php endif; ?>

  <?php if ($semesters): ?>
  <details class="detail-card" id="detail-semester"><summary>Evaluasi per semester</summary><div class="detail-body"><section class="section" id="semester">
    <div class="kicker">07 / Evaluasi</div><h2>Evaluasi Semester per Bidang</h2>
    <div class="card"><div class="tablewrap"><table><thead><tr><th>Bidang</th><th>Semester 1</th><th>Semester 2</th></tr></thead><tbody><?php foreach ($semesters as $s): ?><tr><td><b><?php echo h($s['bidang']); ?></b></td><td><?php echo nl2br(h($s['semester1'])); ?></td><td><?php echo nl2br(h($s['semester2'])); ?></td></tr><?php endforeach; ?></tbody></table></div></div>
  </section></div></details>
  <?php endif; ?>

  <details class="detail-card" id="detail-monitoring"><summary>Status kegiatan dan penjelasan warna</summary><div class="detail-body"><section class="section" id="monitoring">
    <div class="kicker">07 · Kabar pelaksanaan</div><h2>Status kegiatan yang tercatat</h2>
    <p class="lead">Daftar ini menunjukkan tahap dan arah kerja, bukan janji tanggal selesai. Jika data progres belum masuk, status akan tertulis belum dinilai.</p>
    <?php if ($monitoring): ?>
    <?php if (!$monitorStarted): ?><div class="status-note" style="margin-bottom:12px"><b>Belum ada laporan progres yang diverifikasi.</b> Semua status masih berupa penanda awal. Tanyakan kepada pemerintah desa tentang pembaruan hasil musyawarah dan laporan berkala.</div><?php endif; ?>
    <div class="tablewrap"><table>
      <thead><tr><th>Kegiatan</th><th>Hal yang dituju</th><th>Status</th></tr></thead>
      <tbody><?php foreach ($monitoring as $m): ?><tr>
        <td><?php echo h($m['program']); ?></td>
        <td><?php echo h($m['target']); ?></td>
        <td><span class="badge <?php echo status_class($m['status'] ?? ''); ?>"><?php echo h($m['status'] ?: 'Belum dinilai'); ?></span></td>
      </tr><?php endforeach; ?></tbody>
    </table></div>
    <?php else: ?><div class="card muted">Belum ada data monitoring.</div><?php endif; ?>
  </section></div></details>

  <?php if ($groupedBudget): ?>
  <details class="detail-card" id="detail-anggaran"><summary>Catatan tentang anggaran</summary><div class="detail-body"><section class="section" id="anggaran">
    <div class="kicker">09 / Disiplin Anggaran</div><h2>Strategi & Catatan Anggaran</h2>
    <div class="grid">
      <?php foreach ($groupedBudget as $kat => $items): ?><div class="card c6"><h3><?php echo h($kat); ?></h3><ul class="clean"><?php foreach ($items as $it): ?><li><?php echo h($it['item']); ?></li><?php endforeach; ?></ul></div><?php endforeach; ?>
    </div>
  </section></div></details>
  <?php endif; ?>

  <details class="detail-card" id="detail-checklist"><summary>Langkah persiapan tiga bulan pertama</summary><div class="detail-body"><section class="section" id="checklist">
    <div class="kicker">08 · Tiga bulan pertama</div><h2>Langkah awal yang perlu disiapkan</h2>
    <p class="lead">Tandai pekerjaan yang sudah selesai melalui admin. Warga dapat memakai daftar ini untuk mengikuti proses persiapan desa.</p>
    <div class="card" style="margin-bottom:12px">
      <div class="checklist-summary"><div class="checklist-ring" style="--pct:<?php echo $checkPct; ?>"><b><?php echo $checklistStarted ? $checkPct.'%' : '0%'; ?></b></div><div><b><?php echo $checklistStarted ? 'Kemajuan persiapan' : 'Persiapan belum ditandai selesai'; ?></b><div class="muted" style="margin-top:5px"><?php echo $checkDone; ?> dari <?php echo $checkTotal; ?> langkah dicatat selesai. Persentase ini mengikuti centang admin.</div></div></div>
    </div>
    <?php if ($groupedChecklist): ?>
      <?php foreach ($groupedChecklist as $tahap => $items): $done = 0; foreach($items as $i){ if((int)$i['selesai']===1) $done++; } $tot = count($items); $pct = $tot? (int)round($done*100/$tot):0; ?>
        <div class="card" style="margin-bottom:10px">
          <h3><?php echo h($tahap); ?> <span class="muted" style="font-weight:400">(<?php echo $done; ?>/<?php echo $tot; ?> · <?php echo $pct; ?>%)</span></h3>
          <div class="progress sm"><div class="bar" style="width:<?php echo $pct; ?>%"></div></div>
          <?php foreach ($items as $it): $ok = (int)$it['selesai'] === 1; ?>
            <div class="check-item"><span class="mark <?php echo $ok?'done':'todo'; ?>"><?php echo $ok ? '✓' : '○'; ?></span><span><?php echo h($it['item']); ?></span></div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    <?php else: ?><div class="card muted">Belum ada item checklist.</div><?php endif; ?>
  </section></div></details>

  <?php if ($notes): ?>
  <details class="detail-card"><summary>Catatan dokumen dan kebijakan</summary><div class="detail-body"><section class="section" id="catatan">
    <div class="kicker">11 / Catatan Kebijakan</div><h2>Dokumen & Referensi</h2>
    <?php foreach ($notes as $n): ?>
      <div class="note-box"><h3><?php echo h($n['judul']); ?></h3><div class="muted" style="color:#425a4c"><?php echo nl2br(h($n['isi'])); ?></div></div>
    <?php endforeach; ?>
  </section></div></details>
  <?php endif; ?>

  <details class="detail-card wide" id="detail-masukan"><summary>Sampaikan usul atau masukan</summary><div class="detail-body"><section class="section" id="kritik-saran">
    <div class="kicker">09 · Suara warga</div><h2>Usul dan masukan untuk desa</h2>
    <div class="grid">
      <div class="card c8 feedback-card">
        <?php if ($fb_msg): ?><div class="alert ok"><?php echo h($fb_msg); ?></div><?php endif; ?>
        <?php if ($fb_err): ?><div class="alert err"><?php echo h($fb_err); ?></div><?php endif; ?>
        <form method="post" action="#kritik-saran">
          <input type="hidden" name="form_type" value="feedback">
          <div class="grid">
            <div class="c6 form-group"><label class="f">Nama (opsional)</label><input type="text" name="nama" maxlength="100" value="<?php echo h($_POST['nama'] ?? ''); ?>"></div>
            <div class="c6 form-group"><label class="f">Kontak (opsional, HP/email)</label><input type="text" name="kontak" maxlength="100" value="<?php echo h($_POST['kontak'] ?? ''); ?>"></div>
            <div class="c12 form-group"><label class="f">Jenis</label>
              <select name="jenis">
                <?php foreach (['Saran','Kritik','Lainnya'] as $j): ?><option <?php echo (($_POST['jenis'] ?? 'Saran')===$j)?'selected':''; ?>><?php echo h($j); ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="c12 form-group"><label class="f">Pesan <span style="color:#b94c42">*</span></label><textarea name="pesan" required maxlength="4000" placeholder="Tulis kritik, saran, atau masukan Anda..."><?php echo h($_POST['pesan'] ?? ''); ?></textarea></div>
            <div class="c12 form-group"><label class="f">Keamanan: Berapa <?php echo h($captcha_q); ?> ? <span style="color:#b94c42">*</span></label><input type="text" name="captcha" required inputmode="numeric" pattern="[0-9]*" maxlength="3" placeholder="Jawaban penjumlahan" autocomplete="off"></div>
            <div class="c12"><button class="btn" type="submit">Kirim Masukan</button></div>
          </div>
        </form>
      </div>
      <div class="card c4">
        <h3>Catatan</h3>
        <ul class="clean">
          <li>Masukan Anda membantu perbaikan perencanaan dan pelayanan desa.</li>
          <li>Nama dan kontak bersifat opsional.</li>
          <li>Jangan mengirim data pribadi sensitif. Data akan ditinjau oleh admin.</li>
        </ul>
      </div>
    </div>
  </section></div></details>

    </div>
  </section>

  <div class="muted" style="text-align:center;padding:12px 0 4px">Data ditampilkan dari sistem pengelolaan perencanaan desa. Pembaruan dilakukan oleh admin melalui halaman pengelolaan.</div>
</main>
<div class="detail-modal" id="detailModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="modalTitle"><div class="modal-panel"><div class="modal-head"><h2 id="modalTitle">Rincian program</h2><button class="modal-close" type="button" aria-label="Tutup rincian">×</button></div><div class="modal-content" id="modalContent"></div></div></div>
<script>
(() => {
  const modal = document.getElementById('detailModal');
  const modalTitle = document.getElementById('modalTitle');
  const modalContent = document.getElementById('modalContent');
  const closeModal = () => {
    modal.setAttribute('aria-hidden', 'true');
    modalContent.replaceChildren();
    document.body.style.overflow = '';
  };
  const openModal = (title, content) => {
    modalTitle.textContent = title;
    modalContent.replaceChildren(content);
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    modal.querySelector('.modal-close').focus();
  };
  document.querySelectorAll('.program-card').forEach(button => button.addEventListener('click', () => {
    const content = document.createElement('div');
    const heading = document.createElement('h3');
    heading.textContent = button.dataset.title;
    const description = document.createElement('p');
    description.className = 'lead';
    description.textContent = button.dataset.description;
    const listTitle = document.createElement('b');
    listTitle.textContent = 'Hasil yang ingin dicapai';
    const list = document.createElement('ul');
    list.className = 'clean';
    JSON.parse(button.dataset.indicators || '[]').forEach(item => { const li = document.createElement('li'); li.textContent = item; list.appendChild(li); });
    content.append(heading, description, listTitle, list);
    openModal(button.dataset.title, content);
  }));
  document.querySelector('.modal-close').addEventListener('click', closeModal);
  modal.addEventListener('click', event => { if (event.target === modal) closeModal(); });
  document.addEventListener('keydown', event => { if (event.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') closeModal(); });
  document.querySelectorAll('a[href^="#detail-"]').forEach(link => link.addEventListener('click', event => {
    const details = document.querySelector(link.getAttribute('href'));
    const summary = details?.querySelector('summary');
    if (!summary) return;
    event.preventDefault();
    summary.click();
  }));
  document.querySelectorAll('.detail-card summary').forEach(summary => summary.addEventListener('click', event => {
    event.preventDefault();
    const details = summary.parentElement;
    const section = details.querySelector('.detail-body .section');
    if (!section) { details.open = !details.open; return; }
    openModal(summary.textContent.trim(), section.cloneNode(true));
  }));
})();
</script>
</body>
</html>

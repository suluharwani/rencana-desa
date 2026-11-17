<?php
function page_start(string $title, string $active = ''): void {
    $flash = flash_get();
    $pages = [
        'dashboard' => ['label' => 'Dashboard', 'href' => 'admin.php'],
        'profil' => ['label' => 'Profil & Visi', 'href' => 'profil.php'],
        'baseline' => ['label' => 'Baseline', 'href' => 'baseline.php'],
        'pilar' => ['label' => 'Pilar & Indikator', 'href' => 'pilar.php'],
        'roadmap' => ['label' => 'Roadmap', 'href' => 'roadmap.php'],
        'tahun1' => ['label' => 'Tahun Pertama', 'href' => 'tahun1.php'],
        'monitoring' => ['label' => 'Monitoring', 'href' => 'monitoring.php'],
        'anggaran' => ['label' => 'Anggaran', 'href' => 'anggaran.php'],
        'checklist' => ['label' => 'Checklist 90 Hari', 'href' => 'checklist.php'],
        'catatan' => ['label' => 'Catatan', 'href' => 'catatan.php'],
        'feedback' => ['label' => 'Kritik & Saran', 'href' => 'feedback.php'],
    ];
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo h($title); ?> - <?php echo h(APP_NAME); ?></title>
<style>
:root{--green:#176b45;--bg:#f4f7f4;--line:#dce6df;--ink:#18251f;--muted:#65736b;--white:#fff}
*{box-sizing:border-box}body{margin:0;font:14px/1.5 system-ui,-apple-system,Segoe UI,Arial,sans-serif;background:var(--bg);color:var(--ink)}
a{color:var(--green)}.layout{display:flex;min-height:100vh}
aside{width:240px;background:#123c2b;color:#e6f2ea;padding:18px 14px;position:sticky;top:0;height:100vh}
aside h1{font-size:15px;margin:0 0 14px;line-height:1.3}aside nav a{display:block;color:#d9e9df;text-decoration:none;padding:9px 10px;border-radius:8px;margin-bottom:4px;font-weight:600;font-size:13px}
aside nav a.active,aside nav a:hover{background:#1f5a41;color:#fff}
main{flex:1;padding:22px 26px}.top{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;gap:10px}
.card{background:var(--white);border:1px solid var(--line);border-radius:12px;padding:16px;margin-bottom:14px}
h2{margin:0 0 10px;font-size:22px}h3{margin:0 0 8px;font-size:16px}.muted{color:var(--muted);font-size:13px}
table{width:100%;border-collapse:collapse;font-size:13px}th,td{border:1px solid #e3ebe5;padding:8px;vertical-align:top;text-align:left}th{background:#edf4ef}
input[type=text],input[type=number],input[type=password],textarea,select{width:100%;padding:8px 9px;border:1px solid #ccd9cf;border-radius:8px;font:inherit}
textarea{min-height:70px;resize:vertical}
.grid{display:grid;grid-template-columns:repeat(12,1fr);gap:12px}.col-6{grid-column:span 6}.col-4{grid-column:span 4}.col-12{grid-column:span 12}.col-3{grid-column:span 3}
.btn{display:inline-block;background:var(--green);color:#fff;border:0;border-radius:8px;padding:8px 12px;font-weight:700;cursor:pointer;text-decoration:none;font-size:13px}
.btn.sec{background:#e8f1eb;color:var(--green)}.btn.danger{background:#b94c42}.btn.sm{padding:5px 8px;font-size:12px}
.row-actions{display:flex;gap:6px;flex-wrap:wrap}.flash{padding:10px 12px;border-radius:8px;margin-bottom:12px}.flash.ok{background:#e7f5ec;color:#1b5e3b;border:1px solid #cfe8d8}.flash.err{background:#fdeceb;color:#8a332d;border:1px solid #f3c9c5}
.inline-form{display:flex;gap:8px;align-items:end;flex-wrap:wrap}.inline-form>div{flex:1;min-width:140px}
@media(max-width:900px){.layout{display:block}aside{width:auto;height:auto;position:relative}.grid .col-6,.grid .col-4,.grid .col-3{grid-column:span 12}}
</style>
</head>
<body>
<div class="layout">
<aside>
  <h1><?php echo h(APP_NAME); ?></h1>
  <nav>
    <?php foreach ($pages as $key => $p): ?>
      <a href="<?php echo h($p['href']); ?>" class="<?php echo $active===$key?'active':''; ?>"><?php echo h($p['label']); ?></a>
    <?php endforeach; ?>
  </nav>
  <hr style="border:0;border-top:1px solid #2f624c;margin:12px 0">
  <a href="index.php" target="_blank" style="display:block;color:#d9e9df;text-decoration:none;padding:8px 10px;border-radius:8px;margin-bottom:8px;font-weight:600;font-size:13px;background:#1a4d38">Lihat Halaman Publik â†—</a>
  <div style="font-size:12px;color:#bdd3c5;margin-bottom:8px">Login: <?php echo h($_SESSION['username'] ?? 'admin'); ?></div>
  <a class="btn sec sm" href="logout.php">Logout</a>
</aside>
<main>
<?php if ($flash): ?><div class="flash <?php echo h($flash['type']); ?>"><?php echo h($flash['msg']); ?></div><?php endif; ?>
<?php
}
function page_end(): void { echo "</main></div></body></html>"; }
?>

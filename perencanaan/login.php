<?php
require_once __DIR__ . '/config.php';

if (!empty($_SESSION['logged_in'])) {
    header('Location: admin.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = post('username');
    $p = (string)($_POST['password'] ?? '');
    if ($u === ADMIN_USER && $p === ADMIN_PASS) {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $u;
        header('Location: admin.php');
        exit;
    }
    $error = 'Username atau password salah.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - <?php echo h(APP_NAME); ?></title>
<style>
body{margin:0;min-height:100vh;display:grid;place-items:center;background:linear-gradient(120deg,#103f2c,#176b45);font:14px/1.5 system-ui,Segoe UI,Arial,sans-serif}
.box{background:#fff;border-radius:14px;padding:24px;width:100%;max-width:380px;box-shadow:0 10px 30px rgba(0,0,0,.15)}
h1{margin:0 0 6px;font-size:22px;color:#163d2b}p{margin:0 0 16px;color:#5f6f66}
label{display:block;font-weight:700;margin:10px 0 5px;font-size:13px}
input{width:100%;padding:10px;border:1px solid #ccd9cf;border-radius:8px;font:inherit}
button{margin-top:16px;width:100%;padding:10px;border:0;border-radius:8px;background:#176b45;color:#fff;font-weight:700;cursor:pointer}
.err{background:#fdeceb;color:#8a332d;border:1px solid #f3c9c5;padding:9px;border-radius:8px;margin-bottom:10px;font-size:13px}
.hint{font-size:12px;color:#6a766f;margin-top:12px}
</style>
</head>
<body>
<div class="box">
  <h1>Login Admin</h1>
  <p><?php echo h(APP_NAME); ?></p>
  <?php if ($error): ?><div class="err"><?php echo h($error); ?></div><?php endif; ?>
  <form method="post">
    <label>Username</label>
    <input type="text" name="username" required autofocus>
    <label>Password</label>
    <input type="password" name="password" required>
    <button type="submit">Masuk</button>
  </form>
  <div class="hint">Kredensial default: <b>admin</b> / <b>password</b></div>
  <div style="margin-top:14px;text-align:center"><a href="public.php" style="color:#176b45;font-weight:700;text-decoration:none">Lihat Progress Publik (tanpa login)</a></div>
</div>
</body>
</html>
<?php
require_once __DIR__ . '/includes/inv360-bootstrap.php';
$msg=''; $ok=false;
if (inv360_current_user()) { header('Location: dashboard.php'); exit; }
if (($_SERVER['REQUEST_METHOD']??'')==='POST') {
  inv360_csrf_require();
  $res = inv360_login(trim((string)($_POST['username']??'')), (string)($_POST['password']??''));
  $ok=!empty($res['ok']); $msg=(string)($res['message']??'');
  if ($ok) { header('Location: dashboard.php'); exit; }
}
?><!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>ورود | Inventory360</title>
<link rel="stylesheet" href="../assets/css/m360-suite-theme.css">
</head><body>
<div class="m360-login-shell">
<div class="m360-login-card">
<h1>Inventory360</h1>
<span class="subtitle">سامانه مستقل انبار، خرید و لجستیک — مجموعه ماهین 360°</span>
<?php if ($msg !== ''): ?>
<div class="m360-alert <?= $ok ? 'm360-alert-ok' : 'm360-alert-err' ?>"><?= inv360_h($msg) ?></div>
<?php endif; ?>
<form method="post">
<?= inv360_csrf_field() ?>
<label>نام کاربری</label><input name="username" required autocomplete="username">
<label>رمز عبور</label><input type="password" name="password" required autocomplete="current-password">
<button type="submit">ورود</button>
</form>
</div>
</div>
</body></html>

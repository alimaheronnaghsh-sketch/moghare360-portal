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
?><!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ورود | Inventory360</title><link rel="stylesheet" href="assets/inv360.css"></head>
<body><div class="inv-content" style="max-width:420px;margin:8vh auto;">
<h1>ورود به Inventory360</h1>
<p class="muted">سامانه مستقل انبار، خرید و لجستیک — جدا از ERP جاری</p>
<?php inv360_flash_render($msg,$ok); ?>
<form method="post" class="inv-form">
<?= inv360_csrf_field() ?>
<label>نام کاربری<input name="username" required autocomplete="username"></label>
<label>رمز عبور<input type="password" name="password" required autocomplete="current-password"></label>
<button type="submit">ورود</button>
</form>
</div></body></html>
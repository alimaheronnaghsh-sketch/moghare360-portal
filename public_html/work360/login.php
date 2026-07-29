<?php
require_once __DIR__ . '/includes/bootstrap.php';
$msg = ''; $ok = false;
if (work360_current_user()) { header('Location: dashboard.php'); exit; }
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    work360_csrf_require();
    $res = work360_login(trim((string)($_POST['username'] ?? '')), (string)($_POST['password'] ?? ''));
    $ok = !empty($res['ok']); $msg = (string)($res['message'] ?? '');
    if ($ok) { header('Location: dashboard.php'); exit; }
}
?><!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>ورود | Work360</title>
<link rel="stylesheet" href="../assets/css/m360-suite-theme.css">
</head><body>
<div class="m360-login-shell"><div class="m360-login-card">
<h1>Work360</h1>
<span class="subtitle">مرکز کار و پیگیری روزانه — مجموعه MOGHARE360</span>
<?php if ($msg !== ''): ?><div class="m360-alert <?= $ok ? 'm360-alert-ok' : 'm360-alert-err' ?>"><?= work360_h($msg) ?></div><?php endif; ?>
<form method="post">
<?= work360_csrf_field() ?>
<label>نام کاربری</label><input name="username" required autocomplete="username">
<label>رمز عبور</label><input type="password" name="password" required autocomplete="current-password">
<button type="submit">ورود</button>
</form>
</div></div>
</body></html>

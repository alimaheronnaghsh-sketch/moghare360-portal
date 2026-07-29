<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
if (p360_current_user()) { header('Location: dashboard.php'); exit; }
$msg = null; $ok = true;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $r = p360_login((string)($_POST['username'] ?? ''), (string)($_POST['password'] ?? ''));
    $ok = !empty($r['ok']); $msg = (string)($r['message'] ?? '');
    if ($ok) { header('Location: dashboard.php'); exit; }
}
?><!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>ورود | PeopleOS360</title>
<link rel="stylesheet" href="../assets/css/m360-suite-theme.css">
</head><body>
<div class="m360-login-shell">
<div class="m360-login-card">
<h1>PeopleOS360</h1>
<span class="subtitle">سامانه مستقل اداری و منابع انسانی — مجموعه MOGHARE360</span>
<?php if ($msg): ?>
<div class="m360-alert <?= $ok ? 'm360-alert-ok' : 'm360-alert-err' ?>"><?= p360_h($msg) ?></div>
<?php endif; ?>
<form method="post">
<label>نام کاربری</label><input name="username" required autocomplete="username">
<label>رمز عبور</label><input name="password" type="password" required autocomplete="current-password">
<button type="submit">ورود</button>
</form>
</div>
</div>
</body></html>

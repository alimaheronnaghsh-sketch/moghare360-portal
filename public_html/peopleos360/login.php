<?php
require_once __DIR__ . '/includes/p360-bootstrap.php';
if (p360_current_user()) { header('Location: dashboard.php'); exit; }
$msg = null; $ok = true;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $r = p360_login((string)($_POST['username'] ?? ''), (string)($_POST['password'] ?? ''));
    $ok = !empty($r['ok']); $msg = (string)($r['message'] ?? '');
    if ($ok) { header('Location: dashboard.php'); exit; }
}
?><!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><title>ورود | PeopleOS360</title><link rel="stylesheet" href="assets/peopleos360.css"></head><body>
<div class="login-wrap"><h1>PeopleOS360</h1><?php p360_flash($msg, $ok); ?>
<form method="post"><label>نام کاربری</label><input name="username" required><label>رمز عبور</label><input name="password" type="password" required><button>ورود</button></form></div></body></html>
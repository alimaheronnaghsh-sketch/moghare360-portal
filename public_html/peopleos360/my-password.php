<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/p360-hr-central-bridge.php';

p360hr_require_central_login();
$forced = isset($_GET['forced']);
$user = p360hr_current_core_user_row();
$uid = (int)($user['user_id'] ?? $user['USER_ID'] ?? 0);
$msg = null;
$ok = false;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $token = (string)($_POST['erp_csrf_token'] ?? '');
    if (!erp_csrf_validate_token('p360hr_password_change', $token)) {
        $msg = 'توکن امنیتی نامعتبر است.';
    } else {
        $res = p360hr_change_password(
            $uid,
            (string)($_POST['current_password'] ?? ''),
            (string)($_POST['new_password'] ?? ''),
            (string)($_POST['confirm_password'] ?? '')
        );
        $ok = !empty($res['ok']);
        $msg = (string)($res['message'] ?? '');
        if ($ok && $forced) {
            header('Location: my-cartable.php');
            exit;
        }
    }
}

p360hr_layout_start($forced ? 'تغییر اجباری رمز عبور' : 'تغییر رمز عبور');
if ($forced) {
    echo '<div class="m360-alert m360-alert-err">برای ادامه کار با سامانه، ابتدا رمز عبور موقت را تغییر دهید.</div>';
}
if ($msg !== null) {
    echo '<div class="m360-alert ' . ($ok ? 'm360-alert-ok' : 'm360-alert-err') . '">' . p360hr_h($msg) . '</div>';
}
$csrf = erp_csrf_create_token('p360hr_password_change');
echo '<form class="m360-card m360-form" method="post" style="max-width:480px">';
echo '<input type="hidden" name="erp_csrf_token" value="' . p360hr_h($csrf) . '">';
echo '<label>رمز فعلی</label><input type="password" name="current_password" required autocomplete="current-password">';
echo '<label>رمز جدید</label><input type="password" name="new_password" required autocomplete="new-password">';
echo '<label>تکرار رمز جدید</label><input type="password" name="confirm_password" required autocomplete="new-password">';
echo '<button type="submit" class="m360-btn">ثبت تغییر رمز</button>';
echo '</form>';
p360hr_layout_end();

<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/includes/m360-access-management-helper.php';
require_once __DIR__ . '/includes/m360-access-user-helper.php';

$actorId = m360_access_mgmt_require_admin();
$conn = m360_access_mgmt_db();
$userId = (int)($_GET['user_id'] ?? m360_access_mgmt_post_string('user_id'));
$error = '';
$tempPasswordOnce = '';

if ($userId <= 0 || $conn === false) {
    m360_access_mgmt_render_head('بازنشانی رمز');
    echo '<div class="m360-access-alert m360-access-alert-error">user_id نامعتبر یا DB در دسترس نیست.</div>';
    m360_access_mgmt_render_foot();
    exit;
}

$user = m360_access_user_get($conn, $userId);
if ($user === null) {
    m360_access_mgmt_render_head('بازنشانی رمز');
    echo '<div class="m360-access-alert m360-access-alert-error">کاربر یافت نشد.</div>';
    m360_access_mgmt_render_foot();
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        m360_access_mgmt_require_post_csrf();
        m360_access_user_guard_target($conn, $actorId, $userId);
        $generate = m360_access_mgmt_post_string('generate') === '1';
        $manual = m360_access_mgmt_post_string('temporary_password');
        $result = m360_access_user_reset_password(
            $conn,
            $actorId,
            $userId,
            $generate ? null : $manual,
            $generate
        );
        $tempPasswordOnce = (string)($result['temporary_password'] ?? '');
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

m360_access_mgmt_render_head('بازنشانی رمز موقت — ' . (string)($user['username'] ?? ''));
if ($error !== '') {
    echo '<div class="m360-access-alert m360-access-alert-error">' . m360_access_mgmt_h($error) . '</div>';
}
if ($tempPasswordOnce !== '') {
    echo '<div class="m360-access-alert m360-access-alert-warn"><strong>رمز موقت (فقط یک‌بار نمایش داده می‌شود):</strong> ';
    echo m360_access_mgmt_h($tempPasswordOnce) . '</div>';
    echo '<p>hash در UI نمایش داده نمی‌شود. force-change-password هنوز پشتیبانی نمی‌شود — پس از اولین ورود رمز را تغییر دهید.</p>';
}

echo '<section class="m360-access-card m360-access-form">';
echo '<p>کاربر: <strong>' . m360_access_mgmt_h((string)($user['username'] ?? '')) . '</strong></p>';
echo '<form method="post" action="erp-access-password-reset.php?user_id=' . $userId . '">';
echo erp_csrf_input(M360_ACCESS_MGMT_CSRF);
echo '<input type="hidden" name="user_id" value="' . $userId . '">';
echo '<label for="temporary_password">رمز موقت (دستی)</label><input id="temporary_password" name="temporary_password" type="password" autocomplete="new-password" minlength="8">';
echo '<label><input type="checkbox" name="generate" value="1"> تولید خودکار رمز امن</label>';
echo '<button type="submit" class="m360-access-btn warn">بازنشانی رمز</button>';
echo '</form></section>';
echo '<p><a class="m360-access-btn secondary" href="erp-access-management.php">بازگشت</a></p>';
m360_access_mgmt_render_foot();

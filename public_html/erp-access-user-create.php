<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/includes/m360-access-management-helper.php';
require_once __DIR__ . '/includes/m360-access-user-helper.php';

$actorId = m360_access_mgmt_require_admin();
$conn = m360_access_mgmt_db();
$error = '';
$success = '';

$departments = $conn !== false ? m360_access_user_departments($conn) : [];
$positions = $conn !== false ? m360_access_user_positions($conn) : [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $conn !== false) {
    try {
        m360_access_mgmt_require_post_csrf();
        $result = m360_access_user_create($conn, $actorId, [
            'username' => m360_access_mgmt_post_string('username'),
            'display_name' => m360_access_mgmt_post_string('display_name'),
            'mobile' => m360_access_mgmt_post_string('mobile'),
            'email' => m360_access_mgmt_post_string('email'),
            'department_id' => (int)m360_access_mgmt_post_string('department_id'),
            'position_id' => (int)m360_access_mgmt_post_string('position_id'),
            'role_code' => m360_access_mgmt_post_string('role_code'),
            'temporary_password' => m360_access_mgmt_post_string('temporary_password'),
            'lifecycle_state' => m360_access_mgmt_post_string('lifecycle_state') ?: 'ACTIVE',
            'is_login_enabled' => m360_access_mgmt_post_string('is_login_enabled') !== '' ? 1 : 0,
        ]);
        $success = (string)($result['message'] ?? 'Created') . ' user_id=' . (int)($result['user_id'] ?? 0);
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

m360_access_mgmt_render_head('ایجاد پرسنل');
if ($error !== '') {
    echo '<div class="m360-access-alert m360-access-alert-error">' . m360_access_mgmt_h($error) . '</div>';
}
if ($success !== '') {
    echo '<div class="m360-access-alert m360-access-alert-info">' . m360_access_mgmt_h($success) . '</div>';
}

echo '<section class="m360-access-card m360-access-form">';
echo '<form method="post" action="erp-access-user-create.php">';
echo erp_csrf_input(M360_ACCESS_MGMT_CSRF);
echo '<label for="username">نام کاربری *</label><input id="username" name="username" required autocomplete="off">';
echo '<label for="display_name">نام نمایشی *</label><input id="display_name" name="display_name" required>';
echo '<label for="mobile">موبایل (اختیاری)</label><input id="mobile" name="mobile" autocomplete="off">';
echo '<label for="email">ایمیل (اختیاری)</label><input id="email" name="email" type="email" autocomplete="off">';
echo '<label for="department_id">واحد</label><select id="department_id" name="department_id"><option value="">—</option>';
foreach ($departments as $d) {
    echo '<option value="' . m360_access_mgmt_h((string)($d['department_id'] ?? '')) . '">' . m360_access_mgmt_h((string)($d['dept_name'] ?? '')) . '</option>';
}
echo '</select>';
echo '<label for="position_id">سمت</label><select id="position_id" name="position_id"><option value="">—</option>';
foreach ($positions as $p) {
    echo '<option value="' . m360_access_mgmt_h((string)($p['position_id'] ?? '')) . '">' . m360_access_mgmt_h((string)($p['position_name'] ?? '')) . '</option>';
}
echo '</select>';
echo '<label for="role_code">نقش *</label><select id="role_code" name="role_code" required><option value="">—</option>';
foreach (m360_access_mgmt_role_code_map() as $code => $meta) {
    if (in_array($meta['role_key'], M360_ACCESS_MGMT_PROTECTED_ROLE_KEYS, true)) {
        continue;
    }
    echo '<option value="' . m360_access_mgmt_h($code) . '">' . m360_access_mgmt_h($meta['label_fa'] . ' (' . $code . ' → ' . $meta['role_key'] . ')') . '</option>';
}
echo '</select>';
echo '<label for="temporary_password">رمز موقت *</label><input id="temporary_password" name="temporary_password" type="password" required autocomplete="new-password" minlength="8">';
echo '<label for="lifecycle_state">lifecycle_state</label><select id="lifecycle_state" name="lifecycle_state">';
foreach (m360_access_mgmt_lifecycle_options() as $val => $label) {
    $sel = $val === 'ACTIVE' ? ' selected' : '';
    echo '<option value="' . m360_access_mgmt_h($val) . '"' . $sel . '>' . m360_access_mgmt_h($label) . '</option>';
}
echo '</select>';
echo '<label><input type="checkbox" name="is_login_enabled" value="1" checked> ورود فعال (is_login_enabled)</label>';
echo '<button type="submit" class="m360-access-btn">ایجاد پرسنل</button>';
echo '</form></section>';
echo '<p><a class="m360-access-btn secondary" href="erp-access-management.php">بازگشت</a></p>';
m360_access_mgmt_render_foot();

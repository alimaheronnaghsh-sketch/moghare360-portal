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
$success = '';

if ($userId <= 0 || $conn === false) {
    m360_access_mgmt_render_head('ویرایش پرسنل');
    echo '<div class="m360-access-alert m360-access-alert-error">user_id نامعتبر یا DB در دسترس نیست.</div>';
    m360_access_mgmt_render_foot();
    exit;
}

$user = m360_access_user_get($conn, $userId);
if ($user === null) {
    m360_access_mgmt_render_head('ویرایش پرسنل');
    echo '<div class="m360-access-alert m360-access-alert-error">کاربر یافت نشد.</div>';
    m360_access_mgmt_render_foot();
    exit;
}

$departments = m360_access_user_departments_for_staff_form($conn, false);
$positionsJson = m360_access_user_positions_json_for_form($conn, false);
$deptNamesJson = m360_access_user_dept_labels_json_for_form($conn, false);

$selectedDepartmentId = (int)($user['department_id'] ?? 0);
$selectedPositionId = (int)($user['position_id'] ?? 0);

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $selectedDepartmentId = (int)m360_access_mgmt_post_string('department_id');
    $selectedPositionId = (int)m360_access_mgmt_post_string('position_id');

    try {
        m360_access_mgmt_require_post_csrf();
        m360_access_user_guard_target($conn, $actorId, $userId);
        $result = m360_access_user_update($conn, $actorId, $userId, [
            'display_name' => m360_access_mgmt_post_string('display_name'),
            'mobile' => m360_access_mgmt_post_string('mobile'),
            'email' => m360_access_mgmt_post_string('email'),
            'department_id' => $selectedDepartmentId,
            'position_id' => $selectedPositionId,
            'lifecycle_state' => m360_access_mgmt_post_string('lifecycle_state'),
            'is_login_enabled' => m360_access_mgmt_post_string('is_login_enabled') !== '' ? 1 : 0,
        ]);
        $success = (string)($result['message'] ?? 'Updated');
        $user = m360_access_user_get($conn, $userId) ?? $user;
        $selectedDepartmentId = (int)($user['department_id'] ?? $selectedDepartmentId);
        $selectedPositionId = (int)($user['position_id'] ?? $selectedPositionId);
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

m360_access_mgmt_render_head('ویرایش پرسنل — ' . (string)($user['username'] ?? ''));
if ($error !== '') {
    echo '<div class="m360-access-alert m360-access-alert-error">' . m360_access_mgmt_h($error) . '</div>';
}
if ($success !== '') {
    echo '<div class="m360-access-alert m360-access-alert-info">' . m360_access_mgmt_h($success) . '</div>';
}

echo '<section class="m360-access-card m360-access-form">';
echo '<p>نام کاربری: <strong>' . m360_access_mgmt_h((string)($user['username'] ?? '')) . '</strong> — user_id=' . m360_access_mgmt_h((string)$userId) . '</p>';
echo '<form method="post" action="erp-access-user-edit.php?user_id=' . $userId . '">';
echo erp_csrf_input(M360_ACCESS_MGMT_CSRF);
echo '<input type="hidden" name="user_id" value="' . m360_access_mgmt_h((string)$userId) . '">';
echo '<label for="display_name">نام نمایشی *</label><input id="display_name" name="display_name" required value="' . m360_access_mgmt_h((string)($user['full_name'] ?? '')) . '">';
echo '<label for="mobile">موبایل</label><input id="mobile" name="mobile" value="' . m360_access_mgmt_h((string)($user['mobile'] ?? '')) . '">';
echo '<p>نمایش امن: ' . m360_access_mgmt_h(m360_access_mgmt_mask_mobile((string)($user['mobile'] ?? ''))) . '</p>';
echo '<label for="email">ایمیل</label><input id="email" name="email" type="email" value="' . m360_access_mgmt_h((string)($user['email'] ?? '')) . '">';

m360_access_user_render_department_position_fields(
    $departments,
    $positionsJson,
    $deptNamesJson,
    $selectedDepartmentId,
    $selectedPositionId
);

echo '<label for="lifecycle_state">lifecycle_state</label><select id="lifecycle_state" name="lifecycle_state">';
foreach (m360_access_mgmt_lifecycle_options() as $val => $label) {
    $sel = $val === (string)($user['lifecycle_state'] ?? '') ? ' selected' : '';
    echo '<option value="' . m360_access_mgmt_h($val) . '"' . $sel . '>' . m360_access_mgmt_h($label) . '</option>';
}
echo '</select>';
$checked = (string)($user['is_login_enabled'] ?? '0') === '1' ? ' checked' : '';
echo '<label><input type="checkbox" name="is_login_enabled" value="1"' . $checked . '> ورود فعال</label>';
echo '<button type="submit" class="m360-access-btn">ذخیره</button>';
echo '</form></section>';
echo '<p><a class="m360-access-btn secondary" href="erp-access-management.php">بازگشت</a></p>';
m360_access_mgmt_render_foot();

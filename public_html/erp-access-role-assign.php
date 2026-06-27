<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/includes/m360-access-management-helper.php';
require_once __DIR__ . '/includes/m360-access-user-helper.php';
require_once __DIR__ . '/includes/m360-access-role-helper.php';

$actorId = m360_access_mgmt_require_admin();
$conn = m360_access_mgmt_db();
$userId = (int)($_GET['user_id'] ?? m360_access_mgmt_post_string('user_id'));
$error = '';
$success = '';

if ($userId <= 0 || $conn === false) {
    m360_access_mgmt_render_head('تخصیص نقش');
    echo '<div class="m360-access-alert m360-access-alert-error">user_id نامعتبر یا DB در دسترس نیست.</div>';
    m360_access_mgmt_render_foot();
    exit;
}

$user = m360_access_user_get($conn, $userId);
if ($user === null) {
    m360_access_mgmt_render_head('تخصیص نقش');
    echo '<div class="m360-access-alert m360-access-alert-error">کاربر یافت نشد.</div>';
    m360_access_mgmt_render_foot();
    exit;
}

$privileged = m360_access_mgmt_actor_can_manage_privileged($conn, $actorId);
$assignable = m360_access_role_list_assignable($conn, $privileged);
$activeRoles = m360_access_role_active_for_user($conn, $userId);

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        m360_access_mgmt_require_post_csrf();
        m360_access_user_guard_target($conn, $actorId, $userId);
        $action = m360_access_mgmt_post_string('action');
        $reason = m360_access_mgmt_post_string('reason');

        if ($action === 'assign') {
            $roleKey = m360_access_mgmt_post_string('role_key');
            if ($roleKey === '') {
                throw new RuntimeException('role_key required.');
            }
            $result = m360_access_role_assign($conn, $actorId, $userId, $roleKey, $reason !== '' ? $reason : null);
            $success = (string)($result['message'] ?? 'Assigned');
        } elseif ($action === 'revoke') {
            $userRoleId = (int)m360_access_mgmt_post_string('user_role_id');
            $result = m360_access_role_revoke($conn, $actorId, $userId, $userRoleId, $reason !== '' ? $reason : null);
            $success = (string)($result['message'] ?? 'Revoked');
        } else {
            throw new RuntimeException('Unknown action.');
        }
        $activeRoles = m360_access_role_active_for_user($conn, $userId);
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

m360_access_mgmt_render_head('تخصیص نقش — ' . (string)($user['username'] ?? ''));
if ($error !== '') {
    echo '<div class="m360-access-alert m360-access-alert-error">' . m360_access_mgmt_h($error) . '</div>';
}
if ($success !== '') {
    echo '<div class="m360-access-alert m360-access-alert-info">' . m360_access_mgmt_h($success) . '</div>';
}

echo '<section class="m360-access-card"><h2>نقش‌های فعال</h2><table class="m360-access-table"><thead><tr><th>role_key</th><th>نام</th><th>وضعیت</th><th>عملیات</th></tr></thead><tbody>';
foreach ($activeRoles as $r) {
    $revoked = trim((string)($r['revoked_at'] ?? '')) !== '';
    echo '<tr><td>' . m360_access_mgmt_h((string)($r['role_key'] ?? '')) . '</td>';
    echo '<td>' . m360_access_mgmt_h((string)($r['role_name'] ?? '')) . '</td>';
    echo '<td>' . m360_access_mgmt_h($revoked ? 'REVOKED' : 'ACTIVE') . '</td><td>';
    if (!$revoked) {
        echo '<form method="post" style="display:inline"><input type="hidden" name="user_id" value="' . $userId . '">';
        echo erp_csrf_input(M360_ACCESS_MGMT_CSRF);
        echo '<input type="hidden" name="action" value="revoke"><input type="hidden" name="user_role_id" value="' . m360_access_mgmt_h((string)($r['user_role_id'] ?? '')) . '">';
        echo '<input type="hidden" name="reason" value="Revoked from access management UI"><button type="submit" class="m360-access-btn warn">لغو نقش</button></form>';
    }
    echo '</td></tr>';
}
echo '</tbody></table></section>';

echo '<section class="m360-access-card m360-access-form"><h2>افزودن نقش موجود</h2>';
echo '<form method="post" action="erp-access-role-assign.php?user_id=' . $userId . '">';
echo erp_csrf_input(M360_ACCESS_MGMT_CSRF);
echo '<input type="hidden" name="user_id" value="' . $userId . '"><input type="hidden" name="action" value="assign">';
echo '<label for="role_key">نقش (core_roles)</label><select id="role_key" name="role_key" required><option value="">—</option>';
foreach ($assignable as $role) {
    echo '<option value="' . m360_access_mgmt_h((string)($role['role_key'] ?? '')) . '">' . m360_access_mgmt_h((string)($role['role_name'] ?? '') . ' (' . ($role['role_key'] ?? '') . ')') . '</option>';
}
echo '</select>';
echo '<label for="reason">دلیل (اختیاری)</label><input id="reason" name="reason" value="Assigned from access management UI">';
echo '<button type="submit" class="m360-access-btn">تخصیص نقش</button></form></section>';
echo '<p><a class="m360-access-btn secondary" href="erp-access-management.php">بازگشت</a></p>';
m360_access_mgmt_render_foot();

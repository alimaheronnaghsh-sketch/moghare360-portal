<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/includes/m360-access-management-helper.php';
require_once __DIR__ . '/includes/m360-access-user-helper.php';
require_once __DIR__ . '/includes/m360-access-permission-preview-helper.php';

$actorId = m360_access_mgmt_require_admin();
$conn = m360_access_mgmt_db();
$staff = $conn !== false ? m360_access_user_list_staff($conn) : [];
$readiness = $conn !== false ? m360_access_readiness_report($conn) : ['status' => 'BLOCKED', 'checks' => []];

m360_access_mgmt_render_head('مدیریت دسترسی پرسنل');
m360_access_mgmt_render_flash();

echo '<div class="m360-access-warning-box"><strong>مسیر اصلی:</strong> مدیریت دسترسی از این UI — JSON import فقط bootstrap/fallback است.</div>';

if ($conn === false) {
    echo '<div class="m360-access-alert m360-access-alert-error">اتصال ODBC برقرار نشد.</div>';
} else {
    $badgeClass = match ($readiness['status']) {
        'PASS' => 'pass',
        'WARNING' => 'warn',
        default => 'block',
    };
    echo '<section class="m360-access-card"><h2>آمادگی One-Day Run</h2>';
    echo '<p>وضعیت: <span class="m360-access-badge ' . m360_access_mgmt_h($badgeClass) . '">' . m360_access_mgmt_h((string)$readiness['status']) . '</span></p>';
    echo '<div class="m360-access-grid">';
    echo '<div class="m360-access-kpi"><div class="val">' . count($staff) . '</div><div class="lbl">کاربران ثبت‌شده</div></div>';
    echo '<div class="m360-access-kpi"><div class="val">' . m360_access_user_count_non_owner_staff($conn) . '</div><div class="lbl">پرسنل (20001+)</div></div>';
    echo '<div class="m360-access-kpi"><div class="val">' . m360_access_user_count_login_enabled_staff($conn) . '</div><div class="lbl">ورود فعال</div></div>';
    echo '</div></section>';

    echo '<section class="m360-access-card"><h2>لیست پرسنل / کاربران</h2>';
    echo '<p><a class="m360-access-btn" href="erp-access-user-create.php">+ ایجاد پرسنل</a></p>';
    if ($staff === []) {
        echo '<p>کاربری یافت نشد.</p>';
    } else {
        echo '<table class="m360-access-table"><thead><tr>';
        echo '<th>ID</th><th>نام کاربری</th><th>نام</th><th>واحد</th><th>سمت</th><th>نقش</th><th>ورود</th><th>وضعیت</th><th>دسترسی</th><th>عملیات</th>';
        echo '</tr></thead><tbody>';
        foreach ($staff as $row) {
            $uid = (int)($row['user_id'] ?? 0);
            $effective = m360_access_mgmt_effective_access_label($conn, $uid);
            echo '<tr>';
            echo '<td>' . m360_access_mgmt_h((string)$uid) . '</td>';
            echo '<td>' . m360_access_mgmt_h((string)($row['username'] ?? '')) . '</td>';
            echo '<td>' . m360_access_mgmt_h((string)($row['full_name'] ?? '')) . '</td>';
            echo '<td>' . m360_access_mgmt_h((string)($row['dept_name'] ?? '—')) . '</td>';
            echo '<td>' . m360_access_mgmt_h((string)($row['position_name'] ?? '—')) . '</td>';
            echo '<td>' . m360_access_mgmt_h((string)($row['active_role_keys'] ?? '—')) . '</td>';
            echo '<td>' . m360_access_mgmt_h((string)($row['is_login_enabled'] ?? '0') === '1' ? 'فعال' : 'غیرفعال') . '</td>';
            echo '<td>' . m360_access_mgmt_h((string)($row['lifecycle_state'] ?? '')) . '</td>';
            echo '<td>' . m360_access_mgmt_h($effective) . '</td>';
            echo '<td class="m360-access-actions">';
            echo '<a class="m360-access-btn secondary" href="erp-access-user-edit.php?user_id=' . $uid . '">ویرایش</a> ';
            echo '<a class="m360-access-btn secondary" href="erp-access-role-assign.php?user_id=' . $uid . '">نقش</a> ';
            echo '<a class="m360-access-btn secondary" href="erp-access-password-reset.php?user_id=' . $uid . '">رمز</a> ';
            echo '<a class="m360-access-btn secondary" href="erp-access-permission-preview.php?user_id=' . $uid . '">پیش‌نمایش</a> ';
            echo '<a class="m360-access-btn secondary" href="erp-access-change-history.php?user_id=' . $uid . '">تاریخچه</a>';
            echo '</td></tr>';
        }
        echo '</tbody></table>';
    }
    echo '</section>';
}

m360_access_mgmt_render_foot();

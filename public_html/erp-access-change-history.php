<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/includes/m360-access-management-helper.php';
require_once __DIR__ . '/includes/m360-access-audit-helper.php';

$actorId = m360_access_mgmt_require_admin();
$conn = m360_access_mgmt_db();
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
if ($userId !== null && $userId <= 0) {
    $userId = null;
}

$history = $conn !== false ? m360_access_audit_list_history($conn, $userId, 200) : [];

m360_access_mgmt_render_head('تاریخچه تغییرات دسترسی — read-only');
echo '<section class="m360-access-card">';
echo '<p>فیلتر: ' . ($userId !== null ? 'user_id=' . m360_access_mgmt_h((string)$userId) : 'همه') . '</p>';
echo '<p><a href="erp-access-change-history.php">همه</a></p>';
if ($conn === false) {
    echo '<div class="m360-access-alert m360-access-alert-error">DB unavailable.</div>';
} elseif ($history === []) {
    echo '<p>رکord تاریخچه‌ای یافت نشد (یا جدول موجود نیست).</p>';
} else {
    echo '<table class="m360-access-table"><thead><tr>';
    echo '<th>زمان</th><th>user</th><th>change_type</th><th>entity</th><th>by</th><th>request</th><th>after (safe)</th>';
    echo '</tr></thead><tbody>';
    foreach ($history as $row) {
        $after = (string)($row['after_json'] ?? '');
        $after = preg_replace('/password[^"]*"[^"]*"/i', 'password":"[REDACTED]"', $after) ?? $after;
        echo '<tr>';
        echo '<td>' . m360_access_mgmt_h((string)($row['changed_at'] ?? '')) . '</td>';
        echo '<td>' . m360_access_mgmt_h((string)($row['subject_username'] ?? $row['user_id'] ?? '')) . '</td>';
        echo '<td>' . m360_access_mgmt_h((string)($row['change_type'] ?? '')) . '</td>';
        echo '<td>' . m360_access_mgmt_h((string)($row['entity_type'] ?? '')) . '</td>';
        echo '<td>' . m360_access_mgmt_h((string)($row['changed_by_username'] ?? $row['changed_by_user_id'] ?? '')) . '</td>';
        echo '<td>' . m360_access_mgmt_h((string)($row['request_id'] ?? '')) . '</td>';
        echo '<td><code style="font-size:.75rem">' . m360_access_mgmt_h(substr($after, 0, 180)) . '</code></td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}
echo '</section>';
echo '<p><a class="m360-access-btn secondary" href="erp-access-management.php">بازگشت</a></p>';
m360_access_mgmt_render_foot();

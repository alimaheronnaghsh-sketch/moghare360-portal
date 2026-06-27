<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/includes/m360-access-management-helper.php';
require_once __DIR__ . '/includes/m360-access-permission-preview-helper.php';

$actorId = m360_access_mgmt_require_admin();
$conn = m360_access_mgmt_db();
$userId = (int)($_GET['user_id'] ?? 0);
$error = '';

if ($userId <= 0 || $conn === false) {
    m360_access_mgmt_render_head('پیش‌نمایش دسترسی');
    echo '<div class="m360-access-alert m360-access-alert-error">user_id نامعتبر یا DB در دسترس نیست.</div>';
    m360_access_mgmt_render_foot();
    exit;
}

try {
    $preview = m360_access_preview_load($conn, $userId);
    $routes = m360_access_preview_routes($conn, $userId);
} catch (Throwable $e) {
    $error = $e->getMessage();
    $preview = null;
    $routes = ['routes' => [], 'warnings' => [], 'mapped_count' => 0, 'unmapped_count' => 0];
}

m360_access_mgmt_render_head('پیش‌نمایش دسترسی — read-only');
if ($error !== '') {
    echo '<div class="m360-access-alert m360-access-alert-error">' . m360_access_mgmt_h($error) . '</div>';
}

if ($preview !== null) {
    $user = $preview['user'];
    echo '<section class="m360-access-card"><h2>کاربر</h2>';
    echo '<p>' . m360_access_mgmt_h((string)($user['username'] ?? '')) . ' — ' . m360_access_mgmt_h((string)($user['full_name'] ?? '')) . '</p></section>';

    echo '<section class="m360-access-card"><h2>نقش‌های فعال</h2><ul>';
    foreach ($preview['roles'] as $role) {
        if (trim((string)($role['revoked_at'] ?? '')) !== '') {
            continue;
        }
        echo '<li>' . m360_access_mgmt_h((string)($role['role_key'] ?? '') . ' — ' . (string)($role['role_name'] ?? '')) . '</li>';
    }
    echo '</ul></section>';

    echo '<section class="m360-access-card"><h2>Permissionهای مؤثر (' . count($preview['permissions']) . ')</h2><ul>';
    foreach ($preview['permissions'] as $perm) {
        echo '<li><code>' . m360_access_mgmt_h($perm) . '</code></li>';
    }
    echo '</ul></section>';

    if (($routes['warnings'] ?? []) !== []) {
        echo '<section class="m360-access-card"><h2>هشدارهای نگاشت Route</h2><ul>';
        foreach ($routes['warnings'] as $w) {
            echo '<li>' . m360_access_mgmt_h($w) . '</li>';
        }
        echo '</ul></section>';
    }

    echo '<section class="m360-access-card"><h2>Routeهای P1–P11 (heuristic)</h2>';
    echo '<p>mapped=' . (int)($routes['mapped_count'] ?? 0) . ' unmapped=' . (int)($routes['unmapped_count'] ?? 0) . '</p>';
    echo '<table class="m360-access-table"><thead><tr><th>Phase</th><th>Route</th><th>URL</th><th>دسترسی</th><th>یادداشت</th></tr></thead><tbody>';
    foreach ($routes['routes'] as $route) {
        echo '<tr><td>' . m360_access_mgmt_h((string)($route['phase_code'] ?? '')) . '</td>';
        echo '<td>' . m360_access_mgmt_h((string)($route['title_fa'] ?? '')) . '</td>';
        echo '<td>' . m360_access_mgmt_h((string)($route['url'] ?? '')) . '</td>';
        echo '<td>' . m360_access_mgmt_h(!empty($route['accessible_heuristic']) ? 'YES' : 'NO') . '</td>';
        echo '<td>' . m360_access_mgmt_h((string)($route['mapping_note'] ?? '')) . '</td></tr>';
    }
    echo '</tbody></table></section>';
}

echo '<p><a class="m360-access-btn secondary" href="erp-access-management.php">بازگشت</a></p>';
m360_access_mgmt_render_foot();

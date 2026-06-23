<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/moghare360-security-audit-helper.php';

try {
    $c = security_audit_db();
    if ($c !== false) {
        security_audit_require_auth($c, 'security.audit.view');
        @odbc_close($c);
    }
} catch (Throwable) {
    security_audit_error('ممیزی مدیریت خطا', 'دسترسی ممکن نیست.');
}

$policy = security_audit_error_handling_policy();
$classification = security_audit_route_classification();

security_audit_render_head('ممیزی مدیریت خطا');
/* Boundaries: Not Production, Not SaaS, Not Customer Portal, Not Official Accounting */
echo '<div class="p13sec-hero"><h1>ممیزی مدیریت خطا</h1>';
echo '<p>Error Handling Audit — سیاست خطای امن و فارسی</p></div>';

security_audit_render_boundary_warnings();
security_audit_render_nav();

echo '<div class="p13sec-card-dark"><h2 class="p13sec-section-title">سیاست خطا</h2>';
echo '<table class="p1cc-table"><thead><tr><th>حوزه</th><th>سیاست</th><th>وضعیت</th></tr></thead><tbody>';
foreach ($policy as $row) {
    echo '<tr><td>' . security_audit_h((string)$row['area']) . '</td>';
    echo '<td>' . security_audit_h((string)$row['policy']) . '</td>';
    echo '<td><span class="p13sec-badge ' . security_audit_safe_status_badge((string)$row['status']) . '">' . security_audit_h((string)$row['status']) . '</span></td></tr>';
}
echo '</tbody></table></div>';

echo '<div class="p13sec-card-dark"><h2 class="p13sec-section-title">طبقه‌بندی مسیرها (خلاصه)</h2>';
echo '<table class="p1cc-table"><thead><tr><th>Route</th><th>Classification</th><th>Module</th><th>Access</th><th>Status</th></tr></thead><tbody>';
$shown = 0;
foreach ($classification as $row) {
    if ($shown >= 25) {
        break;
    }
    echo '<tr><td><code class="m360-ltr">' . security_audit_h((string)$row['route']) . '</code></td>';
    echo '<td>' . security_audit_h((string)$row['classification']) . '</td>';
    echo '<td>' . security_audit_h((string)$row['module']) . '</td>';
    echo '<td>' . security_audit_h((string)$row['access']) . '</td>';
    echo '<td><span class="p13sec-badge ' . security_audit_safe_status_badge((string)$row['status']) . '">' . security_audit_h((string)$row['status']) . '</span></td></tr>';
    $shown++;
}
echo '</tbody></table>';
echo '<p style="margin-top:.5rem;font-size:.85rem;color:#9aa8b5">نمایش ' . $shown . ' از ' . count($classification) . ' مسیر — <a href="erp-write-route-audit.php">ممیزی Write کامل</a></p></div>';

echo '<div class="p13sec-boundary-box"><strong>اصول</strong><ul>';
echo '<li>بدون نمایش raw exception به کاربر</li>';
echo '<li>بدون افشای credential دیتابیس</li>';
echo '<li>بدون stack trace در صفحات کاربری</li>';
echo '<li>redirect امن پس از write موفق یا CSRF failure</li>';
echo '</ul></div>';

security_audit_render_foot();

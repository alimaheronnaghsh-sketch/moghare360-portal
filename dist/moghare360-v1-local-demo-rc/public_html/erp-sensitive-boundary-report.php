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
    security_audit_error('گزارش مرز حساس', 'دسترسی ممکن نیست.');
}

$forbidden = security_audit_forbidden_files();
$helpers = security_audit_helper_inventory();

security_audit_render_head('گزارش مرزهای حساس');
/* Boundaries: Not Production, Not SaaS, Not Customer Portal, Not Official Accounting */
echo '<div class="p13sec-hero"><h1>گزارش مرزهای حساس</h1>';
echo '<p>Sensitive Boundary Report — بدون نمایش محتوای فایل حساس</p></div>';

echo '<div class="p13sec-warning-box"><strong>هشدار:</strong> این صفحه فایل حساس را نمایش نمی‌دهد و فقط وضعیت مرزی را گزارش می‌کند.</div>';

security_audit_render_boundary_warnings();
security_audit_render_nav();

echo '<div class="p13sec-card-dark"><h2 class="p13sec-section-title">Forbidden Files</h2>';
echo '<table class="p1cc-table"><thead><tr><th>فایل</th><th>وضعیت</th><th>یادداشت</th></tr></thead><tbody>';
foreach ($forbidden as $row) {
    echo '<tr><td><code class="m360-ltr">' . security_audit_h((string)$row['file']) . '</code></td>';
    echo '<td><span class="p13sec-badge ' . security_audit_safe_status_badge((string)$row['status']) . '">' . security_audit_h((string)$row['status']) . '</span></td>';
    echo '<td>' . security_audit_h((string)$row['notes']) . '</td></tr>';
}
echo '</tbody></table></div>';

echo '<div class="p13sec-card-dark"><h2 class="p13sec-section-title">Helpers (read-only inspection)</h2>';
echo '<table class="p1cc-table"><thead><tr><th>Helper</th><th>مسیر</th><th>نقش</th><th>وضعیت</th></tr></thead><tbody>';
foreach ($helpers as $h) {
    echo '<tr><td>' . security_audit_h((string)$h['name']) . '</td>';
    echo '<td><code class="m360-ltr">' . security_audit_h((string)$h['path']) . '</code></td>';
    echo '<td>' . security_audit_h((string)$h['role']) . '</td>';
    echo '<td><span class="p13sec-badge ' . security_audit_safe_status_badge((string)$h['status']) . '">' . security_audit_h((string)$h['status']) . '</span></td></tr>';
}
echo '</tbody></table></div>';

echo '<div class="p13sec-boundary-box"><strong>مرزهای معماری</strong><ul>';
foreach (security_audit_sensitive_boundaries() as $b) {
    echo '<li>' . security_audit_h($b) . '</li>';
}
echo '</ul></div>';

security_audit_render_foot();

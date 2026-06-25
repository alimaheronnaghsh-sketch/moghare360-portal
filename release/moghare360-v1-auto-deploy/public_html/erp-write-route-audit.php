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
    security_audit_error('ممیزی Write Route', 'دسترسی ممکن نیست.');
}

$routes = security_audit_write_routes();

security_audit_render_head('ممیزی مسیرهای Write');
/* Boundaries: Not Production, Not SaaS, Not Customer Portal, Not Official Accounting */
echo '<div class="p13sec-hero"><h1>ممیزی مسیرهای Write</h1>';
echo '<p>Write Route Audit — static classification + light file scan</p></div>';

security_audit_render_boundary_warnings();
security_audit_render_nav();

echo '<div class="p13sec-card-dark"><table class="p1cc-table"><thead><tr>';
echo '<th>Route</th><th>Module</th><th>File</th><th>Method</th><th>CSRF</th><th>Auth</th><th>Permission</th>';
echo '<th>Redirect</th><th>Error</th><th>Audit</th><th>Risk</th><th>Notes</th>';
echo '</tr></thead><tbody>';
foreach ($routes as $r) {
    echo '<tr>';
    echo '<td><code class="m360-ltr">' . security_audit_h((string)$r['route']) . '</code></td>';
    echo '<td>' . security_audit_h((string)$r['module']) . '</td>';
    echo '<td><span class="p13sec-badge ' . security_audit_safe_status_badge((string)$r['file_status']) . '">' . security_audit_h((string)$r['file_status']) . '</span></td>';
    echo '<td>' . security_audit_h((string)$r['method_expected']) . '</td>';
    echo '<td>' . security_audit_h((string)$r['csrf_required']) . '</td>';
    echo '<td>' . security_audit_h((string)$r['auth_expected']) . '</td>';
    echo '<td>' . security_audit_h((string)$r['permission_expected']) . '</td>';
    echo '<td>' . security_audit_h((string)$r['safe_redirect_expected']) . '</td>';
    echo '<td>' . security_audit_h((string)$r['safe_error_expected']) . '</td>';
    echo '<td>' . security_audit_h((string)$r['audit_history_expected']) . '</td>';
    echo '<td><span class="p13sec-badge ' . security_audit_safe_status_badge((string)$r['risk']) . '">' . security_audit_h((string)$r['risk']) . '</span></td>';
    echo '<td>' . security_audit_h((string)$r['notes']) . '</td>';
    echo '</tr>';
}
echo '</tbody></table></div>';

echo '<div class="p13sec-boundary-box"><strong>یادداشت</strong><p>این صفحه write route را تغییر نمی‌دهد؛ فقط ممیزی و طبقه‌بندی است. فایل‌های MISSING بدون crash گزارش می‌شوند.</p></div>';

security_audit_render_foot();

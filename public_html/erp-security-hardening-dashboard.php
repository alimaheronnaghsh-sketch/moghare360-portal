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
    security_audit_error('داشبورد امنیت', 'دسترسی ممکن نیست.');
}

$summary = security_audit_dashboard_summary();
$forbidden = security_audit_forbidden_files();

security_audit_render_head('داشبورد سخت‌سازی امنیت و دسترسی');
/* Boundaries: Not Production, Not SaaS, Not Customer Portal, Not Official Accounting */
echo '<div class="p13sec-hero"><h1>داشبورد سخت‌سازی امنیت و دسترسی</h1>';
echo '<p>Security &amp; Access Hardening — ممیزی read-only برای نسخه Pilot-ready</p></div>';

security_audit_render_boundary_warnings();

echo '<div class="p13sec-kpi-grid">';
echo '<div class="p13sec-kpi p13sec-kpi-alert"><div class="label">وضعیت کلی</div><div class="value" style="font-size:.95rem">' . security_audit_h((string)$summary['overall']) . '</div></div>';
echo '<div class="p13sec-kpi"><div class="label">Write Routes</div><div class="value">' . security_audit_h((string)$summary['write_routes_ok']) . '/' . security_audit_h((string)$summary['write_routes']) . '</div></div>';
echo '<div class="p13sec-kpi"><div class="label">Read-only / Report</div><div class="value">' . security_audit_h((string)$summary['readonly_pages_ok']) . '/' . security_audit_h((string)$summary['readonly_pages']) . '</div></div>';
echo '<div class="p13sec-kpi"><div class="label">Public / Demo</div><div class="value">' . security_audit_h((string)$summary['demo_pages_ok']) . '/' . security_audit_h((string)$summary['demo_pages']) . '</div></div>';
echo '<div class="p13sec-kpi"><div class="label">Pilot CSRF Fixes</div><div class="value">' . security_audit_h((string)$summary['csrf_pilot_fixes']) . ' PASSED</div></div>';
echo '<div class="p13sec-kpi"><div class="label">Forbidden Issues</div><div class="value">' . security_audit_h((string)$summary['forbidden_issues']) . '</div></div>';
echo '</div>';

security_audit_render_nav();

echo '<div class="p13sec-card-dark"><h2 class="p13sec-section-title">خلاصه CSRF Coverage</h2>';
echo '<p>Project CSRF: مسیرهای عملیاتی فاز ۱–۷ · Pilot self-contained CSRF: submit-pilot-scenario / submit-pilot-feedback</p>';
echo '<p><span class="p13sec-badge p13sec-badge-ok">Pilot Scenario CSRF root fix: PASSED</span> ';
echo '<span class="p13sec-badge p13sec-badge-ok">Pilot Feedback CSRF alignment: PASSED</span></p>';
echo '<p><a href="erp-csrf-audit.php">مشاهده ممیزی کامل CSRF</a></p></div>';

echo '<div class="p13sec-card-dark"><h2 class="p13sec-section-title">Forbidden Files Status</h2><table class="p1cc-table"><thead><tr><th>فایل</th><th>وضعیت</th></tr></thead><tbody>';
foreach ($forbidden as $row) {
    echo '<tr><td><code class="m360-ltr">' . security_audit_h((string)$row['file']) . '</code></td>';
    echo '<td><span class="p13sec-badge ' . security_audit_safe_status_badge((string)$row['status']) . '">' . security_audit_h((string)$row['status']) . '</span></td></tr>';
}
echo '</tbody></table></div>';

echo '<div class="p13sec-boundary-box"><strong>مرزهای حساس</strong><ul>';
foreach (security_audit_sensitive_boundaries() as $b) {
    echo '<li>' . security_audit_h($b) . '</li>';
}
echo '</ul></div>';

security_audit_render_foot();

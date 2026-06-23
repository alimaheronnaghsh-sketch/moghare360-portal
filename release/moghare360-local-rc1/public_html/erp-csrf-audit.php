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
    security_audit_error('ممیزی CSRF', 'دسترسی ممکن نیست.');
}

$csrfRows = security_audit_csrf_expectations();

security_audit_render_head('ممیزی CSRF');
/* Boundaries: Not Production, Not SaaS, Not Customer Portal, Not Official Accounting */
echo '<div class="p13sec-hero"><h1>ممیزی CSRF</h1>';
echo '<p>CSRF Audit — فرم‌ها و مسیرهای controlled</p></div>';

echo '<div class="p13sec-warning-box"><strong>هشدار:</strong> این صفحه CSRF را تغییر نمی‌دهد؛ فقط ممیزی می‌کند.</div>';

security_audit_render_boundary_warnings();
security_audit_render_nav();

echo '<div class="p13sec-card-dark"><h2 class="p13sec-section-title">وضعیت شناخته‌شده / رفع‌شده</h2>';
echo '<p><span class="p13sec-badge p13sec-badge-ok">Pilot Scenario CSRF root fix: PASSED</span></p>';
echo '<p><span class="p13sec-badge p13sec-badge-ok">Pilot Feedback CSRF alignment: PASSED</span></p></div>';

echo '<div class="p13sec-card-dark"><h2 class="p13sec-section-title">مسیرهای controlled</h2>';
echo '<table class="p1cc-table"><thead><tr><th>فرم</th><th>Route</th><th>نوع CSRF</th><th>وضعیت</th><th>یادداشت</th></tr></thead><tbody>';
foreach ($csrfRows as $row) {
    $st = (string)$row['status'];
    echo '<tr><td>' . security_audit_h((string)$row['form']) . '</td>';
    echo '<td><code class="m360-ltr">' . security_audit_h((string)$row['route']) . '</code></td>';
    echo '<td>' . security_audit_h((string)$row['csrf_type']) . '</td>';
    echo '<td><span class="p13sec-badge ' . security_audit_safe_status_badge($st) . '">' . security_audit_h($st) . '</span></td>';
    echo '<td>' . security_audit_h((string)$row['notes']) . '</td></tr>';
}
echo '</tbody></table></div>';

echo '<div class="p13sec-card-dark"><h2 class="p13sec-section-title">نیازمند بازبینی دستی</h2><ul>';
foreach ($csrfRows as $row) {
    if (!in_array((string)$row['status'], ['PASSED', 'EXPECTED'], true)) {
        echo '<li>' . security_audit_h((string)$row['form']) . ' — ' . security_audit_h((string)$row['route']) . '</li>';
    }
}
echo '</ul></div>';

security_audit_render_foot();

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
    security_audit_error('ماتریس دسترسی نقش‌ها', 'دسترسی ممکن نیست.');
}

$matrix = security_audit_role_matrix();
$roles = security_audit_roles();
$modules = security_audit_modules();

security_audit_render_head('ماتریس دسترسی نقش‌ها');
/* Boundaries: Not Production, Not SaaS, Not Customer Portal, Not Official Accounting */
echo '<div class="p13sec-hero"><h1>ماتریس دسترسی نقش‌ها</h1>';
echo '<p>Role Access Matrix — طراحی کنترلی · Permission واقعی را تغییر نمی‌دهد</p></div>';

echo '<div class="p13sec-warning-box"><strong>هشدار:</strong> این ماتریس طراحی دسترسی است و Permission واقعی را تغییر نمی‌دهد.</div>';

security_audit_render_boundary_warnings();
security_audit_render_nav();

echo '<div class="p13sec-card-dark"><h2 class="p13sec-section-title">ماتریس ماژول × نقش (View/Create baseline)</h2>';
echo '<table class="p13sec-matrix-table"><thead><tr><th>ماژول</th>';
foreach ($roles as $role) {
    echo '<th>' . security_audit_h($role) . '</th>';
}
echo '</tr></thead><tbody>';
foreach ($modules as $mod) {
    echo '<tr><td><strong>' . security_audit_h($mod) . '</strong></td>';
    foreach ($roles as $role) {
        $level = $matrix[$mod][$role] ?? 'Review';
        echo '<td><span class="p13sec-badge ' . security_audit_safe_status_badge($level) . '">' . security_audit_h($level) . '</span></td>';
    }
    echo '</tr>';
}
echo '</tbody></table></div>';

echo '<div class="p13sec-card-dark"><h2 class="p13sec-section-title">Actions (مرجع طراحی)</h2><p>';
foreach (security_audit_actions() as $action) {
    echo '<span class="p13sec-badge p13sec-badge-muted" style="margin-left:.35rem">' . security_audit_h($action) . '</span> ';
}
echo '</p><p style="margin-top:.75rem;color:#9aa8b5;font-size:.88rem">Allowed = مجاز · Limited = محدود · Review = نیاز بازبینی · Not Recommended = توصیه نمی‌شود</p></div>';

security_audit_render_foot();

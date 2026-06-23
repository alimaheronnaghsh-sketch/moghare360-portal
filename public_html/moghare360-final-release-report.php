<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/erp-commercial-system-helper.php';

try {
    $c = commercial_db();
    if ($c !== false) { cs_require_auth($c, 'commercial.demo.view'); @odbc_close($c); }
} catch (Throwable) { cs_error('Final Release Report', 'دسترسی ممکن نیست.'); }

$phases = [
    ['1', 'Customer Core', 'COMPLETED'], ['2', 'Operation Engine', 'COMPLETED'], ['3', 'Rule Engine', 'COMPLETED'],
    ['4', 'Inventory & Purchase', 'COMPLETED'], ['5', 'Financial System', 'COMPLETED'], ['6', 'CRM System', 'COMPLETED'],
    ['7', 'HR & Internal Admin', 'COMPLETED'], ['8', 'UI Productization', 'COMPLETED'], ['9', 'Business Ready', 'COMPLETED'],
    ['10', 'Commercial System', 'COMPLETED'],
    ['11', 'Stabilization Sprint', 'IN PROGRESS / RC1'],
];

$statusRows = [
    ['Internal ERP', 'READY'], ['Business Ready System', 'READY'], ['Commercial Demo Ready', 'READY AFTER TEST'],
    ['SaaS Ready', 'DESIGN READY / NOT PRODUCTION SAAS'],
];

$urls = [
    'Commercial' => ['moghare360-commercial-demo.php', 'moghare360-sales-showcase.php', 'moghare360-product-packages.php', 'moghare360-license-preview.php', 'moghare360-commercial-checklist.php', 'moghare360-final-release-report.php'],
    'Management' => ['erp-management-dashboard.php', 'erp-kpi-report.php', 'erp-soft-run-audit.php'],
    'Product' => ['erp-business-command-center.php', 'erp-product-status.php', 'erp-soft-run-home.php'],
];

cs_render_head('Final Commercial Release Report');
echo '<div class="p10cs-hero"><h1>گزارش نهایی Commercial Release</h1><p>MOGHARE360 — Phases 1–10 Summary</p></div>';

echo '<div class="p1cc-card"><h2 class="p10cs-section-title">وضعیت فازها</h2><table class="p1cc-table"><thead><tr><th>فاز</th><th>عنوان</th><th>وضعیت</th></tr></thead><tbody>';
foreach ($phases as [$n, $t, $s]) {
    echo '<tr><td class="m360-num">' . commercial_h($n) . '</td><td>' . commercial_h($t) . '</td>';
    echo '<td><span class="p10cs-badge ' . cs_badge_class($s) . '">' . commercial_h($s) . '</span></td></tr>';
}
echo '</tbody></table></div>';

echo '<div class="p1cc-card"><h2 class="p10cs-section-title">وضعیت محصول</h2><table class="p1cc-table"><tbody>';
foreach ($statusRows as [$l, $v]) {
    echo '<tr><td>' . commercial_h($l) . '</td><td><span class="p10cs-badge ' . cs_badge_class($v) . '">' . commercial_h($v) . '</span></td></tr>';
}
echo '</tbody></table></div>';

echo '<div class="p1cc-card"><h2 class="p10cs-section-title">مرزهای معماری حفظ‌شده</h2><ul class="p10cs-list">';
foreach (commercial_product_boundaries() as $b) echo '<li>' . commercial_h($b) . '</li>';
echo '</ul></div>';

echo '<div class="p1cc-card"><h2 class="p10cs-section-title">عمداً ساخته نشده</h2><ul class="p10cs-list">';
foreach (commercial_not_built_items() as $item) echo '<li>' . commercial_h($item) . '</li>';
echo '</ul></div>';

echo '<div class="p1cc-card"><h2 class="p10cs-section-title">Browser URL Checklist</h2>';
foreach ($urls as $group => $list) {
    echo '<h3 class="p10cs-sub">' . commercial_h($group) . '</h3><ul class="p10cs-list">';
    foreach ($list as $page) {
        $ok = commercial_page_exists($page);
        echo '<li>' . ($ok ? '<a href="' . commercial_h($page) . '">' . commercial_h($page) . '</a>' : commercial_h($page));
        echo ' <span class="p10cs-badge ' . ($ok ? 'p10cs-badge-ok' : 'p10cs-badge-fail') . '">' . ($ok ? 'OK' : 'MISSING') . '</span></li>';
    }
    echo '</ul>';
}
echo '</div>';

echo '<div class="p1cc-card p10cs-final-statement"><p><strong>MOGHARE360 has been converted from Soft Run Internal ERP into a Business-Ready Repair Shop Operating System with Commercial Demo Readiness.</strong></p>';
echo '<p style="margin-top:.75rem">PHASE 11 Stabilization Sprint follows Commercial Release for Local Release Candidate 1 preparation — see <a href="erp-stabilization-dashboard.php">Stabilization Dashboard</a> and <a href="erp-local-release-candidate.php">Local RC1</a>.</p>';
echo '<p style="margin-top:.5rem">PHASE 12.5 — <a href="erp-brand-system.php">Brand System</a> · <a href="erp-localization-audit.php">Localization Audit</a> · <a href="erp-asset-registry.php">Asset Registry</a> · <a href="moghare360-demo-package.php">Demo Package</a></p>';
echo '<p style="margin-top:.5rem">PHASE 13 — <a href="erp-security-hardening-dashboard.php">Security Hardening Dashboard</a> · <a href="erp-write-route-audit.php">Write Route Audit</a> · <a href="erp-csrf-audit.php">CSRF Audit</a></p></div>';

cs_render_foot();

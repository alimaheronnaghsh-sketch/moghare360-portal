<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/moghare360-deployment-helper.php';

try {
    $c = mogh_deploy_db();
    if ($c !== false) {
        mogh_deploy_require_auth($c, 'deployment.plan.view');
        @odbc_close($c);
    }
} catch (Throwable) {
    mogh_deploy_error('داشبورد آمادگی استقرار', 'دسترسی ممکن نیست.');
}

$envs = mogh_deploy_environment_registry();
$backup = mogh_deploy_backup_requirements();
$migration = mogh_deploy_migration_plan_summary();
$rollback = mogh_deploy_rollback_plan_summary();
$monitoring = mogh_deploy_monitoring_plan_summary();
$config = mogh_deploy_config_boundary();
$security = mogh_deploy_security_prerequisites();
$blockers = mogh_deploy_production_blockers();

mogh_deploy_render_head('داشبورد آمادگی استقرار');
/* Boundaries: Not Production, Not SaaS, Not Customer Portal, Not Official Accounting, Not Production Deployed, No credentials in repo, No deployment executed */
echo '<div class="p14dep-hero"><h1>داشبورد آمادگی استقرار</h1>';
echo '<p>Production Deployment Plan — نقشه استقرار بدون اجرای deploy واقعی</p></div>';

mogh_deploy_render_warnings();

echo '<div class="p14dep-kpi-grid">';
foreach ($envs as $e) {
    $cls = ($e['status'] ?? '') === 'NOT DEPLOYED' ? 'p14dep-kpi-blocked' : 'p14dep-kpi-ready';
    echo '<div class="p14dep-kpi ' . $cls . '"><div class="label">' . mogh_deploy_h((string)$e['env']) . '</div>';
    echo '<div class="value" style="font-size:.85rem">' . mogh_deploy_h((string)$e['status']) . '</div></div>';
}
echo '</div>';

echo '<div class="p14dep-nav-grid">';
echo '<a class="p14dep-nav-card" href="erp-production-readiness-checklist.php"><span class="p14dep-nav-title">چک‌لیست آمادگی Production</span><span class="p14dep-nav-sub">Production Readiness Checklist</span></a>';
foreach (mogh_deploy_doc_links() as $doc) {
    $ok = mogh_deploy_doc_exists((string)$doc['path']);
    echo '<div class="p14dep-nav-card" style="cursor:default">';
    echo '<span class="p14dep-nav-title">' . mogh_deploy_h((string)$doc['title']) . '</span>';
    echo '<span class="p14dep-nav-sub"><code class="m360-ltr">' . mogh_deploy_h((string)$doc['path']) . '</code> ';
    echo '<span class="p14dep-badge ' . ($ok ? 'p14dep-badge-ok' : 'p14dep-badge-warn') . '">' . ($ok ? 'DOC OK' : 'PENDING') . '</span></span></div>';
}
echo '</div>';

echo '<div class="p14dep-card-dark"><h2 class="p14dep-section-title">Backup Requirements</h2><table class="p1cc-table"><thead><tr><th>نوع</th><th>فرکانس</th><th>مسئول</th><th>وضعیت</th></tr></thead><tbody>';
foreach ($backup as $row) {
    echo '<tr><td>' . mogh_deploy_h((string)$row['type']) . '</td><td>' . mogh_deploy_h((string)$row['frequency']) . '</td>';
    echo '<td>' . mogh_deploy_h((string)$row['owner']) . '</td>';
    echo '<td><span class="p14dep-badge ' . mogh_deploy_status_badge((string)$row['status']) . '">' . mogh_deploy_h((string)$row['status']) . '</span></td></tr>';
}
echo '</tbody></table></div>';

echo '<div class="p14dep-card-dark"><h2 class="p14dep-section-title">Database Migration Plan (خلاصه)</h2><table class="p1cc-table"><thead><tr><th>#</th><th>فایل SQL</th><th>وضعیت</th><th>قانون</th></tr></thead><tbody>';
foreach ($migration as $row) {
    echo '<tr><td class="m360-num">' . mogh_deploy_h((string)$row['seq']) . '</td>';
    echo '<td><code class="m360-ltr">' . mogh_deploy_h((string)$row['file']) . '</code></td>';
    echo '<td><span class="p14dep-badge ' . mogh_deploy_status_badge((string)$row['status']) . '">' . mogh_deploy_h((string)$row['status']) . '</span></td>';
    echo '<td>' . mogh_deploy_h((string)$row['rule']) . '</td></tr>';
}
echo '</tbody></table><p style="margin-top:.5rem;font-size:.85rem;color:#9aa8b5">No migration executed in this phase.</p></div>';

echo '<div class="p14dep-card-dark"><h2 class="p14dep-section-title">Rollback Plan (خلاصه)</h2><table class="p1cc-table"><thead><tr><th>مرحله</th><th>اقدام</th><th>جزئیات</th><th>وضعیت</th></tr></thead><tbody>';
foreach ($rollback as $row) {
    echo '<tr><td class="m360-num">' . mogh_deploy_h((string)$row['step']) . '</td>';
    echo '<td>' . mogh_deploy_h((string)$row['action']) . '</td><td>' . mogh_deploy_h((string)$row['detail']) . '</td>';
    echo '<td><span class="p14dep-badge ' . mogh_deploy_status_badge((string)$row['status']) . '">' . mogh_deploy_h((string)$row['status']) . '</span></td></tr>';
}
echo '</tbody></table></div>';

echo '<div class="p14dep-card-dark"><h2 class="p14dep-section-title">Monitoring Plan (خلاصه)</h2><table class="p1cc-table"><thead><tr><th>حوزه</th><th>فرکانس</th><th>وضعیت</th></tr></thead><tbody>';
foreach ($monitoring as $row) {
    echo '<tr><td>' . mogh_deploy_h((string)$row['area']) . '</td><td>' . mogh_deploy_h((string)$row['frequency']) . '</td>';
    echo '<td><span class="p14dep-badge ' . mogh_deploy_status_badge((string)$row['status']) . '">' . mogh_deploy_h((string)$row['status']) . '</span></td></tr>';
}
echo '</tbody></table><p style="margin-top:.5rem;font-size:.85rem;color:#9aa8b5">No monitoring integration created in this phase.</p></div>';

echo '<div class="p14dep-card-dark"><h2 class="p14dep-section-title">Config Boundary</h2><table class="p1cc-table"><tbody>';
foreach ($config as $row) {
    echo '<tr><td>' . mogh_deploy_h((string)$row['boundary']) . '</td>';
    echo '<td><span class="p14dep-badge ' . mogh_deploy_status_badge((string)$row['status']) . '">' . mogh_deploy_h((string)$row['status']) . '</span></td>';
    echo '<td>' . mogh_deploy_h((string)$row['policy']) . '</td></tr>';
}
echo '</tbody></table></div>';

echo '<div class="p14dep-card-dark"><h2 class="p14dep-section-title">Security Prerequisites</h2><ul>';
foreach ($security as $row) {
    echo '<li>' . mogh_deploy_h((string)$row['item']) . ' — <span class="p14dep-badge ' . mogh_deploy_status_badge((string)$row['status']) . '">' . mogh_deploy_h((string)$row['status']) . '</span>';
    if (($row['link'] ?? '') !== '') {
        echo ' <a href="' . mogh_deploy_h((string)$row['link']) . '">مشاهده</a>';
    }
    echo '</li>';
}
echo '</ul></div>';

echo '<div class="p14dep-card-dark"><h2 class="p14dep-section-title">Production Blockers</h2><ul>';
foreach ($blockers as $b) {
    echo '<li>' . mogh_deploy_h($b) . '</li>';
}
echo '</ul></div>';

echo '<div class="p14dep-card-dark"><h2 class="p14dep-section-title">Release Package (Phase 15)</h2>';
echo '<p>بسته خروجی قابل دانلود در <a href="erp-release-package-dashboard.php">Phase 15</a> ساخته می‌شود — Demo Package و Local RC1 ZIP.</p></div>';

mogh_deploy_render_foot();

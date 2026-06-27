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
    mogh_deploy_error('چک‌لیست Production', 'دسترسی ممکن نیست.');
}

$items = mogh_deploy_readiness_items();

mogh_deploy_render_head('چک‌لیست آمادگی Production');
/* Boundaries: Not Production, Not SaaS, Not Customer Portal, Not Official Accounting, Not Production Deployed, No credentials in repo, No deployment executed */
echo '<div class="p14dep-hero"><h1>چک‌لیست آمادگی Production</h1>';
echo '<p>Production Readiness Checklist — برنامه‌ریزی بدون اجرای استقرار</p></div>';

mogh_deploy_render_warnings();

echo '<div class="p14dep-card-dark"><h2 class="p14dep-section-title">چک‌لیست آمادگی</h2>';
foreach ($items as $row) {
    echo '<div class="p14dep-checklist-row">';
    echo '<span>' . mogh_deploy_h((string)$row['item']) . '</span>';
    echo '<span class="p14dep-badge ' . mogh_deploy_status_badge((string)$row['status']) . '">' . mogh_deploy_h((string)$row['status']) . '</span>';
    echo '</div>';
    if (($row['notes'] ?? '') !== '') {
        echo '<p style="margin:0 0 .5rem;font-size:.82rem;color:#8a9aab;padding-right:.5rem">' . mogh_deploy_h((string)$row['notes']) . '</p>';
    }
}
echo '</div>';

echo '<div class="p14dep-decision-box">';
echo '<h2 class="p14dep-section-title">تصمیم استقرار</h2>';
echo '<p>Ready for deployment planning: <span class="p14dep-badge p14dep-badge-ok">YES</span></p>';
echo '<p style="margin-top:.75rem">Ready for production execution: <span class="p14dep-badge p14dep-badge-fail">NO</span></p>';
echo '<p style="margin-top:1rem;font-size:.88rem;color:#9aa8b5">بسته دانلودی (PHASE 15) و استقرار واقعی پس از تأیید نهایی انجام می‌شود.</p>';
echo '</div>';

echo '<p style="margin-top:1.25rem"><a class="p1cc-btn p1cc-btn-primary" href="erp-deployment-readiness-dashboard.php">بازگشت به داشبورد استقرار</a></p>';

mogh_deploy_render_foot();

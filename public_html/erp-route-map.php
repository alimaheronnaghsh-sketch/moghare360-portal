<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/includes/m360-route-audit-helper.php';
require_once __DIR__ . '/includes/m360-route-operational-safety-helper.php';

m360_release_hardening_require_staff();

$view = m360_route_ops_normalize_view((string)($_GET['view'] ?? M360_ROUTE_OPS_VIEW_OPERATIONAL));
$rows = m360_route_ops_enrich_audit_rows();
$counts = m360_route_ops_summary_counts($rows);
$isOperationalView = $view === M360_ROUTE_OPS_VIEW_OPERATIONAL;

function m360_rmap_badge_class(string $opsClass): string
{
    return match ($opsClass) {
        M360_ROUTE_OPS_CLASS_OPERATIONAL => 'm360-rmap-badge-operational',
        M360_ROUTE_OPS_CLASS_GUIDED => 'm360-rmap-badge-guided',
        M360_ROUTE_OPS_CLASS_ACTION => 'm360-rmap-badge-action',
        M360_ROUTE_OPS_CLASS_API => 'm360-rmap-badge-api',
        M360_ROUTE_OPS_CLASS_CUSTOMER => 'm360-rmap-badge-customer',
        M360_ROUTE_OPS_CLASS_DIAGNOSTIC => 'm360-rmap-badge-diagnostic',
        M360_ROUTE_OPS_CLASS_RUNTIME_HOLD => 'm360-rmap-badge-runtime',
        default => 'm360-rmap-badge-guided',
    };
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نقشه مسیرها — MOGHARE360 V1</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/m360-release-hardening.css">
    <link rel="stylesheet" href="assets/css/m360-route-map-safety.css">
</head>
<body class="m360-rc-page">
<div class="w1c-wrap m360-rc-wrap">
    <header class="w1c-banner">
        <h1>نقشه مسیرها</h1>
        <p>P1–P10 — <?= (int)$counts['file_exists'] ?>/<?= (int)$counts['total'] ?> فایل موجود در دیسک</p>
    </header>
    <nav class="m360-rc-nav">
        <?php foreach (m360_nav_rc_links() as $link): ?>
            <a href="<?= m360_release_h((string)$link['href']) ?>" class="<?= $link['href'] === 'erp-route-map.php' ? 'active' : '' ?>"><?= m360_release_h((string)$link['label']) ?></a>
        <?php endforeach; ?>
    </nav>

    <div class="m360-rmap-view-tabs" aria-label="نمای Route Map">
        <a class="m360-rmap-view-tab <?= $isOperationalView ? 'active' : '' ?>" href="erp-route-map.php?view=operational">نمای عملیاتی</a>
        <a class="m360-rmap-view-tab <?= !$isOperationalView ? 'active' : '' ?>" href="erp-route-map.php?view=technical">نمای فنی</a>
    </div>

    <div class="m360-rmap-note">
        <?php if ($isOperationalView): ?>
            نمای عملیاتی — فقط مسیرهای <strong>قابل ورود</strong> و <strong>تشخیصی / مدیریتی</strong> (GET) لینک فعال دارند. وجود فایل به معنی آمادگی عملیاتی نیست.
        <?php else: ?>
            نمای فنی — فهرست کامل registry برای ممیزی. مسیرهای ناامن به صورت متن/کد نمایش داده می‌شوند، نه لینک «کلیک برای استفاده».
        <?php endif; ?>
    </div>

    <div class="m360-rmap-summary">
        <div class="m360-rmap-summary-card"><div class="val"><?= (int)$counts['total'] ?></div><div class="lbl">کل مسیرها</div></div>
        <div class="m360-rmap-summary-card"><div class="val"><?= (int)$counts['ops_clickable'] ?></div><div class="lbl">لینک فعال (عملیاتی)</div></div>
        <div class="m360-rmap-summary-card"><div class="val"><?= (int)$counts['unsafe_links_prevented'] ?></div><div class="lbl">مسیرهای محافظت‌شده</div></div>
    </div>

    <section class="w1c-card">
        <table class="m360-rc-table m360-rmap-table">
            <thead>
            <tr>
                <th>فاز</th>
                <th>عنوان / مسیر</th>
                <th>دسته</th>
                <th>متد</th>
                <th>فایل</th>
                <th>ایمنی عملیاتی</th>
                <th>رفتار لینک</th>
                <th>توضیح</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r):
                $opsClass = (string)($r['ops_class'] ?? '');
                $linkBehavior = m360_route_ops_link_behavior_fa($opsClass, $view, !empty($r['file_exists']));
                ?>
                <tr class="m360-rmap-row-<?= m360_route_ops_h($opsClass) ?>">
                    <td><?= m360_release_h((string)$r['phase_code']) ?></td>
                    <td><?php m360_route_ops_render_url_cell($r, $view); ?></td>
                    <td><?= m360_release_h((string)$r['category']) ?></td>
                    <td><?= m360_release_h((string)$r['expected_method']) ?></td>
                    <td>
                        <span class="m360-rmap-badge <?= !empty($r['file_exists']) ? 'm360-rmap-file-present' : 'm360-rmap-file-missing' ?>">
                            <?= m360_route_ops_h((string)($r['ops_file_status_fa'] ?? '')) ?>
                        </span>
                    </td>
                    <td>
                        <span class="m360-rmap-badge <?= m360_rmap_badge_class($opsClass) ?>">
                            <?= m360_route_ops_h((string)($r['ops_badge_fa'] ?? '')) ?>
                        </span>
                    </td>
                    <td class="<?= $linkBehavior === 'فعال' ? 'm360-rmap-link-active' : 'm360-rmap-link-disabled' ?>">
                        <?= m360_route_ops_h($linkBehavior) ?>
                    </td>
                    <td><span class="m360-rmap-reason"><?= m360_route_ops_h((string)($r['ops_reason_fa'] ?? '')) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>
</body>
</html>

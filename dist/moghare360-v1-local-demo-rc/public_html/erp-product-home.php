<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/includes/m360-release-hardening-helper.php';
require_once __DIR__ . '/includes/m360-release-readiness-helper.php';

m360_release_hardening_require_staff();

$audit = m360_release_hardening_audit();
$report = m360_release_readiness_report();

$cards = [
    ['label' => 'Reception', 'href' => 'erp-reception-jobcards.php', 'phase' => 'P2'],
    ['label' => 'Technical', 'href' => 'erp-technical-board.php', 'phase' => 'P3'],
    ['label' => 'Estimate', 'href' => 'erp-estimate-board.php', 'phase' => 'P4'],
    ['label' => 'Work Execution', 'href' => 'erp-work-execution-board.php', 'phase' => 'P5'],
    ['label' => 'QC', 'href' => 'erp-qc-board.php', 'phase' => 'P6'],
    ['label' => 'Final Invoice / Delivery', 'href' => 'erp-final-invoice-board.php', 'phase' => 'P7'],
    ['label' => 'Management Dashboard', 'href' => 'erp-management-dashboard.php', 'phase' => 'P8'],
    ['label' => 'Soft Run Control Center', 'href' => 'erp-soft-run-control-center.php', 'phase' => 'P9'],
    ['label' => 'Demo Flow Map', 'href' => 'erp-demo-flow-map.php', 'phase' => 'P9'],
    ['label' => 'Release Readiness', 'href' => 'erp-release-readiness.php', 'phase' => 'P10'],
];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MOGHARE360 V1 — Product Home</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/m360-release-hardening.css">
</head>
<body class="m360-rc-page">
<div class="w1c-wrap m360-rc-wrap">
    <header class="w1c-banner">
        <h1>MOGHARE360 V1 Product Home</h1>
        <p>ورود محصولی ERP — Navigation فقط، بدون تغییر workflow</p>
    </header>
    <nav class="m360-rc-nav">
        <?php foreach (m360_nav_rc_links() as $link): ?>
            <a href="<?= m360_release_h((string)$link['href']) ?>" class="<?= $link['href'] === 'erp-product-home.php' ? 'active' : '' ?>"><?= m360_release_h((string)$link['label']) ?></a>
        <?php endforeach; ?>
    </nav>
    <div class="m360-rc-cards">
        <div class="m360-rc-card"><div class="val"><span class="m360-rc-badge pass">READY</span></div><div class="lbl">Operational Workflow P1–P7</div></div>
        <div class="m360-rc-card"><div class="val"><span class="m360-rc-badge pass">READY</span></div><div class="lbl">Management Dashboard P8</div></div>
        <div class="m360-rc-card"><div class="val"><span class="m360-rc-badge pass">READY</span></div><div class="lbl">Soft Run P9</div></div>
        <div class="m360-rc-card"><div class="val"><span class="m360-rc-badge <?= m360_nav_badge_class((string)$audit['rc_status']) ?>"><?= m360_release_h((string)$audit['rc_status']) ?></span></div><div class="lbl">Demo Package RC P10</div></div>
    </div>
    <section class="w1c-card">
        <h2>ورود به ماژول‌ها</h2>
        <div class="m360-rc-cards">
            <?php foreach ($cards as $c): ?>
                <a class="m360-rc-card" href="<?= m360_release_h((string)$c['href']) ?>">
                    <div class="val"><?= m360_release_h((string)$c['phase']) ?></div>
                    <div class="lbl"><?= m360_release_h((string)$c['label']) ?></div>
                </a>
            <?php endforeach; ?>
        </div>
        <p class="m360-rc-note">Readiness Score: <?= m360_release_h((string)($audit['readiness_score'] ?? 0)) ?>% | Routes: <?= (int)($audit['existing_files'] ?? 0) ?>/<?= (int)($audit['total_routes'] ?? 0) ?></p>
        <p>
            <a class="m360-rc-btn" href="erp-route-map.php">Route Map</a>
            <a class="m360-rc-btn secondary" href="erp-link-audit.php">Link Audit</a>
            <a class="m360-rc-btn secondary" href="erp-demo-package-rc.php">Demo Package RC</a>
        </p>
    </section>
</div>
<script src="assets/js/m360-release-hardening.js"></script>
</body>
</html>

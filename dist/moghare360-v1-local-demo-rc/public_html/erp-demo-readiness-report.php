<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/includes/m360-demo-readiness-helper.php';

m360_soft_run_require_staff();

$conn = customer_core_db();
$report = $conn !== false ? m360_readiness_report($conn) : ['readiness_score' => 0, 'counts' => [], 'recommendation_fa' => '', 'items' => [], 'p8_views' => [], 'migrations' => []];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>گزارش Demo Readiness</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/m360-soft-run.css">
</head>
<body class="m360-sr-page">
<div class="w1c-wrap m360-sr-wrap">
    <header class="w1c-banner">
        <h1>گزارش Demo Readiness</h1>
        <p>read-only — <?= m360_soft_run_h((string)($report['recommendation_fa'] ?? '')) ?></p>
    </header>
    <nav class="m360-sr-nav">
        <?php foreach (m360_soft_run_nav() as $link): ?>
            <a href="<?= m360_soft_run_h($link['href']) ?>" class="<?= $link['href'] === 'erp-demo-readiness-report.php' ? 'active' : '' ?>"><?= m360_soft_run_h($link['label']) ?></a>
        <?php endforeach; ?>
    </nav>
    <div class="m360-sr-cards">
        <div class="m360-sr-card"><div class="val"><?= m360_soft_run_h((string)($report['readiness_score'] ?? 0)) ?>%</div><div class="lbl">Readiness Score</div></div>
        <div class="m360-sr-card"><div class="val"><?= (int)($report['counts']['PASS'] ?? 0) ?></div><div class="lbl">PASS</div></div>
        <div class="m360-sr-card"><div class="val"><?= (int)($report['counts']['WARNING'] ?? 0) ?></div><div class="lbl">WARNING</div></div>
        <div class="m360-sr-card"><div class="val"><?= (int)($report['counts']['BLOCKED'] ?? 0) ?></div><div class="lbl">BLOCKED</div></div>
    </div>
    <section class="w1c-card">
        <h2>Migrationها</h2>
        <ul>
            <li>P8: <?= !empty($report['migrations']['p8']) ? 'موجود' : 'ناموجود' ?></li>
            <li>P9: <?= !empty($report['migrations']['p9']) ? 'موجود' : 'ناموجود' ?></li>
        </ul>
        <h2>Viewهای P8</h2>
        <ul>
            <?php foreach (($report['p8_views'] ?? []) as $k => $ok): ?>
                <li><?= m360_soft_run_h((string)$k) ?>: <?= $ok ? 'OK' : 'Missing' ?></li>
            <?php endforeach; ?>
        </ul>
        <h2>Scope</h2>
        <ul>
            <li>بدون workflow mutation</li>
            <li>بدون payment gateway / accounting voucher</li>
        </ul>
        <p><a class="m360-sr-btn" href="erp-jobcard-timeline.php?jobcard_id=<?= (int)($report['demo_jobcard_id'] ?? 0) ?>">Timeline Demo JobCard</a></p>
    </section>
</div>
</body>
</html>

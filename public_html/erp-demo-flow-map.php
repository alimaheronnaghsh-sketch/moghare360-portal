<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/includes/m360-demo-scenario-helper.php';

m360_soft_run_require_staff();

$conn = customer_core_db();
$demo = $conn !== false ? m360_soft_run_find_demo_jobcard($conn) : null;
$jobcardId = (int)($demo['jobcard_id'] ?? 0);
$stages = m360_demo_scenario_stages();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نقشه Demo Flow</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/m360-soft-run.css">
</head>
<body class="m360-sr-page">
<div class="w1c-wrap m360-sr-wrap">
    <header class="w1c-banner">
        <h1>نقشه Demo Flow</h1>
        <p>مسیر P1 تا P8 — read-only</p>
    </header>
    <nav class="m360-sr-nav">
        <?php foreach (m360_soft_run_nav() as $link): ?>
            <a href="<?= m360_soft_run_h($link['href']) ?>" class="<?= $link['href'] === 'erp-demo-flow-map.php' ? 'active' : '' ?>"><?= m360_soft_run_h($link['label']) ?></a>
        <?php endforeach; ?>
    </nav>
    <div class="m360-sr-flow">
        <?php foreach ($stages as $st): ?>
            <div class="m360-sr-flow-item">
                <strong><?= m360_soft_run_h((string)$st['label_fa']) ?></strong>
                <div class="m360-sr-note">فاز <?= m360_soft_run_h((string)$st['phase']) ?></div>
                <div class="m360-sr-note">جدول: <?= m360_soft_run_h((string)$st['source_table']) ?></div>
                <a class="m360-sr-btn secondary" href="<?= m360_soft_run_h(m360_demo_stage_page_link($st, $jobcardId)) ?>">صفحه عملیاتی</a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>

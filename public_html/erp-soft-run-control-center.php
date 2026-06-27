<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/includes/m360-soft-run-helper.php';
require_once __DIR__ . '/includes/m360-demo-readiness-helper.php';

m360_soft_run_require_staff();

$conn = customer_core_db();
$categories = $conn !== false ? m360_soft_run_readiness_categories($conn) : [];
$phases = $conn !== false ? m360_soft_run_phase_status($conn) : [];
$report = $conn !== false ? m360_readiness_report($conn) : ['readiness_score' => 0, 'counts' => [], 'recommendation_fa' => ''];

function m360_sr_badge(string $s): string {
    return match (strtoupper($s)) {
        M360_SOFT_RUN_STATUS_PASS => 'pass',
        M360_SOFT_RUN_STATUS_WARNING => 'warn',
        M360_SOFT_RUN_STATUS_BLOCKED => 'block',
        default => 'notrun',
    };
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>کنترل‌سنتر Soft Run MOGHARE360</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/m360-soft-run.css">
</head>
<body class="m360-sr-page">
<div class="w1c-wrap m360-sr-wrap">
    <header class="w1c-banner">
        <h1>کنترل‌سنتر Soft Run</h1>
        <p>آمادگی Demo و بهره‌برداری آزمایشی — P1 تا P8</p>
    </header>
    <nav class="m360-sr-nav">
        <?php foreach (m360_soft_run_nav() as $link): ?>
            <a href="<?= m360_soft_run_h($link['href']) ?>" class="<?= $link['href'] === 'erp-soft-run-control-center.php' ? 'active' : '' ?>"><?= m360_soft_run_h($link['label']) ?></a>
        <?php endforeach; ?>
    </nav>
    <div class="m360-sr-cards">
        <div class="m360-sr-card"><div class="val"><?= m360_soft_run_h((string)($report['readiness_score'] ?? 0)) ?>%</div><div class="lbl">امتیاز Readiness</div></div>
        <div class="m360-sr-card"><div class="val"><?= (int)($report['demo_jobcard_id'] ?? 0) ?></div><div class="lbl">JobCard Demo</div></div>
        <div class="m360-sr-card"><div class="val"><?= (int)($report['counts']['PASS'] ?? 0) ?></div><div class="lbl">PASS</div></div>
        <div class="m360-sr-card"><div class="val"><?= (int)($report['counts']['WARNING'] ?? 0) ?></div><div class="lbl">WARNING</div></div>
        <div class="m360-sr-card"><div class="val"><?= (int)($report['counts']['BLOCKED'] ?? 0) ?></div><div class="lbl">BLOCKED</div></div>
    </div>
    <p class="m360-sr-note"><strong>توصیه:</strong> <?= m360_soft_run_h((string)($report['recommendation_fa'] ?? '')) ?></p>
    <section class="w1c-card">
        <h2>Readiness Categories</h2>
        <table class="m360-sr-table">
            <thead><tr><th>حوزه</th><th>وضعیت</th></tr></thead>
            <tbody>
            <?php
            $labels = ['database' => 'Database', 'workflow' => 'Workflow', 'gates' => 'Gates', 'ui' => 'UI', 'demo_data' => 'Demo Data', 'management' => 'Management P8', 'security' => 'Security/Scope'];
            foreach ($labels as $k => $label):
                $st = (string)($categories[$k] ?? M360_SOFT_RUN_STATUS_NOT_RUN);
            ?>
                <tr><td><?= m360_soft_run_h($label) ?></td><td><span class="m360-sr-badge <?= m360_sr_badge($st) ?>"><?= m360_soft_run_h($st) ?></span></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
    <section class="w1c-card">
        <h2>مسیر P1 تا P8</h2>
        <table class="m360-sr-table">
            <thead><tr><th>فاز</th><th>وضعیت</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($phases as $p): ?>
                <tr>
                    <td><?= m360_soft_run_h((string)$p['label_fa']) ?></td>
                    <td><span class="m360-sr-badge <?= m360_sr_badge((string)$p['status']) ?>"><?= m360_soft_run_h((string)$p['status']) ?></span></td>
                    <td><a class="m360-sr-btn secondary" href="<?= m360_soft_run_h((string)$p['href']) ?>">مشاهده</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>
<script src="assets/js/m360-soft-run.js"></script>
</body>
</html>

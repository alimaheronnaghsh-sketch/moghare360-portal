<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-bottleneck-helper.php';

m360_mgmt_require_staff();

$conn = customer_core_db();
$summary = $conn !== false ? m360_bottleneck_summary($conn) : ['stages' => [], 'highest_pressure_stage' => '', 'highest_pressure_count' => 0];
$stuck = $conn !== false ? m360_bottleneck_stuck_list($conn, 40) : [];
$stages = $summary['stages'] ?? [];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مانیتور گلوگاه‌ها</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/m360-management-dashboard.css">
</head>
<body class="m360-mgmt-page">
<div class="w1c-wrap m360-mgmt-wrap">
    <header class="w1c-banner">
        <h1>مانیتور گلوگاه‌ها</h1>
        <p>فشار مرحله‌ای — <?= m360_mgmt_h((string)($summary['highest_pressure_stage'] ?? '-')) ?> (<?= (int)($summary['highest_pressure_count'] ?? 0) ?>)</p>
    </header>
    <nav class="m360-mgmt-nav">
        <?php foreach (m360_mgmt_nav_links() as $link): ?>
            <a href="<?= m360_mgmt_h($link['href']) ?>" class="<?= $link['href'] === 'erp-bottleneck-monitor.php' ? 'active' : '' ?>"><?= m360_mgmt_h($link['label']) ?></a>
        <?php endforeach; ?>
    </nav>
    <section class="w1c-card">
        <table class="m360-mgmt-table">
            <thead><tr><th>مرحله</th><th>تعداد</th><th>میانگین سن (ساعت)</th><th>قدیمی‌ترین</th><th>بحرانی</th></tr></thead>
            <tbody>
            <?php foreach ($stages as $s): ?>
                <tr>
                    <td><?= m360_mgmt_h((string)($s['label_fa'] ?? '')) ?></td>
                    <td><?= (int)($s['count'] ?? 0) ?></td>
                    <td><?= m360_mgmt_h((string)($s['avg_age_hours'] ?? 0)) ?></td>
                    <td><?= (int)($s['oldest_jobcard_id'] ?? 0) ?> (<?= m360_mgmt_h((string)($s['oldest_age_hours'] ?? 0)) ?>h)</td>
                    <td><?= !empty($s['critical']) ? 'بله' : 'خیر' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
    <h2 class="m360-mgmt-section-title">JobCardهای گیرکرده</h2>
    <section class="w1c-card">
        <?php if ($stuck === []): ?>
            <p class="m360-mgmt-empty">گلوگاه فعالی شناسایی نشد.</p>
        <?php else: ?>
            <table class="m360-mgmt-table">
                <thead><tr><th>کارت کار</th><th>مرحله</th><th>سن</th><th>ریسک</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($stuck as $r): ?>
                    <tr>
                        <td><?= m360_mgmt_h((string)$r['jobcard_id']) ?></td>
                        <td><?= m360_mgmt_h((string)($r['current_stage_label_fa'] ?? '')) ?></td>
                        <td><?= m360_mgmt_h((string)($r['age_hours'] ?? 0)) ?></td>
                        <td><?= m360_mgmt_h((string)($r['risk_flags_text'] ?? '')) ?></td>
                        <td><a class="m360-mgmt-btn secondary" href="<?= m360_mgmt_h((string)$r['timeline_href']) ?>">Timeline</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</div>
</body>
</html>

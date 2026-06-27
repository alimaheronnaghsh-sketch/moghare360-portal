<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-management-kpi-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-owner-control-helper.php';

m360_mgmt_require_staff();

$period = isset($_GET['period']) ? strtolower(trim((string)$_GET['period'])) : 'today';
if (!isset(M360_MGMT_PERIOD_LABELS_FA[$period])) {
    $period = 'today';
}

$conn = customer_core_db();
$cards = $conn !== false ? m360_mgmt_dashboard_cards($conn, $period) : [];
$highRisk = $conn !== false ? m360_mgmt_high_risk_rows($conn, 20) : [];

$cardDefs = [
    ['key' => 'open_jobcards', 'label' => 'پرونده باز'],
    ['key' => 'closed_jobcards', 'label' => 'بسته‌شده'],
    ['key' => 'waiting_approval', 'label' => 'منتظر تأیید'],
    ['key' => 'approved_for_work', 'label' => 'تأیید برای کار'],
    ['key' => 'ready_for_qc', 'label' => 'آماده QC'],
    ['key' => 'delivery_ready', 'label' => 'آماده تحویل'],
    ['key' => 'settlement_pending', 'label' => 'در انتظار تسویه'],
    ['key' => 'overdue_24h', 'label' => 'معوق ۲۴س', 'class' => 'warn'],
    ['key' => 'overdue_48h', 'label' => 'معوق ۴۸س', 'class' => 'warn'],
    ['key' => 'overdue_72h', 'label' => 'معوق ۷۲س', 'class' => 'crit'],
    ['key' => 'rework_required', 'label' => 'Rework'],
    ['key' => 'final_invoice_total', 'label' => 'جمع فاکتور نهایی', 'money' => true],
    ['key' => 'paid_amount', 'label' => 'وصول‌شده', 'money' => true],
    ['key' => 'remaining_amount', 'label' => 'مانده', 'money' => true, 'class' => 'warn'],
];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد مدیریت MOGHARE360</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/m360-management-dashboard.css">
</head>
<body class="m360-mgmt-page">
<div class="w1c-wrap m360-mgmt-wrap">
    <header class="w1c-banner">
        <h1>داشبورد مدیریت</h1>
        <p>نمای read-only عملیاتی — P1 تا P7</p>
    </header>
    <nav class="m360-mgmt-nav">
        <?php foreach (m360_mgmt_nav_links() as $link): ?>
            <a href="<?= m360_mgmt_h($link['href']) ?>" class="<?= $link['href'] === 'erp-management-dashboard.php' ? 'active' : '' ?>"><?= m360_mgmt_h($link['label']) ?></a>
        <?php endforeach; ?>
        <a href="erp-jobcard-timeline.php">Timeline</a>
    </nav>
    <div class="m360-mgmt-filters">
        <?php foreach (M360_MGMT_PERIOD_LABELS_FA as $code => $label): ?>
            <a href="?period=<?= m360_mgmt_h($code) ?>" class="<?= $period === $code ? 'active' : '' ?>"><?= m360_mgmt_h($label) ?></a>
        <?php endforeach; ?>
    </div>
    <?php if ($conn === false): ?>
        <section class="w1c-card"><p>اتصال به پایگاه داده برقرار نشد.</p></section>
    <?php else: ?>
        <div class="m360-mgmt-cards">
            <?php foreach ($cardDefs as $def):
                $val = $cards[(string)$def['key']] ?? 0;
                $cls = (string)($def['class'] ?? '');
            ?>
                <div class="m360-mgmt-card <?= m360_mgmt_h($cls) ?>">
                    <div class="val"><?= m360_mgmt_h(!empty($def['money']) ? number_format((float)$val) : (string)$val) ?></div>
                    <div class="lbl"><?= m360_mgmt_h((string)$def['label']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <h2 class="m360-mgmt-section-title">پرونده‌های پرریسک</h2>
        <section class="w1c-card">
            <?php if ($highRisk === []): ?>
                <p class="m360-mgmt-empty">پرونده پرریسک فعالی ثبت نشده است.</p>
            <?php else: ?>
                <table class="m360-mgmt-table">
                    <thead><tr><th>کارت کار</th><th>مشتری</th><th>مرحله</th><th>ریسک</th><th>سن (ساعت)</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($highRisk as $r): ?>
                        <tr>
                            <td><?= m360_mgmt_h((string)$r['jobcard_id']) ?></td>
                            <td><?= m360_mgmt_h((string)($r['customer_name'] ?? '-')) ?></td>
                            <td><?= m360_mgmt_h((string)($r['current_stage_label_fa'] ?? '-')) ?></td>
                            <td><?= m360_mgmt_h((string)($r['risk_flags_text'] ?? '-')) ?></td>
                            <td><?= m360_mgmt_h((string)($r['age_hours'] ?? '0')) ?></td>
                            <td><a class="m360-mgmt-btn secondary" href="<?= m360_mgmt_h((string)$r['timeline_href']) ?>">Timeline</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>
<script src="assets/js/m360-management-dashboard.js"></script>
</body>
</html>

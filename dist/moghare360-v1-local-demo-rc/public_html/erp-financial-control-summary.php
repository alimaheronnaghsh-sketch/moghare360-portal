<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-financial-control-helper.php';

m360_mgmt_require_staff();

$conn = customer_core_db();
$summary = $conn !== false ? m360_financial_control_summary($conn) : m360_financial_control_empty();
$rows = $conn !== false ? m360_financial_control_rows($conn, 40) : [];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>خلاصه کنترل مالی عملیاتی</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/m360-management-dashboard.css">
</head>
<body class="m360-mgmt-page">
<div class="w1c-wrap m360-mgmt-wrap">
    <header class="w1c-banner">
        <h1>کنترل مالی عملیاتی</h1>
        <p>read-only — بدون payment entry / voucher / gateway</p>
    </header>
    <nav class="m360-mgmt-nav">
        <?php foreach (m360_mgmt_nav_links() as $link): ?>
            <a href="<?= m360_mgmt_h($link['href']) ?>" class="<?= $link['href'] === 'erp-financial-control-summary.php' ? 'active' : '' ?>"><?= m360_mgmt_h($link['label']) ?></a>
        <?php endforeach; ?>
    </nav>
    <div class="m360-mgmt-cards">
        <?php
        $finCards = [
            ['key' => 'final_invoice_total', 'label' => 'جمع فاکتور نهایی'],
            ['key' => 'paid_total', 'label' => 'پرداخت ثبت‌شده'],
            ['key' => 'remaining_total', 'label' => 'مانده', 'class' => 'warn'],
            ['key' => 'settlement_pending_count', 'label' => 'در انتظار تسویه'],
            ['key' => 'delivery_ready_unpaid_count', 'label' => 'آماده تحویل — unpaid', 'class' => 'crit'],
            ['key' => 'released_with_balance_count', 'label' => 'خروج با مانده', 'class' => 'crit'],
            ['key' => 'variance_cases_count', 'label' => 'Variance فاکتور'],
            ['key' => 'manager_release_count', 'label' => 'مجوز خروج مدیر'],
        ];
        foreach ($finCards as $c):
        ?>
            <div class="m360-mgmt-card <?= m360_mgmt_h((string)($c['class'] ?? '')) ?>">
                <div class="val"><?= m360_mgmt_h(number_format((float)($summary[(string)$c['key']] ?? 0))) ?></div>
                <div class="lbl"><?= m360_mgmt_h((string)$c['label']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    <section class="w1c-card">
        <h2 class="m360-mgmt-section-title">پرونده‌های دارای مانده / variance</h2>
        <?php if ($rows === []): ?>
            <p class="m360-mgmt-empty">مورد مالی پرریسک ثبت نشده است.</p>
        <?php else: ?>
            <table class="m360-mgmt-table">
                <thead><tr><th>کارت کار</th><th>مشتری</th><th>تسویه</th><th>مانده</th><th>ریسک</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= m360_mgmt_h((string)$r['jobcard_id']) ?></td>
                        <td><?= m360_mgmt_h((string)($r['customer_name'] ?? '-')) ?></td>
                        <td><?= m360_mgmt_h((string)($r['settlement_status'] ?? '-')) ?></td>
                        <td><?= m360_mgmt_h(number_format((float)($r['settlement_remaining_amount'] ?? $r['remaining_amount'] ?? 0))) ?></td>
                        <td><?= m360_mgmt_h((string)($r['risk_flags_text'] ?? '')) ?></td>
                        <td><a class="m360-mgmt-btn secondary" href="erp-jobcard-timeline.php?jobcard_id=<?= (int)$r['jobcard_id'] ?>">Timeline</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</div>
</body>
</html>

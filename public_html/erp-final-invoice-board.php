<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-final-invoice-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-operational-shell-helper.php';

m360_fi_require_staff();

/** @var array<string, string> */
$boardFilterLabels = [
    'DELIVERY_READY' => 'آماده تحویل',
    'DRAFT' => 'پیش‌نویس',
    'CALCULATED' => 'محاسبه‌شده',
    'FINALIZED' => 'نهایی‌شده',
    'SETTLEMENT_PENDING' => 'در انتظار تسویه',
    'SETTLED' => 'تسویه‌شده',
    'DELIVERY_SIGNED' => 'امضای تحویل',
    'DELIVERED' => 'تحویل‌شده',
    'CLOSED' => 'بسته‌شده',
];

$filter = isset($_GET['status']) ? strtoupper(trim((string)$_GET['status'])) : 'ALL';
$conn = customer_core_db();
$rows = $conn !== false ? m360_fi_board_list($conn, $filter === 'ALL' ? null : $filter, 150) : [];

$fiLabel = static function (string $code) use ($boardFilterLabels): string {
    if (function_exists('m360_fi_status_label')) {
        return m360_fi_status_label($code);
    }
    return $boardFilterLabels[$code] ?? $code;
};

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>برد فاکتور نهایی و تحویل</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
    <link rel="stylesheet" href="assets/css/m360-final-delivery.css">
    <link rel="stylesheet" href="<?= m360_operational_shell_h(m360_operational_shell_css_href()) ?>">
</head>
<body class="m360-fi-page">
<div class="w1c-wrap m360-fi-wrap">
    <?php m360_operational_shell_render_board('invoice_board'); ?>
    <header class="w1c-banner">
        <h1>برد فاکتور نهایی و تحویل</h1>
        <p>فاکتور نهایی — تسویه — امضای تحویل — خروج خودرو</p>
    </header>
    <?php if ($conn === false): ?>
        <section class="w1c-card"><p>اتصال به پایگاه داده برقرار نشد.</p></section>
    <?php else: ?>
        <nav class="m360-fi-filters">
            <a href="?status=ALL" class="<?= $filter === 'ALL' ? 'active' : '' ?>">همه</a>
            <?php foreach ($boardFilterLabels as $code => $label): ?>
                <a href="?status=<?= m360_fi_h($code) ?>" class="<?= $filter === $code ? 'active' : '' ?>"><?= m360_fi_h($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <section class="w1c-card">
            <?php if ($rows === []): ?>
                <p class="m360-fi-empty">پرونده‌ای برای فاکتور نهایی نیست.</p>
            <?php else: ?>
                <table class="m360-fi-table">
                    <thead><tr>
                        <th>کارت کار</th><th>مشتری</th><th>موبایل</th><th>خودرو</th><th>پلاک</th>
                        <th>QC</th><th>آمادگی تحویل</th><th>فاکتور</th><th>مبلغ نهایی</th>
                        <th>پرداخت‌شده</th><th>مانده</th><th>تسویه</th><th>تحویل</th><th></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($rows as $r):
                        $invoiceStatus = strtoupper(trim((string)($r['invoice_status'] ?? $r['final_invoice_status'] ?? '')));
                        $vehicle = trim((string)($r['vehicle_label'] ?? ''));
                        if ($vehicle === '') {
                            $vehicle = trim(trim((string)($r['brand'] ?? '')) . ' ' . trim((string)($r['model'] ?? ''))) ?: '-';
                        }
                        $detailHref = 'erp-final-invoice-detail.php?jobcard_id=' . (int)$r['jobcard_id'];
                        if ((int)($r['final_invoice_id'] ?? 0) > 0) {
                            $detailHref .= '&final_invoice_id=' . (int)$r['final_invoice_id'];
                        }
                    ?>
                        <tr>
                            <td><?= m360_fi_h((string)$r['jobcard_id']) ?></td>
                            <td><?= m360_fi_h((string)($r['customer_name'] ?? '-')) ?></td>
                            <td><?= m360_fi_h((string)($r['customer_mobile'] ?? '-')) ?></td>
                            <td><?= m360_fi_h($vehicle) ?></td>
                            <td><?= m360_fi_h((string)($r['plate_number'] ?? '-')) ?></td>
                            <td><?= m360_fi_h((string)($r['qc_status_label'] ?? $r['qc_status'] ?? '-')) ?></td>
                            <td><?= m360_fi_h((string)($r['delivery_readiness_status'] ?? '-')) ?></td>
                            <td><?= m360_fi_h($invoiceStatus !== '' ? $fiLabel($invoiceStatus) : '-') ?></td>
                            <td><?= m360_fi_h(number_format((float)($r['total_amount'] ?? $r['final_invoice_amount'] ?? 0))) ?></td>
                            <td><?= m360_fi_h(number_format((float)($r['settlement_amount_paid'] ?? $r['total_paid_amount'] ?? 0))) ?></td>
                            <td><?= m360_fi_h(number_format((float)($r['settlement_remaining_amount'] ?? $r['remaining_amount'] ?? 0))) ?></td>
                            <td><?= m360_fi_h((string)($r['settlement_status_label'] ?? $r['settlement_status'] ?? '-')) ?></td>
                            <td><?= m360_fi_h((string)($r['customer_delivery_status'] ?? '-')) ?></td>
                            <td><a class="m360-fi-btn" href="<?= m360_fi_h($detailHref) ?>">جزئیات</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>
</body>
</html>

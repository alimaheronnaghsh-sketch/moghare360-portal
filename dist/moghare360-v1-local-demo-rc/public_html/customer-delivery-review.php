<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-customer-delivery-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-technical-operation-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-parts-consumption-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-qc-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-settlement-helper.php';

$token = trim((string)($_GET['token'] ?? ''));
$resolved = m360_delivery_resolve_token($token);
$error = !$resolved['ok'];
$invoice = $resolved['invoice'] ?? null;

/** @var array<string, string> */
$settleLabels = [
    M360_SETTLE_PAYMENT_PENDING => 'در انتظار پرداخت',
    M360_SETTLE_PARTIAL => 'تسویه جزئی',
    M360_SETTLE_SETTLED => 'تسویه کامل',
    M360_SETTLE_MANAGER_RELEASE => 'مجوز مدیریتی',
    M360_SETTLE_BLOCKED => 'مسدود',
];

$jc = null;
$items = [];
$serviceOps = [];
$consumedParts = [];
$settlement = null;
$confirmed = false;
$signUrl = 'customer-delivery-sign.php?token=' . rawurlencode($token);

if (is_array($invoice)) {
    $conn = customer_core_db();
    $jobcardId = (int)($invoice['jobcard_id'] ?? 0);
    if ($conn !== false && $jobcardId > 0) {
        $jc = m360_fi_fetch_jobcard($conn, $jobcardId);
        $items = m360_fi_list_items($conn, (int)($invoice['final_invoice_id'] ?? 0));
        $serviceOps = m360_technical_list_service_operations($conn, $jobcardId);
        $consumedParts = m360_parts_list_consumed($conn, $jobcardId);
        $settlement = m360_settlement_fetch_active($conn, $jobcardId);
        $confirmed = m360_delivery_is_confirmed($conn, $jobcardId);
    }
}

function m360_del_review_h(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function m360_del_review_settle_label(string $status, array $labels): string
{
    return $labels[strtoupper(trim($status))] ?? $status;
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بررسی تحویل خودرو</title>
    <link rel="stylesheet" href="assets/css/m360-estimate.css">
</head>
<body class="m360-est-page m360-est-customer">
<div class="m360-est-wrap">
    <?php if ($error || !is_array($invoice)): ?>
        <div class="m360-est-flash err"><?= m360_del_review_h($resolved['message'] ?: 'لینک نامعتبر است.') ?></div>
    <?php elseif ($confirmed): ?>
        <div class="m360-est-flash ok">تحویل قبلاً تأیید شده است — فقط مشاهده</div>
        <p>مبلغ نهایی: <?= m360_del_review_h(number_format((float)($invoice['total_amount'] ?? 0))) ?> تومان</p>
    <?php else: ?>
        <h1>بررسی تحویل خودرو</h1>

        <?php if (is_array($jc)): ?>
            <p>خودرو: <?= m360_del_review_h(trim((string)($jc['brand'] ?? '') . ' ' . (string)($jc['model'] ?? '')) ?: '-') ?> — پلاک <?= m360_del_review_h((string)($jc['plate_number'] ?? '-')) ?></p>
            <p>مشتری: <?= m360_del_review_h((string)($jc['customer_name'] ?? '-')) ?></p>
        <?php endif; ?>

        <?php if ($serviceOps !== []): ?>
            <h2 style="font-size:1rem;margin-top:1.25rem">خدمات انجام‌شده</h2>
            <table class="m360-est-table">
                <thead><tr><th>شرح</th><th>وضعیت</th></tr></thead>
                <tbody>
                <?php foreach ($serviceOps as $op): ?>
                    <tr>
                        <td><?= m360_del_review_h((string)($op['operation_title'] ?? $op['service_title'] ?? '-')) ?></td>
                        <td><?= m360_del_review_h((string)($op['operation_status'] ?? '-')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if ($consumedParts !== []): ?>
            <h2 style="font-size:1rem;margin-top:1.25rem">قطعات مصرف‌شده</h2>
            <table class="m360-est-table">
                <thead><tr><th>قطعه</th><th>تعداد</th></tr></thead>
                <tbody>
                <?php foreach ($consumedParts as $part): ?>
                    <tr>
                        <td><?= m360_del_review_h((string)($part['part_name'] ?? $part['item_title'] ?? '-')) ?></td>
                        <td><?= m360_del_review_h((string)($part['quantity_used'] ?? $part['quantity'] ?? '-')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if (is_array($jc)): ?>
            <p style="margin-top:1rem"><strong>QC:</strong> <?= m360_del_review_h(m360_qc_status_label(m360_qc_effective_status($jc))) ?></p>
        <?php endif; ?>

        <p class="m360-est-total" style="margin-top:1rem">مبلغ نهایی: <strong><?= m360_del_review_h(number_format((float)($invoice['total_amount'] ?? 0))) ?></strong> تومان</p>

        <?php if (is_array($settlement)): ?>
            <p><strong>وضعیت تسویه:</strong> <?= m360_del_review_h(m360_del_review_settle_label((string)($settlement['settlement_status'] ?? ''), $settleLabels)) ?></p>
            <p>پرداخت‌شده: <?= m360_del_review_h(number_format((float)($settlement['total_paid_amount'] ?? 0))) ?> — مانده: <?= m360_del_review_h(number_format((float)($settlement['remaining_amount'] ?? 0))) ?> تومان</p>
        <?php endif; ?>

        <?php if ($items !== []): ?>
            <h2 style="font-size:1rem;margin-top:1.25rem">اقلام فاکتور نهایی</h2>
            <table class="m360-est-table">
                <thead><tr><th>شرح</th><th>مبلغ</th></tr></thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td><?= m360_del_review_h((string)($it['item_title'] ?? '-')) ?></td>
                        <td><?= m360_del_review_h(number_format((float)($it['line_total'] ?? 0))) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <p class="m360-est-legal">اینجانب خودرو را پس از انجام خدمات، کنترل نهایی و اعلام وضعیت مالی، مشاهده و تحویل می‌گیرم. موارد باقی‌مانده یا توصیه‌های بعدی در سیستم ثبت شده است.</p>

        <form id="m360-delivery-review-form" action="<?= m360_del_review_h($signUrl) ?>" method="get">
            <input type="hidden" name="token" value="<?= m360_del_review_h($token) ?>">
            <label><input type="checkbox" name="c1" required> خودرو را مشاهده کردم</label><br>
            <label><input type="checkbox" name="c2" required> خدمات و توضیحات نهایی را مشاهده کردم</label><br>
            <label><input type="checkbox" name="c3" required> وضعیت مالی/تسویه را مشاهده کردم</label><br>
            <label><input type="checkbox" name="c4" required> شرایط تحویل را می‌پذیرم</label><br>
            <button type="submit" class="m360-est-btn" style="margin-top:1rem">ادامه — امضای تحویل</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>

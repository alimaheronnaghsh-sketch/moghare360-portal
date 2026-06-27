<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-estimate-approval-helper.php';

$token = trim((string)($_GET['token'] ?? ''));
$resolved = m360_estimate_resolve_token($token);
$error = !$resolved['ok'] && $resolved['estimate'] === null;
$est = $resolved['estimate'] ?? null;
$approved = is_array($est) && m360_estimate_is_customer_approved($est);

if (!$error && is_array($est)) {
    $conn = customer_core_db();
    if ($conn !== false) {
        m360_estimate_mark_viewed($conn, (int)$est['estimate_id']);
    }
}

$items = [];
$jc = null;
if (is_array($est) && customer_core_db() !== false) {
    $conn = customer_core_db();
    $items = m360_estimate_list_items($conn, (int)$est['estimate_id']);
    $jc = m360_estimate_fetch_jobcard($conn, (int)$est['jobcard_id']);
}

$signUrl = 'customer-estimate-approval-sign.php?token=' . rawurlencode($token);

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>برآورد هزینه تعمیر</title>
    <link rel="stylesheet" href="assets/css/m360-estimate.css">
</head>
<body class="m360-est-page m360-est-customer">
<div class="m360-est-wrap">
    <?php if ($error): ?>
        <div class="m360-est-flash err"><?= m360_estimate_h($resolved['message']) ?></div>
    <?php elseif ($approved): ?>
        <div class="m360-est-flash ok">این برآورد قبلاً تأیید شده است.</div>
        <p>مبلغ تأیید‌شده: <?= m360_estimate_h(number_format((float)($est['total_amount'] ?? 0))) ?> تومان</p>
    <?php else: ?>
        <h1>برآورد هزینه خدمات</h1>
        <p>خودرو: <?= m360_estimate_h((string)($jc['vehicle_label'] ?? trim((string)($jc['brand'] ?? '') . ' ' . (string)($jc['model'] ?? '')))) ?> — پلاک <?= m360_estimate_h((string)($jc['plate_number'] ?? '-')) ?></p>
        <table class="m360-est-table">
            <thead><tr><th>شرح</th><th>تعداد</th><th>مبلغ</th></tr></thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td><?= m360_estimate_h((string)$it['item_title']) ?></td>
                    <td><?= m360_estimate_h((string)$it['quantity']) ?></td>
                    <td><?= m360_estimate_h(number_format((float)$it['line_total'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p class="m360-est-total">مبلغ کل: <strong><?= m360_estimate_h(number_format((float)($est['total_amount'] ?? 0))) ?></strong> تومان</p>
        <p>علی‌الحساب لازم: <strong><?= m360_estimate_h(number_format((float)($est['advance_required_amount'] ?? 0))) ?></strong> تومان</p>
        <p class="m360-est-legal">این برآورد ممکن است پس از باز شدن قطعات یا کشف ایرادات پنهان تغییر کند و تغییرات خارج از سقف تأیید نیازمند تأیید مجدد است.</p>
        <a class="m360-est-btn" href="<?= m360_estimate_h($signUrl) ?>">مشاهده کردم — ادامه تأیید</a>
    <?php endif; ?>
</div>
</body>
</html>

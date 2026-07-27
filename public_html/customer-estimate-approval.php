<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-estimate-approval-helper.php';

$conn = customer_core_db();
$token = trim((string)($_GET['token'] ?? ''));
$taskId = (int)($_GET['task_id'] ?? 0);
$context = is_resource($conn)
    ? m360_estimate_resolve_customer_context($conn, ['token' => $token, 'task_id' => $taskId])
    : ['ok' => false, 'message' => 'سرویس در دسترس نیست.', 'estimate' => null, 'version' => null, 'items' => [], 'task' => null, 'entry_mode' => '', 'raw_token' => $token, 'jobcard' => null];

$error = !$context['ok'];
$est = $context['estimate'] ?? null;
$version = $context['version'] ?? null;
$items = is_array($context['items'] ?? null) ? $context['items'] : [];
$jc = $context['jobcard'] ?? null;
$approved = is_array($est) && is_array($version) && m360_estimate_is_customer_approved($est, $version);
$rejected = is_array($version) && strtoupper((string)($version['version_status'] ?? '')) === M360_EST_VERSION_STATUS_REJECTED;
$contentHash = is_array($version) ? (string)($version['content_hash'] ?? '') : '';
$versionNumber = is_array($version) ? (int)($version['version_number'] ?? 0) : 0;
$issuedAt = is_array($version) ? (string)($version['issued_at'] ?? '') : '';
$requestLabel = '';
if (is_array($jc)) {
    $requestId = (int)($jc['online_request_id'] ?? 0);
    $requestLabel = $requestId > 0 ? 'REQ-' . (string)$requestId : (string)($jc['jobcard_number'] ?? ('JC-' . (string)($jc['jobcard_id'] ?? '')));
}

if (!$error && is_array($version) && is_resource($conn)) {
    m360_estimate_mark_version_viewed($conn, $version);
    if (is_array($context['task'] ?? null)) {
        m360_cartable_mark_opened($conn, (int)$context['task']['task_id'], 'CUSTOMER', null);
    }
}

$signQuery = [];
if ($taskId > 0) {
    $signQuery['task_id'] = (string)$taskId;
}
if ($token !== '') {
    $signQuery['token'] = $token;
}
$signUrl = 'customer-estimate-approval-sign.php' . ($signQuery !== [] ? '?' . http_build_query($signQuery) : '');

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
        <div class="m360-est-flash err"><?= m360_estimate_h((string)$context['message']) ?></div>
    <?php elseif ($approved): ?>
        <div class="m360-est-flash ok">این برآورد قبلاً تأیید شده است.</div>
        <p>مبلغ تأیید‌شده: <?= m360_estimate_h(number_format((float)($version['total_amount'] ?? $est['total_amount'] ?? 0))) ?> تومان</p>
    <?php elseif ($rejected): ?>
        <div class="m360-est-flash err">این برآورد رد شده است.</div>
    <?php else: ?>
        <h1>برآورد هزینه خدمات</h1>
        <p>پرونده مرتبط: <?= m360_estimate_h($requestLabel) ?></p>
        <p>خودرو: <?= m360_estimate_h((string)($jc['vehicle_label'] ?? trim((string)($jc['brand'] ?? '') . ' ' . (string)($jc['model'] ?? '')))) ?> — پلاک <?= m360_estimate_h((string)($jc['plate_number'] ?? '-')) ?></p>
        <p>شماره نسخه برآورد: <?= m360_estimate_h((string)$versionNumber) ?></p>
        <?php if ($issuedAt !== ''): ?><p>زمان صدور: <?= m360_estimate_h($issuedAt) ?></p><?php endif; ?>
        <?php if ($versionNumber > 1): ?><p class="m360-est-legal">این نسخه به‌روزرسانی برآورد قبلی است.</p><?php endif; ?>
        <table class="m360-est-table">
            <thead><tr><th>شرح</th><th>تعداد</th><th>واحد</th><th>مبلغ</th></tr></thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td>
                        <?= m360_estimate_h((string)$it['item_title']) ?>
                        <?php if (trim((string)($it['item_description'] ?? '')) !== ''): ?>
                            <div class="m360-est-muted"><?= m360_estimate_h((string)$it['item_description']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= m360_estimate_h((string)$it['quantity']) ?></td>
                    <td><?= m360_estimate_h((string)($it['unit_name'] ?? 'عدد')) ?></td>
                    <td><?= m360_estimate_h(number_format((float)$it['line_total'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p>جمع جزء: <?= m360_estimate_h(number_format((float)($version['subtotal_amount'] ?? 0))) ?> تومان</p>
        <p>تخفیف: <?= m360_estimate_h(number_format((float)($version['discount_amount'] ?? 0))) ?> تومان</p>
        <p>مالیات: <?= m360_estimate_h(number_format((float)($version['tax_amount'] ?? 0))) ?> تومان</p>
        <p class="m360-est-total">مبلغ کل قابل پرداخت: <strong><?= m360_estimate_h(number_format((float)($version['total_amount'] ?? 0))) ?></strong> تومان</p>
        <p class="m360-est-legal">این برآورد ممکن است پس از باز شدن قطعات یا کشف ایرادات پنهان تغییر کند و تغییرات خارج از سقف تأیید نیازمند تأیید مجدد است.</p>
        <a class="m360-est-btn" href="<?= m360_estimate_h($signUrl) ?>">مشاهده کردم — ادامه تأیید</a>
    <?php endif; ?>
</div>
</body>
</html>

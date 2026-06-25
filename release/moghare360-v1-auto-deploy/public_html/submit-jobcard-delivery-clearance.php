<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Submit JobCard Delivery Clearance (Wave 4C)
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-delivery-clearance-helper.php';

function wave4c_submit_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: erp-jobcard-delivery-clearance.php');
    exit;
}

$payload = [
    'jobcard_id' => trim((string)($_POST['jobcard_id'] ?? '')),
    'clearance_status' => trim((string)($_POST['clearance_status'] ?? '')),
    'clearance_decision' => trim((string)($_POST['clearance_decision'] ?? '')),
    'reviewer_name' => trim((string)($_POST['reviewer_name'] ?? '')),
    'clearance_note' => trim((string)($_POST['clearance_note'] ?? '')),
];

$validation = moghare360_delivery_clearance_validate_payload($payload);
$result = null;
$eligibility = null;

if ($validation['ok']) {
    $jobcardId = (int)$validation['clean']['jobcard_id'];
    $eligibility = moghare360_delivery_clearance_fetch_eligibility($jobcardId);
    $result = moghare360_delivery_clearance_create($payload);
}

$jobcardIdForLink = trim((string)($_POST['jobcard_id'] ?? '1'));

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>نتیجه ثبت Clearance تحویل</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w4c-wrap">
    <header class="w1c-banner w4c-banner">
        <h1>نتیجه ثبت Clearance تحویل</h1>
        <p>Wave 4C — Internal delivery clearance only</p>
    </header>

    <section class="w1c-card w4c-warning">
        <strong>This is internal delivery clearance only — not final vehicle delivery. Not legal final e-signature.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">پرداخت/حسابداری/پورتال عمومی فعال نیست. تحویل نهایی خودرو انجام نمی‌شود.</p>
    </section>

    <?php if (!$validation['ok']): ?>
        <section class="w1c-card w1c-error-box">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">خطای اعتبارسنجی</h2>
            <ul style="margin:0;padding-right:1.25rem;">
                <?php foreach ($validation['errors'] as $error): ?>
                    <li><?= wave4c_submit_h((string)($error['message'] ?? '')) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php elseif ($result !== null && ($result['ok'] ?? false) === true): ?>
        <section class="w1c-card w1c-success">
            <p style="margin:0;"><?= wave4c_submit_h((string)($result['message'] ?? 'ثبت موفق')) ?></p>
            <?php if (($result['clearance_id'] ?? null) !== null): ?>
                <p style="margin:0.5rem 0 0;"><strong>شناسه Clearance:</strong> <?= wave4c_submit_h((string)$result['clearance_id']) ?></p>
            <?php endif; ?>
            <p style="margin:0.35rem 0 0;font-size:0.85rem;">وضعیت پایه داده: <?= wave4c_submit_h((string)($result['schema_status'] ?? '')) ?></p>
        </section>
    <?php elseif ($result !== null && ($result['schema_status'] ?? '') === MOGHARE360_DELIVERY_CLEARANCE_SCHEMA_BLOCKED): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave4c_submit_h(MOGHARE360_DELIVERY_CLEARANCE_BLOCK_MESSAGE) ?></p>
            <p style="margin:0.5rem 0 0;font-size:0.85rem;">ثبت رکورد تا اجرای SQL در SSMS مسدود است — موفقیت جعلی نمایش داده نمی‌شود.</p>
        </section>
    <?php elseif ($result !== null && !empty($result['errors'])): ?>
        <section class="w1c-card w1c-error-box">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;"><?= wave4c_submit_h((string)($result['message'] ?? 'ثبت ناموفق')) ?></h2>
            <?php if ($eligibility !== null): ?>
                <p style="margin:0 0 0.5rem;font-size:0.88rem;">
                    صلاحیت تحویل: <?= wave4c_submit_h(moghare360_delivery_eligibility_status_label((string)($eligibility['status'] ?? ''))) ?>
                    (<?= wave4c_submit_h((string)($eligibility['status'] ?? '')) ?>)
                </p>
            <?php endif; ?>
            <ul style="margin:0;padding-right:1.25rem;">
                <?php foreach ($result['errors'] as $error): ?>
                    <li><?= wave4c_submit_h((string)($error['message'] ?? '')) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php else: ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave4c_submit_h((string)($result['message'] ?? 'ثبت Clearance ناموفق بود.')) ?></p>
        </section>
    <?php endif; ?>

    <nav class="w1c-card w1c-links w4c-nav">
        <a href="erp-jobcard-delivery-clearance.php?jobcard_id=<?= wave4c_submit_h($jobcardIdForLink) ?>">ثبت Clearance جدید</a>
        <a href="erp-jobcard-delivery-clearance-preview.php?jobcard_id=<?= wave4c_submit_h($jobcardIdForLink) ?>">سوابق Clearance تحویل</a>
        <a href="erp-jobcard-delivery-eligibility.php?jobcard_id=<?= wave4c_submit_h($jobcardIdForLink) ?>">بررسی صلاحیت تحویل</a>
    </nav>
</div>
</body>
</html>

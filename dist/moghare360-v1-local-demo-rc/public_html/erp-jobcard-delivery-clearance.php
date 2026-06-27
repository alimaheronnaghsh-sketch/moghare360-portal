<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — JobCard Delivery Clearance (Wave 4C)
 * Internal delivery clearance only — NOT final vehicle delivery
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-delivery-clearance-helper.php';

function wave4c_form_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$statuses = moghare360_delivery_clearance_allowed_statuses();
$decisions = moghare360_delivery_clearance_allowed_decisions();
$statusLabels = moghare360_delivery_clearance_status_labels();
$decisionLabels = moghare360_delivery_clearance_decision_labels();
$schema = moghare360_delivery_clearance_schema_status();

$jobcardIdRaw = trim((string)($_GET['jobcard_id'] ?? ''));
$prefillJobcardId = ($jobcardIdRaw !== '' && ctype_digit($jobcardIdRaw) && (int)$jobcardIdRaw >= 1)
    ? (int)$jobcardIdRaw
    : null;
$eligibility = $prefillJobcardId !== null
    ? moghare360_delivery_clearance_fetch_eligibility($prefillJobcardId)
    : null;

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>ثبت Clearance داخلی تحویل</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w4c-wrap">
    <header class="w1c-banner w4c-banner">
        <h1>ثبت Clearance داخلی تحویل</h1>
        <p>WAVE 4C — Delivery Clearance Record Foundation</p>
    </header>

    <section class="w1c-card w4c-warning">
        <strong>This is internal delivery clearance only — not final vehicle delivery. not legal final e-signature.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">این Clearance داخلی است — تحویل نهایی خودرو نیست. امضای الکترونیکی قانونی نهایی نیست. پرداخت/حسابداری/پورتال عمومی فعال نیست.</p>
    </section>

    <?php if (($schema['schema_status'] ?? '') === MOGHARE360_DELIVERY_CLEARANCE_SCHEMA_BLOCKED): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave4c_form_h(MOGHARE360_DELIVERY_CLEARANCE_BLOCK_MESSAGE) ?></p>
            <p style="margin:0.5rem 0 0;font-size:0.85rem;">پایه داده باید پس از تأیید ChatGPT در SSMS اجرا شود: wave_4c_delivery_clearance_foundation.sql</p>
        </section>
    <?php else: ?>
        <section class="w1c-card w1c-success">
            <p style="margin:0;">پایه داده Clearance تحویل تأیید شد — ثبت رکورد فعال است.</p>
        </section>
    <?php endif; ?>

    <?php if ($eligibility !== null): ?>
        <section class="w1c-card w4c-eligibility">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">صلاحیت تحویل (WAVE 4B) — کارت کار <?= wave4c_form_h((string)$prefillJobcardId) ?></h2>
            <p style="margin:0;">
                <strong><?= wave4c_form_h(moghare360_delivery_eligibility_status_label((string)($eligibility['status'] ?? ''))) ?></strong>
                (<?= wave4c_form_h((string)($eligibility['status'] ?? '')) ?>)
            </p>
            <p style="margin:0.35rem 0 0;font-size:0.88rem;"><?= wave4c_form_h((string)($eligibility['message'] ?? '')) ?></p>
        </section>
    <?php endif; ?>

    <section class="w1c-card w1c-form">
        <form method="post" action="submit-jobcard-delivery-clearance.php">
            <label for="jobcard_id">شناسه کارت کار <span style="color:#b91c1c;">*</span></label>
            <input type="number" id="jobcard_id" name="jobcard_id" min="1" required
                   value="<?= $prefillJobcardId !== null ? wave4c_form_h((string)$prefillJobcardId) : '' ?>"
                   placeholder="مثال: 1">

            <label for="clearance_status">وضعیت Clearance <span style="color:#b91c1c;">*</span></label>
            <select id="clearance_status" name="clearance_status" required>
                <?php foreach ($statuses as $status): ?>
                    <option value="<?= wave4c_form_h($status) ?>"><?= wave4c_form_h($statusLabels[$status] ?? $status) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="clearance_decision">تصمیم Clearance <span style="color:#b91c1c;">*</span></label>
            <select id="clearance_decision" name="clearance_decision" required>
                <?php foreach ($decisions as $decision): ?>
                    <option value="<?= wave4c_form_h($decision) ?>"><?= wave4c_form_h($decisionLabels[$decision] ?? $decision) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="reviewer_name">نام بازبین <span style="color:#b91c1c;">*</span></label>
            <input type="text" id="reviewer_name" name="reviewer_name" maxlength="200" required placeholder="نام اپراتور/بازبین داخلی">

            <label for="clearance_note">یادداشت Clearance</label>
            <textarea id="clearance_note" name="clearance_note" rows="3" maxlength="2000" placeholder="توضیحات داخلی (اختیاری)"></textarea>

            <button type="submit" class="w1c-btn">ثبت Clearance داخلی تحویل</button>
        </form>
    </section>

    <nav class="w1c-card w1c-links w4c-nav">
        <a href="erp-jobcard-delivery-clearance-preview.php?jobcard_id=<?= wave4c_form_h($prefillJobcardId !== null ? (string)$prefillJobcardId : '1') ?>">سوابق Clearance تحویل</a>
        <a href="erp-jobcard-delivery-eligibility.php?jobcard_id=<?= wave4c_form_h($prefillJobcardId !== null ? (string)$prefillJobcardId : '1') ?>">بررسی صلاحیت تحویل</a>
        <a href="erp-jobcard-final-readiness.php?jobcard_id=<?= wave4c_form_h($prefillJobcardId !== null ? (string)$prefillJobcardId : '1') ?>">آمادگی نهایی</a>
    </nav>
</div>
</body>
</html>

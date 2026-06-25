<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — JobCard Delivery Eligibility Review (Wave 4B)
 * Read-only · no DB write · no delivery record · no POST
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-delivery-eligibility-helper.php';

function wave4b_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$jobcardIdRaw = trim((string)($_GET['jobcard_id'] ?? ''));
$invalidId = $jobcardIdRaw === '' || !ctype_digit($jobcardIdRaw) || (int)$jobcardIdRaw < 1;
$jobcardId = $invalidId ? 0 : (int)$jobcardIdRaw;
$eligibility = $invalidId
    ? [
        'ok' => false,
        'status' => MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_ERROR,
        'jobcard_id' => 0,
        'final_readiness' => [],
        'rules' => moghare360_delivery_eligibility_rules(),
        'eligible_items' => [],
        'review_items' => [],
        'blocking_items' => [],
        'recommended_action' => moghare360_delivery_eligibility_recommended_action(MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_ERROR),
        'message' => 'شناسه کارت کار نامعتبر است.',
        'errors' => ['شناسه کارت کار باید عدد مثبت باشد.'],
    ]
    : moghare360_delivery_eligibility_evaluate($jobcardId);

$finalReadiness = (array)($eligibility['final_readiness'] ?? []);
$evidenceGate = (array)($finalReadiness['evidence_gate'] ?? []);
$authGate = (array)($finalReadiness['authorization_gate'] ?? []);
$ruleLabels = moghare360_delivery_eligibility_rule_labels();

$statusClass = match ($eligibility['status'] ?? '') {
    MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_ELIGIBLE => 'w4b-status-eligible',
    MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_REVIEW_REQUIRED => 'w4b-status-review',
    MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_NOT_ELIGIBLE => 'w4b-status-not-eligible',
    MOGHARE360_DELIVERY_ELIGIBILITY_STATUS_EMPTY => 'w4b-status-empty',
    default => 'w4b-status-error',
};

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>بررسی صلاحیت تحویل کارت کار</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w4b-wrap">
    <header class="w1c-banner w4b-banner">
        <h1>بررسی صلاحیت تحویل کارت کار</h1>
        <p>WAVE 4B — Delivery Eligibility Review (read-only)</p>
    </header>

    <section class="w1c-card w4b-warning">
        <strong>This is read-only delivery eligibility review — not legal e-signature. No delivery action on this page.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">تحویل نهایی در این صفحه فعال نیست. رکورد تحویل ایجاد نمی‌شود. پورتال عمومی و پرداخت فعال نیست.</p>
    </section>

    <section class="w1c-card w1c-form">
        <form method="get" action="erp-jobcard-delivery-eligibility.php">
            <label for="jobcard_id_lookup">شناسه کارت کار</label>
            <input type="number" id="jobcard_id_lookup" name="jobcard_id" min="1" value="<?= $invalidId ? '' : wave4b_h((string)$jobcardId) ?>" required>
            <button type="submit" class="w1c-btn">بررسی صلاحیت تحویل</button>
        </form>
    </section>

    <?php if ($invalidId): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;">شناسه کارت کار نامعتبر است. مقدار عددی مثبت وارد کنید.</p>
        </section>
    <?php else: ?>
        <section class="w1c-card <?= wave4b_h($statusClass) ?>">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">کارت کار <?= wave4b_h((string)$jobcardId) ?></h2>
            <p style="margin:0;">
                <strong><?= wave4b_h(moghare360_delivery_eligibility_status_label((string)($eligibility['status'] ?? ''))) ?></strong>
                (<?= wave4b_h((string)($eligibility['status'] ?? '')) ?>)
            </p>
            <p style="margin:0.5rem 0 0;"><?= wave4b_h((string)($eligibility['message'] ?? '')) ?></p>
        </section>

        <section class="w1c-card">
            <h2 style="margin:0 0 0.75rem;font-size:1rem;">وضعیت گیت‌های زیرمجموعه</h2>
            <dl class="w4b-detail-dl">
                <dt>آمادگی نهایی WAVE 4A</dt>
                <dd>
                    <strong><?= wave4b_h(moghare360_jobcard_final_readiness_status_label((string)($finalReadiness['status'] ?? ''))) ?></strong>
                    (<?= wave4b_h((string)($finalReadiness['status'] ?? '')) ?>)
                </dd>
                <dt>گیت مدارک WAVE 2</dt>
                <dd>
                    <strong><?= wave4b_h(moghare360_jobcard_evidence_status_label((string)($evidenceGate['status'] ?? ''))) ?></strong>
                    (<?= wave4b_h((string)($evidenceGate['status'] ?? '')) ?>)
                </dd>
                <dt>گیت مجوز WAVE 3</dt>
                <dd>
                    <strong><?= wave4b_h(moghare360_contract_authorization_gate_status_label((string)($authGate['status'] ?? ''))) ?></strong>
                    (<?= wave4b_h((string)($authGate['status'] ?? '')) ?>)
                </dd>
            </dl>
        </section>

        <section class="w1c-card">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">اقدام توصیه‌شده</h2>
            <p style="margin:0;font-size:0.9rem;"><?= wave4b_h((string)($eligibility['recommended_action'] ?? '')) ?></p>
        </section>

        <?php foreach (['eligible_items' => 'صلاحیت‌دار', 'review_items' => 'نیازمند بازبینی', 'blocking_items' => 'مسدودکننده'] as $listKey => $title): ?>
            <?php if (!empty($eligibility[$listKey])): ?>
                <section class="w1c-card">
                    <h2 style="margin:0 0 0.5rem;font-size:1rem;"><?= wave4b_h($title) ?></h2>
                    <ul style="margin:0;padding-right:1.25rem;font-size:0.9rem;">
                        <?php foreach ($eligibility[$listKey] as $item): ?>
                            <li><?= wave4b_h($ruleLabels[(string)$item] ?? (string)$item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>
        <?php endforeach; ?>

        <section class="w1c-card w1c-note">
            <p style="margin:0;font-size:0.88rem;"><?= wave4b_h(MOGHARE360_DELIVERY_ELIGIBILITY_INTERNAL_NOTICE) ?></p>
        </section>
    <?php endif; ?>

    <nav class="w1c-card w1c-links w4b-nav">
        <a href="erp-jobcard-delivery-clearance.php?jobcard_id=<?= wave4b_h($invalidId ? '1' : (string)$jobcardId) ?>">ثبت Clearance داخلی تحویل</a>
        <a href="erp-jobcard-delivery-clearance-preview.php?jobcard_id=<?= wave4b_h($invalidId ? '1' : (string)$jobcardId) ?>">سوابق Clearance تحویل</a>
        <a href="erp-jobcard-final-readiness.php?jobcard_id=<?= wave4b_h($invalidId ? '1' : (string)$jobcardId) ?>">آمادگی نهایی</a>
        <a href="erp-jobcard-evidence-review.php?jobcard_id=<?= wave4b_h($invalidId ? '1' : (string)$jobcardId) ?>">بازبینی مدارک</a>
        <a href="erp-jobcard-authorization-gate.php?jobcard_id=<?= wave4b_h($invalidId ? '1' : (string)$jobcardId) ?>">گیت مجوز</a>
        <a href="erp-jobcard-contract-authorization-preview.php?jobcard_id=<?= wave4b_h($invalidId ? '1' : (string)$jobcardId) ?>">پیش‌نمایش مجوزها</a>
        <a href="erp-media-evidence-closure-dashboard.php">داشبورد بستن WAVE 2</a>
        <a href="erp-authorization-closure-dashboard.php">داشبورد بستن WAVE 3</a>
    </nav>
</div>
</body>
</html>

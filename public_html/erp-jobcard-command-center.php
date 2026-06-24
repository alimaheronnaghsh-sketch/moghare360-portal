<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Unified JobCard Operational Command Center (Wave 5A)
 * Read-only · no DB write · no POST
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-unified-jobcard-command-helper.php';

function wave5a_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$jobcardIdRaw = trim((string)($_GET['jobcard_id'] ?? ''));
$invalidId = $jobcardIdRaw === '' || !ctype_digit($jobcardIdRaw) || (int)$jobcardIdRaw < 1;
$jobcardId = $invalidId ? 0 : (int)$jobcardIdRaw;
$command = $invalidId
    ? [
        'ok' => false,
        'status' => MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_ERROR,
        'jobcard_id' => 0,
        'jobcard' => [],
        'evidence' => [],
        'authorization' => [],
        'final_readiness' => [],
        'delivery_eligibility' => [],
        'delivery_clearance' => [],
        'ready_items' => [],
        'action_items' => [],
        'blocked_items' => [],
        'missing_items' => [],
        'message' => 'شناسه کارت کار نامعتبر است.',
        'errors' => ['شناسه کارت کار باید عدد مثبت باشد.'],
    ]
    : moghare360_unified_jobcard_command_evaluate($jobcardId);

$jobcard = (array)($command['jobcard'] ?? []);
$evidence = (array)($command['evidence'] ?? []);
$authorization = (array)($command['authorization'] ?? []);
$finalReadiness = (array)($command['final_readiness'] ?? []);
$deliveryEligibility = (array)($command['delivery_eligibility'] ?? []);
$deliveryClearance = (array)($command['delivery_clearance'] ?? []);
$latestClearance = (array)($deliveryClearance['latest'] ?? []);
$itemLabels = array_merge(
    moghare360_unified_jobcard_command_item_labels(),
    moghare360_jobcard_final_readiness_item_labels(),
    moghare360_jobcard_evidence_required_labels(),
    moghare360_contract_authorization_gate_rule_labels()
);

$statusClass = match ($command['status'] ?? '') {
    MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_OPERATION_READY => 'w5a-status-ready',
    MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_ACTION_REQUIRED => 'w5a-status-action',
    MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_BLOCKED => 'w5a-status-blocked',
    MOGHARE360_UNIFIED_JOBCARD_COMMAND_STATUS_EMPTY => 'w5a-status-empty',
    default => 'w5a-status-error',
};

function wave5a_item_label(array $labels, string $item): string
{
    if (isset($labels[$item])) {
        return $labels[$item];
    }
    if (str_starts_with($item, 'evidence:')) {
        $key = substr($item, 9);
        return $labels[$key] ?? $item;
    }
    if (str_starts_with($item, 'authorization:') || str_starts_with($item, 'authorization_pending:')) {
        $key = preg_replace('/^authorization(_pending)?:/', '', $item) ?? $item;
        return $labels[$key] ?? $item;
    }

    return $item;
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>مرکز فرمان عملیاتی کارت کار</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w5a-wrap">
    <header class="w1c-banner w5a-banner">
        <h1>مرکز فرمان عملیاتی کارت کار</h1>
        <p>WAVE 5A — Unified JobCard Operational Command Center</p>
    </header>

    <section class="w1c-card w5a-warning">
        <strong>This is read-only operational command review — not final vehicle delivery. not legal final e-signature.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">تحویل نهایی خودرو انجام نمی‌شود. رکورد تکمیل تحویل ایجاد نمی‌شود. پورتال عمومی و پرداخت فعال نیست.</p>
    </section>

    <section class="w1c-card w1c-form">
        <form method="get" action="erp-jobcard-command-center.php">
            <label for="jobcard_id_lookup">شناسه کارت کار</label>
            <input type="number" id="jobcard_id_lookup" name="jobcard_id" min="1" value="<?= $invalidId ? '' : wave5a_h((string)$jobcardId) ?>" required>
            <button type="submit" class="w1c-btn">نمایش وضعیت یکپارچه</button>
        </form>
    </section>

    <?php if ($invalidId): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;">شناسه کارت کار نامعتبر است. مقدار عددی مثبت وارد کنید.</p>
        </section>
    <?php else: ?>
        <section class="w1c-card <?= wave5a_h($statusClass) ?>">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">کارت کار <?= wave5a_h((string)$jobcardId) ?></h2>
            <p style="margin:0;">
                <strong><?= wave5a_h(moghare360_unified_jobcard_command_status_label((string)($command['status'] ?? ''))) ?></strong>
                (<?= wave5a_h((string)($command['status'] ?? '')) ?>)
            </p>
            <p style="margin:0.5rem 0 0;"><?= wave5a_h((string)($command['message'] ?? '')) ?></p>
        </section>

        <?php if (!empty($jobcard)): ?>
            <section class="w1c-card">
                <h2 style="margin:0 0 0.5rem;font-size:1rem;">زمینه کارت کار</h2>
                <dl class="w5a-detail-dl">
                    <?php foreach ($jobcard as $key => $value): ?>
                        <dt><?= wave5a_h((string)$key) ?></dt>
                        <dd><?= wave5a_h((string)$value) ?></dd>
                    <?php endforeach; ?>
                </dl>
            </section>
        <?php endif; ?>

        <section class="w1c-card">
            <h2 style="margin:0 0 0.75rem;font-size:1rem;">وضعیت لایه‌های عملیاتی</h2>
            <dl class="w5a-detail-dl">
                <dt>مدارک WAVE 2</dt>
                <dd>
                    <strong><?= wave5a_h(moghare360_jobcard_evidence_status_label((string)($evidence['status'] ?? ''))) ?></strong>
                    (<?= wave5a_h((string)($evidence['status'] ?? '')) ?>)
                </dd>
                <dt>مجوز WAVE 3</dt>
                <dd>
                    <strong><?= wave5a_h(moghare360_contract_authorization_gate_status_label((string)($authorization['status'] ?? ''))) ?></strong>
                    (<?= wave5a_h((string)($authorization['status'] ?? '')) ?>)
                </dd>
                <dt>آمادگی نهایی WAVE 4A</dt>
                <dd>
                    <strong><?= wave5a_h(moghare360_jobcard_final_readiness_status_label((string)($finalReadiness['status'] ?? ''))) ?></strong>
                    (<?= wave5a_h((string)($finalReadiness['status'] ?? '')) ?>)
                </dd>
                <dt>صلاحیت تحویل WAVE 4B</dt>
                <dd>
                    <strong><?= wave5a_h(moghare360_delivery_eligibility_status_label((string)($deliveryEligibility['status'] ?? ''))) ?></strong>
                    (<?= wave5a_h((string)($deliveryEligibility['status'] ?? '')) ?>)
                </dd>
                <dt>Clearance تحویل WAVE 4C</dt>
                <dd>
                    <?php if ($latestClearance !== []): ?>
                        <strong><?= wave5a_h((string)($latestClearance['clearance_status'] ?? '')) ?></strong>
                        / <?= wave5a_h((string)($latestClearance['clearance_decision'] ?? '')) ?>
                        — <?= wave5a_h((string)($latestClearance['created_at'] ?? '')) ?>
                    <?php else: ?>
                        <span>رکورد Clearance یافت نشد</span>
                    <?php endif; ?>
                    <span style="display:block;font-size:0.85rem;color:#525252;margin-top:0.25rem;">
                        تعداد رکورد: <?= wave5a_h((string)($deliveryClearance['record_count'] ?? 0)) ?>
                        — cleared: <?= wave5a_h((string)($deliveryClearance['cleared_count'] ?? 0)) ?>
                    </span>
                </dd>
            </dl>
        </section>

        <?php foreach (['ready_items' => 'آماده', 'action_items' => 'نیازمند اقدام', 'blocked_items' => 'مسدود', 'missing_items' => 'ناقص'] as $listKey => $title): ?>
            <?php if (!empty($command[$listKey])): ?>
                <section class="w1c-card">
                    <h2 style="margin:0 0 0.5rem;font-size:1rem;"><?= wave5a_h($title) ?></h2>
                    <ul style="margin:0;padding-right:1.25rem;font-size:0.9rem;">
                        <?php foreach ($command[$listKey] as $item): ?>
                            <li><?= wave5a_h(wave5a_item_label($itemLabels, (string)$item)) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>
        <?php endforeach; ?>

        <section class="w1c-card w1c-note">
            <p style="margin:0;font-size:0.88rem;"><?= wave5a_h(MOGHARE360_UNIFIED_JOBCARD_COMMAND_INTERNAL_NOTICE) ?></p>
        </section>
    <?php endif; ?>

    <nav class="w1c-card w1c-links w5a-nav">
        <a href="erp-jobcard-final-readiness.php?jobcard_id=<?= wave5a_h($invalidId ? '1' : (string)$jobcardId) ?>">آمادگی نهایی</a>
        <a href="erp-jobcard-delivery-eligibility.php?jobcard_id=<?= wave5a_h($invalidId ? '1' : (string)$jobcardId) ?>">صلاحیت تحویل</a>
        <a href="erp-jobcard-delivery-clearance.php?jobcard_id=<?= wave5a_h($invalidId ? '1' : (string)$jobcardId) ?>">ثبت Clearance</a>
        <a href="erp-jobcard-delivery-clearance-preview.php?jobcard_id=<?= wave5a_h($invalidId ? '1' : (string)$jobcardId) ?>">سوابق Clearance</a>
        <a href="erp-jobcard-evidence-review.php?jobcard_id=<?= wave5a_h($invalidId ? '1' : (string)$jobcardId) ?>">بازبینی مدارک</a>
        <a href="erp-jobcard-authorization-gate.php?jobcard_id=<?= wave5a_h($invalidId ? '1' : (string)$jobcardId) ?>">گیت مجوز</a>
        <a href="erp-jobcard-contract-authorization-preview.php?jobcard_id=<?= wave5a_h($invalidId ? '1' : (string)$jobcardId) ?>">پیش‌نمایش مجوزها</a>
        <a href="erp-media-evidence-closure-dashboard.php">داشبورد بستن WAVE 2</a>
        <a href="erp-authorization-closure-dashboard.php">داشبورد بستن WAVE 3</a>
        <a href="erp-delivery-control-closure-dashboard.php">داشبورد بستن WAVE 4</a>
    </nav>
</div>
</body>
</html>

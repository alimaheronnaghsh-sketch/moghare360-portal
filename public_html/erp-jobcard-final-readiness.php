<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — JobCard Final Readiness Review (Wave 4A)
 * Read-only · no DB write · no final delivery
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-jobcard-final-readiness-helper.php';

function wave4a_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$jobcardIdRaw = trim((string)($_GET['jobcard_id'] ?? ''));
$invalidId = $jobcardIdRaw === '' || !ctype_digit($jobcardIdRaw) || (int)$jobcardIdRaw < 1;
$jobcardId = $invalidId ? 0 : (int)$jobcardIdRaw;
$readiness = $invalidId
    ? [
        'ok' => false,
        'status' => MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_ERROR,
        'jobcard_id' => 0,
        'jobcard' => [],
        'evidence_gate' => [],
        'authorization_gate' => [],
        'ready_items' => [],
        'partial_items' => [],
        'blocked_items' => [],
        'missing_items' => [],
        'message' => 'شناسه کارت کار نامعتبر است.',
        'errors' => ['شناسه کارت کار باید عدد مثبت باشد.'],
    ]
    : moghare360_jobcard_final_readiness_evaluate($jobcardId);

$itemLabels = moghare360_jobcard_final_readiness_item_labels();
$evidenceGate = (array)($readiness['evidence_gate'] ?? []);
$authGate = (array)($readiness['authorization_gate'] ?? []);

$statusClass = match ($readiness['status'] ?? '') {
    MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_READY => 'w4a-status-ready',
    MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_PARTIAL => 'w4a-status-partial',
    MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_BLOCKED => 'w4a-status-blocked',
    MOGHARE360_JOBCARD_FINAL_READINESS_STATUS_EMPTY => 'w4a-status-empty',
    default => 'w4a-status-error',
};

$evidenceLabels = moghare360_jobcard_evidence_required_labels();
$authRuleLabels = moghare360_contract_authorization_gate_rule_labels();

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>آمادگی نهایی کارت کار</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w4a-wrap">
    <header class="w1c-banner w4a-banner">
        <h1>آمادگی نهایی کارت کار</h1>
        <p>WAVE 4A — Final Readiness Gate (read-only)</p>
    </header>

    <section class="w1c-card w4a-warning">
        <strong>This is read-only final readiness evaluation — not legal e-signature. No delivery action on this page.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">تحویل نهایی در این صفحه فعال نیست. پورتال عمومی و پرداخت فعال نیست.</p>
    </section>

    <section class="w1c-card w1c-form">
        <form method="get" action="erp-jobcard-final-readiness.php">
            <label for="jobcard_id_lookup">شناسه کارت کار</label>
            <input type="number" id="jobcard_id_lookup" name="jobcard_id" min="1" value="<?= $invalidId ? '' : wave4a_h((string)$jobcardId) ?>" required>
            <button type="submit" class="w1c-btn">بررسی آمادگی نهایی</button>
        </form>
    </section>

    <?php if ($invalidId): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;">شناسه کارت کار نامعتبر است. مقدار عددی مثبت وارد کنید.</p>
        </section>
    <?php else: ?>
        <section class="w1c-card <?= wave4a_h($statusClass) ?>">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">کارت کار <?= wave4a_h((string)$jobcardId) ?></h2>
            <p style="margin:0;">
                <strong><?= wave4a_h(moghare360_jobcard_final_readiness_status_label((string)($readiness['status'] ?? ''))) ?></strong>
                (<?= wave4a_h((string)($readiness['status'] ?? '')) ?>)
            </p>
            <p style="margin:0.5rem 0 0;"><?= wave4a_h((string)($readiness['message'] ?? '')) ?></p>
        </section>

        <?php if (!empty($readiness['errors'])): ?>
            <section class="w1c-card w1c-error-box">
                <ul style="margin:0;padding-right:1.25rem;">
                    <?php foreach ($readiness['errors'] as $error): ?>
                        <li><?= wave4a_h(is_string($error) ? $error : json_encode($error, JSON_UNESCAPED_UNICODE)) ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <section class="w1c-card">
            <h2 style="margin:0 0 0.75rem;font-size:1rem;">وضعیت گیت‌های زیرمجموعه</h2>
            <dl class="w4a-detail-dl">
                <dt>گیت مدارک WAVE 2</dt>
                <dd>
                    <strong><?= wave4a_h(moghare360_jobcard_evidence_status_label((string)($evidenceGate['status'] ?? ''))) ?></strong>
                    (<?= wave4a_h((string)($evidenceGate['status'] ?? '')) ?>)
                </dd>
                <dt>گیت مجوز WAVE 3</dt>
                <dd>
                    <strong><?= wave4a_h(moghare360_contract_authorization_gate_status_label((string)($authGate['status'] ?? ''))) ?></strong>
                    (<?= wave4a_h((string)($authGate['status'] ?? '')) ?>)
                </dd>
            </dl>
            <p style="margin:0.75rem 0 0;font-size:0.85rem;color:#525252;">
                <?= wave4a_h((string)($evidenceGate['message'] ?? '')) ?>
                <?php if (($authGate['message'] ?? '') !== ''): ?> — <?= wave4a_h((string)$authGate['message']) ?><?php endif; ?>
            </p>
        </section>

        <?php foreach (['ready_items' => 'آماده', 'partial_items' => 'ناقص', 'blocked_items' => 'مسدود', 'missing_items' => 'مفقود'] as $listKey => $title): ?>
            <?php if (!empty($readiness[$listKey])): ?>
                <section class="w1c-card">
                    <h2 style="margin:0 0 0.5rem;font-size:1rem;"><?= wave4a_h($title) ?></h2>
                    <ul style="margin:0;padding-right:1.25rem;font-size:0.9rem;">
                        <?php foreach ($readiness[$listKey] as $item): ?>
                            <?php
                            $itemStr = (string)$item;
                            $label = $itemLabels[$itemStr] ?? $itemStr;
                            if (str_starts_with($itemStr, 'evidence:')) {
                                $label = $evidenceLabels[substr($itemStr, 9)] ?? $itemStr;
                            } elseif (str_starts_with($itemStr, 'authorization:') || str_starts_with($itemStr, 'authorization_pending:')) {
                                $key = preg_replace('/^authorization(_pending)?:/', '', $itemStr) ?? $itemStr;
                                $label = $authRuleLabels[$key] ?? $itemStr;
                            }
                            ?>
                            <li><?= wave4a_h($label) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>
        <?php endforeach; ?>

        <section class="w1c-card w1c-note">
            <p style="margin:0;font-size:0.88rem;"><?= wave4a_h(MOGHARE360_JOBCARD_FINAL_READINESS_INTERNAL_NOTICE) ?></p>
        </section>
    <?php endif; ?>

    <nav class="w1c-card w1c-links w4a-nav">
        <a href="erp-jobcard-evidence-review.php?jobcard_id=<?= wave4a_h($invalidId ? '1' : (string)$jobcardId) ?>">بازبینی مدارک</a>
        <a href="erp-jobcard-authorization-gate.php?jobcard_id=<?= wave4a_h($invalidId ? '1' : (string)$jobcardId) ?>">گیت مجوز</a>
        <a href="erp-jobcard-contract-authorization-preview.php?jobcard_id=<?= wave4a_h($invalidId ? '1' : (string)$jobcardId) ?>">پیش‌نمایش مجوزها</a>
        <a href="erp-media-evidence-closure-dashboard.php">داشبورد بستن WAVE 2</a>
        <a href="erp-authorization-closure-dashboard.php">داشبورد بستن WAVE 3</a>
    </nav>
</div>
</body>
</html>

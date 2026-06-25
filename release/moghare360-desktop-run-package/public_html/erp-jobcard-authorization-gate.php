<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — JobCard Authorization Requirement Gate (Wave 3C)
 * Read-only readiness evaluation · no DB write
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-contract-authorization-gate-helper.php';

function wave3c_gate_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$jobcardIdRaw = trim((string)($_GET['jobcard_id'] ?? ''));
$invalidId = $jobcardIdRaw === '' || !ctype_digit($jobcardIdRaw) || (int)$jobcardIdRaw < 1;
$jobcardId = $invalidId ? 0 : (int)$jobcardIdRaw;
$gate = $invalidId
    ? [
        'ok' => false,
        'status' => MOGHARE360_CONTRACT_AUTH_GATE_STATUS_ERROR,
        'jobcard_id' => 0,
        'required' => [],
        'approved' => [],
        'pending' => [],
        'missing' => [],
        'rejected' => [],
        'cancelled' => [],
        'authorization_count' => 0,
        'history_count' => 0,
        'diagnostic_evidence_count' => 0,
        'message' => 'شناسه کارت کار نامعتبر است.',
        'errors' => ['شناسه کارت کار باید عدد مثبت باشد.'],
        'authorization_rows' => [],
    ]
    : moghare360_contract_authorization_gate_review($jobcardId);

$ruleLabels = moghare360_contract_authorization_gate_rule_labels();
$statusClass = match ($gate['status'] ?? '') {
    MOGHARE360_CONTRACT_AUTH_GATE_STATUS_READY => 'w3c-status-ready',
    MOGHARE360_CONTRACT_AUTH_GATE_STATUS_PARTIAL => 'w3c-status-partial',
    MOGHARE360_CONTRACT_AUTH_GATE_STATUS_BLOCKED => 'w3c-status-blocked',
    MOGHARE360_CONTRACT_AUTH_GATE_STATUS_EMPTY => 'w3c-status-empty',
    default => 'w3c-status-error',
};

$lastRows = array_slice($gate['authorization_rows'] ?? [], 0, 10);

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>گیت مجوزهای کارت کار</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w3c-wrap">
    <header class="w1c-banner w3c-banner">
        <h1>گیت مجوزهای کارت کار</h1>
        <p>WAVE 3C — Authorization Requirement Gate (read-only)</p>
    </header>

    <section class="w1c-card w3c-warning">
        <strong>This is internal controlled authorization readiness evaluation, not final legal e-signature.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">این گیت فقط وضعیت آمادگی را گزارش می‌کند — هنوز عملیات کارت کار را مسدود نمی‌کند. پورتال عمومی و پرداخت فعال نیست.</p>
    </section>

    <section class="w1c-card w1c-form">
        <form method="get" action="erp-jobcard-authorization-gate.php">
            <label for="jobcard_id_lookup">شناسه کارت کار</label>
            <input type="number" id="jobcard_id_lookup" name="jobcard_id" min="1" value="<?= $invalidId ? '' : wave3c_gate_h((string)$jobcardId) ?>" required>
            <button type="submit" class="w1c-btn">بررسی گیت مجوز</button>
        </form>
    </section>

    <?php if ($invalidId): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;">شناسه کارت کار نامعتبر است. مقدار عددی مثبت وارد کنید.</p>
        </section>
    <?php else: ?>
        <section class="w1c-card <?= wave3c_gate_h($statusClass) ?>">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">کارت کار <?= wave3c_gate_h((string)$jobcardId) ?></h2>
            <p style="margin:0;">
                <strong><?= wave3c_gate_h(moghare360_contract_authorization_gate_status_label((string)($gate['status'] ?? ''))) ?></strong>
                (<?= wave3c_gate_h((string)($gate['status'] ?? '')) ?>)
            </p>
            <p style="margin:0.5rem 0 0;"><?= wave3c_gate_h((string)($gate['message'] ?? '')) ?></p>
        </section>

        <?php if (!empty($gate['errors'])): ?>
            <section class="w1c-card w1c-error-box">
                <ul style="margin:0;padding-right:1.25rem;">
                    <?php foreach ($gate['errors'] as $error): ?>
                        <li><?= wave3c_gate_h((string)$error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <section class="w1c-card">
            <h2 style="margin:0 0 0.75rem;font-size:1rem;">شمارش‌ها</h2>
            <div class="w3c-stat-grid">
                <div class="w3c-stat"><span>مجوزها</span><strong><?= wave3c_gate_h((string)($gate['authorization_count'] ?? 0)) ?></strong></div>
                <div class="w3c-stat"><span>تاریخچه</span><strong><?= wave3c_gate_h((string)($gate['history_count'] ?? 0)) ?></strong></div>
                <div class="w3c-stat"><span>فایل تشخیصی</span><strong><?= wave3c_gate_h((string)($gate['diagnostic_evidence_count'] ?? 0)) ?></strong></div>
            </div>
        </section>

        <?php foreach (['required' => 'الزامی', 'approved' => 'تأیید شده', 'pending' => 'در انتظار', 'missing' => 'مفقود', 'rejected' => 'رد شده', 'cancelled' => 'لغو شده'] as $listKey => $listTitle): ?>
            <?php if (!empty($gate[$listKey])): ?>
                <section class="w1c-card">
                    <h2 style="margin:0 0 0.5rem;font-size:1rem;"><?= wave3c_gate_h($listTitle) ?></h2>
                    <ul style="margin:0;padding-right:1.25rem;font-size:0.9rem;">
                        <?php foreach ($gate[$listKey] as $itemKey): ?>
                            <li><?= wave3c_gate_h($ruleLabels[$itemKey] ?? (string)$itemKey) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if ($lastRows !== []): ?>
            <section class="w1c-card">
                <h2 style="margin:0 0 0.75rem;font-size:1rem;">آخرین رکوردهای مجوز</h2>
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:0.85rem;">
                        <thead>
                        <tr>
                            <th style="text-align:right;padding:0.35rem;">شناسه</th>
                            <th style="text-align:right;padding:0.35rem;">نوع</th>
                            <th style="text-align:right;padding:0.35rem;">وضعیت</th>
                            <th style="text-align:right;padding:0.35rem;">گردش کار</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($lastRows as $row): ?>
                            <tr>
                                <td style="padding:0.35rem;"><?= wave3c_gate_h((string)($row['authorization_id'] ?? '')) ?></td>
                                <td style="padding:0.35rem;"><?= wave3c_gate_h((string)($row['authorization_type'] ?? '')) ?></td>
                                <td style="padding:0.35rem;"><?= wave3c_gate_h((string)($row['authorization_status'] ?? '')) ?></td>
                                <td style="padding:0.35rem;">
                                    <a href="erp-jobcard-contract-authorization-workflow.php?authorization_id=<?= wave3c_gate_h((string)($row['authorization_id'] ?? '')) ?>">گردش کار</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    <?php endif; ?>

    <nav class="w1c-card w1c-links">
        <a href="erp-jobcard-contract-authorization.php">ثبت مجوز/قرارداد</a>
        <a href="erp-jobcard-contract-authorization-preview.php?jobcard_id=<?= wave3c_gate_h($invalidId ? '1' : (string)$jobcardId) ?>">پیش‌نمایش مجوزها</a>
        <a href="erp-jobcard-evidence-review.php?jobcard_id=<?= wave3c_gate_h($invalidId ? '1' : (string)$jobcardId) ?>">بازبینی مدارک</a>
    </nav>
</div>
</body>
</html>

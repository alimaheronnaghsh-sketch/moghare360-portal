<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Submit Soft Run Finding Workflow (Wave 8B)
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-finding-helper.php';

function wave8b_submit_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: erp-soft-run-finding-board.php');
    exit;
}

$findingIdRaw = trim((string)($_POST['finding_id'] ?? ''));
$findingId = ($findingIdRaw !== '' && ctype_digit($findingIdRaw) && (int)$findingIdRaw >= 1)
    ? (int)$findingIdRaw
    : 0;

$payload = [
    'finding_id' => $findingIdRaw,
    'new_finding_status' => trim((string)($_POST['new_finding_status'] ?? '')),
    'corrective_action_status' => trim((string)($_POST['corrective_action_status'] ?? '')),
    'change_reason' => trim((string)($_POST['change_reason'] ?? '')),
    'corrective_action' => trim((string)($_POST['corrective_action'] ?? '')),
    'owner_user_id' => trim((string)($_POST['owner_user_id'] ?? '')),
    'due_at' => trim((string)($_POST['due_at'] ?? '')),
    'resolved_at' => trim((string)($_POST['resolved_at'] ?? '')),
];

$validation = moghare360_soft_run_finding_validate_workflow_payload($payload);
$result = ($validation['ok'] && $findingId > 0)
    ? moghare360_soft_run_finding_update_workflow($findingId, $payload)
    : null;

if ($result === null && $validation['ok'] && $findingId < 1) {
    $validation = [
        'ok' => false,
        'errors' => [[
            'field' => 'finding_id',
            'rule' => 'required_positive_number',
            'message' => 'شناسه یافته الزامی و باید عدد مثبت باشد.',
        ]],
        'clean' => [],
    ];
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>نتیجه گردش کار یافته Soft Run</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w8b-wrap">
    <header class="w1c-banner w8b-banner">
        <h1>نتیجه گردش کار یافته Soft Run</h1>
        <p>WAVE 8B — Controlled Finding Workflow Submit Result</p>
    </header>

    <section class="w1c-card w8b-warning">
        <strong>Internal Soft Run finding workflow only — not final delivery. Not delivery completion. Not legal e-signature. Not payment/accounting.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">پورتال عمومی و ورود تولید فعال نیست. نوشتن به جداول اجرای پایلوت انجام نمی‌شود.</p>
    </section>

    <?php if (!$validation['ok']): ?>
        <section class="w1c-card w1c-error-box">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">خطای اعتبارسنجی</h2>
            <ul style="margin:0;padding-right:1.25rem;">
                <?php foreach ($validation['errors'] as $error): ?>
                    <li><?= wave8b_submit_h((string)($error['message'] ?? '')) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php elseif ($result !== null && ($result['ok'] ?? false) === true): ?>
        <section class="w1c-card w1c-success">
            <p style="margin:0;"><?= wave8b_submit_h((string)($result['message'] ?? 'به‌روزرسانی موفق')) ?></p>
            <p style="margin:0.5rem 0 0;"><strong>شناسه یافته:</strong> <?= wave8b_submit_h((string)($result['finding_id'] ?? '')) ?></p>
            <p style="margin:0.35rem 0 0;"><strong>کد یافته:</strong> <?= wave8b_submit_h((string)($result['finding_code'] ?? '')) ?></p>
            <p style="margin:0.35rem 0 0;"><strong>وضعیت یافته قبلی:</strong>
                <?= wave8b_submit_h(moghare360_soft_run_finding_status_label((string)($result['old_finding_status'] ?? ''))) ?>
                (<?= wave8b_submit_h((string)($result['old_finding_status'] ?? '')) ?>)</p>
            <p style="margin:0.35rem 0 0;"><strong>وضعیت یافته جدید:</strong>
                <?= wave8b_submit_h(moghare360_soft_run_finding_status_label((string)($result['new_finding_status'] ?? ''))) ?>
                (<?= wave8b_submit_h((string)($result['new_finding_status'] ?? '')) ?>)</p>
            <p style="margin:0.35rem 0 0;"><strong>اقدام اصلاحی قبلی:</strong>
                <?= wave8b_submit_h(moghare360_soft_run_finding_status_label((string)($result['old_corrective_action_status'] ?? ''))) ?>
                (<?= wave8b_submit_h((string)($result['old_corrective_action_status'] ?? '')) ?>)</p>
            <p style="margin:0.35rem 0 0;"><strong>اقدام اصلاحی جدید:</strong>
                <?= wave8b_submit_h(moghare360_soft_run_finding_status_label((string)($result['new_corrective_action_status'] ?? ''))) ?>
                (<?= wave8b_submit_h((string)($result['new_corrective_action_status'] ?? '')) ?>)</p>
        </section>
    <?php elseif ($result !== null && ($result['schema_status'] ?? '') === MOGHARE360_SOFT_RUN_FINDING_SCHEMA_BLOCKED): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave8b_submit_h(MOGHARE360_SOFT_RUN_FINDING_BLOCK_MESSAGE) ?></p>
        </section>
    <?php elseif ($result !== null && !empty($result['errors'])): ?>
        <section class="w1c-card w1c-error-box">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;"><?= wave8b_submit_h((string)($result['message'] ?? 'به‌روزرسانی ناموفق')) ?></h2>
            <ul style="margin:0;padding-right:1.25rem;">
                <?php foreach ($result['errors'] as $error): ?>
                    <li><?= wave8b_submit_h((string)($error['message'] ?? '')) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php else: ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave8b_submit_h((string)($result['message'] ?? 'به‌روزرسانی گردش کار ناموفق بود.')) ?></p>
        </section>
    <?php endif; ?>

    <nav class="w1c-card w1c-links w8b-nav">
        <?php if ($findingId > 0): ?>
            <a href="erp-soft-run-finding-workflow.php?finding_id=<?= wave8b_submit_h((string)$findingId) ?>">گردش کار یافته</a>
            <a href="erp-soft-run-finding-detail.php?finding_id=<?= wave8b_submit_h((string)$findingId) ?>">جزئیات یافته</a>
        <?php endif; ?>
        <a href="erp-soft-run-finding-board.php">برد یافته‌ها</a>
    </nav>
</div>
</body>
</html>

<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Submit Soft Run Pilot Execution Workflow (Wave 7B)
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-pilot-execution-helper.php';

function wave7b_submit_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: erp-soft-run-pilot-execution-board.php');
    exit;
}

$executionIdRaw = trim((string)($_POST['execution_id'] ?? ''));
$executionId = ($executionIdRaw !== '' && ctype_digit($executionIdRaw) && (int)$executionIdRaw >= 1)
    ? (int)$executionIdRaw
    : 0;

$payload = [
    'execution_id' => $executionIdRaw,
    'new_execution_status' => trim((string)($_POST['new_execution_status'] ?? '')),
    'evidence_status' => trim((string)($_POST['evidence_status'] ?? '')),
    'result_status' => trim((string)($_POST['result_status'] ?? '')),
    'change_reason' => trim((string)($_POST['change_reason'] ?? '')),
    'actual_evidence' => trim((string)($_POST['actual_evidence'] ?? '')),
    'blocker_notes' => trim((string)($_POST['blocker_notes'] ?? '')),
    'internal_notes' => trim((string)($_POST['internal_notes'] ?? '')),
];

$validation = moghare360_soft_run_pilot_execution_validate_workflow_payload($payload);
$result = ($validation['ok'] && $executionId > 0)
    ? moghare360_soft_run_pilot_execution_update_workflow($executionId, $payload)
    : null;

if ($result === null && $validation['ok'] && $executionId < 1) {
    $validation = [
        'ok' => false,
        'errors' => [[
            'field' => 'execution_id',
            'rule' => 'required_positive_number',
            'message' => 'شناسه اجرا الزامی و باید عدد مثبت باشد.',
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
    <title>نتیجه گردش کار اجرای پایلوت Soft Run</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w7b-wrap">
    <header class="w1c-banner w7b-banner">
        <h1>نتیجه گردش کار اجرای پایلوت Soft Run</h1>
        <p>WAVE 7B — Controlled Workflow Submit Result</p>
    </header>

    <section class="w1c-card w7b-warning">
        <strong>Internal Soft Run workflow only — not final delivery. Not delivery completion. Not legal e-signature. Not payment/accounting.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">پورتال عمومی و ورود تولید فعال نیست.</p>
    </section>

    <?php if (!$validation['ok']): ?>
        <section class="w1c-card w1c-error-box">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">خطای اعتبارسنجی</h2>
            <ul style="margin:0;padding-right:1.25rem;">
                <?php foreach ($validation['errors'] as $error): ?>
                    <li><?= wave7b_submit_h((string)($error['message'] ?? '')) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php elseif ($result !== null && ($result['ok'] ?? false) === true): ?>
        <section class="w1c-card w1c-success">
            <p style="margin:0;"><?= wave7b_submit_h((string)($result['message'] ?? 'به‌روزرسانی موفق')) ?></p>
            <p style="margin:0.5rem 0 0;"><strong>شناسه اجرا:</strong> <?= wave7b_submit_h((string)($result['execution_id'] ?? '')) ?></p>
            <p style="margin:0.35rem 0 0;"><strong>کد اجرا:</strong> <?= wave7b_submit_h((string)($result['execution_code'] ?? '')) ?></p>
            <p style="margin:0.35rem 0 0;"><strong>وضعیت قبلی:</strong>
                <?= wave7b_submit_h(moghare360_soft_run_pilot_execution_status_label((string)($result['old_execution_status'] ?? ''))) ?>
                (<?= wave7b_submit_h((string)($result['old_execution_status'] ?? '')) ?>)</p>
            <p style="margin:0.35rem 0 0;"><strong>وضعیت جدید:</strong>
                <?= wave7b_submit_h(moghare360_soft_run_pilot_execution_status_label((string)($result['new_execution_status'] ?? ''))) ?>
                (<?= wave7b_submit_h((string)($result['new_execution_status'] ?? '')) ?>)</p>
        </section>
    <?php elseif ($result !== null && ($result['schema_status'] ?? '') === MOGHARE360_SOFT_RUN_PILOT_EXECUTION_SCHEMA_BLOCKED): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave7b_submit_h(MOGHARE360_SOFT_RUN_PILOT_EXECUTION_BLOCK_MESSAGE) ?></p>
        </section>
    <?php elseif ($result !== null && !empty($result['errors'])): ?>
        <section class="w1c-card w1c-error-box">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;"><?= wave7b_submit_h((string)($result['message'] ?? 'به‌روزرسانی ناموفق')) ?></h2>
            <ul style="margin:0;padding-right:1.25rem;">
                <?php foreach ($result['errors'] as $error): ?>
                    <li><?= wave7b_submit_h((string)($error['message'] ?? '')) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php else: ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave7b_submit_h((string)($result['message'] ?? 'به‌روزرسانی گردش کار ناموفق بود.')) ?></p>
        </section>
    <?php endif; ?>

    <nav class="w1c-card w1c-links w7b-nav">
        <?php if ($executionId > 0): ?>
            <a href="erp-soft-run-pilot-execution-workflow.php?execution_id=<?= wave7b_submit_h((string)$executionId) ?>">گردش کار اجرا</a>
            <a href="erp-soft-run-pilot-execution-detail.php?execution_id=<?= wave7b_submit_h((string)$executionId) ?>">جزئیات اجرا</a>
        <?php endif; ?>
        <a href="erp-soft-run-pilot-execution-board.php">برد اجرای پایلوت</a>
    </nav>
</div>
</body>
</html>

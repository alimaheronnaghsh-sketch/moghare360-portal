<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Submit Soft Run Pilot Execution (Wave 7A)
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-pilot-execution-helper.php';

function wave7a_submit_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: erp-soft-run-pilot-execution-create.php');
    exit;
}

$payload = [
    'jobcard_id' => trim((string)($_POST['jobcard_id'] ?? '')),
    'scenario_key' => trim((string)($_POST['scenario_key'] ?? '')),
    'scenario_title' => trim((string)($_POST['scenario_title'] ?? '')),
    'execution_status' => trim((string)($_POST['execution_status'] ?? 'STARTED')),
    'evidence_status' => trim((string)($_POST['evidence_status'] ?? 'PENDING_REVIEW')),
    'result_status' => trim((string)($_POST['result_status'] ?? 'NOT_EVALUATED')),
    'observed_summary' => trim((string)($_POST['observed_summary'] ?? '')),
    'expected_evidence' => trim((string)($_POST['expected_evidence'] ?? '')),
    'actual_evidence' => trim((string)($_POST['actual_evidence'] ?? '')),
    'blocker_notes' => trim((string)($_POST['blocker_notes'] ?? '')),
    'internal_notes' => trim((string)($_POST['internal_notes'] ?? '')),
];

$validation = moghare360_soft_run_pilot_execution_validate_payload($payload);
$result = $validation['ok'] ? moghare360_soft_run_pilot_execution_create($payload) : null;

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>نتیجه ثبت اجرای پایلوت Soft Run</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w7a-wrap">
    <header class="w1c-banner w7a-banner">
        <h1>نتیجه ثبت اجرای پایلوت Soft Run</h1>
        <p>WAVE 7A — Controlled Pilot Execution Submit Result</p>
    </header>

    <section class="w1c-card w7a-warning">
        <strong>Internal Soft Run log only — not final delivery. Not delivery completion. Not legal e-signature. Not payment/accounting.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">پورتال عمومی و ورود تولید فعال نیست. تحویل نهایی خودرو انجام نمی‌شود.</p>
    </section>

    <?php if (!$validation['ok']): ?>
        <section class="w1c-card w1c-error-box">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">خطای اعتبارسنجی</h2>
            <ul style="margin:0;padding-right:1.25rem;">
                <?php foreach ($validation['errors'] as $error): ?>
                    <li><?= wave7a_submit_h((string)($error['message'] ?? '')) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php elseif ($result !== null && ($result['ok'] ?? false) === true): ?>
        <section class="w1c-card w1c-success">
            <p style="margin:0;"><?= wave7a_submit_h((string)($result['message'] ?? 'ثبت موفق')) ?></p>
            <?php if (($result['execution_id'] ?? null) !== null): ?>
                <p style="margin:0.5rem 0 0;"><strong>شناسه اجرا:</strong> <?= wave7a_submit_h((string)$result['execution_id']) ?></p>
            <?php endif; ?>
            <?php if (($result['execution_code'] ?? null) !== null): ?>
                <p style="margin:0.35rem 0 0;"><strong>کد اجرا:</strong> <?= wave7a_submit_h((string)$result['execution_code']) ?></p>
            <?php endif; ?>
        </section>
    <?php elseif ($result !== null && ($result['schema_status'] ?? '') === MOGHARE360_SOFT_RUN_PILOT_EXECUTION_SCHEMA_BLOCKED): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave7a_submit_h(MOGHARE360_SOFT_RUN_PILOT_EXECUTION_BLOCK_MESSAGE) ?></p>
            <p style="margin:0.5rem 0 0;font-size:0.85rem;">ثبت رکورد تا اجرای SQL در SSMS مسدود است — موفقیت جعلی نمایش داده نمی‌شود.</p>
        </section>
    <?php elseif ($result !== null && !empty($result['errors'])): ?>
        <section class="w1c-card w1c-error-box">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;"><?= wave7a_submit_h((string)($result['message'] ?? 'ثبت ناموفق')) ?></h2>
            <ul style="margin:0;padding-right:1.25rem;">
                <?php foreach ($result['errors'] as $error): ?>
                    <li><?= wave7a_submit_h((string)($error['message'] ?? '')) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php else: ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave7a_submit_h((string)($result['message'] ?? 'ثبت اجرای پایلوت ناموفق بود.')) ?></p>
        </section>
    <?php endif; ?>

    <nav class="w1c-card w1c-links w7a-nav">
        <a href="erp-soft-run-pilot-execution-create.php">ثبت اجرای پایلوت جدید</a>
        <?php if ($result !== null && ($result['execution_id'] ?? null) !== null): ?>
            <a href="erp-soft-run-pilot-execution-detail.php?execution_id=<?= wave7a_submit_h((string)$result['execution_id']) ?>">جزئیات اجرا</a>
        <?php endif; ?>
        <a href="erp-soft-run-pilot-execution-board.php">برد اجرای پایلوت Soft Run</a>
        <a href="erp-soft-run-final-closure-dashboard.php">داشبورد نهایی آمادگی پایلوت</a>
    </nav>
</div>
</body>
</html>

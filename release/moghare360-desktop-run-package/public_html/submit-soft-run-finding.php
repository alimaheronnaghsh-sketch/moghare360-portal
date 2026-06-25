<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Submit Soft Run Finding (Wave 8A)
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-finding-helper.php';

function wave8a_submit_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: erp-soft-run-finding-create.php');
    exit;
}

$payload = [
    'execution_id' => trim((string)($_POST['execution_id'] ?? '')),
    'jobcard_id' => trim((string)($_POST['jobcard_id'] ?? '')),
    'finding_type' => trim((string)($_POST['finding_type'] ?? '')),
    'severity_level' => trim((string)($_POST['severity_level'] ?? '')),
    'finding_status' => trim((string)($_POST['finding_status'] ?? 'OPEN')),
    'corrective_action_status' => trim((string)($_POST['corrective_action_status'] ?? 'NOT_STARTED')),
    'finding_title' => trim((string)($_POST['finding_title'] ?? '')),
    'finding_description' => trim((string)($_POST['finding_description'] ?? '')),
    'expected_behavior' => trim((string)($_POST['expected_behavior'] ?? '')),
    'actual_behavior' => trim((string)($_POST['actual_behavior'] ?? '')),
    'corrective_action' => trim((string)($_POST['corrective_action'] ?? '')),
    'owner_user_id' => trim((string)($_POST['owner_user_id'] ?? '')),
    'due_at' => trim((string)($_POST['due_at'] ?? '')),
];

$validation = moghare360_soft_run_finding_validate_payload($payload);
$result = $validation['ok'] ? moghare360_soft_run_finding_create($payload) : null;

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>نتیجه ثبت یافته Soft Run</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w8a-wrap">
    <header class="w1c-banner w8a-banner">
        <h1>نتیجه ثبت یافته Soft Run</h1>
        <p>WAVE 8A — Controlled Finding Submit Result</p>
    </header>

    <section class="w1c-card w8a-warning">
        <strong>Internal Soft Run finding/corrective action log only — not final delivery. Not delivery completion. Not legal e-signature. Not payment/accounting.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">پورتال عمومی و ورود تولید فعال نیست. تحویل نهایی خودرو انجام نمی‌شود.</p>
    </section>

    <?php if (!$validation['ok']): ?>
        <section class="w1c-card w1c-error-box">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">خطای اعتبارسنجی</h2>
            <ul style="margin:0;padding-right:1.25rem;">
                <?php foreach ($validation['errors'] as $error): ?>
                    <li><?= wave8a_submit_h((string)($error['message'] ?? '')) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php elseif ($result !== null && ($result['ok'] ?? false) === true): ?>
        <section class="w1c-card w1c-success">
            <p style="margin:0;"><?= wave8a_submit_h((string)($result['message'] ?? 'ثبت موفق')) ?></p>
            <?php if (($result['finding_id'] ?? null) !== null): ?>
                <p style="margin:0.5rem 0 0;"><strong>شناسه یافته:</strong> <?= wave8a_submit_h((string)$result['finding_id']) ?></p>
            <?php endif; ?>
            <?php if (($result['finding_code'] ?? null) !== null): ?>
                <p style="margin:0.35rem 0 0;"><strong>کد یافته:</strong> <?= wave8a_submit_h((string)$result['finding_code']) ?></p>
            <?php endif; ?>
        </section>
    <?php elseif ($result !== null && ($result['schema_status'] ?? '') === MOGHARE360_SOFT_RUN_FINDING_SCHEMA_BLOCKED): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave8a_submit_h(MOGHARE360_SOFT_RUN_FINDING_BLOCK_MESSAGE) ?></p>
            <p style="margin:0.5rem 0 0;font-size:0.85rem;">ثبت رکورد تا اجرای SQL در SSMS مسدود است — موفقیت جعلی نمایش داده نمی‌شود.</p>
        </section>
    <?php elseif ($result !== null && !empty($result['errors'])): ?>
        <section class="w1c-card w1c-error-box">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;"><?= wave8a_submit_h((string)($result['message'] ?? 'ثبت ناموفق')) ?></h2>
            <ul style="margin:0;padding-right:1.25rem;">
                <?php foreach ($result['errors'] as $error): ?>
                    <li><?= wave8a_submit_h((string)($error['message'] ?? '')) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php else: ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave8a_submit_h((string)($result['message'] ?? 'ثبت یافته ناموفق بود.')) ?></p>
        </section>
    <?php endif; ?>

    <nav class="w1c-card w1c-links w8a-nav">
        <a href="erp-soft-run-finding-create.php">ثبت یافته جدید</a>
        <?php if ($result !== null && ($result['finding_id'] ?? null) !== null): ?>
            <a href="erp-soft-run-finding-detail.php?finding_id=<?= wave8a_submit_h((string)$result['finding_id']) ?>">جزئیات یافته</a>
        <?php endif; ?>
        <a href="erp-soft-run-finding-board.php">برد یافته‌های Soft Run</a>
        <a href="erp-soft-run-pilot-final-closure-dashboard.php">داشبورد نهایی بستن اجرای پایلوت</a>
    </nav>
</div>
</body>
</html>

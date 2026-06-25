<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Submit JobCard Create v2 (Wave 1F)
 * Validation-first → controlled DB write to erp_jobcards
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-form-validation-bridge.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-jobcard-v2-write-helper.php';

function wave1f_jobcard_submit_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function wave1f_jobcard_post_string(string $key): string
{
    return trim((string)($_POST[$key] ?? ''));
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: erp-jobcard-create-v2.php');
    exit;
}

$payload = [
    'customer_id' => wave1f_jobcard_post_string('customer_id'),
    'vehicle_id' => wave1f_jobcard_post_string('vehicle_id'),
    'reception_date' => wave1f_jobcard_post_string('reception_date'),
    'odometer' => wave1f_jobcard_post_string('odometer'),
    'complaint_text' => wave1f_jobcard_post_string('complaint_text'),
    'jobcard_type' => wave1f_jobcard_post_string('jobcard_type') !== '' ? wave1f_jobcard_post_string('jobcard_type') : 'repair',
    'service_category' => wave1f_jobcard_post_string('service_category') !== '' ? wave1f_jobcard_post_string('service_category') : 'mechanical',
];

$validationResult = moghare360_validate_form_payload('jobcard_create_v2', $payload);
$validationFailed = moghare360_validation_has_failed($validationResult);

if ($validationFailed) {
    ?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>خطای اعتبارسنجی — JobCard Create v2</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap">
    <header class="w1c-banner">
        <h1>نتیجه — JobCard Create v2</h1>
        <p>Critical Form v2 — Validation First</p>
    </header>
    <section class="w1c-card w1c-error-box">
        <h2 style="margin:0 0 0.5rem;font-size:1rem;">خطای اعتبارسنجی</h2>
        <p style="margin:0 0 0.5rem;"><?= wave1f_jobcard_submit_h(moghare360_validation_error_summary($validationResult)) ?></p>
        <?= moghare360_validation_errors_as_html($validationResult) ?>
    </section>
    <nav class="w1c-card w1c-links">
        <a href="erp-jobcard-create-v2.php">بازگشت به فرم</a>
        <a href="erp-critical-forms-v2-live-preview.php">فهرست پیش‌نمایش</a>
    </nav>
</div>
</body>
</html>
    <?php
    exit;
}

$extraNotes = wave1f_jobcard_post_string('notes');
$writeResult = moghare360_jobcard_v2_write($validationResult['clean'] ?? [], $extraNotes);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$_SESSION['moghare360_jobcard_v2_result'] = [
    'ok' => (bool)($writeResult['ok'] ?? false),
    'jobcard_id' => $writeResult['jobcard_id'] ?? null,
    'jobcard_number' => $writeResult['jobcard_number'] ?? null,
    'message' => (string)($writeResult['message'] ?? ''),
    'error' => (string)($writeResult['error'] ?? ''),
    'notes' => is_array($writeResult['notes'] ?? null) ? $writeResult['notes'] : [],
    'clean' => $validationResult['clean'] ?? [],
    'created_at' => date('Y-m-d H:i:s'),
];

header('Location: erp-jobcard-create-v2-result.php');
exit;

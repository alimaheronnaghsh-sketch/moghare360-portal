<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Submit JobCard Create v2 (Wave 1C)
 * Validation-first preview — no DB write · no config
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-form-validation-bridge.php';

function wave1c_jobcard_submit_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function wave1c_jobcard_post_string(string $key): string
{
    return trim((string)($_POST[$key] ?? ''));
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: erp-jobcard-create-v2.php');
    exit;
}

$payload = [
    'customer_id' => wave1c_jobcard_post_string('customer_id'),
    'vehicle_id' => wave1c_jobcard_post_string('vehicle_id'),
    'reception_date' => wave1c_jobcard_post_string('reception_date'),
    'odometer' => wave1c_jobcard_post_string('odometer'),
    'complaint_text' => wave1c_jobcard_post_string('complaint_text'),
    'jobcard_type' => wave1c_jobcard_post_string('jobcard_type') !== '' ? wave1c_jobcard_post_string('jobcard_type') : 'repair',
    'service_category' => wave1c_jobcard_post_string('service_category') !== '' ? wave1c_jobcard_post_string('service_category') : 'mechanical',
];

$result = moghare360_validate_form_payload('jobcard_create_v2', $payload);
$validationFailed = moghare360_validation_has_failed($result);

$previewPayload = $result['clean'] ?? [];
$notes = wave1c_jobcard_post_string('notes');
if ($notes !== '') {
    $previewPayload['notes'] = $notes;
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>نتیجه ثبت کارت کار v2</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap">
    <header class="w1c-banner">
        <h1>نتیجه — JobCard Create v2</h1>
        <p>Critical Form v2 — Validation First</p>
    </header>

    <?php if ($validationFailed): ?>
        <section class="w1c-card w1c-error-box">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">خطای اعتبارسنجی</h2>
            <p style="margin:0 0 0.5rem;"><?= wave1c_jobcard_submit_h(moghare360_validation_error_summary($result)) ?></p>
            <?= moghare360_validation_errors_as_html($result) ?>
        </section>
    <?php else: ?>
        <section class="w1c-card w1c-success">
            JobCard Create v2 validation passed — DB write intentionally not activated in WAVE 1C
        </section>
        <section class="w1c-card">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">داده پاک‌شده (clean)</h2>
            <pre class="w1c-payload"><?= wave1c_jobcard_submit_h(json_encode($previewPayload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) ?></pre>
        </section>
    <?php endif; ?>

    <nav class="w1c-card w1c-links">
        <a href="erp-jobcard-create-v2.php">بازگشت به فرم</a>
        <a href="erp-critical-forms-v2-live-preview.php">فهرست پیش‌نمایش</a>
    </nav>
</div>
</body>
</html>

<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Submit Vehicle Create v2 (Wave 1E)
 * Validation-first → controlled DB write to erp_vehicles
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-form-validation-bridge.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-vehicle-v2-write-helper.php';

function wave1e_vehicle_submit_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function wave1e_vehicle_post_string(string $key): string
{
    return trim((string)($_POST[$key] ?? ''));
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: erp-vehicle-create-v2.php');
    exit;
}

$plate = [
    'left' => wave1e_vehicle_post_string('plate_left'),
    'letter' => wave1e_vehicle_post_string('plate_letter'),
    'middle' => wave1e_vehicle_post_string('plate_middle'),
    'right' => wave1e_vehicle_post_string('plate_right'),
];

$payload = [
    'plate' => [
        'province' => $plate['left'],
        'letter' => $plate['letter'],
        'number' => $plate['middle'],
        'series' => $plate['right'],
    ],
    'vin' => wave1e_vehicle_post_string('vin'),
    'chassis_no' => wave1e_vehicle_post_string('chassis_no'),
    'engine_no' => wave1e_vehicle_post_string('engine_no'),
    'vehicle_notes' => wave1e_vehicle_post_string('notes'),
    'brand_id' => wave1e_vehicle_post_string('brand_id') !== '' ? wave1e_vehicle_post_string('brand_id') : '10',
    'model_id' => wave1e_vehicle_post_string('model_id') !== '' ? wave1e_vehicle_post_string('model_id') : '25',
    'vehicle_class' => wave1e_vehicle_post_string('vehicle_class') !== '' ? wave1e_vehicle_post_string('vehicle_class') : 'sedan',
];

$validationResult = moghare360_validate_form_payload('vehicle_create_v2', $payload);
$validationFailed = moghare360_validation_has_failed($validationResult);

if ($validationFailed) {
    ?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>خطای اعتبارسنجی — Vehicle Create v2</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap">
    <header class="w1c-banner">
        <h1>نتیجه — Vehicle Create v2</h1>
        <p>Critical Form v2 — Validation First</p>
    </header>
    <section class="w1c-card w1c-error-box">
        <h2 style="margin:0 0 0.5rem;font-size:1rem;">خطای اعتبارسنجی</h2>
        <p style="margin:0 0 0.5rem;"><?= wave1e_vehicle_submit_h(moghare360_validation_error_summary($validationResult)) ?></p>
        <?= moghare360_validation_errors_as_html($validationResult) ?>
    </section>
    <nav class="w1c-card w1c-links">
        <a href="erp-vehicle-create-v2.php">بازگشت به فرم</a>
        <a href="erp-critical-forms-v2-live-preview.php">فهرست پیش‌نمایش</a>
    </nav>
</div>
</body>
</html>
    <?php
    exit;
}

$writeResult = moghare360_vehicle_v2_write($validationResult['clean'] ?? []);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$cleanPayload = $validationResult['clean'] ?? [];
$cleanPayload['plate_structured'] = $plate;

$_SESSION['moghare360_vehicle_v2_result'] = [
    'ok' => (bool)($writeResult['ok'] ?? false),
    'vehicle_id' => $writeResult['vehicle_id'] ?? null,
    'vehicle_code' => $writeResult['vehicle_code'] ?? null,
    'message' => (string)($writeResult['message'] ?? ''),
    'error' => (string)($writeResult['error'] ?? ''),
    'audit_note' => (string)($writeResult['audit_note'] ?? ''),
    'clean' => $cleanPayload,
    'created_at' => date('Y-m-d H:i:s'),
];

header('Location: erp-vehicle-create-v2-result.php');
exit;

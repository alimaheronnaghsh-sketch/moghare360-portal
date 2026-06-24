<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Submit JobCard Camera Capture (Wave 2A)
 * Camera-only local save · no DB · no $_FILES
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-camera-media-helper.php';

function wave2a_submit_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: erp-jobcard-camera-capture.php');
    exit;
}

if (!empty($_FILES)) {
    $uploadReject = [
        'ok' => false,
        'errors' => [[
            'field' => '_files',
            'rule' => 'upload_bypass_forbidden',
            'message' => 'آپلود فایل مجاز نیست — فقط دوربین مستقیم.',
        ]],
    ];
} else {
    $payload = [
        'jobcard_id' => trim((string)($_POST['jobcard_id'] ?? '')),
        'media_stage' => trim((string)($_POST['media_stage'] ?? '')),
        'media_type' => trim((string)($_POST['media_type'] ?? '')),
        'camera_data' => trim((string)($_POST['camera_data'] ?? '')),
    ];

    $saveResult = moghare360_camera_media_save_base64($payload);
    $uploadReject = null;
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>نتیجه ثبت تصویر دوربین</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap">
    <header class="w1c-banner">
        <h1>نتیجه ثبت تصویر دوربین</h1>
        <p>Wave 2A — Local storage only · No DB write</p>
    </header>

    <?php if ($uploadReject !== null): ?>
        <section class="w1c-card w1c-error-box">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">آپلود مسدود شد</h2>
            <p style="margin:0;"><?= wave2a_submit_h($uploadReject['errors'][0]['message'] ?? '') ?></p>
        </section>
    <?php elseif (!($saveResult['ok'] ?? false)): ?>
        <section class="w1c-card w1c-error-box">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">خطای اعتبارسنجی</h2>
            <ul style="margin:0;padding-right:1.25rem;">
                <?php foreach (($saveResult['errors'] ?? []) as $error): ?>
                    <li><?= wave2a_submit_h((string)($error['message'] ?? '')) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php else: ?>
        <section class="w1c-card w1c-success">
            <?= wave2a_submit_h((string)($saveResult['message'] ?? 'ذخیره موفق')) ?>
        </section>
        <section class="w1c-card">
            <p style="margin:0 0 0.35rem;"><strong>مسیر نسبی:</strong></p>
            <pre class="w1c-payload"><?= wave2a_submit_h((string)($saveResult['relative_path'] ?? '')) ?></pre>
            <p style="margin:0.75rem 0 0;font-size:0.85rem;color:#525252;">نوشتن متادیتا در DB در Wave 2A فعال نیست.</p>
        </section>
    <?php endif; ?>

    <nav class="w1c-card w1c-links">
        <a href="erp-jobcard-camera-capture.php">بازگشت به دوربین</a>
        <a href="erp-jobcard-media-preview.php?jobcard_id=<?= wave2a_submit_h(trim((string)($_POST['jobcard_id'] ?? '1'))) ?>">پیش‌نمایش رسانه</a>
    </nav>
</div>
</body>
</html>

<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Submit JobCard Camera Capture (Wave 2A + 2B)
 * Camera-only local save · metadata DB binding when safe schema confirmed
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-camera-media-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-jobcard-media-metadata-helper.php';

function wave2b_submit_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: erp-jobcard-camera-capture.php');
    exit;
}

$metadataResult = null;

if (!empty($_FILES)) {
    $uploadReject = [
        'ok' => false,
        'errors' => [[
            'field' => '_files',
            'rule' => 'upload_bypass_forbidden',
            'message' => 'آپلود فایل مجاز نیست — فقط دوربین مستقیم.',
        ]],
    ];
    $saveResult = null;
} else {
    $payload = [
        'jobcard_id' => trim((string)($_POST['jobcard_id'] ?? '')),
        'media_stage' => trim((string)($_POST['media_stage'] ?? '')),
        'media_type' => trim((string)($_POST['media_type'] ?? '')),
        'camera_data' => trim((string)($_POST['camera_data'] ?? '')),
    ];

    $saveResult = moghare360_camera_media_save_base64($payload);
    $uploadReject = null;

    if (($saveResult['ok'] ?? false) === true) {
        $filePath = (string)($saveResult['file_path'] ?? '');
        $fileSize = is_file($filePath) ? (int)filesize($filePath) : 0;
        $validation = moghare360_validate_camera_media_payload($payload);
        $mimeType = (string)($validation['clean']['mime'] ?? 'image/jpeg');

        $metadataResult = moghare360_jobcard_media_metadata_bind([
            'jobcard_id' => (int)($validation['clean']['jobcard_id'] ?? 0),
            'media_stage' => (string)($validation['clean']['media_stage'] ?? ''),
            'media_type' => (string)($validation['clean']['media_type'] ?? ''),
            'relative_path' => (string)($saveResult['relative_path'] ?? ''),
            'file_path' => $filePath,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
        ]);
    }
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
        <p>Wave 2B — Local save + controlled metadata DB binding</p>
    </header>

    <?php if ($uploadReject !== null): ?>
        <section class="w1c-card w1c-error-box">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">آپلود مسدود شد</h2>
            <p style="margin:0;"><?= wave2b_submit_h($uploadReject['errors'][0]['message'] ?? '') ?></p>
        </section>
    <?php elseif (!($saveResult['ok'] ?? false)): ?>
        <section class="w1c-card w1c-error-box">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">خطای اعتبارسنجی</h2>
            <ul style="margin:0;padding-right:1.25rem;">
                <?php foreach (($saveResult['errors'] ?? []) as $error): ?>
                    <li><?= wave2b_submit_h((string)($error['message'] ?? '')) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php else: ?>
        <section class="w1c-card w1c-success">
            <?= wave2b_submit_h((string)($saveResult['message'] ?? 'ذخیره موفق')) ?>
        </section>
        <section class="w1c-card">
            <p style="margin:0 0 0.35rem;"><strong>مسیر نسبی:</strong></p>
            <pre class="w1c-payload"><?= wave2b_submit_h((string)($saveResult['relative_path'] ?? '')) ?></pre>
        </section>

        <?php if ($metadataResult !== null && ($metadataResult['ok'] ?? false) === true): ?>
            <section class="w1c-card w1c-success">
                <p style="margin:0;"><?= wave2b_submit_h((string)($metadataResult['message'] ?? '')) ?></p>
                <?php if (($metadataResult['media_id'] ?? null) !== null): ?>
                    <p style="margin:0.5rem 0 0;"><strong>شناسه متادیتا:</strong> <?= wave2b_submit_h((string)$metadataResult['media_id']) ?></p>
                <?php endif; ?>
            </section>
        <?php elseif ($metadataResult !== null): ?>
            <section class="w1c-card w1c-note">
                <p style="margin:0 0 0.35rem;"><strong>وضعیت متادیتای DB:</strong></p>
                <?php if (($metadataResult['error'] ?? '') === MOGHARE360_JOBCARD_MEDIA_METADATA_STATUS_BLOCKED): ?>
                    <p style="margin:0;">فایل رسانه در ذخیره‌سازی محلی ثبت شد؛ اتصال متادیتای DB به‌صورت ایمن مسدود است.</p>
                    <p style="margin:0.5rem 0 0;font-size:0.9rem;color:#525252;">Media file saved locally, metadata DB binding safely blocked</p>
                <?php else: ?>
                    <p style="margin:0;"><?= wave2b_submit_h((string)($metadataResult['message'] ?? 'ثبت متادیتا ناموفق بود.')) ?></p>
                <?php endif; ?>
                <?php if (!empty($metadataResult['notes'])): ?>
                    <ul style="margin:0.5rem 0 0;padding-right:1.25rem;font-size:0.85rem;color:#525252;">
                        <?php foreach ($metadataResult['notes'] as $note): ?>
                            <li><?= wave2b_submit_h((string)$note) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    <?php endif; ?>

    <nav class="w1c-card w1c-links">
        <a href="erp-jobcard-camera-capture.php">بازگشت به دوربین</a>
        <a href="erp-jobcard-media-preview.php?jobcard_id=<?= wave2b_submit_h(trim((string)($_POST['jobcard_id'] ?? '1'))) ?>">پیش‌نمایش رسانه</a>
    </nav>
</div>
</body>
</html>

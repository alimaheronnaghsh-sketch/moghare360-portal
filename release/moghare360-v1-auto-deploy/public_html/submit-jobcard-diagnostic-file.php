<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Submit JobCard Diagnostic File (Wave 2C)
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-diagnostic-file-helper.php';

function wave2c_submit_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: erp-jobcard-diagnostic-file.php');
    exit;
}

$payload = [
    'jobcard_id' => trim((string)($_POST['jobcard_id'] ?? '')),
    'diagnostic_stage' => trim((string)($_POST['diagnostic_stage'] ?? '')),
    'diagnostic_type' => trim((string)($_POST['diagnostic_type'] ?? '')),
    'external_url' => trim((string)($_POST['external_url'] ?? '')),
];

$file = $_FILES['diagnostic_file'] ?? [];

$saveResult = moghare360_diagnostic_save_file($payload, $file);
$metadataResult = null;

if (($saveResult['ok'] ?? false) === true) {
    $metadataResult = moghare360_diagnostic_register_metadata($saveResult);
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>نتیجه ثبت فایل تشخیصی</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w2c-wrap">
    <header class="w1c-banner w2c-banner">
        <h1>نتیجه ثبت فایل تشخیصی</h1>
        <p>Wave 2C — Local save + metadata DB binding</p>
    </header>

    <?php if (!($saveResult['ok'] ?? false)): ?>
        <section class="w1c-card w1c-error-box">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">خطای اعتبارسنجی</h2>
            <ul style="margin:0;padding-right:1.25rem;">
                <?php foreach (($saveResult['errors'] ?? []) as $error): ?>
                    <li><?= wave2c_submit_h((string)($error['message'] ?? '')) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php else: ?>
        <section class="w1c-card w1c-success">
            <?= wave2c_submit_h((string)($saveResult['message'] ?? 'ذخیره موفق')) ?>
        </section>
        <section class="w1c-card">
            <p style="margin:0 0 0.35rem;"><strong>مسیر نسبی:</strong></p>
            <pre class="w1c-payload"><?= wave2c_submit_h((string)($saveResult['relative_path'] ?? '')) ?></pre>
        </section>

        <?php if ($metadataResult !== null && ($metadataResult['ok'] ?? false) === true): ?>
            <section class="w1c-card w1c-success">
                <p style="margin:0;"><?= wave2c_submit_h((string)($metadataResult['message'] ?? '')) ?></p>
                <?php if (($metadataResult['media_id'] ?? null) !== null): ?>
                    <p style="margin:0.5rem 0 0;"><strong>شناسه متادیتا:</strong> <?= wave2c_submit_h((string)$metadataResult['media_id']) ?></p>
                <?php endif; ?>
                <?php if (($metadataResult['history_id'] ?? null) !== null): ?>
                    <p style="margin:0.35rem 0 0;"><strong>شناسه تاریخچه:</strong> <?= wave2c_submit_h((string)$metadataResult['history_id']) ?></p>
                <?php endif; ?>
            </section>
        <?php elseif ($metadataResult !== null): ?>
            <section class="w1c-card w1c-error-box">
                <p style="margin:0;"><?= wave2c_submit_h((string)($metadataResult['message'] ?? 'ثبت متادیتا ناموفق بود.')) ?></p>
                <?php if (!empty($metadataResult['notes'])): ?>
                    <ul style="margin:0.5rem 0 0;padding-right:1.25rem;font-size:0.85rem;">
                        <?php foreach ($metadataResult['notes'] as $note): ?>
                            <li><?= wave2c_submit_h((string)$note) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    <?php endif; ?>

    <nav class="w1c-card w1c-links">
        <a href="erp-jobcard-diagnostic-file.php">بازگشت به ثبت تشخیصی</a>
        <a href="erp-jobcard-diagnostic-preview.php?jobcard_id=<?= wave2c_submit_h(trim((string)($_POST['jobcard_id'] ?? '1'))) ?>">پیش‌نمایش تشخیصی</a>
    </nav>
</div>
</body>
</html>

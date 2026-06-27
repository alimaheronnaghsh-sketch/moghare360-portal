<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — JobCard Diagnostic Preview (Wave 2C)
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-diagnostic-file-helper.php';

function wave2c_preview_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$jobcardIdRaw = trim((string)($_GET['jobcard_id'] ?? ''));
$invalidId = $jobcardIdRaw === '' || !ctype_digit($jobcardIdRaw) || (int)$jobcardIdRaw < 1;
$jobcardId = $invalidId ? 0 : (int)$jobcardIdRaw;
$localFiles = $invalidId ? [] : moghare360_diagnostic_list_local_files($jobcardId);
$metadata = $invalidId ? ['ok' => false, 'records' => [], 'notes' => []] : moghare360_diagnostic_list_metadata($jobcardId);

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>پیش‌نمایش فایل‌های تشخیصی</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w2c-wrap">
    <header class="w1c-banner w2c-banner">
        <h1>پیش‌نمایش فایل‌های تشخیصی</h1>
        <p>Wave 2C — Local diagnostic files + metadata DB read</p>
    </header>

    <?php if ($invalidId): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;">شناسه کارت کار نامعتبر است. مقدار عددی مثبت وارد کنید.</p>
        </section>
    <?php else: ?>
        <?php if (($metadata['ok'] ?? false) === true && !empty($metadata['records'])): ?>
            <section class="w1c-card">
                <h2 style="margin:0 0 0.75rem;font-size:1rem;">متادیتای DB — تشخیصی کارت کار <?= wave2c_preview_h((string)$jobcardId) ?></h2>
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:0.9rem;">
                        <thead>
                        <tr>
                            <th style="text-align:right;padding:0.35rem;">شناسه</th>
                            <th style="text-align:right;padding:0.35rem;">مرحله</th>
                            <th style="text-align:right;padding:0.35rem;">MIME</th>
                            <th style="text-align:right;padding:0.35rem;">مسیر</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($metadata['records'] as $row): ?>
                            <tr>
                                <td style="padding:0.35rem;"><?= wave2c_preview_h((string)($row['media_id'] ?? '')) ?></td>
                                <td style="padding:0.35rem;"><?= wave2c_preview_h((string)($row['media_stage'] ?? '')) ?></td>
                                <td style="padding:0.35rem;"><?= wave2c_preview_h((string)($row['mime_type'] ?? '')) ?></td>
                                <td style="padding:0.35rem;"><code><?= wave2c_preview_h((string)($row['relative_path'] ?? '')) ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php else: ?>
            <section class="w1c-card w1c-note">
                <p style="margin:0;">رکورد متادیتای تشخیصی در DB برای این کارت کار یافت نشد.</p>
            </section>
        <?php endif; ?>

        <?php if ($localFiles === []): ?>
            <section class="w1c-card w1c-note">
                <p style="margin:0;">فایل تشخیصی محلی برای کارت کار <strong><?= wave2c_preview_h((string)$jobcardId) ?></strong> یافت نشد.</p>
            </section>
        <?php else: ?>
            <section class="w1c-card">
                <h2 style="margin:0 0 0.75rem;font-size:1rem;">فایل‌های محلی — کارت کار <?= wave2c_preview_h((string)$jobcardId) ?></h2>
                <ul style="margin:0;padding-right:1.25rem;">
                    <?php foreach ($localFiles as $relativePath): ?>
                        <li style="margin-bottom:0.5rem;">
                            <a href="<?= wave2c_preview_h($relativePath) ?>" target="_blank" rel="noopener noreferrer"><?= wave2c_preview_h(basename($relativePath)) ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>
    <?php endif; ?>

    <section class="w1c-card w1c-form">
        <form method="get" action="erp-jobcard-diagnostic-preview.php">
            <label for="jobcard_id_lookup">جستجوی شناسه کارت کار</label>
            <input type="number" id="jobcard_id_lookup" name="jobcard_id" min="1" value="<?= $invalidId ? '' : wave2c_preview_h((string)$jobcardId) ?>" required>
            <button type="submit" class="w1c-btn">نمایش</button>
        </form>
    </section>

    <nav class="w1c-card w1c-links">
        <a href="erp-jobcard-diagnostic-file.php">ثبت فایل تشخیصی</a>
        <a href="erp-jobcard-camera-capture.php">ثبت تصویر دوربین</a>
    </nav>
</div>
</body>
</html>

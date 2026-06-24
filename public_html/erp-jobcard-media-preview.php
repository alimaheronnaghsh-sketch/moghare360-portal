<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — JobCard Media Preview (Wave 2A)
 * Local filesystem preview only · no DB
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-camera-media-helper.php';

function wave2a_preview_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$jobcardIdRaw = trim((string)($_GET['jobcard_id'] ?? ''));
$invalidId = $jobcardIdRaw === '' || !ctype_digit($jobcardIdRaw) || (int)$jobcardIdRaw < 1;
$jobcardId = $invalidId ? 0 : (int)$jobcardIdRaw;
$files = $invalidId ? [] : moghare360_camera_media_list_jobcard_files($jobcardId);

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>پیش‌نمایش رسانه کارت کار</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w2a-wrap">
    <header class="w1c-banner w2a-banner">
        <h1>پیش‌نمایش رسانه کارت کار</h1>
        <p>Wave 2A — Local storage preview · No DB read</p>
    </header>

  <?php if ($invalidId): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;">شناسه کارت کار نامعتبر است. مقدار عددی مثبت وارد کنید.</p>
        </section>
    <?php elseif ($files === []): ?>
        <section class="w1c-card w1c-note">
            برای کارت کار <strong><?= wave2a_preview_h((string)$jobcardId) ?></strong> رسانه‌ای در ذخیره‌سازی محلی یافت نشد.
        </section>
    <?php else: ?>
        <section class="w1c-card">
            <h2 style="margin:0 0 0.75rem;font-size:1rem;">رسانه‌های کارت کار <?= wave2a_preview_h((string)$jobcardId) ?></h2>
            <div class="w2a-thumb-grid">
                <?php foreach ($files as $relativePath): ?>
                    <figure class="w2a-thumb-item">
                        <img src="<?= wave2a_preview_h($relativePath) ?>" alt="رسانه" class="w2a-thumb-img" loading="lazy">
                        <figcaption class="w2a-thumb-cap"><?= wave2a_preview_h(basename($relativePath)) ?></figcaption>
                    </figure>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="w1c-card w1c-form">
        <form method="get" action="erp-jobcard-media-preview.php">
            <label for="jobcard_id_lookup">جستجوی شناسه کارت کار</label>
            <input type="number" id="jobcard_id_lookup" name="jobcard_id" min="1" value="<?= $invalidId ? '' : wave2a_preview_h((string)$jobcardId) ?>" required>
            <button type="submit" class="w1c-btn">نمایش</button>
        </form>
    </section>

    <nav class="w1c-card w1c-links">
        <a href="erp-jobcard-camera-capture.php">ثبت تصویر دوربین</a>
        <a href="erp-critical-forms-v2-live-preview.php">فهرست پیش‌نمایش</a>
    </nav>
</div>
</body>
</html>

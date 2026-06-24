<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — JobCard Media Preview (Wave 2A + 2B)
 * Local filesystem preview + metadata DB read when safe schema confirmed
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-camera-media-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-jobcard-media-metadata-helper.php';

function wave2b_preview_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$jobcardIdRaw = trim((string)($_GET['jobcard_id'] ?? ''));
$invalidId = $jobcardIdRaw === '' || !ctype_digit($jobcardIdRaw) || (int)$jobcardIdRaw < 1;
$jobcardId = $invalidId ? 0 : (int)$jobcardIdRaw;
$files = $invalidId ? [] : moghare360_camera_media_list_jobcard_files($jobcardId);
$metadataList = $invalidId
    ? ['activated' => false, 'status' => MOGHARE360_JOBCARD_MEDIA_METADATA_STATUS_BLOCKED, 'records' => [], 'notes' => []]
    : moghare360_jobcard_media_metadata_list_for_jobcard($jobcardId);

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
        <p>Wave 2B — Local preview + controlled metadata DB read</p>
    </header>

    <?php if ($invalidId): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;">شناسه کارت کار نامعتبر است. مقدار عددی مثبت وارد کنید.</p>
        </section>
    <?php else: ?>
        <?php if (($metadataList['activated'] ?? false) === true && !empty($metadataList['records'])): ?>
            <section class="w1c-card">
                <h2 style="margin:0 0 0.75rem;font-size:1rem;">متادیتای DB — کارت کار <?= wave2b_preview_h((string)$jobcardId) ?></h2>
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:0.9rem;">
                        <thead>
                        <tr>
                            <?php if (isset($metadataList['records'][0]['media_id'])): ?><th>شناسه</th><?php endif; ?>
                            <th>مرحله</th>
                            <th>نوع</th>
                            <th>مسیر</th>
                            <?php if (isset($metadataList['records'][0]['mime_type'])): ?><th>MIME</th><?php endif; ?>
                            <?php if (isset($metadataList['records'][0]['file_size'])): ?><th>حجم</th><?php endif; ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($metadataList['records'] as $row): ?>
                            <tr>
                                <?php if (isset($row['media_id'])): ?><td><?= wave2b_preview_h((string)$row['media_id']) ?></td><?php endif; ?>
                                <td><?= wave2b_preview_h((string)($row['media_stage'] ?? '')) ?></td>
                                <td><?= wave2b_preview_h((string)($row['media_type'] ?? '')) ?></td>
                                <td><code><?= wave2b_preview_h((string)($row['file_path'] ?? '')) ?></code></td>
                                <?php if (isset($metadataList['records'][0]['mime_type'])): ?><td><?= wave2b_preview_h((string)($row['mime_type'] ?? '')) ?></td><?php endif; ?>
                                <?php if (isset($metadataList['records'][0]['file_size'])): ?><td><?= wave2b_preview_h((string)($row['file_size'] ?? '')) ?></td><?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php elseif (($metadataList['activated'] ?? false) === false): ?>
            <section class="w1c-card w1c-note">
                <p style="margin:0;">Metadata DB preview pending safe schema confirmation</p>
                <p style="margin:0.5rem 0 0;font-size:0.9rem;color:#525252;">پیش‌نمایش متادیتای DB پس از تأیید ایمن اسکیما فعال می‌شود.</p>
            </section>
        <?php endif; ?>

        <?php if ($files === []): ?>
            <section class="w1c-card w1c-note">
                برای کارت کار <strong><?= wave2b_preview_h((string)$jobcardId) ?></strong> رسانه‌ای در ذخیره‌سازی محلی یافت نشد.
            </section>
        <?php else: ?>
            <section class="w1c-card">
                <h2 style="margin:0 0 0.75rem;font-size:1rem;">رسانه‌های محلی — کارت کار <?= wave2b_preview_h((string)$jobcardId) ?></h2>
                <div class="w2a-thumb-grid">
                    <?php foreach ($files as $relativePath): ?>
                        <figure class="w2a-thumb-item">
                            <img src="<?= wave2b_preview_h($relativePath) ?>" alt="رسانه" class="w2a-thumb-img" loading="lazy">
                            <figcaption class="w2a-thumb-cap"><?= wave2b_preview_h(basename($relativePath)) ?></figcaption>
                        </figure>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    <?php endif; ?>

    <section class="w1c-card w1c-form">
        <form method="get" action="erp-jobcard-media-preview.php">
            <label for="jobcard_id_lookup">جستجوی شناسه کارت کار</label>
            <input type="number" id="jobcard_id_lookup" name="jobcard_id" min="1" value="<?= $invalidId ? '' : wave2b_preview_h((string)$jobcardId) ?>" required>
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

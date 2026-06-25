<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — JobCard Evidence Timeline (Wave 2E)
 * Read-only audit timeline · no DB write · no upload
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-jobcard-evidence-timeline-helper.php';

function wave2e_timeline_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$jobcardIdRaw = trim((string)($_GET['jobcard_id'] ?? ''));
$invalidId = $jobcardIdRaw === '' || !ctype_digit($jobcardIdRaw) || (int)$jobcardIdRaw < 1;
$jobcardId = $invalidId ? 0 : (int)$jobcardIdRaw;
$timeline = $invalidId
    ? [
        'ok' => false,
        'jobcard_id' => 0,
        'media_count' => 0,
        'history_count' => 0,
        'events' => [],
        'warnings' => [],
        'errors' => ['شناسه کارت کار باید عدد مثبت باشد.'],
    ]
    : moghare360_jobcard_evidence_timeline_review($jobcardId);

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>خط زمانی مدارک کارت کار</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w2e-wrap">
    <header class="w1c-banner w2e-banner">
        <h1>خط زمانی و بازبینی ممیزی مدارک</h1>
        <p>Wave 2E — Read-only evidence timeline</p>
    </header>

    <section class="w1c-card w1c-form">
        <form method="get" action="erp-jobcard-evidence-timeline.php">
            <label for="jobcard_id_lookup">شناسه کارت کار</label>
            <input type="number" id="jobcard_id_lookup" name="jobcard_id" min="1" value="<?= $invalidId ? '' : wave2e_timeline_h((string)$jobcardId) ?>" required>
            <button type="submit" class="w1c-btn">نمایش خط زمانی</button>
        </form>
    </section>

    <?php if ($invalidId): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;">شناسه کارت کار نامعتبر است. مقدار عددی مثبت وارد کنید.</p>
        </section>
    <?php elseif (!($timeline['ok'] ?? false)): ?>
        <section class="w1c-card w1c-error-box">
            <ul style="margin:0;padding-right:1.25rem;">
                <?php foreach (($timeline['errors'] ?? []) as $error): ?>
                    <li><?= wave2e_timeline_h((string)$error) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php else: ?>
        <section class="w1c-card">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">کارت کار <?= wave2e_timeline_h((string)$jobcardId) ?></h2>
            <p style="margin:0;">رسانه: <strong><?= wave2e_timeline_h((string)($timeline['media_count'] ?? 0)) ?></strong>
                — تاریخچه: <strong><?= wave2e_timeline_h((string)($timeline['history_count'] ?? 0)) ?></strong></p>
        </section>

        <?php if (!empty($timeline['warnings'])): ?>
            <section class="w1c-card w2e-warning-box">
                <h2 style="margin:0 0 0.5rem;font-size:1rem;">هشدارهای ممیزی</h2>
                <ul style="margin:0;padding-right:1.25rem;">
                    <?php foreach ($timeline['warnings'] as $warning): ?>
                        <li><?= wave2e_timeline_h((string)$warning) ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <?php if (($timeline['events'] ?? []) === []): ?>
            <section class="w1c-card w1c-note">
                <p style="margin:0;">رویدادی برای این کارت کار یافت نشد.</p>
            </section>
        <?php else: ?>
            <section class="w1c-card">
                <h2 style="margin:0 0 0.75rem;font-size:1rem;">رویدادها (جدیدترین اول)</h2>
                <div class="w2e-timeline">
                    <?php foreach ($timeline['events'] as $event): ?>
                        <article class="w2e-timeline-item">
                            <div class="w2e-timeline-meta">
                                <span class="w2e-timeline-kind"><?= wave2e_timeline_h((string)($event['kind'] ?? '')) ?></span>
                                <time><?= wave2e_timeline_h((string)($event['event_at'] ?? $event['created_at'] ?? '')) ?></time>
                            </div>
                            <h3 class="w2e-timeline-title"><?= wave2e_timeline_h((string)($event['event_label'] ?? $event['event_title'] ?? '')) ?></h3>
                            <dl class="w2e-timeline-dl">
                                <?php if (($event['media_id'] ?? '') !== ''): ?>
                                    <dt>شناسه رسانه</dt><dd><?= wave2e_timeline_h((string)$event['media_id']) ?></dd>
                                <?php endif; ?>
                                <?php if (($event['media_stage'] ?? '') !== ''): ?>
                                    <dt>مرحله</dt><dd><?= wave2e_timeline_h((string)($event['stage_label'] ?? $event['media_stage'])) ?></dd>
                                <?php endif; ?>
                                <?php if (($event['media_type'] ?? '') !== ''): ?>
                                    <dt>نوع</dt><dd><?= wave2e_timeline_h((string)$event['media_type']) ?></dd>
                                <?php endif; ?>
                                <?php if (($event['mime_type'] ?? '') !== ''): ?>
                                    <dt>MIME</dt><dd><?= wave2e_timeline_h((string)$event['mime_type']) ?></dd>
                                <?php endif; ?>
                                <?php if (($event['relative_path'] ?? '') !== ''): ?>
                                    <dt>مسیر نسبی</dt><dd><code><?= wave2e_timeline_h((string)$event['relative_path']) ?></code></dd>
                                <?php endif; ?>
                                <?php if (($event['source'] ?? '') !== ''): ?>
                                    <dt>منبع</dt><dd><?= wave2e_timeline_h((string)$event['source']) ?></dd>
                                <?php endif; ?>
                                <?php if (($event['capture_method'] ?? '') !== ''): ?>
                                    <dt>روش ثبت</dt><dd><?= wave2e_timeline_h((string)$event['capture_method']) ?></dd>
                                <?php endif; ?>
                                <?php if (($event['metadata_status'] ?? '') !== ''): ?>
                                    <dt>وضعیت متادیتا</dt><dd><?= wave2e_timeline_h((string)$event['metadata_status']) ?></dd>
                                <?php endif; ?>
                                <?php if (($event['event_code'] ?? '') !== ''): ?>
                                    <dt>کد رویداد</dt><dd><?= wave2e_timeline_h((string)$event['event_code']) ?></dd>
                                <?php endif; ?>
                                <?php if (($event['event_notes'] ?? '') !== ''): ?>
                                    <dt>یادداشت</dt><dd><?= wave2e_timeline_h((string)$event['event_notes']) ?></dd>
                                <?php endif; ?>
                            </dl>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    <?php endif; ?>

    <nav class="w1c-card w1c-links">
        <a href="erp-jobcard-evidence-review.php?jobcard_id=<?= wave2e_timeline_h($invalidId ? '1' : (string)$jobcardId) ?>">بازبینی تکمیل مدارک</a>
        <a href="erp-jobcard-media-preview.php?jobcard_id=<?= wave2e_timeline_h($invalidId ? '1' : (string)$jobcardId) ?>">پیش‌نمایش رسانه</a>
        <a href="erp-jobcard-diagnostic-preview.php?jobcard_id=<?= wave2e_timeline_h($invalidId ? '1' : (string)$jobcardId) ?>">پیش‌نمایش تشخیصی</a>
    </nav>
</div>
</body>
</html>

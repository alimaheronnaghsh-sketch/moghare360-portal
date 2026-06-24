<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Media & Evidence Operational Closure Dashboard (Wave 2F)
 * Read-only · no DB write · no upload
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-wave-2-closure-helper.php';

function wave2f_dash_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$closure = moghare360_wave_2_closure_status();
$summary = (array)($closure['summary'] ?? []);
$recentMedia = moghare360_wave_2_closure_fetch_recent_media(10);
$recentHistory = moghare360_wave_2_closure_fetch_recent_history(10);

$statusClass = match ($closure['status'] ?? '') {
    MOGHARE360_WAVE2_CLOSURE_STATUS_READY => 'w2f-status-ready',
    MOGHARE360_WAVE2_CLOSURE_STATUS_PARTIAL => 'w2f-status-partial',
    MOGHARE360_WAVE2_CLOSURE_STATUS_EMPTY => 'w2f-status-empty',
    default => 'w2f-status-error',
};

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>داشبورد بستن عملیاتی WAVE 2</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w2f-wrap">
    <header class="w1c-banner w2f-banner">
        <h1>داشبورد بستن عملیاتی رسانه و مدارک</h1>
        <p>WAVE 2F — Media &amp; Evidence Operational Closure</p>
    </header>

    <section class="w1c-card <?= wave2f_dash_h($statusClass) ?>">
        <h2 style="margin:0 0 0.5rem;font-size:1rem;">وضعیت بستن WAVE 2</h2>
        <p style="margin:0;">
            <strong><?= wave2f_dash_h(moghare360_wave_2_closure_status_label((string)($closure['status'] ?? ''))) ?></strong>
            (<?= wave2f_dash_h((string)($closure['status'] ?? '')) ?>)
        </p>
        <p style="margin:0.5rem 0 0;"><?= wave2f_dash_h((string)($closure['message'] ?? '')) ?></p>
    </section>

    <?php if (!empty($closure['errors'])): ?>
        <section class="w1c-card w1c-error-box">
            <ul style="margin:0;padding-right:1.25rem;">
                <?php foreach ($closure['errors'] as $error): ?>
                    <li><?= wave2f_dash_h((string)$error) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <?php if ($summary !== []): ?>
        <section class="w1c-card">
            <h2 style="margin:0 0 0.75rem;font-size:1rem;">خلاصه شمارش‌ها</h2>
            <div class="w2f-stat-grid">
                <div class="w2f-stat"><span>کل رسانه</span><strong><?= wave2f_dash_h((string)($summary['total_media'] ?? 0)) ?></strong></div>
                <div class="w2f-stat"><span>دوربین/عکس</span><strong><?= wave2f_dash_h((string)($summary['total_camera_photo'] ?? 0)) ?></strong></div>
                <div class="w2f-stat"><span>تشخیصی</span><strong><?= wave2f_dash_h((string)($summary['total_diagnostic'] ?? 0)) ?></strong></div>
                <div class="w2f-stat"><span>تاریخچه</span><strong><?= wave2f_dash_h((string)($summary['total_history'] ?? 0)) ?></strong></div>
            </div>
            <p style="margin:0.75rem 0 0;font-size:0.85rem;color:#525252;">
                آخرین رسانه: <?= wave2f_dash_h((string)($summary['latest_media_created_at'] ?? '—')) ?>
                — آخرین تاریخچه: <?= wave2f_dash_h((string)($summary['latest_history_event_at'] ?? '—')) ?>
            </p>
        </section>

        <?php foreach (['by_media_stage' => 'بر اساس مرحله', 'by_media_type' => 'بر اساس نوع', 'by_mime_type' => 'بر اساس MIME'] as $key => $title): ?>
            <?php if (!empty($summary[$key]) && is_array($summary[$key])): ?>
                <section class="w1c-card">
                    <h2 style="margin:0 0 0.5rem;font-size:1rem;"><?= wave2f_dash_h($title) ?></h2>
                    <ul style="margin:0;padding-right:1.25rem;font-size:0.9rem;">
                        <?php foreach ($summary[$key] as $grp => $cnt): ?>
                            <li><?= wave2f_dash_h((string)$grp) ?>: <strong><?= wave2f_dash_h((string)$cnt) ?></strong></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <section class="w1c-card">
        <h2 style="margin:0 0 0.5rem;font-size:1rem;">بررسی‌های بستن</h2>
        <ul style="margin:0;padding-right:1.25rem;font-size:0.9rem;">
            <?php foreach (($closure['checks'] ?? []) as $checkKey => $checkVal): ?>
                <li><?= wave2f_dash_h((string)$checkKey) ?>: <?= $checkVal ? '✅' : '❌' ?></li>
            <?php endforeach; ?>
        </ul>
    </section>

    <?php if (($recentMedia['ok'] ?? false) && !empty($recentMedia['rows'])): ?>
        <section class="w1c-card">
            <h2 style="margin:0 0 0.75rem;font-size:1rem;">آخرین رسانه‌ها</h2>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:0.85rem;">
                    <thead>
                    <tr>
                        <th style="text-align:right;padding:0.35rem;">شناسه</th>
                        <th style="text-align:right;padding:0.35rem;">کارت کار</th>
                        <th style="text-align:right;padding:0.35rem;">مرحله</th>
                        <th style="text-align:right;padding:0.35rem;">نوع</th>
                        <th style="text-align:right;padding:0.35rem;">MIME</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentMedia['rows'] as $row): ?>
                        <tr>
                            <td style="padding:0.35rem;"><?= wave2f_dash_h((string)($row['media_id'] ?? '')) ?></td>
                            <td style="padding:0.35rem;"><?= wave2f_dash_h((string)($row['jobcard_id'] ?? '')) ?></td>
                            <td style="padding:0.35rem;"><?= wave2f_dash_h((string)($row['media_stage'] ?? '')) ?></td>
                            <td style="padding:0.35rem;"><?= wave2f_dash_h((string)($row['media_type'] ?? '')) ?></td>
                            <td style="padding:0.35rem;"><?= wave2f_dash_h((string)($row['mime_type'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <?php if (($recentHistory['ok'] ?? false) && !empty($recentHistory['rows'])): ?>
        <section class="w1c-card">
            <h2 style="margin:0 0 0.75rem;font-size:1rem;">آخرین تاریخچه</h2>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:0.85rem;">
                    <thead>
                    <tr>
                        <th style="text-align:right;padding:0.35rem;">شناسه</th>
                        <th style="text-align:right;padding:0.35rem;">کارت کار</th>
                        <th style="text-align:right;padding:0.35rem;">کد رویداد</th>
                        <th style="text-align:right;padding:0.35rem;">زمان</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentHistory['rows'] as $row): ?>
                        <tr>
                            <td style="padding:0.35rem;"><?= wave2f_dash_h((string)($row['history_id'] ?? '')) ?></td>
                            <td style="padding:0.35rem;"><?= wave2f_dash_h((string)($row['jobcard_id'] ?? '')) ?></td>
                            <td style="padding:0.35rem;"><?= wave2f_dash_h((string)($row['event_code'] ?? '')) ?></td>
                            <td style="padding:0.35rem;"><?= wave2f_dash_h((string)($row['event_at'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <nav class="w1c-card w1c-links w2f-nav">
        <a href="erp-jobcard-camera-capture.php">ثبت تصویر دوربین</a>
        <a href="erp-jobcard-media-preview.php?jobcard_id=1">پیش‌نمایش رسانه</a>
        <a href="erp-jobcard-diagnostic-file.php">ثبت فایل تشخیصی</a>
        <a href="erp-jobcard-diagnostic-preview.php?jobcard_id=1">پیش‌نمایش تشخیصی</a>
        <a href="erp-jobcard-evidence-review.php?jobcard_id=1">بازبینی تکمیل مدارک</a>
        <a href="erp-jobcard-evidence-timeline.php?jobcard_id=1">خط زمانی مدارک</a>
    </nav>
</div>
</body>
</html>

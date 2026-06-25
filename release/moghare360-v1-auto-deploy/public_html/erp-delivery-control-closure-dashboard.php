<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Delivery Control Operational Closure Dashboard (Wave 4D)
 * Read-only · no DB write · no upload
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-wave-4-delivery-control-closure-helper.php';

function wave4d_dash_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$closure = moghare360_wave_4_closure_status();
$summary = (array)($closure['summary'] ?? []);
$recentClearances = moghare360_wave_4_closure_fetch_recent_clearances(10);
$recentHistory = moghare360_wave_4_closure_fetch_recent_history(10);
$sampleJobcardId = (int)($summary['sample_jobcard_id'] ?? 1);

$statusClass = match ($closure['status'] ?? '') {
    MOGHARE360_WAVE4_CLOSURE_STATUS_READY => 'w4d-status-ready',
    MOGHARE360_WAVE4_CLOSURE_STATUS_PARTIAL => 'w4d-status-partial',
    MOGHARE360_WAVE4_CLOSURE_STATUS_EMPTY => 'w4d-status-empty',
    default => 'w4d-status-error',
};

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>داشبورد بستن کنترل تحویل WAVE 4</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w4d-wrap">
    <header class="w1c-banner w4d-banner">
        <h1>داشبورد بستن عملیاتی کنترل تحویل</h1>
        <p>WAVE 4D — Delivery Control Operational Closure</p>
    </header>

    <section class="w1c-card w4d-warning">
        <strong>This is read-only operational review — not final vehicle delivery. not legal final e-signature.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">تحویل نهایی خودرو انجام نمی‌شود. رکورد تکمیل تحویل ایجاد نمی‌شود. پورتال عمومی و پرداخت فعال نیست.</p>
    </section>

    <section class="w1c-card <?= wave4d_dash_h($statusClass) ?>">
        <h2 style="margin:0 0 0.5rem;font-size:1rem;">وضعیت بستن WAVE 4</h2>
        <p style="margin:0;">
            <strong><?= wave4d_dash_h(moghare360_wave_4_closure_status_label((string)($closure['status'] ?? ''))) ?></strong>
            (<?= wave4d_dash_h((string)($closure['status'] ?? '')) ?>)
        </p>
        <p style="margin:0.5rem 0 0;"><?= wave4d_dash_h((string)($closure['message'] ?? '')) ?></p>
    </section>

    <?php if (!empty($closure['errors'])): ?>
        <section class="w1c-card w1c-error-box">
            <ul style="margin:0;padding-right:1.25rem;">
                <?php foreach ($closure['errors'] as $error): ?>
                    <li><?= wave4d_dash_h((string)$error) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <?php if ($summary !== []): ?>
        <section class="w1c-card">
            <h2 style="margin:0 0 0.75rem;font-size:1rem;">خلاصه شمارش‌ها</h2>
            <div class="w4d-stat-grid">
                <div class="w4d-stat"><span>کل Clearance</span><strong><?= wave4d_dash_h((string)($summary['total_clearances'] ?? 0)) ?></strong></div>
                <div class="w4d-stat"><span>کل تاریخچه</span><strong><?= wave4d_dash_h((string)($summary['total_history'] ?? 0)) ?></strong></div>
                <div class="w4d-stat"><span>cleared</span><strong><?= wave4d_dash_h((string)($summary['cleared_count'] ?? 0)) ?></strong></div>
                <div class="w4d-stat"><span>not_cleared</span><strong><?= wave4d_dash_h((string)($summary['not_cleared_count'] ?? 0)) ?></strong></div>
                <div class="w4d-stat"><span>cancelled</span><strong><?= wave4d_dash_h((string)($summary['cancelled_count'] ?? 0)) ?></strong></div>
            </div>
            <p style="margin:0.75rem 0 0;font-size:0.85rem;color:#525252;">
                آخرین Clearance (ایجاد): <?= wave4d_dash_h((string)($summary['latest_clearance_created_at'] ?? '—')) ?>
                — آخرین به‌روزرسانی: <?= wave4d_dash_h((string)($summary['latest_clearance_updated_at'] ?? '—')) ?>
                — آخرین تاریخچه: <?= wave4d_dash_h((string)($summary['latest_clearance_history_event_at'] ?? '—')) ?>
            </p>
        </section>

        <section class="w1c-card">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">نمونه کارت کار <?= wave4d_dash_h((string)$sampleJobcardId) ?></h2>
            <dl class="w4d-detail-dl">
                <dt>آمادگی نهایی WAVE 4A</dt>
                <dd><strong><?= wave4d_dash_h((string)($summary['sample_final_readiness_status'] ?? '—')) ?></strong></dd>
                <dt>صلاحیت تحویل WAVE 4B</dt>
                <dd><strong><?= wave4d_dash_h((string)($summary['sample_delivery_eligibility_status'] ?? '—')) ?></strong></dd>
            </dl>
        </section>

        <?php foreach (['by_clearance_status' => 'بر اساس وضعیت Clearance', 'by_clearance_decision' => 'بر اساس تصمیم Clearance'] as $key => $title): ?>
            <?php if (!empty($summary[$key]) && is_array($summary[$key])): ?>
                <section class="w1c-card">
                    <h2 style="margin:0 0 0.5rem;font-size:1rem;"><?= wave4d_dash_h($title) ?></h2>
                    <ul style="margin:0;padding-right:1.25rem;font-size:0.9rem;">
                        <?php foreach ($summary[$key] as $grp => $cnt): ?>
                            <li><?= wave4d_dash_h((string)$grp) ?>: <strong><?= wave4d_dash_h((string)$cnt) ?></strong></li>
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
                <li><?= wave4d_dash_h((string)$checkKey) ?>: <?= $checkVal ? '✅' : '❌' ?></li>
            <?php endforeach; ?>
        </ul>
    </section>

    <?php if (($recentClearances['ok'] ?? false) && !empty($recentClearances['rows'])): ?>
        <section class="w1c-card">
            <h2 style="margin:0 0 0.75rem;font-size:1rem;">آخرین رکوردهای Clearance</h2>
            <div style="overflow-x:auto;">
                <table class="w4d-table">
                    <thead>
                    <tr>
                        <th>شناسه</th>
                        <th>کارت کار</th>
                        <th>وضعیت</th>
                        <th>تصمیم</th>
                        <th>بازبین</th>
                        <th>زمان</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentClearances['rows'] as $row): ?>
                        <tr>
                            <td><?= wave4d_dash_h((string)($row['clearance_id'] ?? '')) ?></td>
                            <td><?= wave4d_dash_h((string)($row['jobcard_id'] ?? '')) ?></td>
                            <td><?= wave4d_dash_h((string)($row['clearance_status'] ?? '')) ?></td>
                            <td><?= wave4d_dash_h((string)($row['clearance_decision'] ?? '')) ?></td>
                            <td><?= wave4d_dash_h((string)($row['reviewer_name'] ?? '')) ?></td>
                            <td><?= wave4d_dash_h((string)($row['created_at'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <?php if (($recentHistory['ok'] ?? false) && !empty($recentHistory['rows'])): ?>
        <section class="w1c-card">
            <h2 style="margin:0 0 0.75rem;font-size:1rem;">آخرین تاریخچه Clearance</h2>
            <div style="overflow-x:auto;">
                <table class="w4d-table">
                    <thead>
                    <tr>
                        <th>شناسه</th>
                        <th>کارت کار</th>
                        <th>کد رویداد</th>
                        <th>وضعیت</th>
                        <th>زمان</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentHistory['rows'] as $row): ?>
                        <tr>
                            <td><?= wave4d_dash_h((string)($row['history_id'] ?? '')) ?></td>
                            <td><?= wave4d_dash_h((string)($row['jobcard_id'] ?? '')) ?></td>
                            <td><?= wave4d_dash_h((string)($row['event_code'] ?? '')) ?></td>
                            <td><?= wave4d_dash_h((string)($row['new_status'] ?? '')) ?></td>
                            <td><?= wave4d_dash_h((string)($row['event_at'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <nav class="w1c-card w1c-links w4d-nav">
        <a href="erp-jobcard-final-readiness.php?jobcard_id=1">آمادگی نهایی</a>
        <a href="erp-jobcard-delivery-eligibility.php?jobcard_id=1">صلاحیت تحویل</a>
        <a href="erp-jobcard-delivery-clearance.php?jobcard_id=1">ثبت Clearance داخلی</a>
        <a href="erp-jobcard-delivery-clearance-preview.php?jobcard_id=1">سوابق Clearance</a>
        <a href="erp-media-evidence-closure-dashboard.php">داشبورد بستن WAVE 2</a>
        <a href="erp-authorization-closure-dashboard.php">داشبورد بستن WAVE 3</a>
    </nav>
</div>
</body>
</html>

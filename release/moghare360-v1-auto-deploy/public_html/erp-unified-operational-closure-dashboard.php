<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Unified Operational Closure Dashboard (Wave 5C)
 * Read-only · no DB write · no POST
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-wave-5-unified-closure-helper.php';

function wave5c_dash_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$closure = moghare360_wave_5_closure_status();
$summary = (array)($closure['summary'] ?? []);
$recentResult = moghare360_wave_5_closure_fetch_recent_jobcards(10);
$recentJobcards = (array)($recentResult['jobcards'] ?? []);
$sampleJobcardId = (int)($summary['sample_jobcard_id'] ?? 1);

$statusClass = match ($closure['status'] ?? '') {
    MOGHARE360_WAVE5_CLOSURE_STATUS_READY => 'w5c-status-ready',
    MOGHARE360_WAVE5_CLOSURE_STATUS_PARTIAL => 'w5c-status-partial',
    MOGHARE360_WAVE5_CLOSURE_STATUS_EMPTY => 'w5c-status-empty',
    default => 'w5c-status-error',
};

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>داشبورد بستن عملیاتی یکپارچه WAVE 5</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w5c-wrap">
    <header class="w1c-banner w5c-banner">
        <h1>داشبورد بستن عملیاتی یکپارچه کارت کار</h1>
        <p>WAVE 5C — Unified Operational Closure Dashboard</p>
    </header>

    <section class="w1c-card w5c-warning">
        <strong>This is read-only operational review — not final vehicle delivery. not legal final e-signature.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">تحویل نهایی خودرو انجام نمی‌شود. رکورد تکمیل تحویل ایجاد نمی‌شود. پورتال عمومی و پرداخت فعال نیست.</p>
    </section>

    <section class="w1c-card <?= wave5c_dash_h($statusClass) ?>">
        <h2 style="margin:0 0 0.5rem;font-size:1rem;">وضعیت بستن WAVE 5</h2>
        <p style="margin:0;">
            <strong><?= wave5c_dash_h(moghare360_wave_5_closure_status_label((string)($closure['status'] ?? ''))) ?></strong>
            (<?= wave5c_dash_h((string)($closure['status'] ?? '')) ?>)
        </p>
        <p style="margin:0.5rem 0 0;"><?= wave5c_dash_h((string)($closure['message'] ?? '')) ?></p>
    </section>

    <?php if (!empty($closure['errors'])): ?>
        <section class="w1c-card w1c-error-box">
            <ul style="margin:0;padding-right:1.25rem;">
                <?php foreach ($closure['errors'] as $error): ?>
                    <li><?= wave5c_dash_h((string)$error) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <?php if ($summary !== []): ?>
        <section class="w1c-card">
            <h2 style="margin:0 0 0.75rem;font-size:1rem;">خلاصه کارت کارها</h2>
            <div class="w5c-stat-grid">
                <div class="w5c-stat"><span>کل در پایگاه</span><strong><?= wave5c_dash_h((string)($summary['total_in_db'] ?? 0)) ?></strong></div>
                <div class="w5c-stat"><span>فهرست‌شده</span><strong><?= wave5c_dash_h((string)($summary['total_listed'] ?? 0)) ?></strong></div>
                <div class="w5c-stat"><span>مرکز فرمان WAVE 5A</span><strong><?= !empty($summary['command_center_helper_ok']) ? 'فعال' : 'غیرفعال' ?></strong></div>
                <div class="w5c-stat"><span>میز فرمان WAVE 5B</span><strong><?= !empty($summary['workbench_helper_ok']) ? 'فعال' : 'غیرفعال' ?></strong></div>
            </div>
            <p style="margin:0.75rem 0 0;font-size:0.85rem;color:#525252;">
                آخرین ایجاد: <?= wave5c_dash_h((string)($summary['latest_jobcard_created_at'] ?? '—')) ?>
                — آخرین به‌روزرسانی: <?= wave5c_dash_h((string)($summary['latest_jobcard_updated_at'] ?? '—')) ?>
            </p>
        </section>

        <section class="w1c-card">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">نمونه وضعیت فرمان یکپارچه — کارت کار <?= wave5c_dash_h((string)$sampleJobcardId) ?></h2>
            <dl class="w5c-detail-dl">
                <dt>وضعیت یکپارچه</dt>
                <dd>
                    <strong><?= wave5c_dash_h((string)($summary['sample_unified_status_label'] ?? '—')) ?></strong>
                    <?php if (!empty($summary['sample_unified_status'])): ?>
                        (<?= wave5c_dash_h((string)$summary['sample_unified_status']) ?>)
                    <?php endif; ?>
                </dd>
                <dt>پیام</dt>
                <dd><?= wave5c_dash_h((string)($summary['sample_unified_message'] ?? '—')) ?></dd>
            </dl>
        </section>

        <?php foreach ([
            'by_jobcard_status' => 'بر اساس jobcard_status',
            'by_lifecycle_state' => 'بر اساس lifecycle_state',
            'by_unified_status' => 'بر اساس وضعیت عملیاتی یکپارچه',
        ] as $key => $title): ?>
            <?php if (!empty($summary[$key]) && is_array($summary[$key])): ?>
                <section class="w1c-card">
                    <h2 style="margin:0 0 0.5rem;font-size:1rem;"><?= wave5c_dash_h($title) ?></h2>
                    <ul style="margin:0;padding-right:1.25rem;font-size:0.9rem;">
                        <?php foreach ($summary[$key] as $grp => $cnt): ?>
                            <li>
                                <?= wave5c_dash_h(moghare360_wave_5_closure_status_label((string)$grp)) ?>
                                (<?= wave5c_dash_h((string)$grp) ?>):
                                <strong><?= wave5c_dash_h((string)$cnt) ?></strong>
                            </li>
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
                <li><?= wave5c_dash_h((string)$checkKey) ?>: <?= $checkVal ? '✅' : '❌' ?></li>
            <?php endforeach; ?>
        </ul>
    </section>

    <?php if (($recentResult['ok'] ?? false) && $recentJobcards !== []): ?>
        <section class="w1c-card">
            <h2 style="margin:0 0 0.75rem;font-size:1rem;">آخرین کارت کارها</h2>
            <div style="overflow-x:auto;">
                <table class="w5c-table">
                    <thead>
                    <tr>
                        <th>شناسه</th>
                        <th>شماره</th>
                        <th>وضعیت</th>
                        <th>چرخه</th>
                        <th>وضعیت یکپارچه</th>
                        <th>ایجاد</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentJobcards as $row): ?>
                        <tr>
                            <td><?= wave5c_dash_h((string)($row['jobcard_id'] ?? '')) ?></td>
                            <td><?= wave5c_dash_h((string)($row['jobcard_number'] ?? '')) ?></td>
                            <td><?= wave5c_dash_h((string)($row['jobcard_status'] ?? '')) ?></td>
                            <td><?= wave5c_dash_h((string)($row['lifecycle_state'] ?? '')) ?></td>
                            <td>
                                <?= wave5c_dash_h(moghare360_wave_5_closure_status_label((string)($row['unified_status'] ?? ''))) ?>
                                <?php if (!empty($row['unified_status'])): ?>
                                    <span style="font-size:0.8rem;color:#525252;">(<?= wave5c_dash_h((string)$row['unified_status']) ?>)</span>
                                <?php endif; ?>
                            </td>
                            <td><?= wave5c_dash_h((string)($row['created_at'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php elseif (($summary['total_in_db'] ?? 0) === 0): ?>
        <section class="w1c-card w1c-note">
            <p style="margin:0;">هیچ کارت کاری در erp_jobcards یافت نشد.</p>
        </section>
    <?php endif; ?>

    <nav class="w1c-card w1c-links w5c-nav">
        <a href="erp-jobcard-command-workbench.php">میز فرمان کارت کار</a>
        <a href="erp-jobcard-command-center.php?jobcard_id=1">مرکز فرمان (نمونه)</a>
        <a href="erp-jobcard-final-readiness.php?jobcard_id=1">آمادگی نهایی (نمونه)</a>
        <a href="erp-jobcard-delivery-eligibility.php?jobcard_id=1">صلاحیت تحویل (نمونه)</a>
        <a href="erp-jobcard-delivery-clearance-preview.php?jobcard_id=1">سوابق Clearance (نمونه)</a>
        <a href="erp-media-evidence-closure-dashboard.php">داشبورد بستن WAVE 2</a>
        <a href="erp-authorization-closure-dashboard.php">داشبورد بستن WAVE 3</a>
        <a href="erp-delivery-control-closure-dashboard.php">داشبورد بستن WAVE 4</a>
    </nav>
</div>
</body>
</html>

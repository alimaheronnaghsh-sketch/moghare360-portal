<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Authorization Operational Closure Dashboard (Wave 3D)
 * Read-only · no DB write · no upload
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-wave-3-authorization-closure-helper.php';

function wave3d_dash_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$closure = moghare360_wave_3_closure_status();
$summary = (array)($closure['summary'] ?? []);
$recentAuth = moghare360_wave_3_closure_fetch_recent_authorizations(10);
$recentHistory = moghare360_wave_3_closure_fetch_recent_history(10);

$statusClass = match ($closure['status'] ?? '') {
    MOGHARE360_WAVE3_CLOSURE_STATUS_READY => 'w3d-status-ready',
    MOGHARE360_WAVE3_CLOSURE_STATUS_PARTIAL => 'w3d-status-partial',
    MOGHARE360_WAVE3_CLOSURE_STATUS_EMPTY => 'w3d-status-empty',
    default => 'w3d-status-error',
};

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>داشبورد بستن عملیاتی WAVE 3</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w3d-wrap">
    <header class="w1c-banner w3d-banner">
        <h1>داشبورد بستن عملیاتی مجوز و قرارداد</h1>
        <p>WAVE 3D — Authorization Operational Closure</p>
    </header>

    <section class="w1c-card w3d-warning">
        <strong>This is internal controlled authorization operational review, not final legal e-signature.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">پورتال عمومی مشتری و پرداخت فعال نیست — فقط خواندن DB.</p>
    </section>

    <section class="w1c-card <?= wave3d_dash_h($statusClass) ?>">
        <h2 style="margin:0 0 0.5rem;font-size:1rem;">وضعیت بستن WAVE 3</h2>
        <p style="margin:0;">
            <strong><?= wave3d_dash_h(moghare360_wave_3_closure_status_label((string)($closure['status'] ?? ''))) ?></strong>
            (<?= wave3d_dash_h((string)($closure['status'] ?? '')) ?>)
        </p>
        <p style="margin:0.5rem 0 0;"><?= wave3d_dash_h((string)($closure['message'] ?? '')) ?></p>
    </section>

    <?php if (!empty($closure['errors'])): ?>
        <section class="w1c-card w1c-error-box">
            <ul style="margin:0;padding-right:1.25rem;">
                <?php foreach ($closure['errors'] as $error): ?>
                    <li><?= wave3d_dash_h((string)$error) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <?php if ($summary !== []): ?>
        <section class="w1c-card">
            <h2 style="margin:0 0 0.75rem;font-size:1rem;">خلاصه شمارش‌ها</h2>
            <div class="w3d-stat-grid">
                <div class="w3d-stat"><span>کل مجوزها</span><strong><?= wave3d_dash_h((string)($summary['total_authorizations'] ?? 0)) ?></strong></div>
                <div class="w3d-stat"><span>تاریخچه</span><strong><?= wave3d_dash_h((string)($summary['total_history'] ?? 0)) ?></strong></div>
                <div class="w3d-stat"><span>تأیید شده</span><strong><?= wave3d_dash_h((string)($summary['approved_count'] ?? 0)) ?></strong></div>
                <div class="w3d-stat"><span>در انتظار</span><strong><?= wave3d_dash_h((string)($summary['pending_count'] ?? 0)) ?></strong></div>
                <div class="w3d-stat"><span>رد شده</span><strong><?= wave3d_dash_h((string)($summary['rejected_count'] ?? 0)) ?></strong></div>
                <div class="w3d-stat"><span>لغو شده</span><strong><?= wave3d_dash_h((string)($summary['cancelled_count'] ?? 0)) ?></strong></div>
            </div>
            <p style="margin:0.75rem 0 0;font-size:0.85rem;color:#525252;">
                آخرین مجوز: <?= wave3d_dash_h((string)($summary['latest_authorization_created_at'] ?? '—')) ?>
                — آخرین به‌روزرسانی: <?= wave3d_dash_h((string)($summary['latest_authorization_updated_at'] ?? '—')) ?>
                — آخرین تاریخچه: <?= wave3d_dash_h((string)($summary['latest_history_event_at'] ?? '—')) ?>
            </p>
        </section>

        <?php foreach (['by_authorization_status' => 'بر اساس وضعیت', 'by_authorization_type' => 'بر اساس نوع', 'by_authorization_method' => 'بر اساس روش'] as $key => $title): ?>
            <?php if (!empty($summary[$key]) && is_array($summary[$key])): ?>
                <section class="w1c-card">
                    <h2 style="margin:0 0 0.5rem;font-size:1rem;"><?= wave3d_dash_h($title) ?></h2>
                    <ul style="margin:0;padding-right:1.25rem;font-size:0.9rem;">
                        <?php foreach ($summary[$key] as $grp => $cnt): ?>
                            <li><?= wave3d_dash_h((string)$grp) ?>: <strong><?= wave3d_dash_h((string)$cnt) ?></strong></li>
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
                <li><?= wave3d_dash_h((string)$checkKey) ?>: <?= $checkVal ? '✅' : '❌' ?></li>
            <?php endforeach; ?>
        </ul>
    </section>

    <?php if (($recentAuth['ok'] ?? false) && !empty($recentAuth['rows'])): ?>
        <section class="w1c-card">
            <h2 style="margin:0 0 0.75rem;font-size:1rem;">آخرین مجوزها</h2>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:0.85rem;">
                    <thead>
                    <tr>
                        <th style="text-align:right;padding:0.35rem;">شناسه</th>
                        <th style="text-align:right;padding:0.35rem;">کارت کار</th>
                        <th style="text-align:right;padding:0.35rem;">نوع</th>
                        <th style="text-align:right;padding:0.35rem;">وضعیت</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentAuth['rows'] as $row): ?>
                        <tr>
                            <td style="padding:0.35rem;"><?= wave3d_dash_h((string)($row['authorization_id'] ?? '')) ?></td>
                            <td style="padding:0.35rem;"><?= wave3d_dash_h((string)($row['jobcard_id'] ?? '')) ?></td>
                            <td style="padding:0.35rem;"><?= wave3d_dash_h((string)($row['authorization_type'] ?? '')) ?></td>
                            <td style="padding:0.35rem;"><?= wave3d_dash_h((string)($row['authorization_status'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <?php if (($recentHistory['ok'] ?? false) && !empty($recentHistory['rows'])): ?>
        <section class="w1c-card">
            <h2 style="margin:0 0 0.75rem;font-size:1rem;">آخرین تاریخچه گردش کار</h2>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:0.85rem;">
                    <thead>
                    <tr>
                        <th style="text-align:right;padding:0.35rem;">شناسه</th>
                        <th style="text-align:right;padding:0.35rem;">کد رویداد</th>
                        <th style="text-align:right;padding:0.35rem;">از</th>
                        <th style="text-align:right;padding:0.35rem;">به</th>
                        <th style="text-align:right;padding:0.35rem;">زمان</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentHistory['rows'] as $row): ?>
                        <tr>
                            <td style="padding:0.35rem;"><?= wave3d_dash_h((string)($row['history_id'] ?? '')) ?></td>
                            <td style="padding:0.35rem;"><?= wave3d_dash_h((string)($row['event_code'] ?? '')) ?></td>
                            <td style="padding:0.35rem;"><?= wave3d_dash_h((string)($row['old_status'] ?? '')) ?></td>
                            <td style="padding:0.35rem;"><?= wave3d_dash_h((string)($row['new_status'] ?? '')) ?></td>
                            <td style="padding:0.35rem;"><?= wave3d_dash_h((string)($row['event_at'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <nav class="w1c-card w1c-links w3d-nav">
        <a href="erp-jobcard-contract-authorization.php">ثبت مجوز/قرارداد</a>
        <a href="erp-jobcard-contract-authorization-preview.php?jobcard_id=1">پیش‌نمایش مجوزها</a>
        <a href="erp-jobcard-contract-authorization-workflow.php?authorization_id=1">گردش کار مجوز</a>
        <a href="erp-jobcard-authorization-gate.php?jobcard_id=1">گیت مجوز کارت کار</a>
        <a href="erp-media-evidence-closure-dashboard.php">داشبورد بستن WAVE 2</a>
    </nav>
</div>
</body>
</html>

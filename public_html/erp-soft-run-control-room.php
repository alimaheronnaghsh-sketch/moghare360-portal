<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Soft Run Control Room (Wave 6A)
 * Read-only · no DB write · no POST
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-control-room-helper.php';

function wave6a_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$evaluation = moghare360_soft_run_control_room_evaluate();
$runtimeSummary = (array)($evaluation['runtime_summary'] ?? []);

$statusClass = match ($evaluation['status'] ?? '') {
    MOGHARE360_SOFT_RUN_STATUS_READY => 'w6a-status-ready',
    MOGHARE360_SOFT_RUN_STATUS_REVIEW_REQUIRED => 'w6a-status-review',
    MOGHARE360_SOFT_RUN_STATUS_BLOCKED => 'w6a-status-blocked',
    MOGHARE360_SOFT_RUN_STATUS_EMPTY => 'w6a-status-empty',
    default => 'w6a-status-error',
};

$wavePanels = [
    ['key' => 'wave_2', 'title' => 'WAVE 2 — مدارک و رسانه', 'dashboard' => 'erp-media-evidence-closure-dashboard.php'],
    ['key' => 'wave_3', 'title' => 'WAVE 3 — مجوز قرارداد', 'dashboard' => 'erp-authorization-closure-dashboard.php'],
    ['key' => 'wave_4', 'title' => 'WAVE 4 — کنترل تحویل', 'dashboard' => 'erp-delivery-control-closure-dashboard.php'],
    ['key' => 'wave_5', 'title' => 'WAVE 5 — عملیات یکپارچه', 'dashboard' => 'erp-unified-operational-closure-dashboard.php'],
];

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>اتاق کنترل Soft Run</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w6a-wrap">
    <header class="w1c-banner w6a-banner">
        <h1>اتاق کنترل Soft Run</h1>
        <p>WAVE 6A — Soft Run Control Room Foundation</p>
    </header>

    <section class="w1c-card w6a-warning">
        <strong>This is read-only internal Soft Run control — not final vehicle delivery. not legal final e-signature.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">تحویل نهایی خودرو انجام نمی‌شود. رکورد تکمیل تحویل ایجاد نمی‌شود. پورتال عمومی، ورود تولید و پرداخت فعال نیست.</p>
    </section>

    <section class="w1c-card <?= wave6a_h($statusClass) ?>">
        <h2 style="margin:0 0 0.5rem;font-size:1rem;">وضعیت Soft Run</h2>
        <p style="margin:0;">
            <strong><?= wave6a_h(moghare360_soft_run_control_room_status_label((string)($evaluation['status'] ?? ''))) ?></strong>
            (<?= wave6a_h((string)($evaluation['status'] ?? '')) ?>)
        </p>
        <p style="margin:0.5rem 0 0;"><?= wave6a_h((string)($evaluation['message'] ?? '')) ?></p>
    </section>

    <?php if (!empty($evaluation['errors'])): ?>
        <section class="w1c-card w1c-error-box">
            <ul style="margin:0;padding-right:1.25rem;">
                <?php foreach ($evaluation['errors'] as $error): ?>
                    <li><?= wave6a_h((string)$error) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <section class="w1c-card">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">خلاصه زمان اجرا</h2>
        <div class="w6a-stat-grid">
            <div class="w6a-stat"><span>کل کارت کارها</span><strong><?= wave6a_h((string)($runtimeSummary['total_jobcards'] ?? 0)) ?></strong></div>
            <div class="w6a-stat"><span>رسانه WAVE 2</span><strong><?= wave6a_h((string)($runtimeSummary['wave_2_media'] ?? 0)) ?></strong></div>
            <div class="w6a-stat"><span>مجوز WAVE 3</span><strong><?= wave6a_h((string)($runtimeSummary['wave_3_authorizations'] ?? 0)) ?></strong></div>
            <div class="w6a-stat"><span>Clearance WAVE 4</span><strong><?= wave6a_h((string)($runtimeSummary['wave_4_clearances'] ?? 0)) ?></strong></div>
            <div class="w6a-stat"><span>فهرست WAVE 5</span><strong><?= wave6a_h((string)($runtimeSummary['wave_5_listed'] ?? 0)) ?></strong></div>
        </div>
        <?php if (!empty($runtimeSummary['closure_helpers']) && is_array($runtimeSummary['closure_helpers'])): ?>
            <p style="margin:0.75rem 0 0;font-size:0.85rem;color:#525252;">
                Helpers بستن:
                <?php foreach ($runtimeSummary['closure_helpers'] as $hk => $hv): ?>
                    <?= wave6a_h((string)$hk) ?>=<?= $hv ? '✅' : '❌' ?>
                    <?php if ($hk !== 'wave_5'): ?> · <?php endif; ?>
                <?php endforeach; ?>
            </p>
        <?php endif; ?>
    </section>

    <?php foreach ($wavePanels as $panel): ?>
        <?php $wave = (array)($evaluation[$panel['key']] ?? []); ?>
        <section class="w1c-card">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;"><?= wave6a_h($panel['title']) ?></h2>
            <p style="margin:0;">
                <strong><?= wave6a_h((string)($wave['label'] ?? '—')) ?></strong>
                (<?= wave6a_h((string)($wave['status'] ?? '')) ?>)
            </p>
            <p style="margin:0.35rem 0 0;font-size:0.88rem;"><?= wave6a_h((string)($wave['message'] ?? '')) ?></p>
            <p style="margin:0.5rem 0 0;">
                <a href="<?= wave6a_h($panel['dashboard']) ?>">داشبورد بستن <?= wave6a_h($panel['key']) ?></a>
            </p>
        </section>
    <?php endforeach; ?>

    <?php foreach ([
        'ready_items' => 'آماده',
        'review_items' => 'نیازمند بازبینی',
        'blocked_items' => 'مسدود',
        'missing_items' => 'مفقود / خالی',
    ] as $listKey => $listTitle): ?>
        <?php if (!empty($evaluation[$listKey]) && is_array($evaluation[$listKey])): ?>
            <section class="w1c-card">
                <h2 style="margin:0 0 0.5rem;font-size:1rem;"><?= wave6a_h($listTitle) ?></h2>
                <ul style="margin:0;padding-right:1.25rem;font-size:0.9rem;">
                    <?php foreach ($evaluation[$listKey] as $item): ?>
                        <li><?= wave6a_h((string)$item) ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>
    <?php endforeach; ?>

    <section class="w1c-card w1c-note">
        <h2 style="margin:0 0 0.5rem;font-size:1rem;">یادداشت‌های عملیاتی</h2>
        <ul style="margin:0;padding-right:1.25rem;font-size:0.88rem;">
            <li>این صفحه فقط بازبینی داخلی Soft Run است — بدون نوشتن پایگاه داده.</li>
            <li>وضعیت‌ها از داشبوردهای بستن WAVE 2، 3، 4 و 5 خوانده می‌شوند.</li>
            <li>تحویل نهایی خودرو، تکمیل تحویل، پورتال عمومی، پرداخت و امضای قانونی نهایی فعال نیست.</li>
            <li>ورود تولید (production login) فعال نشده است.</li>
        </ul>
    </section>

    <nav class="w1c-card w1c-links w6a-nav">
        <a href="erp-media-evidence-closure-dashboard.php">داشبورد بستن WAVE 2</a>
        <a href="erp-authorization-closure-dashboard.php">داشبورد بستن WAVE 3</a>
        <a href="erp-delivery-control-closure-dashboard.php">داشبورد بستن WAVE 4</a>
        <a href="erp-unified-operational-closure-dashboard.php">داشبورد بستن WAVE 5</a>
        <a href="erp-jobcard-command-workbench.php">میز فرمان کارت کار</a>
        <a href="erp-jobcard-command-center.php?jobcard_id=1">مرکز فرمان (نمونه)</a>
    </nav>
</div>
</body>
</html>

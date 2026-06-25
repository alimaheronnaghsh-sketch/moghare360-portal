<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Soft Run Operator Test Pack (Wave 6C)
 * Read-only · no DB write · no POST
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-operator-test-pack-helper.php';

function wave6c_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function wave6c_page_href(string $path): string
{
    if ($path === 'erp-jobcard-command-center.php') {
        return $path . '?jobcard_id=1';
    }
    if (in_array($path, [
        'erp-jobcard-final-readiness.php',
        'erp-jobcard-delivery-eligibility.php',
        'erp-jobcard-delivery-clearance-preview.php',
    ], true)) {
        return $path . '?jobcard_id=1';
    }

    return $path;
}

$evaluation = moghare360_soft_run_operator_test_pack_evaluate();
$scenarioBoard = (array)($evaluation['scenario_board'] ?? []);
$steps = (array)($evaluation['steps'] ?? []);
$expectedEvidence = (array)($evaluation['expected_evidence'] ?? []);
$pageInventory = (array)($evaluation['pages'] ?? []);
$pages = (array)($pageInventory['pages'] ?? []);

$statusClass = match ($evaluation['status'] ?? '') {
    MOGHARE360_SOFT_RUN_TEST_PACK_STATUS_READY => 'w6c-status-ready',
    MOGHARE360_SOFT_RUN_TEST_PACK_STATUS_REVIEW_REQUIRED => 'w6c-status-review',
    MOGHARE360_SOFT_RUN_TEST_PACK_STATUS_BLOCKED => 'w6c-status-blocked',
    MOGHARE360_SOFT_RUN_TEST_PACK_STATUS_EMPTY => 'w6c-status-empty',
    default => 'w6c-status-error',
};

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>بسته تست اپراتوری اجرای آزمایشی Soft Run</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w6c-wrap">
    <header class="w1c-banner w6c-banner">
        <h1>بسته تست اپراتوری اجرای آزمایشی Soft Run</h1>
        <p>WAVE 6C — Soft Run Operator Test Pack & Execution Evidence Board</p>
    </header>

    <section class="w1c-card w6c-warning">
        <strong>This is read-only internal Soft Run operator test — not final vehicle delivery. not legal final e-signature.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">تحویل نهایی خودرو انجام نمی‌شود. رکورد تکمیل تحویل ایجاد نمی‌شود. پورتال عمومی، ورود تولید و پرداخت فعال نیست.</p>
    </section>

    <section class="w1c-card <?= wave6c_h($statusClass) ?>">
        <h2 style="margin:0 0 0.5rem;font-size:1rem;">وضعیت بسته تست اپراتوری</h2>
        <p style="margin:0;">
            <strong><?= wave6c_h(moghare360_soft_run_operator_test_pack_status_label((string)($evaluation['status'] ?? ''))) ?></strong>
            (<?= wave6c_h((string)($evaluation['status'] ?? '')) ?>)
        </p>
        <p style="margin:0.5rem 0 0;"><?= wave6c_h((string)($evaluation['message'] ?? '')) ?></p>
    </section>

    <section class="w1c-card">
        <h2 style="margin:0 0 0.5rem;font-size:1rem;">وضعیت برد سناریو WAVE 6B</h2>
        <p style="margin:0;">
            <strong><?= wave6c_h((string)($scenarioBoard['label'] ?? '—')) ?></strong>
            (<?= wave6c_h((string)($scenarioBoard['status'] ?? '')) ?>)
        </p>
        <p style="margin:0.35rem 0 0;font-size:0.88rem;"><?= wave6c_h((string)($scenarioBoard['message'] ?? '')) ?></p>
        <?php if (!empty($scenarioBoard['control_room_status'])): ?>
            <p style="margin:0.35rem 0 0;font-size:0.85rem;color:#525252;">
                اتاق کنترل: <?= wave6c_h((string)$scenarioBoard['control_room_status']) ?>
            </p>
        <?php endif; ?>
        <p style="margin:0.5rem 0 0;">
            <a href="erp-soft-run-control-room.php">اتاق کنترل Soft Run</a>
            · <a href="erp-soft-run-scenario-board.php">برد سناریوهای اجرای آزمایشی</a>
        </p>
    </section>

    <?php if (!empty($evaluation['errors'])): ?>
        <section class="w1c-card w1c-error-box">
            <ul style="margin:0;padding-right:1.25rem;">
                <?php foreach ($evaluation['errors'] as $error): ?>
                    <li><?= wave6c_h((string)$error) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <section class="w1c-card">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">گام‌های تست اپراتوری</h2>
        <div style="overflow-x:auto;">
            <table class="w6c-table">
                <thead>
                <tr>
                    <th>گام</th>
                    <th>شرح</th>
                    <th>صفحه</th>
                    <th>نوع</th>
                    <th>وضعیت</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($steps as $step): ?>
                    <?php $relatedPage = (string)($step['related_page'] ?? ''); ?>
                    <tr>
                        <td><?= wave6c_h((string)($step['step'] ?? '')) ?></td>
                        <td><?= wave6c_h((string)($step['title_fa'] ?? '')) ?></td>
                        <td>
                            <?php if (!empty($step['page_exists'])): ?>
                                <a href="<?= wave6c_h(wave6c_page_href($relatedPage)) ?>"><?= wave6c_h($relatedPage) ?></a>
                            <?php else: ?>
                                <?= wave6c_h($relatedPage) ?>
                            <?php endif; ?>
                        </td>
                        <td><?= !empty($step['manual']) ? 'دستی' : 'خودکار' ?></td>
                        <td><?= wave6c_h((string)($step['status'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="w1c-card">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">شواهد زمان اجرای مورد انتظار</h2>
        <ul style="margin:0;padding-right:1.25rem;font-size:0.9rem;">
            <?php foreach ($expectedEvidence as $evidence): ?>
                <li>
                    <?= wave6c_h((string)($evidence['title_fa'] ?? '')) ?>
                    <span style="font-size:0.8rem;color:#525252;">
                        (<?= wave6c_h((string)($evidence['status'] ?? '')) ?>)
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="w1c-card">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">صفحات زمان اجرای مورد نیاز</h2>
        <p style="margin:0 0 0.5rem;font-size:0.85rem;color:#525252;">
            موجود: <strong><?= wave6c_h((string)($pageInventory['present'] ?? 0)) ?></strong>
            / <?= wave6c_h((string)($pageInventory['total'] ?? 0)) ?>
        </p>
        <div style="overflow-x:auto;">
            <table class="w6c-table">
                <thead>
                <tr>
                    <th>صفحه</th>
                    <th>برچسب</th>
                    <th>وضعیت</th>
                    <th>پیوند</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($pages as $pageRow): ?>
                    <?php $pagePath = (string)($pageRow['path'] ?? ''); ?>
                    <tr>
                        <td><?= wave6c_h($pagePath) ?></td>
                        <td><?= wave6c_h((string)($pageRow['label_fa'] ?? '')) ?></td>
                        <td><?= wave6c_h((string)($pageRow['status'] ?? '')) ?></td>
                        <td>
                            <?php if (!empty($pageRow['exists'])): ?>
                                <a href="<?= wave6c_h(wave6c_page_href($pagePath)) ?>">باز کردن</a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <?php foreach ([
        'ready_items' => 'آماده',
        'review_items' => 'نیازمند بازبینی / تأیید دستی',
        'blocked_items' => 'مسدود',
        'missing_items' => 'مفقود',
    ] as $listKey => $listTitle): ?>
        <?php if (!empty($evaluation[$listKey]) && is_array($evaluation[$listKey])): ?>
            <section class="w1c-card">
                <h2 style="margin:0 0 0.5rem;font-size:1rem;"><?= wave6c_h($listTitle) ?></h2>
                <ul style="margin:0;padding-right:1.25rem;font-size:0.9rem;">
                    <?php foreach ($evaluation[$listKey] as $item): ?>
                        <li><?= wave6c_h((string)$item) ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>
    <?php endforeach; ?>

    <section class="w1c-card w1c-note">
        <h2 style="margin:0 0 0.5rem;font-size:1rem;">یادداشت‌های تست دستی</h2>
        <ul style="margin:0;padding-right:1.25rem;font-size:0.88rem;">
            <li>گام‌های دستی باید توسط اپراتور در مرورگر اجرا و تأیید شوند.</li>
            <li>شواهد زمان اجرا شامل نمایش jobcard_number، پنل‌های مدارک/مجوز/آمادگی/صلاحیت/Clearance است.</li>
            <li>این بسته فقط برنامه‌ریزی و بازبینی داخلی است — بدون نوشتن پایگاه داده.</li>
            <li>تحویل نهایی، تکمیل تحویل، پورتال عمومی، پرداخت، حسابداری و ورود تولید فعال نیست.</li>
        </ul>
    </section>

    <nav class="w1c-card w1c-links w6c-nav">
        <a href="erp-soft-run-pilot-execution-create.php">ثبت اجرای پایلوت Soft Run</a>
        <a href="erp-soft-run-pilot-execution-board.php">برد اجرای پایلوت Soft Run</a>
        <a href="erp-soft-run-final-closure-dashboard.php">داشبورد نهایی آمادگی پایلوت</a>
        <a href="erp-soft-run-control-room.php">اتاق کنترل Soft Run</a>
        <a href="erp-soft-run-scenario-board.php">برد سناریوهای اجرای آزمایشی</a>
        <a href="erp-jobcard-command-workbench.php">میز فرمان کارت کار</a>
        <a href="erp-jobcard-command-center.php?jobcard_id=1">مرکز فرمان (نمونه)</a>
        <a href="erp-media-evidence-closure-dashboard.php">داشبورد بستن WAVE 2</a>
        <a href="erp-authorization-closure-dashboard.php">داشبورد بستن WAVE 3</a>
        <a href="erp-delivery-control-closure-dashboard.php">داشبورد بستن WAVE 4</a>
        <a href="erp-unified-operational-closure-dashboard.php">داشبورد بستن WAVE 5</a>
        <a href="erp-jobcard-final-readiness.php?jobcard_id=1">آمادگی نهایی (نمونه)</a>
        <a href="erp-jobcard-delivery-eligibility.php?jobcard_id=1">صلاحیت تحویل (نمونه)</a>
        <a href="erp-jobcard-delivery-clearance-preview.php?jobcard_id=1">پیش‌نمایش Clearance (نمونه)</a>
    </nav>
</div>
</body>
</html>

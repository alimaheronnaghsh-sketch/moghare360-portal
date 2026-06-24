<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Soft Run Final Closure Dashboard (Wave 6D)
 * Read-only · no DB write · no POST
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-final-closure-helper.php';

function wave6d_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function wave6d_page_href(string $path): string
{
    if ($path === 'erp-jobcard-command-center.php') {
        return $path . '?jobcard_id=1';
    }

    return $path;
}

$evaluation = moghare360_soft_run_final_closure_evaluate();
$controlRoom = (array)($evaluation['control_room'] ?? []);
$scenarioBoard = (array)($evaluation['scenario_board'] ?? []);
$operatorTestPack = (array)($evaluation['operator_test_pack'] ?? []);
$pageStatus = (array)($evaluation['pages'] ?? []);
$pages = (array)($pageStatus['pages'] ?? []);
$signoffNotes = (array)($evaluation['signoff_notes'] ?? []);

$statusClass = match ($evaluation['status'] ?? '') {
    MOGHARE360_SOFT_RUN_FINAL_STATUS_PILOT_READY => 'w6d-status-ready',
    MOGHARE360_SOFT_RUN_FINAL_STATUS_REVIEW_REQUIRED => 'w6d-status-review',
    MOGHARE360_SOFT_RUN_FINAL_STATUS_BLOCKED => 'w6d-status-blocked',
    MOGHARE360_SOFT_RUN_FINAL_STATUS_EMPTY => 'w6d-status-empty',
    default => 'w6d-status-error',
};

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>داشبورد نهایی آمادگی پایلوت Soft Run</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w6d-wrap">
    <header class="w1c-banner w6d-banner">
        <h1>داشبورد نهایی آمادگی پایلوت Soft Run</h1>
        <p>WAVE 6D — Soft Run Final Closure Dashboard & Pilot Readiness Signoff</p>
    </header>

    <section class="w1c-card w6d-warning">
        <strong>This is read-only internal Soft Run final closure — not final vehicle delivery. not legal final e-signature.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">تحویل نهایی خودرو انجام نمی‌شود. رکورد تکمیل تحویل ایجاد نمی‌شود. پورتال عمومی، ورود تولید، پرداخت و حسابداری رسمی فعال نیست.</p>
    </section>

    <section class="w1c-card <?= wave6d_h($statusClass) ?>">
        <h2 style="margin:0 0 0.5rem;font-size:1rem;">وضعیت بستن نهایی Soft Run</h2>
        <p style="margin:0;">
            <strong><?= wave6d_h(moghare360_soft_run_final_closure_status_label((string)($evaluation['status'] ?? ''))) ?></strong>
            (<?= wave6d_h((string)($evaluation['status'] ?? '')) ?>)
        </p>
        <p style="margin:0.5rem 0 0;"><?= wave6d_h((string)($evaluation['message'] ?? '')) ?></p>
    </section>

    <?php if (!empty($evaluation['errors'])): ?>
        <section class="w1c-card w1c-error-box">
            <ul style="margin:0;padding-right:1.25rem;">
                <?php foreach ($evaluation['errors'] as $error): ?>
                    <li><?= wave6d_h((string)$error) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <section class="w1c-card">
        <h2 style="margin:0 0 0.5rem;font-size:1rem;">WAVE 6A — اتاق کنترل Soft Run</h2>
        <p style="margin:0;">
            <strong><?= wave6d_h((string)($controlRoom['label'] ?? '—')) ?></strong>
            (<?= wave6d_h((string)($controlRoom['status'] ?? '')) ?>)
        </p>
        <p style="margin:0.35rem 0 0;font-size:0.88rem;"><?= wave6d_h((string)($controlRoom['message'] ?? '')) ?></p>
    </section>

    <section class="w1c-card">
        <h2 style="margin:0 0 0.5rem;font-size:1rem;">WAVE 6B — برد سناریوهای اجرای آزمایشی</h2>
        <p style="margin:0;">
            <strong><?= wave6d_h((string)($scenarioBoard['label'] ?? '—')) ?></strong>
            (<?= wave6d_h((string)($scenarioBoard['status'] ?? '')) ?>)
        </p>
        <p style="margin:0.35rem 0 0;font-size:0.88rem;"><?= wave6d_h((string)($scenarioBoard['message'] ?? '')) ?></p>
    </section>

    <section class="w1c-card">
        <h2 style="margin:0 0 0.5rem;font-size:1rem;">WAVE 6C — بسته تست اپراتوری</h2>
        <p style="margin:0;">
            <strong><?= wave6d_h((string)($operatorTestPack['label'] ?? '—')) ?></strong>
            (<?= wave6d_h((string)($operatorTestPack['status'] ?? '')) ?>)
        </p>
        <p style="margin:0.35rem 0 0;font-size:0.88rem;"><?= wave6d_h((string)($operatorTestPack['message'] ?? '')) ?></p>
    </section>

    <section class="w1c-card">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">فهرست صفحات زمان اجرای مورد نیاز</h2>
        <p style="margin:0 0 0.5rem;font-size:0.85rem;color:#525252;">
            موجود: <strong><?= wave6d_h((string)($pageStatus['present'] ?? 0)) ?></strong>
            / <?= wave6d_h((string)($pageStatus['total'] ?? 0)) ?>
        </p>
        <div style="overflow-x:auto;">
            <table class="w6d-table">
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
                        <td><?= wave6d_h($pagePath) ?></td>
                        <td><?= wave6d_h((string)($pageRow['label_fa'] ?? '')) ?></td>
                        <td><?= wave6d_h((string)($pageRow['status'] ?? '')) ?></td>
                        <td>
                            <?php if (!empty($pageRow['exists'])): ?>
                                <a href="<?= wave6d_h(wave6d_page_href($pagePath)) ?>">باز کردن</a>
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
        'review_items' => 'نیازمند بازبینی',
        'blocked_items' => 'مسدود',
        'missing_items' => 'مفقود',
    ] as $listKey => $listTitle): ?>
        <?php if (!empty($evaluation[$listKey]) && is_array($evaluation[$listKey])): ?>
            <section class="w1c-card">
                <h2 style="margin:0 0 0.5rem;font-size:1rem;"><?= wave6d_h($listTitle) ?></h2>
                <ul style="margin:0;padding-right:1.25rem;font-size:0.9rem;">
                    <?php foreach ($evaluation[$listKey] as $item): ?>
                        <li><?= wave6d_h((string)$item) ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php if ($signoffNotes !== []): ?>
        <section class="w1c-card w1c-note">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">یادداشت‌های تأیید پایلوت (Signoff)</h2>
            <ul style="margin:0;padding-right:1.25rem;font-size:0.88rem;">
                <?php foreach ($signoffNotes as $note): ?>
                    <li><?= wave6d_h((string)$note) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <nav class="w1c-card w1c-links w6d-nav">
        <a href="erp-soft-run-pilot-execution-create.php">ثبت اجرای پایلوت Soft Run</a>
        <a href="erp-soft-run-pilot-execution-board.php">برد اجرای پایلوت Soft Run</a>
        <a href="erp-soft-run-control-room.php">اتاق کنترل Soft Run</a>
        <a href="erp-soft-run-scenario-board.php">برد سناریوهای اجرای آزمایشی</a>
        <a href="erp-soft-run-operator-test-pack.php">بسته تست اپراتوری</a>
        <a href="erp-jobcard-command-workbench.php">میز فرمان کارت کار</a>
        <a href="erp-jobcard-command-center.php?jobcard_id=1">مرکز فرمان (نمونه)</a>
        <a href="erp-unified-operational-closure-dashboard.php">داشبورد بستن WAVE 5</a>
        <a href="erp-delivery-control-closure-dashboard.php">داشبورد بستن WAVE 4</a>
        <a href="erp-authorization-closure-dashboard.php">داشبورد بستن WAVE 3</a>
        <a href="erp-media-evidence-closure-dashboard.php">داشبورد بستن WAVE 2</a>
    </nav>
</div>
</body>
</html>

<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Soft Run Scenario Board (Wave 6B)
 * Read-only · no DB write · no POST
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-scenario-helper.php';

function wave6b_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$evaluation = moghare360_soft_run_scenario_evaluate();
$controlRoom = (array)($evaluation['control_room'] ?? []);
$scenarios = (array)($evaluation['scenarios'] ?? []);
$pageStatus = (array)($evaluation['pages'] ?? []);
$pages = (array)($pageStatus['pages'] ?? []);

$statusClass = match ($evaluation['status'] ?? '') {
    MOGHARE360_SOFT_RUN_SCENARIO_STATUS_PILOT_READY => 'w6b-status-ready',
    MOGHARE360_SOFT_RUN_SCENARIO_STATUS_REVIEW_REQUIRED => 'w6b-status-review',
    MOGHARE360_SOFT_RUN_SCENARIO_STATUS_BLOCKED => 'w6b-status-blocked',
    MOGHARE360_SOFT_RUN_SCENARIO_STATUS_EMPTY => 'w6b-status-empty',
    default => 'w6b-status-error',
};

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>برد سناریوهای اجرای آزمایشی Soft Run</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w6b-wrap">
    <header class="w1c-banner w6b-banner">
        <h1>برد سناریوهای اجرای آزمایشی Soft Run</h1>
        <p>WAVE 6B — Soft Run Scenario Checklist & Pilot Execution Board</p>
    </header>

    <section class="w1c-card w6b-warning">
        <strong>This is read-only internal Soft Run pilot checklist — not final vehicle delivery. not legal final e-signature.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">تحویل نهایی خودرو انجام نمی‌شود. رکورد تکمیل تحویل ایجاد نمی‌شود. پورتال عمومی، ورود تولید و پرداخت فعال نیست.</p>
    </section>

    <section class="w1c-card <?= wave6b_h($statusClass) ?>">
        <h2 style="margin:0 0 0.5rem;font-size:1rem;">وضعیت برد اجرای آزمایشی</h2>
        <p style="margin:0;">
            <strong><?= wave6b_h(moghare360_soft_run_scenario_status_label((string)($evaluation['status'] ?? ''))) ?></strong>
            (<?= wave6b_h((string)($evaluation['status'] ?? '')) ?>)
        </p>
        <p style="margin:0.5rem 0 0;"><?= wave6b_h((string)($evaluation['message'] ?? '')) ?></p>
    </section>

    <section class="w1c-card">
        <h2 style="margin:0 0 0.5rem;font-size:1rem;">وضعیت اتاق کنترل Soft Run</h2>
        <p style="margin:0;">
            <strong><?= wave6b_h((string)($controlRoom['label'] ?? '—')) ?></strong>
            (<?= wave6b_h((string)($controlRoom['status'] ?? '')) ?>)
        </p>
        <p style="margin:0.35rem 0 0;font-size:0.88rem;"><?= wave6b_h((string)($controlRoom['message'] ?? '')) ?></p>
        <p style="margin:0.5rem 0 0;">
            <a href="erp-soft-run-control-room.php">بازگشت به اتاق کنترل Soft Run</a>
        </p>
    </section>

    <?php if (!empty($evaluation['errors'])): ?>
        <section class="w1c-card w1c-error-box">
            <ul style="margin:0;padding-right:1.25rem;">
                <?php foreach ($evaluation['errors'] as $error): ?>
                    <li><?= wave6b_h((string)$error) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <section class="w1c-card">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">چک‌لیست سناریوهای آزمایشی</h2>
        <div style="overflow-x:auto;">
            <table class="w6b-table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>سناریو</th>
                    <th>موج</th>
                    <th>صفحه مرتبط</th>
                    <th>وضعیت</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($scenarios as $index => $scenario): ?>
                    <?php
                    $relatedPage = (string)($scenario['related_page'] ?? '');
                    $pageHref = $relatedPage;
                    if ($relatedPage === 'erp-jobcard-command-center.php') {
                        $pageHref = $relatedPage . '?jobcard_id=1';
                    } elseif (in_array($relatedPage, [
                        'erp-jobcard-final-readiness.php',
                        'erp-jobcard-delivery-eligibility.php',
                        'erp-jobcard-delivery-clearance.php',
                        'erp-jobcard-delivery-clearance-preview.php',
                        'erp-jobcard-authorization-gate.php',
                        'erp-jobcard-contract-authorization.php',
                        'erp-jobcard-contract-authorization-preview.php',
                        'erp-jobcard-contract-authorization-workflow.php',
                    ], true)) {
                        $pageHref = $relatedPage . '?jobcard_id=1';
                    }
                    ?>
                    <tr>
                        <td><?= wave6b_h((string)($index + 1)) ?></td>
                        <td><?= wave6b_h((string)($scenario['title_fa'] ?? '')) ?></td>
                        <td><?= wave6b_h((string)($scenario['wave'] ?? '')) ?></td>
                        <td>
                            <?php if (!empty($scenario['page_exists'])): ?>
                                <a href="<?= wave6b_h($pageHref) ?>"><?= wave6b_h($relatedPage) ?></a>
                            <?php else: ?>
                                <?= wave6b_h($relatedPage) ?>
                            <?php endif; ?>
                        </td>
                        <td><?= wave6b_h((string)($scenario['status'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="w1c-card">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">صفحات زمان اجرای مورد نیاز</h2>
        <p style="margin:0 0 0.5rem;font-size:0.85rem;color:#525252;">
            موجود: <strong><?= wave6b_h((string)($pageStatus['present'] ?? 0)) ?></strong>
            / <?= wave6b_h((string)($pageStatus['total'] ?? 0)) ?>
        </p>
        <div style="overflow-x:auto;">
            <table class="w6b-table">
                <thead>
                <tr>
                    <th>صفحه</th>
                    <th>برچسب</th>
                    <th>بحرانی</th>
                    <th>وضعیت</th>
                    <th>پیوند</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($pages as $pageRow): ?>
                    <?php
                    $pagePath = (string)($pageRow['path'] ?? '');
                    $linkHref = $pagePath;
                    if ($pagePath === 'erp-jobcard-command-center.php') {
                        $linkHref = $pagePath . '?jobcard_id=1';
                    } elseif (in_array($pagePath, [
                        'erp-jobcard-final-readiness.php',
                        'erp-jobcard-delivery-eligibility.php',
                        'erp-jobcard-delivery-clearance.php',
                        'erp-jobcard-delivery-clearance-preview.php',
                        'erp-jobcard-authorization-gate.php',
                        'erp-jobcard-contract-authorization.php',
                        'erp-jobcard-contract-authorization-preview.php',
                        'erp-jobcard-contract-authorization-workflow.php',
                    ], true)) {
                        $linkHref = $pagePath . '?jobcard_id=1';
                    }
                    ?>
                    <tr>
                        <td><?= wave6b_h($pagePath) ?></td>
                        <td><?= wave6b_h((string)($pageRow['label_fa'] ?? '')) ?></td>
                        <td><?= !empty($pageRow['critical']) ? 'بله' : 'خیر' ?></td>
                        <td><?= wave6b_h((string)($pageRow['status'] ?? '')) ?></td>
                        <td>
                            <?php if (!empty($pageRow['exists'])): ?>
                                <a href="<?= wave6b_h($linkHref) ?>">باز کردن</a>
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
                <h2 style="margin:0 0 0.5rem;font-size:1rem;"><?= wave6b_h($listTitle) ?></h2>
                <ul style="margin:0;padding-right:1.25rem;font-size:0.9rem;">
                    <?php foreach ($evaluation[$listKey] as $item): ?>
                        <li><?= wave6b_h((string)$item) ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>
    <?php endforeach; ?>

    <section class="w1c-card w1c-note">
        <h2 style="margin:0 0 0.5rem;font-size:1rem;">یادداشت‌های عملیاتی</h2>
        <ul style="margin:0;padding-right:1.25rem;font-size:0.88rem;">
            <li>این برد فقط چک‌لیست داخلی اجرای آزمایشی Soft Run است — بدون نوشتن پایگاه داده.</li>
            <li>وضعیت از اتاق کنترل WAVE 6A و وجود صفحات زمان اجرا خوانده می‌شود.</li>
            <li>تحویل نهایی، تکمیل تحویل، پورتال عمومی، پرداخت و امضای قانونی نهایی فعال نیست.</li>
        </ul>
    </section>

    <nav class="w1c-card w1c-links w6b-nav">
        <a href="erp-soft-run-final-closure-dashboard.php">داشبورد نهایی آمادگی پایلوت</a>
        <a href="erp-soft-run-operator-test-pack.php">بسته تست اپراتوری اجرای آزمایشی</a>
        <a href="erp-soft-run-control-room.php">اتاق کنترل Soft Run</a>
        <a href="erp-media-evidence-closure-dashboard.php">داشبورد بستن WAVE 2</a>
        <a href="erp-authorization-closure-dashboard.php">داشبورد بستن WAVE 3</a>
        <a href="erp-delivery-control-closure-dashboard.php">داشبورد بستن WAVE 4</a>
        <a href="erp-unified-operational-closure-dashboard.php">داشبورد بستن WAVE 5</a>
        <a href="erp-jobcard-command-workbench.php">میز فرمان کارت کار</a>
        <a href="erp-jobcard-command-center.php?jobcard_id=1">مرکز فرمان (نمونه)</a>
        <a href="erp-jobcard-final-readiness.php?jobcard_id=1">آمادگی نهایی (نمونه)</a>
        <a href="erp-jobcard-delivery-eligibility.php?jobcard_id=1">صلاحیت تحویل (نمونه)</a>
        <a href="erp-jobcard-delivery-clearance.php?jobcard_id=1">ثبت Clearance (نمونه)</a>
        <a href="erp-jobcard-delivery-clearance-preview.php?jobcard_id=1">پیش‌نمایش Clearance (نمونه)</a>
        <a href="erp-jobcard-authorization-gate.php?jobcard_id=1">گیت مجوز (نمونه)</a>
        <a href="erp-jobcard-contract-authorization.php?jobcard_id=1">ثبت مجوز (نمونه)</a>
        <a href="erp-jobcard-contract-authorization-preview.php?jobcard_id=1">پیش‌نمایش مجوز (نمونه)</a>
        <a href="erp-jobcard-contract-authorization-workflow.php?jobcard_id=1">گردش کار مجوز (نمونه)</a>
    </nav>
</div>
</body>
</html>

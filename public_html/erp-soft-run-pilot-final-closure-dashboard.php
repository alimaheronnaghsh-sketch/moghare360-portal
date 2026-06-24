<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Soft Run Pilot Final Closure Dashboard (Wave 7D)
 * Read-only · no POST · no DB write
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-pilot-final-closure-helper.php';

function wave7d_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function wave7d_page_href(string $path): string
{
    if (in_array($path, [
        'erp-soft-run-pilot-execution-detail.php',
        'erp-soft-run-pilot-execution-workflow.php',
    ], true)) {
        return $path . '?execution_id=1';
    }

    return $path;
}

$evaluation = moghare360_soft_run_pilot_final_closure_evaluate();
$reviewStatus = (array)($evaluation['review_status'] ?? []);
$executionSummary = (array)($evaluation['execution_summary'] ?? []);
$workflowSummary = (array)($evaluation['workflow_summary'] ?? []);
$historySummary = (array)($evaluation['history_summary'] ?? []);
$pageStatus = (array)($evaluation['pages'] ?? []);
$pages = (array)($pageStatus['pages'] ?? []);
$signoffNotes = (array)($evaluation['signoff_notes'] ?? []);

$sampleExecutionId = (int)($executionSummary['latest_execution_id'] ?? 0);
if ($sampleExecutionId < 1) {
    $sampleExecutionId = 1;
}

$statusClass = match ($evaluation['status'] ?? '') {
    MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_READY => 'w7d-status-ready',
    MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_REVIEW_REQUIRED => 'w7d-status-review',
    MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_BLOCKED => 'w7d-status-blocked',
    MOGHARE360_SOFT_RUN_PILOT_FINAL_STATUS_EMPTY => 'w7d-status-empty',
    default => 'w7d-status-error',
};

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>داشبورد نهایی بستن اجرای پایلوت Soft Run</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w7d-wrap">
    <header class="w1c-banner w7d-banner">
        <h1>داشبورد نهایی بستن اجرای پایلوت Soft Run</h1>
        <p>WAVE 7D — Soft Run Pilot Execution Final Closure & Wave 7 Signoff</p>
    </header>

    <section class="w1c-card w7d-warning">
        <strong>Read-only WAVE 7 final closure — not final vehicle delivery. Not delivery completion. Not legal e-signature. Not payment/accounting. Not production activation.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">این داشبورد فقط بستن نهایی و تأیید داخلی لاگ اجرای پایلوت است — رکوردها را به‌روزرسانی نمی‌کند.</p>
    </section>

    <section class="w1c-card <?= wave7d_h($statusClass) ?>">
        <h2 style="margin:0 0 0.5rem;font-size:1rem;">وضعیت بستن نهایی WAVE 7</h2>
        <p style="margin:0;">
            <strong><?= wave7d_h(moghare360_soft_run_pilot_final_closure_status_label((string)($evaluation['status'] ?? ''))) ?></strong>
            (<?= wave7d_h((string)($evaluation['status'] ?? '')) ?>)
        </p>
        <p style="margin:0.5rem 0 0;"><?= wave7d_h((string)($evaluation['message'] ?? '')) ?></p>
    </section>

    <section class="w1c-card">
        <h2 style="margin:0 0 0.5rem;font-size:1rem;">WAVE 7C — وضعیت بازبینی پایلوت</h2>
        <p style="margin:0;">
            <strong><?= wave7d_h((string)($reviewStatus['label'] ?? '—')) ?></strong>
            (<?= wave7d_h((string)($reviewStatus['status'] ?? '')) ?>)
        </p>
        <p style="margin:0.35rem 0 0;font-size:0.88rem;"><?= wave7d_h((string)($reviewStatus['message'] ?? '')) ?></p>
    </section>

    <?php if (!empty($evaluation['errors'])): ?>
        <section class="w1c-card w1c-error-box">
            <ul style="margin:0;padding-right:1.25rem;">
                <?php foreach ($evaluation['errors'] as $error): ?>
                    <li><?= wave7d_h((string)$error) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <section class="w1c-card w7d-summary">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">خلاصه اجرا و تاریخچه</h2>
        <dl class="w7d-dl">
            <dt>کل رکوردهای اجرا</dt>
            <dd><?= wave7d_h((string)($executionSummary['total_executions'] ?? 0)) ?></dd>
            <dt>کل ردیف‌های تاریخچه</dt>
            <dd><?= wave7d_h((string)($executionSummary['total_history_rows'] ?? 0)) ?></dd>
            <dt>پوشش تاریخچه</dt>
            <dd>
                <?= wave7d_h((string)($executionSummary['history_coverage_count'] ?? 0)) ?>
                /
                <?= wave7d_h((string)($executionSummary['total_executions'] ?? 0)) ?>
                <?php if (($executionSummary['history_coverage_percent'] ?? null) !== null): ?>
                    (<?= wave7d_h((string)$executionSummary['history_coverage_percent']) ?>%)
                <?php endif; ?>
            </dd>
            <dt>آخرین شناسه / کد اجرا</dt>
            <dd><?= wave7d_h((string)($executionSummary['latest_execution_id'] ?? '—')) ?>
                / <?= wave7d_h((string)($executionSummary['latest_execution_code'] ?? '—')) ?></dd>
            <dt>آخرین وضعیت اجرا / نتیجه / شواهد</dt>
            <dd>
                <?= wave7d_h((string)($executionSummary['latest_execution_status'] ?? '—')) ?> /
                <?= wave7d_h((string)($executionSummary['latest_result_status'] ?? '—')) ?> /
                <?= wave7d_h((string)($executionSummary['latest_evidence_status'] ?? '—')) ?>
            </dd>
            <dt>آخرین ایجاد / به‌روزرسانی</dt>
            <dd><?= wave7d_h((string)($executionSummary['latest_created_at'] ?? '—')) ?>
                / <?= wave7d_h((string)($executionSummary['latest_updated_at'] ?? '—')) ?></dd>
        </dl>
    </section>

    <section class="w1c-card">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">خلاصه گردش کار (WAVE 7B)</h2>
        <dl class="w7d-dl">
            <dt>وضعیت‌های نهایی (terminal)</dt>
            <dd><?= wave7d_h((string)($workflowSummary['workflow_terminal_count'] ?? 0)) ?></dd>
            <dt>موفق (PASSED)</dt>
            <dd><?= wave7d_h((string)($workflowSummary['passed_count'] ?? 0)) ?></dd>
            <dt>ناموفق</dt>
            <dd><?= wave7d_h((string)($workflowSummary['failed_count'] ?? 0)) ?></dd>
            <dt>مسدود</dt>
            <dd><?= wave7d_h((string)($workflowSummary['blocked_count'] ?? 0)) ?></dd>
            <dt>نیازمند بازبینی</dt>
            <dd><?= wave7d_h((string)($workflowSummary['review_required_count'] ?? 0)) ?></dd>
        </dl>
        <p style="margin:0.75rem 0 0;font-size:0.85rem;color:#525252;">
            <?= wave7d_h((string)($historySummary['write_boundary_note'] ?? '')) ?>
        </p>
    </section>

    <?php foreach ([
        'execution_status_counts' => 'شمارش وضعیت اجرا',
        'result_status_counts' => 'شمارش وضعیت نتیجه',
        'evidence_status_counts' => 'شمارش وضعیت شواهد',
    ] as $countKey => $countTitle): ?>
        <?php $counts = (array)($executionSummary[$countKey] ?? []); ?>
        <?php if ($counts !== []): ?>
            <section class="w1c-card w7d-counts">
                <h2 style="margin:0 0 0.75rem;font-size:1rem;"><?= wave7d_h($countTitle) ?></h2>
                <div class="w7d-count-grid">
                    <?php foreach ($counts as $status => $count): ?>
                        <div class="w7d-count-item">
                            <span class="w7d-count-label"><?= wave7d_h((string)$status) ?></span>
                            <strong><?= wave7d_h((string)$count) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    <?php endforeach; ?>

    <section class="w1c-card">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">صفحات زمان اجرای مورد نیاز WAVE 7</h2>
        <p style="margin:0 0 0.5rem;font-size:0.85rem;color:#525252;">
            موجود: <strong><?= wave7d_h((string)($pageStatus['present'] ?? 0)) ?></strong>
            / <?= wave7d_h((string)($pageStatus['total'] ?? 0)) ?>
        </p>
        <div style="overflow-x:auto;">
            <table class="w7d-table">
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
                        <td><?= wave7d_h($pagePath) ?></td>
                        <td><?= wave7d_h((string)($pageRow['label_fa'] ?? '')) ?></td>
                        <td><?= wave7d_h((string)($pageRow['status'] ?? '')) ?></td>
                        <td>
                            <?php if (!empty($pageRow['exists'])): ?>
                                <a href="<?= wave7d_h(wave7d_page_href($pagePath)) ?>">باز کردن</a>
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
                <h2 style="margin:0 0 0.5rem;font-size:1rem;"><?= wave7d_h($listTitle) ?></h2>
                <ul style="margin:0;padding-right:1.25rem;font-size:0.9rem;">
                    <?php foreach ($evaluation[$listKey] as $item): ?>
                        <li><?= wave7d_h((string)$item) ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php if ($signoffNotes !== []): ?>
        <section class="w1c-card w1c-note">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">یادداشت‌های تأیید نهایی WAVE 7 (Signoff)</h2>
            <ul style="margin:0;padding-right:1.25rem;font-size:0.88rem;">
                <?php foreach ($signoffNotes as $note): ?>
                    <li><?= wave7d_h((string)$note) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <nav class="w1c-card w1c-links w7d-nav">
        <a href="erp-executive-soft-run-readiness-dashboard.php">داشبورد آمادگی مدیریتی Soft Run</a>
        <a href="erp-soft-run-finding-create.php">ثبت یافته Soft Run</a>
        <a href="erp-soft-run-finding-board.php">برد یافته‌های Soft Run</a>
        <a href="erp-soft-run-pilot-execution-create.php">ثبت اجرای پایلوت Soft Run</a>
        <a href="erp-soft-run-pilot-execution-board.php">برد اجرای پایلوت</a>
        <a href="erp-soft-run-pilot-review-dashboard.php">داشبورد بازبینی اجرای پایلوت</a>
        <a href="erp-soft-run-pilot-execution-detail.php?execution_id=<?= wave7d_h((string)$sampleExecutionId) ?>">جزئیات اجرا (نمونه)</a>
        <a href="erp-soft-run-pilot-execution-workflow.php?execution_id=<?= wave7d_h((string)$sampleExecutionId) ?>">گردش کار اجرا (نمونه)</a>
        <a href="erp-soft-run-final-closure-dashboard.php">داشبورد نهایی آمادگی پایلوت WAVE 6</a>
        <a href="erp-soft-run-operator-test-pack.php">بسته تست اپراتوری</a>
    </nav>
</div>
</body>
</html>

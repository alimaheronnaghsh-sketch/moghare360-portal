<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Soft Run Pilot Review Dashboard (Wave 7C)
 * Read-only · no POST · no DB write
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-pilot-review-helper.php';

function wave7c_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$evaluation = moghare360_soft_run_pilot_review_evaluate();
$summary = (array)($evaluation['summary'] ?? []);
$recentExecutions = (array)($evaluation['recent_executions'] ?? []);
$historyCoverage = (array)($evaluation['history_coverage'] ?? []);
$reviewItems = (array)($evaluation['review_items'] ?? []);
$operationalNotes = (array)($evaluation['operational_notes'] ?? []);
$executionCounts = (array)($summary['execution_status_counts'] ?? []);
$resultCounts = (array)($summary['result_status_counts'] ?? []);
$evidenceCounts = (array)($summary['evidence_status_counts'] ?? []);

$latestExecutionId = (int)($summary['latest_execution_id'] ?? 0);
$sampleExecutionId = $latestExecutionId > 0 ? $latestExecutionId : 1;

$statusClass = match ($evaluation['status'] ?? '') {
    MOGHARE360_SOFT_RUN_PILOT_REVIEW_STATUS_READY => 'w7c-status-ready',
    MOGHARE360_SOFT_RUN_PILOT_REVIEW_STATUS_REVIEW_REQUIRED => 'w7c-status-review',
    MOGHARE360_SOFT_RUN_PILOT_REVIEW_STATUS_BLOCKED => 'w7c-status-blocked',
    MOGHARE360_SOFT_RUN_PILOT_REVIEW_STATUS_EMPTY => 'w7c-status-empty',
    default => 'w7c-status-error',
};

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>داشبورد بازبینی اجرای پایلوت Soft Run</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w7c-wrap">
    <header class="w1c-banner w7c-banner">
        <h1>داشبورد بازبینی اجرای پایلوت Soft Run</h1>
        <p>WAVE 7C — Soft Run Pilot Execution Review & Closure Dashboard</p>
    </header>

    <section class="w1c-card w7c-warning">
        <strong>Read-only internal Soft Run pilot execution review — not final delivery. Not delivery completion. Not legal e-signature. Not payment/accounting. Not production activation.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">این داشبورد فقط می‌خواند — رکوردهای اجرای پایلوت را به‌روزرسانی نمی‌کند.</p>
    </section>

    <section class="w1c-card <?= wave7c_h($statusClass) ?>">
        <h2 style="margin:0 0 0.5rem;font-size:1rem;">وضعیت بازبینی پایلوت</h2>
        <p style="margin:0;">
            <strong><?= wave7c_h((string)($evaluation['label'] ?? '')) ?></strong>
            (<?= wave7c_h((string)($evaluation['status'] ?? '')) ?>)
        </p>
        <p style="margin:0.5rem 0 0;"><?= wave7c_h((string)($evaluation['message'] ?? '')) ?></p>
    </section>

    <?php if (!empty($evaluation['errors'])): ?>
        <section class="w1c-card w1c-error-box">
            <ul style="margin:0;padding-right:1.25rem;">
                <?php foreach ($evaluation['errors'] as $error): ?>
                    <li><?= wave7c_h((string)$error) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <section class="w1c-card w7c-summary">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">خلاصه اجرا</h2>
        <dl class="w7c-dl">
            <dt>کل رکوردهای اجرا</dt>
            <dd><?= wave7c_h((string)($summary['total_executions'] ?? 0)) ?></dd>
            <dt>کل ردیف‌های تاریخچه</dt>
            <dd><?= wave7c_h((string)($summary['total_history_rows'] ?? 0)) ?></dd>
            <dt>پوشش تاریخچه</dt>
            <dd>
                <?= wave7c_h((string)($summary['history_coverage_count'] ?? 0)) ?>
                /
                <?= wave7c_h((string)($summary['total_executions'] ?? 0)) ?>
                <?php if (($summary['history_coverage_percent'] ?? null) !== null): ?>
                    (<?= wave7c_h((string)$summary['history_coverage_percent']) ?>%)
                <?php endif; ?>
            </dd>
            <dt>آخرین شناسه اجرا</dt>
            <dd><?= wave7c_h((string)($summary['latest_execution_id'] ?? '—')) ?></dd>
            <dt>آخرین کد اجرا</dt>
            <dd><?= wave7c_h((string)($summary['latest_execution_code'] ?? '—')) ?></dd>
            <dt>آخرین وضعیت اجرا</dt>
            <dd><?= wave7c_h(moghare360_soft_run_pilot_review_status_label((string)($summary['latest_execution_status'] ?? ''))) ?>
                (<?= wave7c_h((string)($summary['latest_execution_status'] ?? '—')) ?>)</dd>
            <dt>آخرین وضعیت نتیجه</dt>
            <dd><?= wave7c_h(moghare360_soft_run_pilot_review_status_label((string)($summary['latest_result_status'] ?? ''))) ?>
                (<?= wave7c_h((string)($summary['latest_result_status'] ?? '—')) ?>)</dd>
            <dt>آخرین وضعیت شواهد</dt>
            <dd><?= wave7c_h(moghare360_soft_run_pilot_review_status_label((string)($summary['latest_evidence_status'] ?? ''))) ?>
                (<?= wave7c_h((string)($summary['latest_evidence_status'] ?? '—')) ?>)</dd>
            <dt>آخرین ایجاد</dt>
            <dd><?= wave7c_h((string)($summary['latest_created_at'] ?? '—')) ?></dd>
            <dt>آخرین به‌روزرسانی</dt>
            <dd><?= wave7c_h((string)($summary['latest_updated_at'] ?? '—')) ?></dd>
        </dl>
    </section>

    <section class="w1c-card w7c-counts">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">شمارش بر اساس وضعیت اجرا</h2>
        <div class="w7c-count-grid">
            <?php foreach ($executionCounts as $status => $count): ?>
                <div class="w7c-count-item">
                    <span class="w7c-count-label"><?= wave7c_h(moghare360_soft_run_pilot_review_status_label((string)$status)) ?></span>
                    <strong><?= wave7c_h((string)$count) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="w1c-card w7c-counts">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">شمارش بر اساس وضعیت نتیجه</h2>
        <div class="w7c-count-grid">
            <?php foreach ($resultCounts as $status => $count): ?>
                <div class="w7c-count-item">
                    <span class="w7c-count-label"><?= wave7c_h(moghare360_soft_run_pilot_review_status_label((string)$status)) ?></span>
                    <strong><?= wave7c_h((string)$count) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="w1c-card w7c-counts">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">شمارش بر اساس وضعیت شواهد</h2>
        <div class="w7c-count-grid">
            <?php foreach ($evidenceCounts as $status => $count): ?>
                <div class="w7c-count-item">
                    <span class="w7c-count-label"><?= wave7c_h(moghare360_soft_run_pilot_review_status_label((string)$status)) ?></span>
                    <strong><?= wave7c_h((string)$count) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <?php if ($reviewItems !== []): ?>
        <section class="w1c-card">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">موارد بازبینی</h2>
            <ul style="margin:0;padding-right:1.25rem;font-size:0.9rem;">
                <?php foreach ($reviewItems as $item): ?>
                    <li><?= wave7c_h((string)$item) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <section class="w1c-card">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">اجراهای اخیر پایلوت</h2>
        <?php if ($recentExecutions === []): ?>
            <p style="margin:0;font-size:0.9rem;color:#525252;">رکورد اجرایی برای نمایش وجود ندارد.</p>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="w7c-table">
                    <thead>
                    <tr>
                        <th>شناسه</th>
                        <th>کد</th>
                        <th>سناریو</th>
                        <th>وضعیت اجرا</th>
                        <th>شواهد</th>
                        <th>نتیجه</th>
                        <th>جزئیات</th>
                        <th>گردش کار</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentExecutions as $row): ?>
                        <?php $rowId = (string)($row['execution_id'] ?? ''); ?>
                        <tr>
                            <td><?= wave7c_h($rowId) ?></td>
                            <td><?= wave7c_h((string)($row['execution_code'] ?? '')) ?></td>
                            <td><?= wave7c_h((string)($row['scenario_title'] ?? '')) ?></td>
                            <td><?= wave7c_h(moghare360_soft_run_pilot_review_status_label((string)($row['execution_status'] ?? ''))) ?></td>
                            <td><?= wave7c_h(moghare360_soft_run_pilot_review_status_label((string)($row['evidence_status'] ?? ''))) ?></td>
                            <td><?= wave7c_h(moghare360_soft_run_pilot_review_status_label((string)($row['result_status'] ?? ''))) ?></td>
                            <td><a href="erp-soft-run-pilot-execution-detail.php?execution_id=<?= wave7c_h($rowId) ?>">مشاهده</a></td>
                            <td><a href="erp-soft-run-pilot-execution-workflow.php?execution_id=<?= wave7c_h($rowId) ?>">گردش کار</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($operationalNotes !== []): ?>
        <section class="w1c-card w1c-note">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">یادداشت‌های عملیاتی</h2>
            <ul style="margin:0;padding-right:1.25rem;font-size:0.88rem;">
                <?php foreach ($operationalNotes as $note): ?>
                    <li><?= wave7c_h((string)$note) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <nav class="w1c-card w1c-links w7c-nav">
        <a href="erp-soft-run-finding-create.php">ثبت یافته Soft Run</a>
        <a href="erp-soft-run-finding-board.php">برد یافته‌های Soft Run</a>
        <a href="erp-soft-run-pilot-final-closure-dashboard.php">داشبورد نهایی بستن اجرای پایلوت</a>
        <a href="erp-soft-run-pilot-execution-create.php">ثبت اجرای پایلوت Soft Run</a>
        <a href="erp-soft-run-pilot-execution-board.php">برد اجرای پایلوت</a>
        <a href="erp-soft-run-pilot-execution-detail.php?execution_id=<?= wave7c_h((string)$sampleExecutionId) ?>">جزئیات اجرا (نمونه)</a>
        <a href="erp-soft-run-pilot-execution-workflow.php?execution_id=<?= wave7c_h((string)$sampleExecutionId) ?>">گردش کار اجرا (نمونه)</a>
        <a href="erp-soft-run-final-closure-dashboard.php">داشبورد نهایی آمادگی پایلوت</a>
        <a href="erp-soft-run-operator-test-pack.php">بسته تست اپراتوری</a>
    </nav>
</div>
</body>
</html>

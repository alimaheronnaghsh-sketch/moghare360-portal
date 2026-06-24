<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Soft Run Finding Review Dashboard (Wave 8C)
 * Read-only · no POST · no DB write
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-finding-review-helper.php';

function wave8c_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$evaluation = moghare360_soft_run_finding_review_evaluate();
$summary = (array)($evaluation['summary'] ?? []);
$recentFindings = (array)($evaluation['recent_findings'] ?? []);
$historyCoverage = (array)($evaluation['history_coverage'] ?? []);
$reviewItems = (array)($evaluation['review_items'] ?? []);
$operationalNotes = (array)($evaluation['operational_notes'] ?? []);
$findingStatusCounts = (array)($summary['finding_status_counts'] ?? []);
$severityCounts = (array)($summary['severity_level_counts'] ?? []);
$correctiveCounts = (array)($summary['corrective_action_status_counts'] ?? []);

$latestFindingId = (int)($summary['latest_finding_id'] ?? 0);
$sampleFindingId = $latestFindingId > 0 ? $latestFindingId : 1;

$statusClass = match ($evaluation['status'] ?? '') {
    MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_READY => 'w8c-status-ready',
    MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_ACTION_REQUIRED => 'w8c-status-action',
    MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_BLOCKED => 'w8c-status-blocked',
    MOGHARE360_SOFT_RUN_FINDING_REVIEW_STATUS_EMPTY => 'w8c-status-empty',
    default => 'w8c-status-error',
};

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>داشبورد بازبینی یافته‌های Soft Run</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w8c-wrap">
    <header class="w1c-banner w8c-banner">
        <h1>داشبورد بازبینی یافته‌های Soft Run</h1>
        <p>WAVE 8C — Soft Run Findings Review & Corrective Action Monitoring</p>
    </header>

    <section class="w1c-card w8c-warning">
        <strong>Read-only internal Soft Run findings review — not final delivery. Not delivery completion. Not legal e-signature. Not payment/accounting. Not production activation.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">این داشبورد فقط می‌خواند — رکوردهای یافته را به‌روزرسانی نمی‌کند.</p>
    </section>

    <section class="w1c-card <?= wave8c_h($statusClass) ?>">
        <h2 style="margin:0 0 0.5rem;font-size:1rem;">وضعیت بازبینی یافته‌ها</h2>
        <p style="margin:0;">
            <strong><?= wave8c_h((string)($evaluation['label'] ?? '')) ?></strong>
            (<?= wave8c_h((string)($evaluation['status'] ?? '')) ?>)
        </p>
        <p style="margin:0.5rem 0 0;"><?= wave8c_h((string)($evaluation['message'] ?? '')) ?></p>
    </section>

    <?php if (!empty($evaluation['errors'])): ?>
        <section class="w1c-card w1c-error-box">
            <ul style="margin:0;padding-right:1.25rem;">
                <?php foreach ($evaluation['errors'] as $error): ?>
                    <li><?= wave8c_h((string)$error) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <section class="w1c-card w8c-summary">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">خلاصه یافته‌ها</h2>
        <dl class="w8c-dl">
            <dt>کل رکوردهای یافته</dt>
            <dd><?= wave8c_h((string)($summary['total_findings'] ?? 0)) ?></dd>
            <dt>کل ردیف‌های تاریخچه</dt>
            <dd><?= wave8c_h((string)($summary['total_history_rows'] ?? 0)) ?></dd>
            <dt>پوشش تاریخچه</dt>
            <dd>
                <?= wave8c_h((string)($summary['history_coverage_count'] ?? 0)) ?>
                /
                <?= wave8c_h((string)($summary['total_findings'] ?? 0)) ?>
                <?php if (($summary['history_coverage_percent'] ?? null) !== null): ?>
                    (<?= wave8c_h((string)$summary['history_coverage_percent']) ?>%)
                <?php endif; ?>
            </dd>
            <dt>باز (OPEN)</dt>
            <dd><?= wave8c_h((string)($summary['open_count'] ?? 0)) ?></dd>
            <dt>نیازمند اقدام (ACTION_REQUIRED)</dt>
            <dd><?= wave8c_h((string)($summary['action_required_count'] ?? 0)) ?></dd>
            <dt>رفع شده (RESOLVED)</dt>
            <dd><?= wave8c_h((string)($summary['resolved_count'] ?? 0)) ?></dd>
            <dt>بسته شده (CLOSED)</dt>
            <dd><?= wave8c_h((string)($summary['closed_count'] ?? 0)) ?></dd>
            <dt>شدت بالا (HIGH)</dt>
            <dd><?= wave8c_h((string)($summary['high_count'] ?? 0)) ?></dd>
            <dt>شدت بحرانی (CRITICAL)</dt>
            <dd><?= wave8c_h((string)($summary['critical_count'] ?? 0)) ?></dd>
            <dt>اقدام اصلاحی مسدود</dt>
            <dd><?= wave8c_h((string)($summary['blocked_corrective_count'] ?? 0)) ?></dd>
            <dt>آخرین شناسه یافته</dt>
            <dd><?= wave8c_h((string)($summary['latest_finding_id'] ?? '—')) ?></dd>
            <dt>آخرین کد یافته</dt>
            <dd><?= wave8c_h((string)($summary['latest_finding_code'] ?? '—')) ?></dd>
            <dt>آخرین وضعیت یافته</dt>
            <dd><?= wave8c_h(moghare360_soft_run_finding_review_status_label((string)($summary['latest_finding_status'] ?? ''))) ?>
                (<?= wave8c_h((string)($summary['latest_finding_status'] ?? '—')) ?>)</dd>
            <dt>آخرین وضعیت اقدام اصلاحی</dt>
            <dd><?= wave8c_h(moghare360_soft_run_finding_review_status_label((string)($summary['latest_corrective_action_status'] ?? ''))) ?>
                (<?= wave8c_h((string)($summary['latest_corrective_action_status'] ?? '—')) ?>)</dd>
            <dt>آخرین سطح شدت</dt>
            <dd><?= wave8c_h(moghare360_soft_run_finding_review_status_label((string)($summary['latest_severity_level'] ?? ''))) ?>
                (<?= wave8c_h((string)($summary['latest_severity_level'] ?? '—')) ?>)</dd>
            <dt>آخرین ایجاد</dt>
            <dd><?= wave8c_h((string)($summary['latest_created_at'] ?? '—')) ?></dd>
            <dt>آخرین به‌روزرسانی</dt>
            <dd><?= wave8c_h((string)($summary['latest_updated_at'] ?? '—')) ?></dd>
        </dl>
    </section>

    <section class="w1c-card w8c-counts">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">شمارش بر اساس وضعیت یافته</h2>
        <div class="w8c-count-grid">
            <?php foreach ($findingStatusCounts as $status => $count): ?>
                <div class="w8c-count-item">
                    <span class="w8c-count-label"><?= wave8c_h(moghare360_soft_run_finding_review_status_label((string)$status)) ?></span>
                    <strong><?= wave8c_h((string)$count) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="w1c-card w8c-counts">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">شمارش بر اساس سطح شدت</h2>
        <div class="w8c-count-grid">
            <?php foreach ($severityCounts as $severity => $count): ?>
                <div class="w8c-count-item">
                    <span class="w8c-count-label"><?= wave8c_h(moghare360_soft_run_finding_review_status_label((string)$severity)) ?></span>
                    <strong><?= wave8c_h((string)$count) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="w1c-card w8c-counts">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">شمارش بر اساس وضعیت اقدام اصلاحی</h2>
        <div class="w8c-count-grid">
            <?php foreach ($correctiveCounts as $status => $count): ?>
                <div class="w8c-count-item">
                    <span class="w8c-count-label"><?= wave8c_h(moghare360_soft_run_finding_review_status_label((string)$status)) ?></span>
                    <strong><?= wave8c_h((string)$count) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <?php if ($reviewItems !== []): ?>
        <section class="w1c-card">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">موارد بازبینی / پایش</h2>
            <ul style="margin:0;padding-right:1.25rem;font-size:0.9rem;">
                <?php foreach ($reviewItems as $item): ?>
                    <li><?= wave8c_h((string)$item) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <section class="w1c-card">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">یافته‌های اخیر Soft Run</h2>
        <?php if ($recentFindings === []): ?>
            <p style="margin:0;font-size:0.9rem;color:#525252;">هنوز رکورد یافته‌ای برای نمایش وجود ندارد.</p>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="w8c-table">
                    <thead>
                    <tr>
                        <th>شناسه</th>
                        <th>کد</th>
                        <th>نوع</th>
                        <th>شدت</th>
                        <th>وضعیت</th>
                        <th>اقدام اصلاحی</th>
                        <th>عنوان</th>
                        <th>جزئیات</th>
                        <th>گردش کار</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentFindings as $row): ?>
                        <?php $rowFindingId = (string)($row['finding_id'] ?? ''); ?>
                        <tr>
                            <td><?= wave8c_h($rowFindingId) ?></td>
                            <td><?= wave8c_h((string)($row['finding_code'] ?? '')) ?></td>
                            <td><?= wave8c_h(moghare360_soft_run_finding_review_status_label((string)($row['finding_type'] ?? ''))) ?></td>
                            <td><?= wave8c_h(moghare360_soft_run_finding_review_status_label((string)($row['severity_level'] ?? ''))) ?></td>
                            <td><?= wave8c_h(moghare360_soft_run_finding_review_status_label((string)($row['finding_status'] ?? ''))) ?></td>
                            <td><?= wave8c_h(moghare360_soft_run_finding_review_status_label((string)($row['corrective_action_status'] ?? ''))) ?></td>
                            <td><?= wave8c_h((string)($row['finding_title'] ?? '')) ?></td>
                            <td>
                                <a href="erp-soft-run-finding-detail.php?finding_id=<?= wave8c_h($rowFindingId) ?>">مشاهده</a>
                            </td>
                            <td>
                                <a href="erp-soft-run-finding-workflow.php?finding_id=<?= wave8c_h($rowFindingId) ?>">گردش کار</a>
                            </td>
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
                    <li><?= wave8c_h((string)$note) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <nav class="w1c-card w1c-links w8c-nav">
        <a href="erp-soft-run-finding-create.php">ثبت یافته Soft Run</a>
        <a href="erp-soft-run-finding-board.php">برد یافته‌های Soft Run</a>
        <a href="erp-soft-run-finding-detail.php?finding_id=<?= wave8c_h((string)$sampleFindingId) ?>">جزئیات یافته (نمونه)</a>
        <a href="erp-soft-run-finding-workflow.php?finding_id=<?= wave8c_h((string)$sampleFindingId) ?>">گردش کار یافته (نمونه)</a>
        <a href="erp-soft-run-pilot-final-closure-dashboard.php">داشبورد نهایی بستن اجرای پایلوت WAVE 7</a>
        <a href="erp-soft-run-pilot-review-dashboard.php">داشبورد بازبینی اجرای پایلوت WAVE 7</a>
    </nav>
</div>
</body>
</html>

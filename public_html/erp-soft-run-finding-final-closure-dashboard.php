<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Soft Run Finding Final Closure Dashboard (Wave 8D)
 * Read-only · no POST · no DB write
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-finding-final-closure-helper.php';

function wave8d_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function wave8d_page_href(string $path): string
{
    if (in_array($path, [
        'erp-soft-run-finding-detail.php',
        'erp-soft-run-finding-workflow.php',
    ], true)) {
        return $path . '?finding_id=1';
    }

    return $path;
}

$evaluation = moghare360_soft_run_finding_final_closure_evaluate();
$reviewStatus = (array)($evaluation['review_status'] ?? []);
$findingSummary = (array)($evaluation['finding_summary'] ?? []);
$correctiveSummary = (array)($evaluation['corrective_summary'] ?? []);
$historySummary = (array)($evaluation['history_summary'] ?? []);
$pageStatus = (array)($evaluation['pages'] ?? []);
$pages = (array)($pageStatus['pages'] ?? []);
$signoffNotes = (array)($evaluation['signoff_notes'] ?? []);

$sampleFindingId = (int)($findingSummary['latest_finding_id'] ?? 0);
if ($sampleFindingId < 1) {
    $sampleFindingId = 1;
}

$statusClass = match ($evaluation['status'] ?? '') {
    MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_READY => 'w8d-status-ready',
    MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_ACTION_REQUIRED => 'w8d-status-action',
    MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_BLOCKED => 'w8d-status-blocked',
    MOGHARE360_SOFT_RUN_FINDING_FINAL_STATUS_EMPTY => 'w8d-status-empty',
    default => 'w8d-status-error',
};

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>داشبورد نهایی بستن یافته‌های Soft Run</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w8d-wrap">
    <header class="w1c-banner w8d-banner">
        <h1>داشبورد نهایی بستن یافته‌های Soft Run</h1>
        <p>WAVE 8D — Soft Run Findings Final Closure & Corrective Action Signoff</p>
    </header>

    <section class="w1c-card w8d-warning">
        <strong>Read-only WAVE 8 final closure — not final vehicle delivery. Not delivery completion. Not legal e-signature. Not payment/accounting. Not production activation.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">این داشبورد فقط بستن نهایی و تأیید داخلی ثبت یافته‌ها و اقدام اصلاحی است — رکوردها را به‌روزرسانی نمی‌کند.</p>
    </section>

    <section class="w1c-card <?= wave8d_h($statusClass) ?>">
        <h2 style="margin:0 0 0.5rem;font-size:1rem;">وضعیت بستن نهایی WAVE 8</h2>
        <p style="margin:0;">
            <strong><?= wave8d_h(moghare360_soft_run_finding_final_closure_status_label((string)($evaluation['status'] ?? ''))) ?></strong>
            (<?= wave8d_h((string)($evaluation['status'] ?? '')) ?>)
        </p>
        <p style="margin:0.5rem 0 0;"><?= wave8d_h((string)($evaluation['message'] ?? '')) ?></p>
    </section>

    <section class="w1c-card">
        <h2 style="margin:0 0 0.5rem;font-size:1rem;">WAVE 8C — وضعیت بازبینی یافته‌ها</h2>
        <p style="margin:0;">
            <strong><?= wave8d_h((string)($reviewStatus['label'] ?? '—')) ?></strong>
            (<?= wave8d_h((string)($reviewStatus['status'] ?? '')) ?>)
        </p>
        <p style="margin:0.35rem 0 0;font-size:0.88rem;"><?= wave8d_h((string)($reviewStatus['message'] ?? '')) ?></p>
    </section>

    <?php if (!empty($evaluation['errors'])): ?>
        <section class="w1c-card w1c-error-box">
            <ul style="margin:0;padding-right:1.25rem;">
                <?php foreach ($evaluation['errors'] as $error): ?>
                    <li><?= wave8d_h((string)$error) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <section class="w1c-card w8d-summary">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">خلاصه یافته‌ها و تاریخچه</h2>
        <dl class="w8d-dl">
            <dt>کل رکوردهای یافته</dt>
            <dd><?= wave8d_h((string)($findingSummary['total_findings'] ?? 0)) ?></dd>
            <dt>کل ردیف‌های تاریخچه</dt>
            <dd><?= wave8d_h((string)($findingSummary['total_history_rows'] ?? 0)) ?></dd>
            <dt>پوشش تاریخچه</dt>
            <dd>
                <?= wave8d_h((string)($findingSummary['history_coverage_count'] ?? 0)) ?>
                /
                <?= wave8d_h((string)($findingSummary['total_findings'] ?? 0)) ?>
                <?php if (($findingSummary['history_coverage_percent'] ?? null) !== null): ?>
                    (<?= wave8d_h((string)$findingSummary['history_coverage_percent']) ?>%)
                <?php endif; ?>
            </dd>
            <dt>آخرین شناسه / کد یافته</dt>
            <dd><?= wave8d_h((string)($findingSummary['latest_finding_id'] ?? '—')) ?>
                / <?= wave8d_h((string)($findingSummary['latest_finding_code'] ?? '—')) ?></dd>
            <dt>آخرین وضعیت یافته / اقدام اصلاحی / شدت</dt>
            <dd>
                <?= wave8d_h((string)($findingSummary['latest_finding_status'] ?? '—')) ?> /
                <?= wave8d_h((string)($findingSummary['latest_corrective_action_status'] ?? '—')) ?> /
                <?= wave8d_h((string)($findingSummary['latest_severity_level'] ?? '—')) ?>
            </dd>
            <dt>آخرین ایجاد / به‌روزرسانی</dt>
            <dd><?= wave8d_h((string)($findingSummary['latest_created_at'] ?? '—')) ?>
                / <?= wave8d_h((string)($findingSummary['latest_updated_at'] ?? '—')) ?></dd>
        </dl>
    </section>

    <section class="w1c-card">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">خلاصه وضعیت یافته (WAVE 8A/8B)</h2>
        <dl class="w8d-dl">
            <dt>باز (OPEN)</dt>
            <dd><?= wave8d_h((string)($findingSummary['open_count'] ?? 0)) ?></dd>
            <dt>در بازبینی (UNDER_REVIEW)</dt>
            <dd><?= wave8d_h((string)($findingSummary['under_review_count'] ?? 0)) ?></dd>
            <dt>نیازمند اقدام (ACTION_REQUIRED)</dt>
            <dd><?= wave8d_h((string)($findingSummary['action_required_count'] ?? 0)) ?></dd>
            <dt>حل‌شده (RESOLVED)</dt>
            <dd><?= wave8d_h((string)($findingSummary['resolved_count'] ?? 0)) ?></dd>
            <dt>بسته (CLOSED)</dt>
            <dd><?= wave8d_h((string)($findingSummary['closed_count'] ?? 0)) ?></dd>
            <dt>لغو (CANCELLED)</dt>
            <dd><?= wave8d_h((string)($findingSummary['cancelled_count'] ?? 0)) ?></dd>
            <dt>HIGH حل‌نشده</dt>
            <dd><?= wave8d_h((string)($findingSummary['high_unresolved_count'] ?? 0)) ?></dd>
            <dt>CRITICAL حل‌نشده</dt>
            <dd><?= wave8d_h((string)($findingSummary['critical_unresolved_count'] ?? 0)) ?></dd>
        </dl>
    </section>

    <section class="w1c-card">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">خلاصه اقدام اصلاحی (Corrective Action)</h2>
        <dl class="w8d-dl">
            <dt>شروع نشده (NOT_STARTED)</dt>
            <dd><?= wave8d_h((string)($correctiveSummary['corrective_not_started_count'] ?? 0)) ?></dd>
            <dt>در حال انجام (IN_PROGRESS)</dt>
            <dd><?= wave8d_h((string)($correctiveSummary['corrective_in_progress_count'] ?? 0)) ?></dd>
            <dt>انجام شده (DONE)</dt>
            <dd><?= wave8d_h((string)($correctiveSummary['corrective_done_count'] ?? 0)) ?></dd>
            <dt>نیاز نیست (NOT_REQUIRED)</dt>
            <dd><?= wave8d_h((string)($correctiveSummary['corrective_not_required_count'] ?? 0)) ?></dd>
            <dt>مسدود (BLOCKED)</dt>
            <dd><?= wave8d_h((string)($correctiveSummary['corrective_blocked_count'] ?? 0)) ?></dd>
        </dl>
        <p style="margin:0.75rem 0 0;font-size:0.85rem;color:#525252;">
            <?= wave8d_h((string)($historySummary['write_boundary_note'] ?? '')) ?>
        </p>
    </section>

    <?php foreach ([
        'finding_status_counts' => 'شمارش وضعیت یافته',
        'severity_level_counts' => 'شمارش شدت',
        'corrective_action_status_counts' => 'شمارش اقدام اصلاحی',
    ] as $countKey => $countTitle): ?>
        <?php $counts = (array)($findingSummary[$countKey] ?? []); ?>
        <?php if ($counts !== []): ?>
            <section class="w1c-card w8d-counts">
                <h2 style="margin:0 0 0.75rem;font-size:1rem;"><?= wave8d_h($countTitle) ?></h2>
                <div class="w8d-count-grid">
                    <?php foreach ($counts as $status => $count): ?>
                        <div class="w8d-count-item">
                            <span class="w8d-count-label"><?= wave8d_h((string)$status) ?></span>
                            <strong><?= wave8d_h((string)$count) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    <?php endforeach; ?>

    <section class="w1c-card">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">صفحات زمان اجرای مورد نیاز WAVE 8</h2>
        <p style="margin:0 0 0.5rem;font-size:0.85rem;color:#525252;">
            موجود: <strong><?= wave8d_h((string)($pageStatus['present'] ?? 0)) ?></strong>
            / <?= wave8d_h((string)($pageStatus['total'] ?? 0)) ?>
        </p>
        <div style="overflow-x:auto;">
            <table class="w8d-table">
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
                        <td><?= wave8d_h($pagePath) ?></td>
                        <td><?= wave8d_h((string)($pageRow['label_fa'] ?? '')) ?></td>
                        <td><?= wave8d_h((string)($pageRow['status'] ?? '')) ?></td>
                        <td>
                            <?php if (!empty($pageRow['exists'])): ?>
                                <a href="<?= wave8d_h(wave8d_page_href($pagePath)) ?>">باز کردن</a>
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
        'action_items' => 'نیازمند اقدام',
        'blocked_items' => 'مسدود',
        'missing_items' => 'مفقود',
    ] as $listKey => $listTitle): ?>
        <?php if (!empty($evaluation[$listKey]) && is_array($evaluation[$listKey])): ?>
            <section class="w1c-card">
                <h2 style="margin:0 0 0.5rem;font-size:1rem;"><?= wave8d_h($listTitle) ?></h2>
                <ul style="margin:0;padding-right:1.25rem;font-size:0.9rem;">
                    <?php foreach ($evaluation[$listKey] as $item): ?>
                        <li><?= wave8d_h((string)$item) ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php if ($signoffNotes !== []): ?>
        <section class="w1c-card w1c-note">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">یادداشت‌های تأیید نهایی WAVE 8 (Signoff)</h2>
            <ul style="margin:0;padding-right:1.25rem;font-size:0.88rem;">
                <?php foreach ($signoffNotes as $note): ?>
                    <li><?= wave8d_h((string)$note) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <nav class="w1c-card w1c-links w8d-nav">
        <a href="erp-soft-run-finding-create.php">ثبت یافته Soft Run</a>
        <a href="erp-soft-run-finding-board.php">برد یافته‌های Soft Run</a>
        <a href="erp-soft-run-finding-review-dashboard.php">داشبورد بازبینی یافته‌های Soft Run</a>
        <a href="erp-soft-run-finding-detail.php?finding_id=<?= wave8d_h((string)$sampleFindingId) ?>">جزئیات یافته (نمونه)</a>
        <a href="erp-soft-run-finding-workflow.php?finding_id=<?= wave8d_h((string)$sampleFindingId) ?>">گردش کار یافته (نمونه)</a>
        <a href="erp-soft-run-pilot-final-closure-dashboard.php">داشبورد نهایی بستن اجرای پایلوت WAVE 7</a>
        <a href="erp-soft-run-pilot-review-dashboard.php">داشبورد بازبینی اجرای پایلوت WAVE 7</a>
    </nav>
</div>
</body>
</html>

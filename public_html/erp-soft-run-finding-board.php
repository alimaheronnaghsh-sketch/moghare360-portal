<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Soft Run Finding Board (Wave 8A)
 * Read-only · no POST · no DB write from page
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-finding-helper.php';

function wave8a_board_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$schema = moghare360_soft_run_finding_schema_status();
$fetch = moghare360_soft_run_finding_fetch_recent(25);
$records = (array)($fetch['records'] ?? []);
$findingStatusCounts = (array)($fetch['counts']['finding_status'] ?? []);
$severityCounts = (array)($fetch['counts']['severity_level'] ?? []);
$correctiveCounts = (array)($fetch['counts']['corrective_action_status'] ?? []);

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>برد یافته‌های Soft Run</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w8a-wrap">
    <header class="w1c-banner w8a-banner">
        <h1>برد یافته‌های Soft Run</h1>
        <p>WAVE 8A — Read-only Soft Run Findings Board</p>
    </header>

    <section class="w1c-card w8a-warning">
        <strong>Read-only internal Soft Run findings board — not final delivery. Not delivery completion.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">پورتال عمومی، پرداخت، حسابداری و امضای الکترونیکی قانونی نهایی فعال نیست.</p>
    </section>

    <?php if (($schema['schema_status'] ?? '') === MOGHARE360_SOFT_RUN_FINDING_SCHEMA_BLOCKED): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave8a_board_h(MOGHARE360_SOFT_RUN_FINDING_BLOCK_MESSAGE) ?></p>
            <p style="margin:0.5rem 0 0;font-size:0.85rem;">پس از اجرای wave_8a_soft_run_findings_register.sql در SSMS، رکوردها نمایش داده می‌شوند.</p>
        </section>
    <?php endif; ?>

    <section class="w1c-card w8a-counts">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">شمارش بر اساس وضعیت یافته</h2>
        <div class="w8a-count-grid">
            <?php foreach ($findingStatusCounts as $status => $count): ?>
                <div class="w8a-count-item">
                    <span class="w8a-count-label"><?= wave8a_board_h(moghare360_soft_run_finding_status_label((string)$status)) ?></span>
                    <strong><?= wave8a_board_h((string)$count) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="w1c-card w8a-counts">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">شمارش بر اساس سطح شدت</h2>
        <div class="w8a-count-grid">
            <?php foreach ($severityCounts as $severity => $count): ?>
                <div class="w8a-count-item">
                    <span class="w8a-count-label"><?= wave8a_board_h(moghare360_soft_run_finding_severity_label((string)$severity)) ?></span>
                    <strong><?= wave8a_board_h((string)$count) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="w1c-card w8a-counts">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">شمارش بر اساس وضعیت اقدام اصلاحی</h2>
        <div class="w8a-count-grid">
            <?php foreach ($correctiveCounts as $status => $count): ?>
                <div class="w8a-count-item">
                    <span class="w8a-count-label"><?= wave8a_board_h(moghare360_soft_run_finding_status_label((string)$status)) ?></span>
                    <strong><?= wave8a_board_h((string)$count) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="w1c-card">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">یافته‌های اخیر Soft Run</h2>
        <?php if ($records === []): ?>
            <p style="margin:0;font-size:0.9rem;color:#525252;">هنوز رکورد یافته‌ای ثبت نشده است.</p>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="w8a-table">
                    <thead>
                    <tr>
                        <th>شناسه</th>
                        <th>کد</th>
                        <th>نوع</th>
                        <th>شدت</th>
                        <th>وضعیت</th>
                        <th>اقدام اصلاحی</th>
                        <th>عنوان</th>
                        <th>ایجاد</th>
                        <th>جزئیات</th>
                        <th>گردش کار</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($records as $row): ?>
                        <?php $rowFindingId = (string)($row['finding_id'] ?? ''); ?>
                        <tr>
                            <td><?= wave8a_board_h($rowFindingId) ?></td>
                            <td><?= wave8a_board_h((string)($row['finding_code'] ?? '')) ?></td>
                            <td><?= wave8a_board_h(moghare360_soft_run_finding_type_label((string)($row['finding_type'] ?? ''))) ?></td>
                            <td><?= wave8a_board_h(moghare360_soft_run_finding_severity_label((string)($row['severity_level'] ?? ''))) ?></td>
                            <td><?= wave8a_board_h(moghare360_soft_run_finding_status_label((string)($row['finding_status'] ?? ''))) ?></td>
                            <td><?= wave8a_board_h(moghare360_soft_run_finding_status_label((string)($row['corrective_action_status'] ?? ''))) ?></td>
                            <td><?= wave8a_board_h((string)($row['finding_title'] ?? '')) ?></td>
                            <td><?= wave8a_board_h((string)($row['created_at'] ?? '')) ?></td>
                            <td>
                                <a href="erp-soft-run-finding-detail.php?finding_id=<?= wave8a_board_h($rowFindingId) ?>">مشاهده</a>
                            </td>
                            <td>
                                <a href="erp-soft-run-finding-workflow.php?finding_id=<?= wave8a_board_h($rowFindingId) ?>">گردش کار</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <nav class="w1c-card w1c-links w8a-nav">
        <a href="erp-soft-run-finding-create.php">ثبت یافته Soft Run</a>
        <a href="erp-soft-run-pilot-final-closure-dashboard.php">داشبورد نهایی بستن اجرای پایلوت</a>
        <a href="erp-soft-run-pilot-review-dashboard.php">داشبورد بازبینی اجرای پایلوت</a>
        <a href="erp-soft-run-final-closure-dashboard.php">داشبورد نهایی آمادگی پایلوت</a>
    </nav>
</div>
</body>
</html>

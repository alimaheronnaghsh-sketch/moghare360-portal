<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Soft Run Pilot Execution Board (Wave 7A)
 * Read-only · no POST · no DB write from page
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-pilot-execution-helper.php';

function wave7a_board_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$schema = moghare360_soft_run_pilot_execution_schema_status();
$fetch = moghare360_soft_run_pilot_execution_fetch_recent(25);
$records = (array)($fetch['records'] ?? []);
$executionCounts = (array)($fetch['counts']['execution_status'] ?? []);
$resultCounts = (array)($fetch['counts']['result_status'] ?? []);

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>برد اجرای پایلوت Soft Run</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w7a-wrap">
    <header class="w1c-banner w7a-banner">
        <h1>برد اجرای پایلوت Soft Run</h1>
        <p>WAVE 7A — Read-only Pilot Execution Board</p>
    </header>

    <section class="w1c-card w7a-warning">
        <strong>Read-only internal Soft Run pilot execution board — not final delivery. Not delivery completion.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">پورتال عمومی، پرداخت، حسابداری و امضای الکترونیکی قانونی نهایی فعال نیست.</p>
    </section>

    <?php if (($schema['schema_status'] ?? '') === MOGHARE360_SOFT_RUN_PILOT_EXECUTION_SCHEMA_BLOCKED): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave7a_board_h(MOGHARE360_SOFT_RUN_PILOT_EXECUTION_BLOCK_MESSAGE) ?></p>
            <p style="margin:0.5rem 0 0;font-size:0.85rem;">پس از اجرای wave_7a_soft_run_pilot_execution_log.sql در SSMS، رکوردها نمایش داده می‌شوند.</p>
        </section>
    <?php endif; ?>

    <section class="w1c-card w7a-counts">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">شمارش بر اساس وضعیت اجرا</h2>
        <div class="w7a-count-grid">
            <?php foreach ($executionCounts as $status => $count): ?>
                <div class="w7a-count-item">
                    <span class="w7a-count-label"><?= wave7a_board_h(moghare360_soft_run_pilot_execution_status_label((string)$status)) ?></span>
                    <strong><?= wave7a_board_h((string)$count) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="w1c-card w7a-counts">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">شمارش بر اساس وضعیت نتیجه</h2>
        <div class="w7a-count-grid">
            <?php foreach ($resultCounts as $status => $count): ?>
                <div class="w7a-count-item">
                    <span class="w7a-count-label"><?= wave7a_board_h(moghare360_soft_run_pilot_execution_status_label((string)$status)) ?></span>
                    <strong><?= wave7a_board_h((string)$count) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="w1c-card">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">اجراهای اخیر پایلوت</h2>
        <?php if ($records === []): ?>
            <p style="margin:0;font-size:0.9rem;color:#525252;">هنوز رکورد اجرای پایلوت ثبت نشده است.</p>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="w7a-table">
                    <thead>
                    <tr>
                        <th>شناسه</th>
                        <th>کد</th>
                        <th>کارت کار</th>
                        <th>سناریو</th>
                        <th>وضعیت اجرا</th>
                        <th>نتیجه</th>
                        <th>ایجاد</th>
                        <th>جزئیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($records as $row): ?>
                        <tr>
                            <td><?= wave7a_board_h((string)($row['execution_id'] ?? '')) ?></td>
                            <td><?= wave7a_board_h((string)($row['execution_code'] ?? '')) ?></td>
                            <td><?= wave7a_board_h((string)($row['jobcard_id'] ?? '—')) ?></td>
                            <td><?= wave7a_board_h((string)($row['scenario_title'] ?? '')) ?></td>
                            <td><?= wave7a_board_h(moghare360_soft_run_pilot_execution_status_label((string)($row['execution_status'] ?? ''))) ?></td>
                            <td><?= wave7a_board_h(moghare360_soft_run_pilot_execution_status_label((string)($row['result_status'] ?? ''))) ?></td>
                            <td><?= wave7a_board_h((string)($row['created_at'] ?? '')) ?></td>
                            <td>
                                <a href="erp-soft-run-pilot-execution-detail.php?execution_id=<?= wave7a_board_h((string)($row['execution_id'] ?? '')) ?>">مشاهده</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <nav class="w1c-card w1c-links w7a-nav">
        <a href="erp-soft-run-pilot-execution-create.php">ثبت اجرای پایلوت Soft Run</a>
        <a href="erp-soft-run-final-closure-dashboard.php">داشبورد نهایی آمادگی پایلوت</a>
        <a href="erp-soft-run-operator-test-pack.php">بسته تست اپراتوری</a>
        <a href="erp-soft-run-control-room.php">اتاق کنترل Soft Run</a>
    </nav>
</div>
</body>
</html>

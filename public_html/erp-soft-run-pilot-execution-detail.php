<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Soft Run Pilot Execution Detail (Wave 7A)
 * Read-only · no POST · no DB write from page
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-pilot-execution-helper.php';

function wave7a_detail_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$executionIdRaw = trim((string)($_GET['execution_id'] ?? ''));
$executionId = ($executionIdRaw !== '' && ctype_digit($executionIdRaw) && (int)$executionIdRaw >= 1)
    ? (int)$executionIdRaw
    : 0;

$detail = $executionId > 0 ? moghare360_soft_run_pilot_execution_fetch_detail($executionId) : null;
$history = $executionId > 0 ? moghare360_soft_run_pilot_execution_fetch_history($executionId) : null;
$record = ($detail !== null && ($detail['ok'] ?? false)) ? (array)($detail['record'] ?? []) : [];
$historyRows = ($history !== null && ($history['ok'] ?? false)) ? (array)($history['history'] ?? []) : [];

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>جزئیات اجرای پایلوت Soft Run</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w7a-wrap">
    <header class="w1c-banner w7a-banner">
        <h1>جزئیات اجرای پایلوت Soft Run</h1>
        <p>WAVE 7A — Read-only Pilot Execution Detail</p>
    </header>

    <section class="w1c-card w7a-warning">
        <strong>Read-only internal pilot execution detail — not final delivery. Not delivery completion.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">پورتال عمومی، پرداخت، حسابداری و امضای الکترونیکی قانونی نهایی فعال نیست.</p>
    </section>

    <?php if ($executionId < 1): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;">شناسه اجرا نامعتبر است.</p>
        </section>
    <?php elseif ($detail === null || !($detail['ok'] ?? false)): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave7a_detail_h((string)($detail['message'] ?? MOGHARE360_SOFT_RUN_PILOT_EXECUTION_BLOCK_MESSAGE)) ?></p>
        </section>
    <?php else: ?>
        <section class="w1c-card w7a-detail">
            <h2 style="margin:0 0 0.75rem;font-size:1rem;">رکورد اجرا</h2>
            <dl class="w7a-dl">
                <dt>شناسه اجرا</dt>
                <dd><?= wave7a_detail_h((string)($record['execution_id'] ?? '')) ?></dd>
                <dt>کد اجرا</dt>
                <dd><?= wave7a_detail_h((string)($record['execution_code'] ?? '')) ?></dd>
                <dt>شناسه کارت کار</dt>
                <dd><?= wave7a_detail_h((string)($record['jobcard_id'] ?? '—')) ?></dd>
                <dt>کلید سناریو</dt>
                <dd><?= wave7a_detail_h((string)($record['scenario_key'] ?? '')) ?></dd>
                <dt>عنوان سناریو</dt>
                <dd><?= wave7a_detail_h((string)($record['scenario_title'] ?? '')) ?></dd>
                <dt>وضعیت اجرا</dt>
                <dd><?= wave7a_detail_h(moghare360_soft_run_pilot_execution_status_label((string)($record['execution_status'] ?? ''))) ?>
                    (<?= wave7a_detail_h((string)($record['execution_status'] ?? '')) ?>)</dd>
                <dt>وضعیت شواهد</dt>
                <dd><?= wave7a_detail_h(moghare360_soft_run_pilot_execution_status_label((string)($record['evidence_status'] ?? ''))) ?>
                    (<?= wave7a_detail_h((string)($record['evidence_status'] ?? '')) ?>)</dd>
                <dt>وضعیت نتیجه</dt>
                <dd><?= wave7a_detail_h(moghare360_soft_run_pilot_execution_status_label((string)($record['result_status'] ?? ''))) ?>
                    (<?= wave7a_detail_h((string)($record['result_status'] ?? '')) ?>)</dd>
                <dt>خلاصه مشاهده</dt>
                <dd><?= wave7a_detail_h((string)($record['observed_summary'] ?? '—')) ?></dd>
                <dt>شواهد مورد انتظار</dt>
                <dd><?= wave7a_detail_h((string)($record['expected_evidence'] ?? '—')) ?></dd>
                <dt>شواهد واقعی</dt>
                <dd><?= wave7a_detail_h((string)($record['actual_evidence'] ?? '—')) ?></dd>
                <dt>یادداشت مسدودکننده</dt>
                <dd><?= wave7a_detail_h((string)($record['blocker_notes'] ?? '—')) ?></dd>
                <dt>یادداشت داخلی</dt>
                <dd><?= wave7a_detail_h((string)($record['internal_notes'] ?? '—')) ?></dd>
                <dt>شروع</dt>
                <dd><?= wave7a_detail_h((string)($record['started_at'] ?? '—')) ?></dd>
                <dt>پایان</dt>
                <dd><?= wave7a_detail_h((string)($record['completed_at'] ?? '—')) ?></dd>
                <dt>ایجاد</dt>
                <dd><?= wave7a_detail_h((string)($record['created_at'] ?? '')) ?></dd>
                <dt>به‌روزرسانی</dt>
                <dd><?= wave7a_detail_h((string)($record['updated_at'] ?? '—')) ?></dd>
            </dl>
        </section>

        <section class="w1c-card">
            <h2 style="margin:0 0 0.75rem;font-size:1rem;">تاریخچه تغییرات</h2>
            <?php if ($historyRows === []): ?>
                <p style="margin:0;font-size:0.9rem;color:#525252;">تاریخچه‌ای یافت نشد.</p>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="w7a-table">
                        <thead>
                        <tr>
                            <th>شناسه</th>
                            <th>وضعیت اجرا قبلی</th>
                            <th>وضعیت اجرا جدید</th>
                            <th>نتیجه قبلی</th>
                            <th>نتیجه جدید</th>
                            <th>دلیل</th>
                            <th>زمان</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($historyRows as $historyRow): ?>
                            <tr>
                                <td><?= wave7a_detail_h((string)($historyRow['history_id'] ?? '')) ?></td>
                                <td><?= wave7a_detail_h((string)($historyRow['old_execution_status'] ?? '—')) ?></td>
                                <td><?= wave7a_detail_h((string)($historyRow['new_execution_status'] ?? '')) ?></td>
                                <td><?= wave7a_detail_h((string)($historyRow['old_result_status'] ?? '—')) ?></td>
                                <td><?= wave7a_detail_h((string)($historyRow['new_result_status'] ?? '—')) ?></td>
                                <td><?= wave7a_detail_h((string)($historyRow['change_reason'] ?? '')) ?></td>
                                <td><?= wave7a_detail_h((string)($historyRow['changed_at'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <nav class="w1c-card w1c-links w7a-nav">
        <a href="erp-soft-run-pilot-execution-board.php">بازگشت به برد اجرا</a>
        <a href="erp-soft-run-pilot-review-dashboard.php">داشبورد بازبینی اجرای پایلوت</a>
        <?php if ($executionId > 0 && ($detail['ok'] ?? false)): ?>
            <a href="erp-soft-run-pilot-execution-workflow.php?execution_id=<?= wave7a_detail_h((string)$executionId) ?>">گردش کار اجرا</a>
        <?php endif; ?>
        <a href="erp-soft-run-pilot-execution-create.php">ثبت اجرای پایلوت جدید</a>
        <a href="erp-soft-run-final-closure-dashboard.php">داشبورد نهایی آمادگی پایلوت</a>
    </nav>
</div>
</body>
</html>

<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Soft Run Finding Detail (Wave 8A)
 * Read-only · no POST · no DB write from page
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-finding-helper.php';

function wave8a_detail_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$findingIdRaw = trim((string)($_GET['finding_id'] ?? ''));
$findingId = ($findingIdRaw !== '' && ctype_digit($findingIdRaw) && (int)$findingIdRaw >= 1)
    ? (int)$findingIdRaw
    : 0;

$detail = $findingId > 0 ? moghare360_soft_run_finding_fetch_detail($findingId) : null;
$history = $findingId > 0 ? moghare360_soft_run_finding_fetch_history($findingId) : null;
$record = ($detail !== null && ($detail['ok'] ?? false)) ? (array)($detail['record'] ?? []) : [];
$historyRows = ($history !== null && ($history['ok'] ?? false)) ? (array)($history['history'] ?? []) : [];

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>جزئیات یافته Soft Run</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w8a-wrap">
    <header class="w1c-banner w8a-banner">
        <h1>جزئیات یافته Soft Run</h1>
        <p>WAVE 8A — Read-only Soft Run Finding Detail</p>
    </header>

    <section class="w1c-card w8a-warning">
        <strong>Read-only internal finding detail — not final delivery. Not delivery completion.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">پورتال عمومی، پرداخت، حسابداری و امضای الکترونیکی قانونی نهایی فعال نیست.</p>
    </section>

    <?php if ($findingId < 1): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;">شناسه یافته نامعتبر است.</p>
        </section>
    <?php elseif ($detail === null || !($detail['ok'] ?? false)): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave8a_detail_h((string)($detail['message'] ?? MOGHARE360_SOFT_RUN_FINDING_BLOCK_MESSAGE)) ?></p>
        </section>
    <?php else: ?>
        <section class="w1c-card w8a-detail">
            <h2 style="margin:0 0 0.75rem;font-size:1rem;">رکورد یافته</h2>
            <dl class="w8a-dl">
                <dt>شناسه یافته</dt>
                <dd><?= wave8a_detail_h((string)($record['finding_id'] ?? '')) ?></dd>
                <dt>کد یافته</dt>
                <dd><?= wave8a_detail_h((string)($record['finding_code'] ?? '')) ?></dd>
                <dt>شناسه اجرای پایلوت</dt>
                <dd><?= wave8a_detail_h((string)($record['execution_id'] ?? '—')) ?></dd>
                <dt>شناسه کارت کار</dt>
                <dd><?= wave8a_detail_h((string)($record['jobcard_id'] ?? '—')) ?></dd>
                <dt>نوع یافته</dt>
                <dd><?= wave8a_detail_h(moghare360_soft_run_finding_type_label((string)($record['finding_type'] ?? ''))) ?>
                    (<?= wave8a_detail_h((string)($record['finding_type'] ?? '')) ?>)</dd>
                <dt>سطح شدت</dt>
                <dd><?= wave8a_detail_h(moghare360_soft_run_finding_severity_label((string)($record['severity_level'] ?? ''))) ?>
                    (<?= wave8a_detail_h((string)($record['severity_level'] ?? '')) ?>)</dd>
                <dt>وضعیت یافته</dt>
                <dd><?= wave8a_detail_h(moghare360_soft_run_finding_status_label((string)($record['finding_status'] ?? ''))) ?>
                    (<?= wave8a_detail_h((string)($record['finding_status'] ?? '')) ?>)</dd>
                <dt>وضعیت اقدام اصلاحی</dt>
                <dd><?= wave8a_detail_h(moghare360_soft_run_finding_status_label((string)($record['corrective_action_status'] ?? ''))) ?>
                    (<?= wave8a_detail_h((string)($record['corrective_action_status'] ?? '')) ?>)</dd>
                <dt>عنوان</dt>
                <dd><?= wave8a_detail_h((string)($record['finding_title'] ?? '')) ?></dd>
                <dt>شرح</dt>
                <dd><?= wave8a_detail_h((string)($record['finding_description'] ?? '—')) ?></dd>
                <dt>رفتار مورد انتظار</dt>
                <dd><?= wave8a_detail_h((string)($record['expected_behavior'] ?? '—')) ?></dd>
                <dt>رفتار واقعی</dt>
                <dd><?= wave8a_detail_h((string)($record['actual_behavior'] ?? '—')) ?></dd>
                <dt>اقدام اصلاحی</dt>
                <dd><?= wave8a_detail_h((string)($record['corrective_action'] ?? '—')) ?></dd>
                <dt>شناسه مسئول</dt>
                <dd><?= wave8a_detail_h((string)($record['owner_user_id'] ?? '—')) ?></dd>
                <dt>مهلت انجام</dt>
                <dd><?= wave8a_detail_h((string)($record['due_at'] ?? '—')) ?></dd>
                <dt>زمان رفع</dt>
                <dd><?= wave8a_detail_h((string)($record['resolved_at'] ?? '—')) ?></dd>
                <dt>ایجاد</dt>
                <dd><?= wave8a_detail_h((string)($record['created_at'] ?? '')) ?></dd>
                <dt>به‌روزرسانی</dt>
                <dd><?= wave8a_detail_h((string)($record['updated_at'] ?? '—')) ?></dd>
            </dl>
        </section>

        <section class="w1c-card">
            <h2 style="margin:0 0 0.75rem;font-size:1rem;">تاریخچه تغییرات</h2>
            <?php if ($historyRows === []): ?>
                <p style="margin:0;font-size:0.9rem;color:#525252;">تاریخچه‌ای یافت نشد.</p>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="w8a-table">
                        <thead>
                        <tr>
                            <th>شناسه</th>
                            <th>وضعیت یافته قبلی</th>
                            <th>وضعیت یافته جدید</th>
                            <th>اقدام اصلاحی قبلی</th>
                            <th>اقدام اصلاحی جدید</th>
                            <th>دلیل</th>
                            <th>زمان</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($historyRows as $historyRow): ?>
                            <tr>
                                <td><?= wave8a_detail_h((string)($historyRow['history_id'] ?? '')) ?></td>
                                <td><?= wave8a_detail_h((string)($historyRow['old_finding_status'] ?? '—')) ?></td>
                                <td><?= wave8a_detail_h((string)($historyRow['new_finding_status'] ?? '')) ?></td>
                                <td><?= wave8a_detail_h((string)($historyRow['old_corrective_action_status'] ?? '—')) ?></td>
                                <td><?= wave8a_detail_h((string)($historyRow['new_corrective_action_status'] ?? '—')) ?></td>
                                <td><?= wave8a_detail_h((string)($historyRow['change_reason'] ?? '')) ?></td>
                                <td><?= wave8a_detail_h((string)($historyRow['changed_at'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <nav class="w1c-card w1c-links w8a-nav">
        <a href="erp-soft-run-finding-board.php">بازگشت به برد یافته‌ها</a>
        <a href="erp-soft-run-finding-create.php">ثبت یافته جدید</a>
        <a href="erp-soft-run-pilot-final-closure-dashboard.php">داشبورد نهایی بستن اجرای پایلوت</a>
        <a href="erp-soft-run-pilot-review-dashboard.php">داشبورد بازبینی اجرای پایلوت</a>
    </nav>
</div>
</body>
</html>

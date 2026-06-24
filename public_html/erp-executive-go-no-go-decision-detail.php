<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Executive Go/No-Go Decision Detail (Wave 9B)
 * Read-only · no POST · no DB write from page
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-executive-go-no-go-decision-helper.php';

function wave9b_detail_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$decisionId = (int)($_GET['decision_id'] ?? 0);
$detail = moghare360_executive_go_no_go_decision_fetch_detail($decisionId);
$history = ($detail['ok'] ?? false)
    ? moghare360_executive_go_no_go_decision_fetch_history($decisionId)
    : ['ok' => false, 'history' => []];
$record = (array)($detail['record'] ?? []);
$historyRows = (array)($history['history'] ?? []);

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>جزئیات تصمیم مدیریتی Go/No-Go</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w9b-wrap">
    <header class="w1c-banner w9b-banner">
        <h1>جزئیات تصمیم مدیریتی Go/No-Go</h1>
        <p>WAVE 9B — Read-only Executive Decision Detail</p>
    </header>

    <section class="w1c-card w9b-warning">
        <strong>Read-only executive decision detail — not final delivery approval. Not delivery completion.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">پورتال عمومی، پرداخت، حسابداری و امضای الکترونیکی قانونی نهایی فعال نیست.</p>
    </section>

    <?php if (!($detail['ok'] ?? false)): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave9b_detail_h((string)($detail['message'] ?? 'رکورد یافت نشد.')) ?></p>
        </section>
    <?php else: ?>
        <section class="w1c-card">
            <h2 style="margin:0 0 0.75rem;font-size:1rem;">رکورد تصمیم</h2>
            <dl class="w9b-dl">
                <dt>شناسه / کد</dt>
                <dd><?= wave9b_detail_h((string)($record['decision_id'] ?? '')) ?>
                    / <?= wave9b_detail_h((string)($record['decision_code'] ?? '')) ?></dd>
                <dt>وضعیت آمادگی مدیریتی</dt>
                <dd><?= wave9b_detail_h(moghare360_executive_go_no_go_decision_status_label((string)($record['executive_readiness_status'] ?? ''))) ?>
                    (<?= wave9b_detail_h((string)($record['executive_readiness_status'] ?? '')) ?>)</dd>
                <dt>WAVE 6 / 7 / 8</dt>
                <dd>
                    <?= wave9b_detail_h((string)($record['wave6_status'] ?? '—')) ?> /
                    <?= wave9b_detail_h((string)($record['wave7_status'] ?? '—')) ?> /
                    <?= wave9b_detail_h((string)($record['wave8_status'] ?? '—')) ?>
                </dd>
                <dt>نوع تصمیم</dt>
                <dd><?= wave9b_detail_h(moghare360_executive_go_no_go_decision_type_label((string)($record['decision_type'] ?? ''))) ?>
                    (<?= wave9b_detail_h((string)($record['decision_type'] ?? '')) ?>)</dd>
                <dt>وضعیت تصمیم</dt>
                <dd><?= wave9b_detail_h(moghare360_executive_go_no_go_decision_status_label((string)($record['decision_status'] ?? ''))) ?>
                    (<?= wave9b_detail_h((string)($record['decision_status'] ?? '')) ?>)</dd>
                <dt>عنوان</dt>
                <dd><?= wave9b_detail_h((string)($record['decision_title'] ?? '')) ?></dd>
                <dt>خلاصه</dt>
                <dd><?= wave9b_detail_h((string)($record['decision_summary'] ?? '—')) ?></dd>
                <dt>دلیل مدیریتی</dt>
                <dd><?= wave9b_detail_h((string)($record['management_reason'] ?? '')) ?></dd>
                <dt>اقدام مورد نیاز</dt>
                <dd><?= wave9b_detail_h((string)($record['required_action_summary'] ?? '—')) ?></dd>
                <dt>یادداشت ریسک</dt>
                <dd><?= wave9b_detail_h((string)($record['risk_note'] ?? '—')) ?></dd>
                <dt>شناسه یافته / اجرای پایلوت</dt>
                <dd><?= wave9b_detail_h((string)($record['finding_id'] ?? '—')) ?>
                    / <?= wave9b_detail_h((string)($record['pilot_execution_id'] ?? '—')) ?></dd>
                <dt>تصمیم‌گیرنده</dt>
                <dd><?= wave9b_detail_h((string)($record['decided_by_user_id'] ?? '—')) ?></dd>
                <dt>مهلت پیگیری</dt>
                <dd><?= wave9b_detail_h((string)($record['decision_due_at'] ?? '—')) ?></dd>
                <dt>ایجاد / به‌روزرسانی</dt>
                <dd><?= wave9b_detail_h((string)($record['created_at'] ?? '')) ?>
                    / <?= wave9b_detail_h((string)($record['updated_at'] ?? '—')) ?></dd>
            </dl>
        </section>

        <section class="w1c-card">
            <h2 style="margin:0 0 0.75rem;font-size:1rem;">تاریخچه تغییرات</h2>
            <?php if ($historyRows === []): ?>
                <p style="margin:0;font-size:0.9rem;color:#525252;">تاریخچه‌ای یافت نشد.</p>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="w9b-table">
                        <thead>
                        <tr>
                            <th>شناسه</th>
                            <th>وضعیت قبلی</th>
                            <th>وضعیت جدید</th>
                            <th>نوع قبلی</th>
                            <th>نوع جدید</th>
                            <th>دلیل</th>
                            <th>زمان</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($historyRows as $historyRow): ?>
                            <tr>
                                <td><?= wave9b_detail_h((string)($historyRow['history_id'] ?? '')) ?></td>
                                <td><?= wave9b_detail_h((string)($historyRow['old_decision_status'] ?? '—')) ?></td>
                                <td><?= wave9b_detail_h((string)($historyRow['new_decision_status'] ?? '')) ?></td>
                                <td><?= wave9b_detail_h((string)($historyRow['old_decision_type'] ?? '—')) ?></td>
                                <td><?= wave9b_detail_h((string)($historyRow['new_decision_type'] ?? '—')) ?></td>
                                <td><?= wave9b_detail_h((string)($historyRow['change_reason'] ?? '')) ?></td>
                                <td><?= wave9b_detail_h((string)($historyRow['changed_at'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <nav class="w1c-card w1c-links w9b-nav">
        <a href="erp-executive-go-no-go-decision-board.php">بازگشت به برد تصمیم‌ها</a>
        <a href="erp-executive-go-no-go-decision-create.php">ثبت تصمیم جدید</a>
        <a href="erp-executive-soft-run-readiness-dashboard.php">داشبورد آمادگی مدیریتی Soft Run</a>
    </nav>
</div>
</body>
</html>

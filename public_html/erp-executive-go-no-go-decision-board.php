<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Executive Go/No-Go Decision Board (Wave 9B)
 * Read-only · no POST · no DB write from page
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-executive-go-no-go-decision-helper.php';

function wave9b_board_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$schema = moghare360_executive_go_no_go_decision_schema_status();
$fetch = moghare360_executive_go_no_go_decision_fetch_recent(25);
$records = (array)($fetch['records'] ?? []);
$typeCounts = (array)($fetch['counts']['decision_type'] ?? []);
$statusCounts = (array)($fetch['counts']['decision_status'] ?? []);
$readinessCounts = (array)($fetch['counts']['executive_readiness_status'] ?? []);

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>برد تصمیم‌های مدیریتی Go/No-Go</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w9b-wrap">
    <header class="w1c-banner w9b-banner">
        <h1>برد تصمیم‌های مدیریتی Go/No-Go</h1>
        <p>WAVE 9B — Read-only Executive Go/No-Go Decision Board</p>
    </header>

    <section class="w1c-card w9b-warning">
        <strong>Read-only executive decision board — not final delivery approval. Not delivery completion.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">پورتال عمومی، پرداخت، حسابداری و امضای الکترونیکی قانونی نهایی فعال نیست.</p>
    </section>

    <?php if (($schema['schema_status'] ?? '') === MOGHARE360_EXECUTIVE_GO_NO_GO_SCHEMA_BLOCKED): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave9b_board_h(MOGHARE360_EXECUTIVE_GO_NO_GO_BLOCK_MESSAGE) ?></p>
            <p style="margin:0.5rem 0 0;font-size:0.85rem;">پس از اجرای wave_9b_executive_go_no_go_decision_log.sql در SSMS، رکوردها نمایش داده می‌شوند.</p>
        </section>
    <?php endif; ?>

    <section class="w1c-card w9b-counts">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">شمارش بر اساس نوع تصمیم</h2>
        <div class="w9b-count-grid">
            <?php foreach ($typeCounts as $type => $count): ?>
                <div class="w9b-count-item">
                    <span class="w9b-count-label"><?= wave9b_board_h(moghare360_executive_go_no_go_decision_type_label((string)$type)) ?></span>
                    <strong><?= wave9b_board_h((string)$count) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="w1c-card w9b-counts">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">شمارش بر اساس وضعیت تصمیم</h2>
        <div class="w9b-count-grid">
            <?php foreach ($statusCounts as $status => $count): ?>
                <div class="w9b-count-item">
                    <span class="w9b-count-label"><?= wave9b_board_h(moghare360_executive_go_no_go_decision_status_label((string)$status)) ?></span>
                    <strong><?= wave9b_board_h((string)$count) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="w1c-card w9b-counts">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">شمارش بر اساس وضعیت آمادگی مدیریتی</h2>
        <div class="w9b-count-grid">
            <?php foreach ($readinessCounts as $status => $count): ?>
                <div class="w9b-count-item">
                    <span class="w9b-count-label"><?= wave9b_board_h(moghare360_executive_go_no_go_decision_status_label((string)$status)) ?></span>
                    <strong><?= wave9b_board_h((string)$count) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="w1c-card">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">تصمیم‌های اخیر</h2>
        <?php if ($records === []): ?>
            <p style="margin:0;font-size:0.9rem;color:#525252;">هنوز رکورد تصمیمی ثبت نشده است.</p>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="w9b-table">
                    <thead>
                    <tr>
                        <th>شناسه</th>
                        <th>کد</th>
                        <th>عنوان</th>
                        <th>نوع</th>
                        <th>وضعیت</th>
                        <th>آمادگی مدیریتی</th>
                        <th>ایجاد</th>
                        <th>جزئیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($records as $record): ?>
                        <?php $id = (int)($record['decision_id'] ?? 0); ?>
                        <tr>
                            <td><?= wave9b_board_h((string)$id) ?></td>
                            <td><?= wave9b_board_h((string)($record['decision_code'] ?? '')) ?></td>
                            <td><?= wave9b_board_h((string)($record['decision_title'] ?? '')) ?></td>
                            <td><?= wave9b_board_h(moghare360_executive_go_no_go_decision_type_label((string)($record['decision_type'] ?? ''))) ?></td>
                            <td><?= wave9b_board_h(moghare360_executive_go_no_go_decision_status_label((string)($record['decision_status'] ?? ''))) ?></td>
                            <td><?= wave9b_board_h((string)($record['executive_readiness_status'] ?? '')) ?></td>
                            <td><?= wave9b_board_h((string)($record['created_at'] ?? '')) ?></td>
                            <td>
                                <?php if ($id > 0): ?>
                                    <a href="erp-executive-go-no-go-decision-detail.php?decision_id=<?= wave9b_board_h((string)$id) ?>">مشاهده</a>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <nav class="w1c-card w1c-links w9b-nav">
        <a href="erp-executive-go-no-go-decision-create.php">ثبت تصمیم مدیریتی Go/No-Go</a>
        <a href="erp-executive-soft-run-readiness-dashboard.php">داشبورد آمادگی مدیریتی Soft Run</a>
    </nav>
</div>
</body>
</html>

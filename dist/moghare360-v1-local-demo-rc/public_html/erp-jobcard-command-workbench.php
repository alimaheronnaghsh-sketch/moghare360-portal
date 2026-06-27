<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — JobCard Command Operator Workbench (Wave 5B)
 * Read-only · no DB write · no POST
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-jobcard-command-workbench-helper.php';

function wave5b_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$listResult = moghare360_jobcard_command_workbench_fetch_jobcards(25);
$jobcards = (array)($listResult['jobcards'] ?? []);
$summary = moghare360_jobcard_command_workbench_status_summary($jobcards);

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>میز فرمان کارت کار</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w5b-wrap">
    <header class="w1c-banner w5b-banner">
        <h1>میز فرمان عملیاتی کارت کار</h1>
        <p>WAVE 5B — JobCard Command Operator Workbench</p>
    </header>

    <section class="w1c-card w5b-warning">
        <strong>This is read-only operator navigation — not final vehicle delivery. not legal final e-signature.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">تحویل نهایی خودرو انجام نمی‌شود. رکورد تکمیل تحویل ایجاد نمی‌شود. پورتال عمومی و پرداخت فعال نیست.</p>
    </section>

    <?php if (!($listResult['ok'] ?? false)): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave5b_h((string)($listResult['message'] ?? 'خواندن لیست کارت کار ناموفق بود.')) ?></p>
        </section>
    <?php else: ?>
        <section class="w1c-card">
            <h2 style="margin:0 0 0.75rem;font-size:1rem;">خلاصه میز فرمان</h2>
            <div class="w5b-stat-grid">
                <div class="w5b-stat"><span>کل کارت کارها</span><strong><?= wave5b_h((string)($summary['total'] ?? 0)) ?></strong></div>
            </div>
        </section>

        <?php if (!empty($summary['by_jobcard_status'])): ?>
            <section class="w1c-card">
                <h2 style="margin:0 0 0.5rem;font-size:1rem;">بر اساس jobcard_status</h2>
                <ul style="margin:0;padding-right:1.25rem;font-size:0.9rem;">
                    <?php foreach ($summary['by_jobcard_status'] as $key => $count): ?>
                        <li><?= wave5b_h((string)$key) ?>: <strong><?= wave5b_h((string)$count) ?></strong></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <?php if (!empty($summary['by_lifecycle_state'])): ?>
            <section class="w1c-card">
                <h2 style="margin:0 0 0.5rem;font-size:1rem;">بر اساس lifecycle_state</h2>
                <ul style="margin:0;padding-right:1.25rem;font-size:0.9rem;">
                    <?php foreach ($summary['by_lifecycle_state'] as $key => $count): ?>
                        <li><?= wave5b_h((string)$key) ?>: <strong><?= wave5b_h((string)$count) ?></strong></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <?php if (!empty($summary['by_unified_status'])): ?>
            <section class="w1c-card">
                <h2 style="margin:0 0 0.5rem;font-size:1rem;">بر اساس وضعیت عملیاتی یکپارچه</h2>
                <ul style="margin:0;padding-right:1.25rem;font-size:0.9rem;">
                    <?php foreach ($summary['by_unified_status'] as $key => $count): ?>
                        <li><?= wave5b_h(moghare360_jobcard_command_workbench_status_label((string)$key)) ?> (<?= wave5b_h((string)$key) ?>): <strong><?= wave5b_h((string)$count) ?></strong></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <?php if ($jobcards !== []): ?>
            <section class="w1c-card">
                <h2 style="margin:0 0 0.75rem;font-size:1rem;">لیست کارت کارها</h2>
                <div style="overflow-x:auto;">
                    <table class="w5b-table">
                        <thead>
                        <tr>
                            <th>شناسه</th>
                            <th>شماره</th>
                            <th>وضعیت</th>
                            <th>چرخه</th>
                            <th>اولویت</th>
                            <th>پذیرش</th>
                            <th>ایجاد</th>
                            <th>وضعیت یکپارچه</th>
                            <th>اقدام</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($jobcards as $row): ?>
                            <?php
                            $jcId = (int)($row['jobcard_id'] ?? 0);
                            $links = moghare360_jobcard_command_workbench_build_links($jcId);
                            ?>
                            <tr>
                                <td><?= wave5b_h((string)($row['jobcard_id'] ?? '')) ?></td>
                                <td><?= wave5b_h((string)($row['jobcard_number'] ?? '')) ?></td>
                                <td><?= wave5b_h((string)($row['jobcard_status'] ?? '')) ?></td>
                                <td><?= wave5b_h((string)($row['lifecycle_state'] ?? '')) ?></td>
                                <td><?= wave5b_h((string)($row['priority_level'] ?? '')) ?></td>
                                <td><?= wave5b_h((string)($row['reception_at'] ?? '')) ?></td>
                                <td><?= wave5b_h((string)($row['created_at'] ?? '')) ?></td>
                                <td>
                                    <?= wave5b_h((string)($row['unified_status_label'] ?? '')) ?>
                                    <?php if (!empty($row['unified_status'])): ?>
                                        <span style="font-size:0.8rem;color:#525252;">(<?= wave5b_h((string)$row['unified_status']) ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="w5b-action-links">
                                    <a href="<?= wave5b_h($links['command_center']) ?>">فرمان</a>
                                    <a href="<?= wave5b_h($links['final_readiness']) ?>">آمادگی</a>
                                    <a href="<?= wave5b_h($links['delivery_eligibility']) ?>">صلاحیت</a>
                                    <a href="<?= wave5b_h($links['clearance_preview']) ?>">Clearance</a>
                                    <a href="<?= wave5b_h($links['evidence_review']) ?>">مدارک</a>
                                    <a href="<?= wave5b_h($links['authorization_gate']) ?>">مجوز</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php else: ?>
            <section class="w1c-card w1c-note">
                <p style="margin:0;">هیچ کارت کاری در erp_jobcards یافت نشد.</p>
            </section>
        <?php endif; ?>
    <?php endif; ?>

    <section class="w1c-card w1c-note">
        <p style="margin:0;font-size:0.88rem;"><?= wave5b_h(MOGHARE360_JOBCARD_COMMAND_WORKBENCH_INTERNAL_NOTICE) ?></p>
    </section>

    <nav class="w1c-card w1c-links w5b-nav">
        <a href="erp-jobcard-command-center.php?jobcard_id=1">مرکز فرمان (نمونه)</a>
        <a href="erp-jobcard-final-readiness.php?jobcard_id=1">آمادگی نهایی (نمونه)</a>
        <a href="erp-jobcard-delivery-eligibility.php?jobcard_id=1">صلاحیت تحویل (نمونه)</a>
        <a href="erp-jobcard-delivery-clearance-preview.php?jobcard_id=1">سوابق Clearance (نمونه)</a>
        <a href="erp-jobcard-evidence-review.php?jobcard_id=1">بازبینی مدارک (نمونه)</a>
        <a href="erp-jobcard-authorization-gate.php?jobcard_id=1">گیت مجوز (نمونه)</a>
        <a href="erp-media-evidence-closure-dashboard.php">داشبورد بستن WAVE 2</a>
        <a href="erp-authorization-closure-dashboard.php">داشبورد بستن WAVE 3</a>
        <a href="erp-delivery-control-closure-dashboard.php">داشبورد بستن WAVE 4</a>
    </nav>
</div>
</body>
</html>

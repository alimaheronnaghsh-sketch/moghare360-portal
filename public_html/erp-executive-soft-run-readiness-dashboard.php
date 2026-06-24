<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Executive Soft Run Readiness Dashboard (Wave 9A)
 * Read-only · no POST · no DB write
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-executive-soft-run-readiness-helper.php';

function wave9a_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$evaluation = moghare360_executive_soft_run_readiness_evaluate();
$summary = (array)($evaluation['summary'] ?? []);
$wave6 = (array)($evaluation['wave6'] ?? []);
$wave7 = (array)($evaluation['wave7'] ?? []);
$wave8 = (array)($evaluation['wave8'] ?? []);
$snapshot = (array)($evaluation['snapshot'] ?? []);
$pageStatus = (array)($evaluation['pages'] ?? []);
$pages = (array)($pageStatus['pages'] ?? []);
$decisionNotes = (array)($evaluation['decision_notes'] ?? []);

$statusClass = match ($evaluation['status'] ?? '') {
    MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_READY => 'w9a-status-ready',
    MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_GO_REVIEW => 'w9a-status-review',
    MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_BLOCKED => 'w9a-status-blocked',
    MOGHARE360_EXECUTIVE_SOFT_RUN_READINESS_STATUS_EMPTY => 'w9a-status-empty',
    default => 'w9a-status-error',
};

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>داشبورد آمادگی مدیریتی Soft Run</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w9a-wrap">
    <header class="w1c-banner w9a-banner">
        <h1>داشبورد آمادگی مدیریتی Soft Run</h1>
        <p>WAVE 9A — Executive Soft Run Readiness & Go/No-Go Control Foundation</p>
    </header>

    <section class="w1c-card w9a-warning">
        <strong>Read-only executive review — not final vehicle delivery. Not delivery completion. Not legal e-signature. Not payment/accounting. Not production activation.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">این داشبورد فقط تجمیع آمادگی WAVE 6/7/8 برای بازبینی مدیریتی است — تأیید تحویل نهایی نیست.</p>
    </section>

    <section class="w1c-card <?= wave9a_h($statusClass) ?>">
        <h2 style="margin:0 0 0.5rem;font-size:1rem;">وضعیت آمادگی مدیریتی</h2>
        <p style="margin:0;">
            <strong><?= wave9a_h(moghare360_executive_soft_run_readiness_status_label((string)($evaluation['status'] ?? ''))) ?></strong>
            (<?= wave9a_h((string)($evaluation['status'] ?? '')) ?>)
        </p>
        <p style="margin:0.5rem 0 0;"><?= wave9a_h((string)($evaluation['message'] ?? '')) ?></p>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;color:#525252;">
            <strong>تفسیر Go/No-Go:</strong> <?= wave9a_h((string)($evaluation['go_interpretation'] ?? '')) ?>
        </p>
    </section>

    <section class="w1c-card w9a-wave-grid">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">وضعیت لایه‌های Soft Run</h2>
        <dl class="w9a-dl">
            <dt>WAVE 6 — بستن نهایی</dt>
            <dd>
                <?= wave9a_h((string)($wave6['label'] ?? '—')) ?>
                (<?= wave9a_h((string)($wave6['status'] ?? '')) ?>)
            </dd>
            <dt>WAVE 7 — اجرای پایلوت</dt>
            <dd>
                <?= wave9a_h((string)($wave7['label'] ?? '—')) ?>
                (<?= wave9a_h((string)($wave7['status'] ?? '')) ?>)
            </dd>
            <dt>WAVE 8 — یافته‌ها</dt>
            <dd>
                <?= wave9a_h((string)($wave8['label'] ?? '—')) ?>
                (<?= wave9a_h((string)($wave8['status'] ?? '')) ?>)
            </dd>
        </dl>
    </section>

    <?php if (!empty($evaluation['errors'])): ?>
        <section class="w1c-card w1c-error-box">
            <ul style="margin:0;padding-right:1.25rem;">
                <?php foreach ($evaluation['errors'] as $error): ?>
                    <li><?= wave9a_h((string)$error) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <section class="w1c-card">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">خلاصه اجرای پایلوت (WAVE 7)</h2>
        <dl class="w9a-dl">
            <dt>کل رکوردهای اجرای پایلوت</dt>
            <dd><?= wave9a_h((string)($summary['total_pilot_executions'] ?? 0)) ?></dd>
            <dt>پیام WAVE 7</dt>
            <dd><?= wave9a_h((string)($wave7['message'] ?? '—')) ?></dd>
        </dl>
    </section>

    <section class="w1c-card">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">خلاصه یافته‌ها و اقدام اصلاحی (WAVE 8)</h2>
        <dl class="w9a-dl">
            <dt>کل یافته‌ها</dt>
            <dd><?= wave9a_h((string)($summary['total_findings'] ?? 0)) ?></dd>
            <dt>کل ردیف‌های تاریخچه</dt>
            <dd><?= wave9a_h((string)($summary['total_finding_history_rows'] ?? 0)) ?></dd>
            <dt>پوشش تاریخچه</dt>
            <dd>
                <?php if (($summary['history_coverage_percent'] ?? null) !== null): ?>
                    <?= wave9a_h((string)$summary['history_coverage_percent']) ?>%
                <?php else: ?>
                    —
                <?php endif; ?>
            </dd>
            <dt>باز / در بازبینی / نیازمند اقدام</dt>
            <dd>
                <?= wave9a_h((string)($summary['open_finding_count'] ?? 0)) ?> /
                <?= wave9a_h((string)($summary['under_review_finding_count'] ?? 0)) ?> /
                <?= wave9a_h((string)($summary['action_required_finding_count'] ?? 0)) ?>
            </dd>
            <dt>حل‌شده / بسته</dt>
            <dd>
                <?= wave9a_h((string)($summary['resolved_finding_count'] ?? 0)) ?> /
                <?= wave9a_h((string)($summary['closed_finding_count'] ?? 0)) ?>
            </dd>
            <dt>اقدام اصلاحی در حال انجام / مسدود</dt>
            <dd>
                <?= wave9a_h((string)($summary['corrective_in_progress_count'] ?? 0)) ?> /
                <?= wave9a_h((string)($summary['corrective_blocked_count'] ?? 0)) ?>
            </dd>
            <dt>HIGH / CRITICAL حل‌نشده</dt>
            <dd>
                <?= wave9a_h((string)($summary['high_unresolved_count'] ?? 0)) ?> /
                <?= wave9a_h((string)($summary['critical_unresolved_count'] ?? 0)) ?>
            </dd>
        </dl>
    </section>

    <section class="w1c-card">
        <h2 style="margin:0 0 0.75rem;font-size:1rem;">صفحات زمان اجرای مورد نیاز</h2>
        <p style="margin:0 0 0.5rem;font-size:0.85rem;color:#525252;">
            موجود: <strong><?= wave9a_h((string)($summary['required_pages_present_count'] ?? 0)) ?></strong>
            / <?= wave9a_h((string)($summary['required_pages_total_count'] ?? 0)) ?>
        </p>
        <div style="overflow-x:auto;">
            <table class="w9a-table">
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
                        <td><?= wave9a_h($pagePath) ?></td>
                        <td><?= wave9a_h((string)($pageRow['label_fa'] ?? '')) ?></td>
                        <td><?= wave9a_h((string)($pageRow['status'] ?? '')) ?></td>
                        <td>
                            <?php if (!empty($pageRow['exists'])): ?>
                                <a href="<?= wave9a_h($pagePath) ?>">باز کردن</a>
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
        'review_items' => 'نیازمند بازبینی',
        'blocked_items' => 'مسدود',
        'missing_items' => 'مفقود',
    ] as $listKey => $listTitle): ?>
        <?php if (!empty($evaluation[$listKey]) && is_array($evaluation[$listKey])): ?>
            <section class="w1c-card">
                <h2 style="margin:0 0 0.5rem;font-size:1rem;"><?= wave9a_h($listTitle) ?></h2>
                <ul style="margin:0;padding-right:1.25rem;font-size:0.9rem;">
                    <?php foreach ($evaluation[$listKey] as $item): ?>
                        <li><?= wave9a_h((string)$item) ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php if ($decisionNotes !== []): ?>
        <section class="w1c-card w1c-note">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">یادداشت تصمیم مدیریتی (Executive Decision Note)</h2>
            <ul style="margin:0;padding-right:1.25rem;font-size:0.88rem;">
                <?php foreach ($decisionNotes as $note): ?>
                    <li><?= wave9a_h((string)$note) ?></li>
                <?php endforeach; ?>
            </ul>
            <p style="margin:0.75rem 0 0;font-size:0.85rem;color:#525252;">
                <?= wave9a_h((string)($summary['final_management_note'] ?? '')) ?>
            </p>
        </section>
    <?php endif; ?>

    <nav class="w1c-card w1c-links w9a-nav">
        <a href="erp-executive-go-no-go-decision-create.php">ثبت تصمیم مدیریتی Go/No-Go</a>
        <a href="erp-executive-go-no-go-decision-board.php">برد تصمیم‌های مدیریتی Go/No-Go</a>
        <a href="erp-soft-run-control-room.php">اتاق کنترل Soft Run</a>
        <a href="erp-soft-run-final-closure-dashboard.php">داشبورد نهایی آمادگی پایلوت WAVE 6</a>
        <a href="erp-soft-run-pilot-final-closure-dashboard.php">داشبورد نهایی بستن اجرای پایلوت WAVE 7</a>
        <a href="erp-soft-run-finding-final-closure-dashboard.php">داشبورد نهایی بستن یافته‌های Soft Run WAVE 8</a>
        <a href="erp-soft-run-finding-review-dashboard.php">داشبورد بازبینی یافته‌های Soft Run</a>
        <a href="erp-soft-run-finding-board.php">برد یافته‌های Soft Run</a>
        <a href="erp-soft-run-pilot-review-dashboard.php">داشبورد بازبینی اجرای پایلوت WAVE 7</a>
    </nav>
</div>
</body>
</html>

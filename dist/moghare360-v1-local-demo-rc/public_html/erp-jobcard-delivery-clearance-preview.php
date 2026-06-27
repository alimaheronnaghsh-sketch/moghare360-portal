<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — JobCard Delivery Clearance Preview (Wave 4C)
 * Read-only — no DB write
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-delivery-clearance-helper.php';

function wave4c_preview_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$jobcardIdRaw = trim((string)($_GET['jobcard_id'] ?? ''));
$invalidId = $jobcardIdRaw === '' || !ctype_digit($jobcardIdRaw) || (int)$jobcardIdRaw < 1;
$jobcardId = $invalidId ? 0 : (int)$jobcardIdRaw;
$schema = moghare360_delivery_clearance_schema_status();
$statusLabels = moghare360_delivery_clearance_status_labels();
$decisionLabels = moghare360_delivery_clearance_decision_labels();
$eligibility = $invalidId ? null : moghare360_delivery_clearance_fetch_eligibility($jobcardId);
$listResult = $invalidId ? null : moghare360_delivery_clearance_list_by_jobcard($jobcardId);
$historyResult = $invalidId ? null : moghare360_delivery_clearance_history_by_jobcard($jobcardId);

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>سوابق Clearance تحویل</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w4c-wrap">
    <header class="w1c-banner w4c-banner">
        <h1>سوابق Clearance تحویل</h1>
        <p>Wave 4C — Read-only clearance review</p>
    </header>

    <section class="w1c-card w4c-warning">
        <strong>This is internal delivery clearance only — not final vehicle delivery. Not legal final e-signature.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">این صفحه فقط خواندنی است — رکورد تحویل نهایی ایجاد نمی‌شود.</p>
    </section>

    <?php if ($invalidId): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;">شناسه کارت کار نامعتبر است. مقدار عددی مثبت وارد کنید.</p>
        </section>
    <?php elseif (($schema['schema_status'] ?? '') === MOGHARE360_DELIVERY_CLEARANCE_SCHEMA_BLOCKED): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave4c_preview_h(MOGHARE360_DELIVERY_CLEARANCE_BLOCK_MESSAGE) ?></p>
        </section>
    <?php else: ?>
        <?php if ($eligibility !== null): ?>
            <section class="w1c-card w4c-eligibility">
                <h2 style="margin:0 0 0.5rem;font-size:1rem;">صلاحیت تحویل — کارت کار <?= wave4c_preview_h((string)$jobcardId) ?></h2>
                <p style="margin:0;">
                    <strong><?= wave4c_preview_h(moghare360_delivery_eligibility_status_label((string)($eligibility['status'] ?? ''))) ?></strong>
                    (<?= wave4c_preview_h((string)($eligibility['status'] ?? '')) ?>)
                </p>
            </section>
        <?php endif; ?>

        <?php if ($listResult !== null && ($listResult['ok'] ?? false) === true && !empty($listResult['records'])): ?>
            <section class="w1c-card">
                <h2 style="margin:0 0 0.75rem;font-size:1rem;">رکوردهای Clearance</h2>
                <div style="overflow-x:auto;">
                    <table class="w4c-table">
                        <thead>
                        <tr>
                            <th>شناسه</th>
                            <th>وضعیت</th>
                            <th>تصمیم</th>
                            <th>بازبین</th>
                            <th>یادداشت</th>
                            <th>زمان</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($listResult['records'] as $row): ?>
                            <?php
                            $statusKey = (string)($row['clearance_status'] ?? '');
                            $decisionKey = (string)($row['clearance_decision'] ?? '');
                            ?>
                            <tr>
                                <td><?= wave4c_preview_h((string)($row['clearance_id'] ?? '')) ?></td>
                                <td><?= wave4c_preview_h($statusLabels[$statusKey] ?? $statusKey) ?></td>
                                <td><?= wave4c_preview_h($decisionLabels[$decisionKey] ?? $decisionKey) ?></td>
                                <td><?= wave4c_preview_h((string)($row['reviewer_name'] ?? '')) ?></td>
                                <td><?= wave4c_preview_h((string)($row['clearance_note'] ?? '')) ?></td>
                                <td><?= wave4c_preview_h((string)($row['created_at'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php elseif ($listResult !== null && ($listResult['ok'] ?? false) === true): ?>
            <section class="w1c-card w1c-note">
                <p style="margin:0;">رکورد Clearance برای کارت کار <strong><?= wave4c_preview_h((string)$jobcardId) ?></strong> یافت نشد.</p>
            </section>
        <?php endif; ?>

        <?php if ($historyResult !== null && ($historyResult['ok'] ?? false) === true && !empty($historyResult['records'])): ?>
            <section class="w1c-card">
                <h2 style="margin:0 0 0.75rem;font-size:1rem;">تاریخچه Clearance</h2>
                <div style="overflow-x:auto;">
                    <table class="w4c-table">
                        <thead>
                        <tr>
                            <th>شناسه</th>
                            <th>رویداد</th>
                            <th>وضعیت جدید</th>
                            <th>تصمیم</th>
                            <th>زمان</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($historyResult['records'] as $row): ?>
                            <tr>
                                <td><?= wave4c_preview_h((string)($row['history_id'] ?? '')) ?></td>
                                <td><?= wave4c_preview_h((string)($row['event_title'] ?? '')) ?></td>
                                <td><?= wave4c_preview_h($statusLabels[(string)($row['new_status'] ?? '')] ?? (string)($row['new_status'] ?? '')) ?></td>
                                <td><?= wave4c_preview_h($decisionLabels[(string)($row['clearance_decision'] ?? '')] ?? (string)($row['clearance_decision'] ?? '')) ?></td>
                                <td><?= wave4c_preview_h((string)($row['event_at'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    <?php endif; ?>

    <section class="w1c-card w1c-form">
        <form method="get" action="erp-jobcard-delivery-clearance-preview.php">
            <label for="jobcard_id_lookup">جستجوی شناسه کارت کار</label>
            <input type="number" id="jobcard_id_lookup" name="jobcard_id" min="1" value="<?= $invalidId ? '' : wave4c_preview_h((string)$jobcardId) ?>" required>
            <button type="submit" class="w1c-btn">نمایش</button>
        </form>
    </section>

    <nav class="w1c-card w1c-links w4c-nav">
        <a href="erp-jobcard-delivery-eligibility.php?jobcard_id=<?= wave4c_preview_h($invalidId ? '1' : (string)$jobcardId) ?>">بررسی صلاحیت تحویل</a>
        <a href="erp-jobcard-final-readiness.php?jobcard_id=<?= wave4c_preview_h($invalidId ? '1' : (string)$jobcardId) ?>">آمادگی نهایی</a>
        <a href="erp-jobcard-delivery-clearance.php?jobcard_id=<?= wave4c_preview_h($invalidId ? '1' : (string)$jobcardId) ?>">ثبت Clearance داخلی تحویل</a>
    </nav>
</div>
</body>
</html>

<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — JobCard Evidence Review (Wave 2D)
 * Read-only completeness gate · no DB write · no upload
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-jobcard-evidence-gate-helper.php';

function wave2d_review_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$jobcardIdRaw = trim((string)($_GET['jobcard_id'] ?? ''));
$invalidId = $jobcardIdRaw === '' || !ctype_digit($jobcardIdRaw) || (int)$jobcardIdRaw < 1;
$jobcardId = $invalidId ? 0 : (int)$jobcardIdRaw;
$review = $invalidId
    ? [
        'ok' => false,
        'status' => MOGHARE360_JOBCARD_EVIDENCE_STATUS_ERROR,
        'jobcard_id' => 0,
        'required' => [],
        'found' => [],
        'missing' => [],
        'media_count' => 0,
        'diagnostic_count' => 0,
        'history_count' => 0,
        'message' => 'شناسه کارت کار نامعتبر است.',
        'errors' => ['شناسه کارت کار باید عدد مثبت باشد.'],
        'media_rows' => [],
    ]
    : moghare360_jobcard_evidence_review($jobcardId);

$labels = moghare360_jobcard_evidence_required_labels();
$statusClass = match ($review['status'] ?? '') {
    MOGHARE360_JOBCARD_EVIDENCE_STATUS_COMPLETE => 'w2d-status-complete',
    MOGHARE360_JOBCARD_EVIDENCE_STATUS_PARTIAL => 'w2d-status-partial',
    MOGHARE360_JOBCARD_EVIDENCE_STATUS_EMPTY => 'w2d-status-empty',
    default => 'w2d-status-error',
};

$lastMediaRows = array_slice($review['media_rows'] ?? [], 0, 10);

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>بازبینی مدارک کارت کار</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w2d-wrap">
    <header class="w1c-banner w2d-banner">
        <h1>بازبینی مدارک و دروازه تکمیل</h1>
        <p>Wave 2D — Read-only evidence gate</p>
    </header>

    <section class="w1c-card w1c-form">
        <form method="get" action="erp-jobcard-evidence-review.php">
            <label for="jobcard_id_lookup">شناسه کارت کار</label>
            <input type="number" id="jobcard_id_lookup" name="jobcard_id" min="1" value="<?= $invalidId ? '' : wave2d_review_h((string)$jobcardId) ?>" required>
            <button type="submit" class="w1c-btn">بررسی مدارک</button>
        </form>
    </section>

    <?php if ($invalidId): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;">شناسه کارت کار نامعتبر است. مقدار عددی مثبت وارد کنید.</p>
        </section>
    <?php else: ?>
        <section class="w1c-card <?= wave2d_review_h($statusClass) ?>">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">کارت کار <?= wave2d_review_h((string)$jobcardId) ?></h2>
            <p style="margin:0;">
                <strong>وضعیت:</strong>
                <?= wave2d_review_h(moghare360_jobcard_evidence_status_label((string)($review['status'] ?? ''))) ?>
                (<?= wave2d_review_h((string)($review['status'] ?? '')) ?>)
            </p>
            <p style="margin:0.5rem 0 0;"><?= wave2d_review_h((string)($review['message'] ?? '')) ?></p>
        </section>

        <section class="w1c-card">
            <h2 style="margin:0 0 0.75rem;font-size:1rem;">شمارش‌ها</h2>
            <ul style="margin:0;padding-right:1.25rem;">
                <li>تعداد رسانه: <strong><?= wave2d_review_h((string)($review['media_count'] ?? 0)) ?></strong></li>
                <li>تعداد تشخیصی: <strong><?= wave2d_review_h((string)($review['diagnostic_count'] ?? 0)) ?></strong></li>
                <li>تعداد تاریخچه: <strong><?= wave2d_review_h((string)($review['history_count'] ?? 0)) ?></strong></li>
            </ul>
        </section>

        <section class="w1c-card">
            <h2 style="margin:0 0 0.75rem;font-size:1rem;">موارد الزامی</h2>
            <ul style="margin:0;padding-right:1.25rem;">
                <?php foreach ($labels as $key => $label): ?>
                    <li>
                        <?= wave2d_review_h($label) ?>
                        — <?= in_array($key, $review['found'] ?? [], true) ? '✅' : '❌' ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <?php if (!empty($review['missing'])): ?>
            <section class="w1c-card w1c-note">
                <h2 style="margin:0 0 0.5rem;font-size:1rem;">موارد مفقود</h2>
                <ul style="margin:0;padding-right:1.25rem;">
                    <?php foreach ($review['missing'] as $missingKey): ?>
                        <li><?= wave2d_review_h($labels[$missingKey] ?? (string)$missingKey) ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <?php if (($review['errors'] ?? []) !== []): ?>
            <section class="w1c-card w1c-error-box">
                <ul style="margin:0;padding-right:1.25rem;">
                    <?php foreach ($review['errors'] as $error): ?>
                        <li><?= wave2d_review_h((string)$error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <?php if ($lastMediaRows !== []): ?>
            <section class="w1c-card">
                <h2 style="margin:0 0 0.75rem;font-size:1rem;">آخرین رکوردهای متادیتا</h2>
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:0.88rem;">
                        <thead>
                        <tr>
                            <th style="text-align:right;padding:0.35rem;">شناسه</th>
                            <th style="text-align:right;padding:0.35rem;">مرحله</th>
                            <th style="text-align:right;padding:0.35rem;">نوع</th>
                            <th style="text-align:right;padding:0.35rem;">MIME</th>
                            <th style="text-align:right;padding:0.35rem;">مسیر</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($lastMediaRows as $row): ?>
                            <tr>
                                <td style="padding:0.35rem;"><?= wave2d_review_h((string)($row['media_id'] ?? '')) ?></td>
                                <td style="padding:0.35rem;"><?= wave2d_review_h((string)($row['media_stage'] ?? '')) ?></td>
                                <td style="padding:0.35rem;"><?= wave2d_review_h((string)($row['media_type'] ?? '')) ?></td>
                                <td style="padding:0.35rem;"><?= wave2d_review_h((string)($row['mime_type'] ?? '')) ?></td>
                                <td style="padding:0.35rem;"><code><?= wave2d_review_h((string)($row['relative_path'] ?? '')) ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    <?php endif; ?>

    <nav class="w1c-card w1c-links">
        <a href="erp-jobcard-camera-capture.php">ثبت تصویر دوربین</a>
        <a href="erp-jobcard-media-preview.php?jobcard_id=<?= wave2d_review_h($invalidId ? '1' : (string)$jobcardId) ?>">پیش‌نمایش رسانه</a>
        <a href="erp-jobcard-diagnostic-file.php">ثبت فایل تشخیصی</a>
        <a href="erp-jobcard-diagnostic-preview.php?jobcard_id=<?= wave2d_review_h($invalidId ? '1' : (string)$jobcardId) ?>">پیش‌نمایش تشخیصی</a>
    </nav>
</div>
</body>
</html>

<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Soft Run Finding Create (Wave 8A)
 * Controlled internal create form — NOT final vehicle delivery
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-finding-helper.php';

function wave8a_create_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$schema = moghare360_soft_run_finding_schema_status();
$findingTypes = moghare360_soft_run_finding_allowed_types();
$severities = moghare360_soft_run_finding_allowed_severities();
$findingStatuses = moghare360_soft_run_finding_allowed_statuses();
$correctiveStatuses = moghare360_soft_run_finding_allowed_corrective_statuses();

$prefillExecutionId = trim((string)($_GET['execution_id'] ?? ''));
$prefillJobcardId = trim((string)($_GET['jobcard_id'] ?? ''));

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>ثبت یافته Soft Run</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w8a-wrap">
    <header class="w1c-banner w8a-banner">
        <h1>ثبت یافته Soft Run</h1>
        <p>WAVE 8A — Controlled Soft Run Findings Register</p>
    </header>

    <section class="w1c-card w8a-warning">
        <strong>Internal Soft Run finding/corrective action log only — not final delivery. Not delivery completion. Not legal e-signature. Not payment/accounting. Not production activation.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">این صفحه فقط برای ثبت کنترل‌شده یافته‌ها، مسائل، مشاهدات، ریسک‌ها و اقدامات اصلاحی داخلی است. تحویل نهایی خودرو، تکمیل تحویل، پورتال عمومی، پرداخت و حسابداری فعال نیست.</p>
    </section>

    <?php if (($schema['schema_status'] ?? '') === MOGHARE360_SOFT_RUN_FINDING_SCHEMA_BLOCKED): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave8a_create_h(MOGHARE360_SOFT_RUN_FINDING_BLOCK_MESSAGE) ?></p>
            <p style="margin:0.5rem 0 0;font-size:0.85rem;">پایه داده باید در SSMS اجرا شود: sql/wave_8a_soft_run_findings_register.sql</p>
        </section>
    <?php else: ?>
        <section class="w1c-card w1c-success">
            <p style="margin:0;">پایه داده ثبت یافته‌های Soft Run تأیید شد — ثبت رکورد فعال است.</p>
        </section>
    <?php endif; ?>

    <section class="w1c-card w1c-form w8a-form">
        <form method="post" action="submit-soft-run-finding.php">
            <label for="execution_id">شناسه اجرای پایلوت (اختیاری)</label>
            <input type="number" id="execution_id" name="execution_id" min="1"
                   value="<?= wave8a_create_h($prefillExecutionId) ?>"
                   placeholder="مثلاً 1">

            <label for="jobcard_id">شناسه کارت کار (اختیاری)</label>
            <input type="number" id="jobcard_id" name="jobcard_id" min="1"
                   value="<?= wave8a_create_h($prefillJobcardId) ?>"
                   placeholder="مثلاً 1">

            <label for="finding_type">نوع یافته <span style="color:#b91c1c;">*</span></label>
            <select id="finding_type" name="finding_type" required>
                <option value="">— انتخاب نوع —</option>
                <?php foreach ($findingTypes as $type): ?>
                    <option value="<?= wave8a_create_h($type) ?>">
                        <?= wave8a_create_h(moghare360_soft_run_finding_type_label($type)) ?>
                        (<?= wave8a_create_h($type) ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="severity_level">سطح شدت <span style="color:#b91c1c;">*</span></label>
            <select id="severity_level" name="severity_level" required>
                <option value="">— انتخاب شدت —</option>
                <?php foreach ($severities as $severity): ?>
                    <option value="<?= wave8a_create_h($severity) ?>" <?= $severity === 'MEDIUM' ? 'selected' : '' ?>>
                        <?= wave8a_create_h(moghare360_soft_run_finding_severity_label($severity)) ?>
                        (<?= wave8a_create_h($severity) ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="finding_status">وضعیت یافته</label>
            <select id="finding_status" name="finding_status">
                <?php foreach ($findingStatuses as $status): ?>
                    <option value="<?= wave8a_create_h($status) ?>" <?= $status === 'OPEN' ? 'selected' : '' ?>>
                        <?= wave8a_create_h(moghare360_soft_run_finding_status_label($status)) ?>
                        (<?= wave8a_create_h($status) ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="corrective_action_status">وضعیت اقدام اصلاحی</label>
            <select id="corrective_action_status" name="corrective_action_status">
                <?php foreach ($correctiveStatuses as $status): ?>
                    <option value="<?= wave8a_create_h($status) ?>" <?= $status === 'NOT_STARTED' ? 'selected' : '' ?>>
                        <?= wave8a_create_h(moghare360_soft_run_finding_status_label($status)) ?>
                        (<?= wave8a_create_h($status) ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="finding_title">عنوان یافته <span style="color:#b91c1c;">*</span></label>
            <input type="text" id="finding_title" name="finding_title" maxlength="250" required
                   placeholder="عنوان کوتاه یافته">

            <label for="finding_description">شرح یافته (اختیاری)</label>
            <textarea id="finding_description" name="finding_description" rows="3" maxlength="1500"></textarea>

            <label for="expected_behavior">رفتار مورد انتظار (اختیاری)</label>
            <textarea id="expected_behavior" name="expected_behavior" rows="3" maxlength="1000"></textarea>

            <label for="actual_behavior">رفتار واقعی (اختیاری)</label>
            <textarea id="actual_behavior" name="actual_behavior" rows="3" maxlength="1000"></textarea>

            <label for="corrective_action">اقدام اصلاحی پیشنهادی (اختیاری)</label>
            <textarea id="corrective_action" name="corrective_action" rows="3" maxlength="1500"></textarea>

            <label for="owner_user_id">شناسه مسئول (اختیاری)</label>
            <input type="number" id="owner_user_id" name="owner_user_id" min="1"
                   placeholder="مثلاً 10001">

            <label for="due_at">مهلت انجام (اختیاری)</label>
            <input type="datetime-local" id="due_at" name="due_at">

            <button type="submit" class="w1c-btn w8a-btn">ثبت یافته Soft Run</button>
        </form>
    </section>

    <nav class="w1c-card w1c-links w8a-nav">
        <a href="erp-soft-run-finding-board.php">برد یافته‌های Soft Run</a>
        <a href="erp-soft-run-pilot-review-dashboard.php">داشبورد بازبینی اجرای پایلوت</a>
        <a href="erp-soft-run-pilot-final-closure-dashboard.php">داشبورد نهایی بستن اجرای پایلوت</a>
    </nav>
</div>
</body>
</html>

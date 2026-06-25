<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Soft Run Pilot Execution Create (Wave 7A)
 * Controlled internal create form — NOT final vehicle delivery
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-pilot-execution-helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-soft-run-scenario-helper.php';

function wave7a_create_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$schema = moghare360_soft_run_pilot_execution_schema_status();
$executionStatuses = moghare360_soft_run_pilot_execution_allowed_statuses();
$evidenceStatuses = moghare360_soft_run_pilot_execution_allowed_evidence_statuses();
$resultStatuses = moghare360_soft_run_pilot_execution_allowed_result_statuses();
$scenarios = moghare360_soft_run_scenario_required_scenarios();

$prefillJobcardId = trim((string)($_GET['jobcard_id'] ?? ''));
$prefillScenarioKey = trim((string)($_GET['scenario_key'] ?? ''));

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>ثبت اجرای پایلوت Soft Run</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w7a-wrap">
    <header class="w1c-banner w7a-banner">
        <h1>ثبت اجرای پایلوت Soft Run</h1>
        <p>WAVE 7A — Controlled Soft Run Pilot Execution Log</p>
    </header>

    <section class="w1c-card w7a-warning">
        <strong>Internal Soft Run log only — not final delivery. Not delivery completion. Not legal e-signature. Not payment/accounting. Not production activation.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">این صفحه فقط برای ثبت کنترل‌شده لاگ اجرای پایلوت داخلی است. تحویل نهایی خودرو، تکمیل تحویل، پورتال عمومی، پرداخت و حسابداری فعال نیست.</p>
    </section>

    <?php if (($schema['schema_status'] ?? '') === MOGHARE360_SOFT_RUN_PILOT_EXECUTION_SCHEMA_BLOCKED): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave7a_create_h(MOGHARE360_SOFT_RUN_PILOT_EXECUTION_BLOCK_MESSAGE) ?></p>
            <p style="margin:0.5rem 0 0;font-size:0.85rem;">پایه داده باید در SSMS اجرا شود: sql/wave_7a_soft_run_pilot_execution_log.sql</p>
        </section>
    <?php else: ?>
        <section class="w1c-card w1c-success">
            <p style="margin:0;">پایه داده لاگ اجرای پایلوت تأیید شد — ثبت رکورد فعال است.</p>
        </section>
    <?php endif; ?>

    <section class="w1c-card w1c-form w7a-form">
        <form method="post" action="submit-soft-run-pilot-execution.php">
            <label for="jobcard_id">شناسه کارت کار (اختیاری)</label>
            <input type="number" id="jobcard_id" name="jobcard_id" min="1"
                   value="<?= wave7a_create_h($prefillJobcardId) ?>"
                   placeholder="مثلاً 1">

            <label for="scenario_key">کلید سناریو <span style="color:#b91c1c;">*</span></label>
            <select id="scenario_key" name="scenario_key" required>
                <option value="">— انتخاب سناریو —</option>
                <?php foreach ($scenarios as $scenario): ?>
                    <?php $key = (string)($scenario['key'] ?? ''); ?>
                    <option value="<?= wave7a_create_h($key) ?>"
                        <?= $prefillScenarioKey === $key ? 'selected' : '' ?>>
                        <?= wave7a_create_h((string)($scenario['title_fa'] ?? $scenario['title'] ?? $key)) ?>
                        (<?= wave7a_create_h($key) ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="scenario_title">عنوان سناریو <span style="color:#b91c1c;">*</span></label>
            <input type="text" id="scenario_title" name="scenario_title" maxlength="250" required
                   placeholder="عنوان فارسی یا انگلیسی سناریو">

            <label for="execution_status">وضعیت اجرا</label>
            <select id="execution_status" name="execution_status">
                <?php foreach ($executionStatuses as $status): ?>
                    <option value="<?= wave7a_create_h($status) ?>" <?= $status === 'STARTED' ? 'selected' : '' ?>>
                        <?= wave7a_create_h(moghare360_soft_run_pilot_execution_status_label($status)) ?>
                        (<?= wave7a_create_h($status) ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="evidence_status">وضعیت شواهد</label>
            <select id="evidence_status" name="evidence_status">
                <?php foreach ($evidenceStatuses as $status): ?>
                    <option value="<?= wave7a_create_h($status) ?>" <?= $status === 'PENDING_REVIEW' ? 'selected' : '' ?>>
                        <?= wave7a_create_h(moghare360_soft_run_pilot_execution_status_label($status)) ?>
                        (<?= wave7a_create_h($status) ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="result_status">وضعیت نتیجه</label>
            <select id="result_status" name="result_status">
                <?php foreach ($resultStatuses as $status): ?>
                    <option value="<?= wave7a_create_h($status) ?>" <?= $status === 'NOT_EVALUATED' ? 'selected' : '' ?>>
                        <?= wave7a_create_h(moghare360_soft_run_pilot_execution_status_label($status)) ?>
                        (<?= wave7a_create_h($status) ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="observed_summary">خلاصه مشاهده (اختیاری)</label>
            <textarea id="observed_summary" name="observed_summary" rows="3" maxlength="1000"></textarea>

            <label for="expected_evidence">شواهد مورد انتظار (اختیاری)</label>
            <textarea id="expected_evidence" name="expected_evidence" rows="3" maxlength="1000"></textarea>

            <label for="actual_evidence">شواهد واقعی (اختیاری)</label>
            <textarea id="actual_evidence" name="actual_evidence" rows="3" maxlength="1000"></textarea>

            <label for="blocker_notes">یادداشت مسدودکننده (اختیاری)</label>
            <textarea id="blocker_notes" name="blocker_notes" rows="2" maxlength="1000"></textarea>

            <label for="internal_notes">یادداشت داخلی (اختیاری)</label>
            <textarea id="internal_notes" name="internal_notes" rows="2" maxlength="1000"></textarea>

            <button type="submit" class="w1c-btn-primary">ثبت اجرای پایلوت</button>
        </form>
    </section>

    <nav class="w1c-card w1c-links w7a-nav">
        <a href="erp-soft-run-pilot-execution-board.php">برد اجرای پایلوت Soft Run</a>
        <a href="erp-soft-run-final-closure-dashboard.php">داشبورد نهایی آمادگی پایلوت</a>
        <a href="erp-soft-run-operator-test-pack.php">بسته تست اپراتوری</a>
        <a href="erp-soft-run-control-room.php">اتاق کنترل Soft Run</a>
    </nav>
</div>
</body>
</html>

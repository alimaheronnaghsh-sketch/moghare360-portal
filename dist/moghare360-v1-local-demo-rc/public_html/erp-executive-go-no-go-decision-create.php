<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Executive Go/No-Go Decision Create (Wave 9B)
 * Controlled internal create form — NOT final vehicle delivery
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-executive-go-no-go-decision-helper.php';

function wave9b_create_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$schema = moghare360_executive_go_no_go_decision_schema_status();
$snapshot = moghare360_executive_go_no_go_decision_fetch_current_snapshot();
$decisionTypes = moghare360_executive_go_no_go_decision_allowed_types();
$decisionStatuses = moghare360_executive_go_no_go_decision_allowed_statuses();
$readinessStatuses = moghare360_executive_go_no_go_decision_allowed_readiness_statuses();

$defaultReadiness = (string)($snapshot['executive_readiness_status'] ?? 'GO_REVIEW_REQUIRED');
$defaultWave6 = (string)($snapshot['wave6_status'] ?? '');
$defaultWave7 = (string)($snapshot['wave7_status'] ?? '');
$defaultWave8 = (string)($snapshot['wave8_status'] ?? '');

$prefillFindingId = trim((string)($_GET['finding_id'] ?? '1'));
$prefillPilotId = trim((string)($_GET['pilot_execution_id'] ?? '1'));

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>ثبت تصمیم مدیریتی Go/No-Go</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w9b-wrap">
    <header class="w1c-banner w9b-banner">
        <h1>ثبت تصمیم مدیریتی Go/No-Go</h1>
        <p>WAVE 9B — Executive Soft Run Go/No-Go Decision Log</p>
    </header>

    <section class="w1c-card w9b-warning">
        <strong>Internal executive Go/No-Go review decision log only — not final delivery approval. Not delivery completion. Not legal e-signature. Not payment/accounting. Not production activation.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">این صفحه فقط برای ثبت کنترل‌شده تصمیم‌های بازبینی مدیریتی Soft Run است. تحویل نهایی خودرو، تکمیل تحویل، پورتال عمومی، پرداخت و حسابداری فعال نیست.</p>
    </section>

    <?php if (($schema['schema_status'] ?? '') === MOGHARE360_EXECUTIVE_GO_NO_GO_SCHEMA_BLOCKED): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave9b_create_h(MOGHARE360_EXECUTIVE_GO_NO_GO_BLOCK_MESSAGE) ?></p>
            <p style="margin:0.5rem 0 0;font-size:0.85rem;">پایه داده باید در SSMS اجرا شود: sql/wave_9b_executive_go_no_go_decision_log.sql</p>
        </section>
    <?php else: ?>
        <section class="w1c-card w1c-success">
            <p style="margin:0;">پایه داده ثبت تصمیم مدیریتی Go/No-Go تأیید شد — ثبت رکورد فعال است.</p>
        </section>
    <?php endif; ?>

    <?php if ($snapshot['snapshot_available'] ?? false): ?>
        <section class="w1c-card">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">تصویر لحظه‌ای WAVE 9A (آمادگی مدیریتی)</h2>
            <p style="margin:0;font-size:0.9rem;">
                <strong><?= wave9b_create_h(moghare360_executive_go_no_go_decision_status_label($defaultReadiness)) ?></strong>
                (<?= wave9b_create_h($defaultReadiness) ?>)
            </p>
            <p style="margin:0.35rem 0 0;font-size:0.85rem;color:#525252;">
                WAVE 6: <?= wave9b_create_h($defaultWave6 !== '' ? $defaultWave6 : '—') ?> ·
                WAVE 7: <?= wave9b_create_h($defaultWave7 !== '' ? $defaultWave7 : '—') ?> ·
                WAVE 8: <?= wave9b_create_h($defaultWave8 !== '' ? $defaultWave8 : '—') ?>
            </p>
            <?php if (($snapshot['go_interpretation'] ?? '') !== ''): ?>
                <p style="margin:0.35rem 0 0;font-size:0.85rem;"><?= wave9b_create_h((string)$snapshot['go_interpretation']) ?></p>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <section class="w1c-card w1c-form w9b-form">
        <form method="post" action="submit-executive-go-no-go-decision.php">
            <label for="executive_readiness_status">وضعیت آمادگی مدیریتی <span style="color:#b91c1c;">*</span></label>
            <select id="executive_readiness_status" name="executive_readiness_status" required>
                <?php foreach ($readinessStatuses as $status): ?>
                    <option value="<?= wave9b_create_h($status) ?>" <?= $status === $defaultReadiness ? 'selected' : '' ?>>
                        <?= wave9b_create_h(moghare360_executive_go_no_go_decision_status_label($status)) ?>
                        (<?= wave9b_create_h($status) ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="wave6_status">وضعیت WAVE 6 (اختیاری)</label>
            <input type="text" id="wave6_status" name="wave6_status" maxlength="80"
                   value="<?= wave9b_create_h($defaultWave6) ?>">

            <label for="wave7_status">وضعیت WAVE 7 (اختیاری)</label>
            <input type="text" id="wave7_status" name="wave7_status" maxlength="80"
                   value="<?= wave9b_create_h($defaultWave7) ?>">

            <label for="wave8_status">وضعیت WAVE 8 (اختیاری)</label>
            <input type="text" id="wave8_status" name="wave8_status" maxlength="80"
                   value="<?= wave9b_create_h($defaultWave8) ?>">

            <label for="decision_type">نوع تصمیم <span style="color:#b91c1c;">*</span></label>
            <select id="decision_type" name="decision_type" required>
                <option value="">— انتخاب نوع —</option>
                <?php foreach ($decisionTypes as $type): ?>
                    <option value="<?= wave9b_create_h($type) ?>" <?= $type === 'REVIEW_REQUIRED' ? 'selected' : '' ?>>
                        <?= wave9b_create_h(moghare360_executive_go_no_go_decision_type_label($type)) ?>
                        (<?= wave9b_create_h($type) ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="decision_status">وضعیت تصمیم</label>
            <select id="decision_status" name="decision_status">
                <?php foreach ($decisionStatuses as $status): ?>
                    <option value="<?= wave9b_create_h($status) ?>" <?= $status === 'RECORDED' ? 'selected' : '' ?>>
                        <?= wave9b_create_h(moghare360_executive_go_no_go_decision_status_label($status)) ?>
                        (<?= wave9b_create_h($status) ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="decision_title">عنوان تصمیم <span style="color:#b91c1c;">*</span></label>
            <input type="text" id="decision_title" name="decision_title" maxlength="250" required
                   placeholder="عنوان کوتاه تصمیم مدیریتی">

            <label for="decision_summary">خلاصه تصمیم (اختیاری)</label>
            <textarea id="decision_summary" name="decision_summary" rows="3" maxlength="1500"></textarea>

            <label for="management_reason">دلیل مدیریتی <span style="color:#b91c1c;">*</span></label>
            <textarea id="management_reason" name="management_reason" rows="3" maxlength="1500" required
                      placeholder="دلیل ثبت تصمیم مدیریتی"></textarea>

            <label for="required_action_summary">خلاصه اقدام مورد نیاز (اختیاری)</label>
            <textarea id="required_action_summary" name="required_action_summary" rows="2" maxlength="1500"></textarea>

            <label for="risk_note">یادداشت ریسک (اختیاری)</label>
            <textarea id="risk_note" name="risk_note" rows="2" maxlength="1500"></textarea>

            <label for="finding_id">شناسه یافته (اختیاری)</label>
            <input type="number" id="finding_id" name="finding_id" min="1"
                   value="<?= wave9b_create_h($prefillFindingId) ?>">

            <label for="pilot_execution_id">شناسه اجرای پایلوت (اختیاری)</label>
            <input type="number" id="pilot_execution_id" name="pilot_execution_id" min="1"
                   value="<?= wave9b_create_h($prefillPilotId) ?>">

            <label for="decided_by_user_id">شناسه تصمیم‌گیرنده (اختیاری)</label>
            <input type="number" id="decided_by_user_id" name="decided_by_user_id" min="1"
                   placeholder="مثلاً 10001">

            <label for="decision_due_at">مهلت پیگیری تصمیم (اختیاری)</label>
            <input type="datetime-local" id="decision_due_at" name="decision_due_at">

            <button type="submit" class="w1c-btn">ثبت تصمیم مدیریتی</button>
        </form>
    </section>

    <nav class="w1c-card w1c-links w9b-nav">
        <a href="erp-executive-go-no-go-decision-board.php">برد تصمیم‌های مدیریتی Go/No-Go</a>
        <a href="erp-executive-soft-run-readiness-dashboard.php">داشبورد آمادگی مدیریتی Soft Run</a>
    </nav>
</div>
</body>
</html>

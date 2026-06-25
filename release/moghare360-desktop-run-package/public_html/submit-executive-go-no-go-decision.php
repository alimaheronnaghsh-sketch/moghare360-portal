<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Submit Executive Go/No-Go Decision (Wave 9B)
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-executive-go-no-go-decision-helper.php';

function wave9b_submit_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: erp-executive-go-no-go-decision-create.php');
    exit;
}

$payload = [
    'executive_readiness_status' => trim((string)($_POST['executive_readiness_status'] ?? '')),
    'wave6_status' => trim((string)($_POST['wave6_status'] ?? '')),
    'wave7_status' => trim((string)($_POST['wave7_status'] ?? '')),
    'wave8_status' => trim((string)($_POST['wave8_status'] ?? '')),
    'decision_type' => trim((string)($_POST['decision_type'] ?? '')),
    'decision_status' => trim((string)($_POST['decision_status'] ?? 'RECORDED')),
    'decision_title' => trim((string)($_POST['decision_title'] ?? '')),
    'decision_summary' => trim((string)($_POST['decision_summary'] ?? '')),
    'management_reason' => trim((string)($_POST['management_reason'] ?? '')),
    'required_action_summary' => trim((string)($_POST['required_action_summary'] ?? '')),
    'risk_note' => trim((string)($_POST['risk_note'] ?? '')),
    'finding_id' => trim((string)($_POST['finding_id'] ?? '')),
    'pilot_execution_id' => trim((string)($_POST['pilot_execution_id'] ?? '')),
    'decided_by_user_id' => trim((string)($_POST['decided_by_user_id'] ?? '')),
    'decision_due_at' => trim((string)($_POST['decision_due_at'] ?? '')),
];

$validation = moghare360_executive_go_no_go_decision_validate_payload($payload);
$result = $validation['ok'] ? moghare360_executive_go_no_go_decision_create($payload) : null;

$decisionId = (int)($result['decision_id'] ?? 0);

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>نتیجه ثبت تصمیم مدیریتی Go/No-Go</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w9b-wrap">
    <header class="w1c-banner w9b-banner">
        <h1>نتیجه ثبت تصمیم مدیریتی Go/No-Go</h1>
        <p>WAVE 9B — Executive Decision Submit Result</p>
    </header>

    <section class="w1c-card w9b-warning">
        <strong>Internal executive Go/No-Go review decision log only — not final delivery. Not delivery completion. Not legal e-signature. Not payment/accounting.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">پورتال عمومی و ورود تولید فعال نیست. تحویل نهایی خودرو انجام نمی‌شود.</p>
    </section>

    <?php if (!$validation['ok']): ?>
        <section class="w1c-card w1c-error-box">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">خطای اعتبارسنجی</h2>
            <ul style="margin:0;padding-right:1.25rem;">
                <?php foreach ($validation['errors'] as $error): ?>
                    <li><?= wave9b_submit_h((string)($error['message'] ?? '')) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php elseif ($result !== null && ($result['ok'] ?? false) === true): ?>
        <section class="w1c-card w1c-success">
            <p style="margin:0;"><?= wave9b_submit_h((string)($result['message'] ?? 'ثبت موفق')) ?></p>
            <?php if (($result['decision_id'] ?? null) !== null): ?>
                <p style="margin:0.5rem 0 0;"><strong>شناسه تصمیم:</strong> <?= wave9b_submit_h((string)$result['decision_id']) ?></p>
            <?php endif; ?>
            <?php if (($result['decision_code'] ?? null) !== null): ?>
                <p style="margin:0.35rem 0 0;"><strong>کد تصمیم:</strong> <?= wave9b_submit_h((string)$result['decision_code']) ?></p>
            <?php endif; ?>
            <p style="margin:0.35rem 0 0;"><strong>وضعیت آمادگی مدیریتی:</strong>
                <?= wave9b_submit_h((string)($result['executive_readiness_status'] ?? '')) ?></p>
            <p style="margin:0.35rem 0 0;"><strong>نوع تصمیم:</strong>
                <?= wave9b_submit_h(moghare360_executive_go_no_go_decision_type_label((string)($result['decision_type'] ?? ''))) ?>
                (<?= wave9b_submit_h((string)($result['decision_type'] ?? '')) ?>)</p>
            <p style="margin:0.35rem 0 0;"><strong>وضعیت تصمیم:</strong>
                <?= wave9b_submit_h(moghare360_executive_go_no_go_decision_status_label((string)($result['decision_status'] ?? ''))) ?>
                (<?= wave9b_submit_h((string)($result['decision_status'] ?? '')) ?>)</p>
        </section>
    <?php elseif ($result !== null && ($result['schema_status'] ?? '') === MOGHARE360_EXECUTIVE_GO_NO_GO_SCHEMA_BLOCKED): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave9b_submit_h(MOGHARE360_EXECUTIVE_GO_NO_GO_BLOCK_MESSAGE) ?></p>
            <p style="margin:0.5rem 0 0;font-size:0.85rem;">ثبت رکورد تا اجرای SQL در SSMS مسدود است — موفقیت جعلی نمایش داده نمی‌شود.</p>
        </section>
    <?php elseif ($result !== null && !empty($result['errors'])): ?>
        <section class="w1c-card w1c-error-box">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;"><?= wave9b_submit_h((string)($result['message'] ?? 'ثبت ناموفق')) ?></h2>
            <ul style="margin:0;padding-right:1.25rem;">
                <?php foreach ($result['errors'] as $error): ?>
                    <li><?= wave9b_submit_h((string)($error['message'] ?? '')) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php else: ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave9b_submit_h((string)($result['message'] ?? 'ثبت تصمیم ناموفق بود.')) ?></p>
        </section>
    <?php endif; ?>

    <nav class="w1c-card w1c-links w9b-nav">
        <?php if ($decisionId > 0): ?>
            <a href="erp-executive-go-no-go-decision-detail.php?decision_id=<?= wave9b_submit_h((string)$decisionId) ?>">جزئیات تصمیم</a>
        <?php endif; ?>
        <a href="erp-executive-go-no-go-decision-board.php">برد تصمیم‌های مدیریتی</a>
        <a href="erp-executive-go-no-go-decision-create.php">ثبت تصمیم جدید</a>
        <a href="erp-executive-soft-run-readiness-dashboard.php">داشبورد آمادگی مدیریتی Soft Run</a>
    </nav>
</div>
</body>
</html>

<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Submit Executive Go/No-Go Decision Workflow (Wave 9C)
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-executive-go-no-go-decision-helper.php';

function wave9c_submit_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: erp-executive-go-no-go-decision-board.php');
    exit;
}

$decisionIdRaw = trim((string)($_POST['decision_id'] ?? ''));
$decisionId = ($decisionIdRaw !== '' && ctype_digit($decisionIdRaw) && (int)$decisionIdRaw >= 1)
    ? (int)$decisionIdRaw
    : 0;

$payload = [
    'decision_id' => $decisionIdRaw,
    'new_decision_status' => trim((string)($_POST['new_decision_status'] ?? '')),
    'decision_type' => trim((string)($_POST['decision_type'] ?? '')),
    'change_reason' => trim((string)($_POST['change_reason'] ?? '')),
    'management_review_note' => trim((string)($_POST['management_review_note'] ?? '')),
    'decision_summary' => trim((string)($_POST['decision_summary'] ?? '')),
    'required_action_summary' => trim((string)($_POST['required_action_summary'] ?? '')),
    'risk_note' => trim((string)($_POST['risk_note'] ?? '')),
];

$validation = moghare360_executive_go_no_go_decision_validate_workflow_payload($payload);
$result = ($validation['ok'] && $decisionId > 0)
    ? moghare360_executive_go_no_go_decision_update_workflow($decisionId, $payload)
    : null;

if ($result === null && $validation['ok'] && $decisionId < 1) {
    $validation = [
        'ok' => false,
        'errors' => [[
            'field' => 'decision_id',
            'rule' => 'required_positive_number',
            'message' => 'شناسه تصمیم الزامی و باید عدد مثبت باشد.',
        ]],
        'clean' => [],
    ];
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>نتیجه گردش کار تصمیم مدیریتی Go/No-Go</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w9c-wrap">
    <header class="w1c-banner w9c-banner">
        <h1>نتیجه گردش کار تصمیم مدیریتی Go/No-Go</h1>
        <p>WAVE 9C — Controlled Executive Decision Workflow Submit Result</p>
    </header>

    <section class="w1c-card w9c-warning">
        <strong>Internal executive decision workflow only — not final delivery approval. Not delivery completion. Not legal e-signature. Not payment/accounting.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">رکورد تصمیم جدید ایجاد نمی‌شود. نوشتن به جداول یافته‌ها و اجرای پایلوت انجام نمی‌شود.</p>
    </section>

    <?php if (!$validation['ok']): ?>
        <section class="w1c-card w1c-error-box">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">خطای اعتبارسنجی</h2>
            <ul style="margin:0;padding-right:1.25rem;">
                <?php foreach ($validation['errors'] as $error): ?>
                    <li><?= wave9c_submit_h((string)($error['message'] ?? '')) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php elseif ($result !== null && ($result['ok'] ?? false) === true): ?>
        <section class="w1c-card w1c-success">
            <p style="margin:0;"><?= wave9c_submit_h((string)($result['message'] ?? 'به‌روزرسانی موفق')) ?></p>
            <p style="margin:0.5rem 0 0;"><strong>شناسه تصمیم:</strong> <?= wave9c_submit_h((string)($result['decision_id'] ?? '')) ?></p>
            <p style="margin:0.35rem 0 0;"><strong>کد تصمیم:</strong> <?= wave9c_submit_h((string)($result['decision_code'] ?? '')) ?></p>
            <p style="margin:0.35rem 0 0;"><strong>وضعیت قبلی:</strong>
                <?= wave9c_submit_h(moghare360_executive_go_no_go_decision_status_label((string)($result['old_decision_status'] ?? ''))) ?>
                (<?= wave9c_submit_h((string)($result['old_decision_status'] ?? '')) ?>)</p>
            <p style="margin:0.35rem 0 0;"><strong>وضعیت جدید:</strong>
                <?= wave9c_submit_h(moghare360_executive_go_no_go_decision_status_label((string)($result['new_decision_status'] ?? ''))) ?>
                (<?= wave9c_submit_h((string)($result['new_decision_status'] ?? '')) ?>)</p>
            <p style="margin:0.35rem 0 0;"><strong>نوع قبلی:</strong>
                <?= wave9c_submit_h(moghare360_executive_go_no_go_decision_type_label((string)($result['old_decision_type'] ?? ''))) ?>
                (<?= wave9c_submit_h((string)($result['old_decision_type'] ?? '')) ?>)</p>
            <p style="margin:0.35rem 0 0;"><strong>نوع جدید:</strong>
                <?= wave9c_submit_h(moghare360_executive_go_no_go_decision_type_label((string)($result['new_decision_type'] ?? ''))) ?>
                (<?= wave9c_submit_h((string)($result['new_decision_type'] ?? '')) ?>)</p>
        </section>
    <?php elseif ($result !== null && ($result['schema_status'] ?? '') === MOGHARE360_EXECUTIVE_GO_NO_GO_SCHEMA_BLOCKED): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave9c_submit_h(MOGHARE360_EXECUTIVE_GO_NO_GO_BLOCK_MESSAGE) ?></p>
        </section>
    <?php elseif ($result !== null && !empty($result['errors'])): ?>
        <section class="w1c-card w1c-error-box">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;"><?= wave9c_submit_h((string)($result['message'] ?? 'به‌روزرسانی ناموفق')) ?></h2>
            <ul style="margin:0;padding-right:1.25rem;">
                <?php foreach ($result['errors'] as $error): ?>
                    <li><?= wave9c_submit_h((string)($error['message'] ?? '')) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php else: ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave9c_submit_h((string)($result['message'] ?? 'به‌روزرسانی گردش کار ناموفق بود.')) ?></p>
        </section>
    <?php endif; ?>

    <nav class="w1c-card w1c-links w9c-nav">
        <?php if ($decisionId > 0): ?>
            <a href="erp-executive-go-no-go-decision-workflow.php?decision_id=<?= wave9c_submit_h((string)$decisionId) ?>">گردش کار تصمیم</a>
            <a href="erp-executive-go-no-go-decision-detail.php?decision_id=<?= wave9c_submit_h((string)$decisionId) ?>">جزئیات تصمیم</a>
        <?php endif; ?>
        <a href="erp-executive-go-no-go-decision-board.php">برد تصمیم‌ها</a>
    </nav>
</div>
</body>
</html>

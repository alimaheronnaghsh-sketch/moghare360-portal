<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Submit Contract Authorization Workflow (Wave 3B)
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-contract-authorization-workflow-helper.php';

function wave3b_submit_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: erp-jobcard-contract-authorization-workflow.php');
    exit;
}

$authIdRaw = trim((string)($_POST['authorization_id'] ?? ''));
$targetStatus = trim((string)($_POST['target_status'] ?? ''));
$workflowNote = trim((string)($_POST['workflow_note'] ?? ''));

$errors = [];
$authorizationId = 0;

if ($authIdRaw === '' || !ctype_digit($authIdRaw) || (int)$authIdRaw < 1) {
    $errors[] = [
        'field' => 'authorization_id',
        'rule' => 'positive_number',
        'message' => 'شناسه مجوز باید عدد مثبت باشد.',
    ];
} else {
    $authorizationId = (int)$authIdRaw;
}

if ($targetStatus === '') {
    $errors[] = [
        'field' => 'target_status',
        'rule' => 'required',
        'message' => 'وضعیت هدف الزامی است.',
    ];
}

$result = null;

if ($errors === []) {
    $recordResult = moghare360_contract_authorization_workflow_get_record($authorizationId);

    if (!($recordResult['ok'] ?? false) || ($recordResult['record'] ?? null) === null) {
        $errors[] = [
            'field' => 'authorization_id',
            'rule' => 'not_found',
            'message' => (string)($recordResult['message'] ?? 'رکورد مجوز یافت نشد.'),
        ];
    } else {
        $validation = moghare360_contract_authorization_workflow_validate_transition(
            $recordResult['record'],
            $targetStatus,
            $workflowNote
        );

        if (!$validation['ok']) {
            $errors = array_merge($errors, $validation['errors']);
        } else {
            $result = moghare360_contract_authorization_workflow_apply($authorizationId, $targetStatus, $workflowNote);
        }
    }
}

$jobcardIdForLink = '1';
if ($result !== null && ($result['ok'] ?? false)) {
    $rec = moghare360_contract_authorization_workflow_get_record($authorizationId);
    if (($rec['record']['jobcard_id'] ?? null) !== null) {
        $jobcardIdForLink = (string)$rec['record']['jobcard_id'];
    }
} elseif ($authorizationId > 0) {
    $rec = moghare360_contract_authorization_workflow_get_record($authorizationId);
    if (($rec['record']['jobcard_id'] ?? null) !== null) {
        $jobcardIdForLink = (string)$rec['record']['jobcard_id'];
    }
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>نتیجه گردش کار مجوز</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w3b-wrap">
    <header class="w1c-banner w3b-banner">
        <h1>نتیجه گردش کار مجوز</h1>
        <p>Wave 3B — Internal controlled workflow</p>
    </header>

    <section class="w1c-card w3b-warning">
        <strong>This is internal controlled workflow, not final legal e-signature.</strong>
    </section>

    <?php if ($errors !== []): ?>
        <section class="w1c-card w1c-error-box">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">خطای اعتبارسنجی / انتقال ممنوع</h2>
            <ul style="margin:0;padding-right:1.25rem;">
                <?php foreach ($errors as $error): ?>
                    <li><?= wave3b_submit_h((string)($error['message'] ?? '')) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php elseif ($result !== null && ($result['ok'] ?? false) === true): ?>
        <section class="w1c-card w1c-success">
            <p style="margin:0;"><?= wave3b_submit_h((string)($result['message'] ?? 'انتقال موفق')) ?></p>
            <p style="margin:0.5rem 0 0;">
                <strong>از:</strong> <?= wave3b_submit_h(moghare360_contract_authorization_workflow_status_label((string)($result['old_status'] ?? ''))) ?>
                → <strong>به:</strong> <?= wave3b_submit_h(moghare360_contract_authorization_workflow_status_label((string)($result['new_status'] ?? ''))) ?>
            </p>
        </section>
    <?php elseif ($result !== null): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave3b_submit_h((string)($result['message'] ?? 'اعمال انتقال ناموفق بود.')) ?></p>
        </section>
    <?php endif; ?>

    <nav class="w1c-card w1c-links">
        <a href="erp-jobcard-contract-authorization-workflow.php?authorization_id=<?= wave3b_submit_h((string)$authorizationId) ?>">بازگشت به گردش کار</a>
        <a href="erp-jobcard-contract-authorization-preview.php?jobcard_id=<?= wave3b_submit_h($jobcardIdForLink) ?>">پیش‌نمایش مجوزها</a>
    </nav>
</div>
</body>
</html>

<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Submit JobCard Contract Authorization (Wave 3A)
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-contract-authorization-helper.php';

function wave3a_submit_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: erp-jobcard-contract-authorization.php');
    exit;
}

$payload = [
    'jobcard_id' => trim((string)($_POST['jobcard_id'] ?? '')),
    'authorization_type' => trim((string)($_POST['authorization_type'] ?? '')),
    'authorization_status' => trim((string)($_POST['authorization_status'] ?? '')),
    'authorization_method' => trim((string)($_POST['authorization_method'] ?? '')),
    'customer_name' => trim((string)($_POST['customer_name'] ?? '')),
    'customer_mobile' => trim((string)($_POST['customer_mobile'] ?? '')),
    'authorization_note' => trim((string)($_POST['authorization_note'] ?? '')),
];

$validation = moghare360_contract_authorization_validate_payload($payload);
$result = null;

if ($validation['ok']) {
    $result = moghare360_contract_authorization_create($payload);
}

$jobcardIdForLink = trim((string)($_POST['jobcard_id'] ?? '1'));

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>نتیجه ثبت مجوز/قرارداد</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w3a-wrap">
    <header class="w1c-banner w3a-banner">
        <h1>نتیجه ثبت مجوز/قرارداد</h1>
        <p>Wave 3A — Internal controlled authorization</p>
    </header>

    <section class="w1c-card w3a-warning">
        <strong>This is internal controlled authorization, not final legal e-signature.</strong>
    </section>

    <?php if (!$validation['ok']): ?>
        <section class="w1c-card w1c-error-box">
            <h2 style="margin:0 0 0.5rem;font-size:1rem;">خطای اعتبارسنجی</h2>
            <ul style="margin:0;padding-right:1.25rem;">
                <?php foreach ($validation['errors'] as $error): ?>
                    <li><?= wave3a_submit_h((string)($error['message'] ?? '')) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php elseif ($result !== null && ($result['ok'] ?? false) === true): ?>
        <section class="w1c-card w1c-success">
            <p style="margin:0;"><?= wave3a_submit_h((string)($result['message'] ?? 'ثبت موفق')) ?></p>
            <?php if (($result['authorization_id'] ?? null) !== null): ?>
                <p style="margin:0.5rem 0 0;"><strong>شناسه مجوز:</strong> <?= wave3a_submit_h((string)$result['authorization_id']) ?></p>
            <?php endif; ?>
            <p style="margin:0.35rem 0 0;font-size:0.85rem;">وضعیت پایه داده: <?= wave3a_submit_h((string)($result['schema_status'] ?? '')) ?></p>
        </section>
    <?php elseif ($result !== null && ($result['schema_status'] ?? '') === MOGHARE360_CONTRACT_AUTH_SCHEMA_BLOCKED): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;">Contract authorization DB foundation is not confirmed yet.</p>
            <p style="margin:0.35rem 0 0;"><?= wave3a_submit_h((string)($result['message'] ?? MOGHARE360_CONTRACT_AUTH_BLOCK_MESSAGE)) ?></p>
            <p style="margin:0.35rem 0 0;font-size:0.85rem;">وضعیت پایه داده: BLOCKED</p>
            <?php if (!empty($result['notes'])): ?>
                <ul style="margin:0.5rem 0 0;padding-right:1.25rem;font-size:0.85rem;">
                    <?php foreach ($result['notes'] as $note): ?>
                        <li><?= wave3a_submit_h((string)$note) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    <?php elseif ($result !== null): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave3a_submit_h((string)($result['message'] ?? 'ثبت مجوز ناموفق بود.')) ?></p>
            <?php if (!empty($result['errors'])): ?>
                <ul style="margin:0.5rem 0 0;padding-right:1.25rem;">
                    <?php foreach ($result['errors'] as $error): ?>
                        <li><?= wave3a_submit_h((string)($error['message'] ?? '')) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <nav class="w1c-card w1c-links">
        <a href="erp-jobcard-contract-authorization.php">بازگشت به ثبت مجوز</a>
        <a href="erp-jobcard-contract-authorization-preview.php?jobcard_id=<?= wave3a_submit_h($jobcardIdForLink) ?>">پیش‌نمایش مجوزها</a>
    </nav>
</div>
</body>
</html>

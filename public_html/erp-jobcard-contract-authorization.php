<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — JobCard Contract Authorization (Wave 3A)
 * Internal controlled authorization — NOT final legal e-signature
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-contract-authorization-helper.php';

$types = moghare360_contract_authorization_allowed_types();
$statuses = moghare360_contract_authorization_allowed_statuses();
$methods = moghare360_contract_authorization_allowed_methods();
$typeLabels = moghare360_contract_authorization_type_labels();
$statusLabels = moghare360_contract_authorization_status_labels();
$methodLabels = moghare360_contract_authorization_method_labels();
$schema = moghare360_contract_authorization_schema_status();

function wave3a_form_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>ثبت مجوز/قرارداد کارت کار</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w3a-wrap">
    <header class="w1c-banner w3a-banner">
        <h1>ثبت مجوز/قرارداد کارت کار</h1>
        <p>WAVE 3A — Contract Authorization Runtime Foundation</p>
    </header>

    <section class="w1c-card w3a-warning">
        <strong>This is internal controlled authorization, not final legal e-signature.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">این مجوز/قرارداد داخلی کنترل‌شده است — امضای الکترونیکی قانونی نهایی نیست. پورتال عمومی مشتری فعال نیست.</p>
    </section>

    <?php if (($schema['schema_status'] ?? '') === MOGHARE360_CONTRACT_AUTH_SCHEMA_BLOCKED): ?>
        <section class="w1c-card w1c-error-box">
            <p style="margin:0;"><?= wave3a_form_h(MOGHARE360_CONTRACT_AUTH_BLOCK_MESSAGE) ?></p>
            <p style="margin:0.5rem 0 0;font-size:0.85rem;">پایه داده باید پس از تأیید ChatGPT در SSMS اجرا شود.</p>
        </section>
    <?php else: ?>
        <section class="w1c-card w1c-success">
            <p style="margin:0;">پایه داده مجوز/قرارداد تأیید شد — ثبت رکورد فعال است.</p>
        </section>
    <?php endif; ?>

    <section class="w1c-card w1c-form">
        <form method="post" action="submit-jobcard-contract-authorization.php">
            <label for="jobcard_id">شناسه کارت کار <span style="color:#b91c1c;">*</span></label>
            <input type="number" id="jobcard_id" name="jobcard_id" min="1" required placeholder="مثال: 1">

            <label for="authorization_type">نوع مجوز/قرارداد <span style="color:#b91c1c;">*</span></label>
            <select id="authorization_type" name="authorization_type" required>
                <?php foreach ($types as $type): ?>
                    <option value="<?= wave3a_form_h($type) ?>"><?= wave3a_form_h($typeLabels[$type] ?? $type) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="authorization_status">وضعیت مجوز <span style="color:#b91c1c;">*</span></label>
            <select id="authorization_status" name="authorization_status" required>
                <?php foreach ($statuses as $status): ?>
                    <option value="<?= wave3a_form_h($status) ?>"><?= wave3a_form_h($statusLabels[$status] ?? $status) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="authorization_method">روش مجوز <span style="color:#b91c1c;">*</span></label>
            <select id="authorization_method" name="authorization_method" required>
                <?php foreach ($methods as $method): ?>
                    <option value="<?= wave3a_form_h($method) ?>"><?= wave3a_form_h($methodLabels[$method] ?? $method) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="customer_name">نام مشتری <span style="color:#b91c1c;">*</span></label>
            <input type="text" id="customer_name" name="customer_name" maxlength="200" required placeholder="نام و نام خانوادگی">

            <label for="customer_mobile">موبایل مشتری <span style="color:#b91c1c;">*</span></label>
            <input type="tel" id="customer_mobile" name="customer_mobile" maxlength="11" required placeholder="09123456789" inputmode="numeric">

            <label for="authorization_note">یادداشت مجوز</label>
            <textarea id="authorization_note" name="authorization_note" rows="3" maxlength="2000" placeholder="توضیحات داخلی (اختیاری)"></textarea>

            <button type="submit" class="w1c-btn">ثبت مجوز/قرارداد داخلی</button>
        </form>
    </section>

    <nav class="w1c-card w1c-links">
        <a href="erp-jobcard-contract-authorization-preview.php?jobcard_id=1">پیش‌نمایش مجوزهای کارت کار</a>
        <a href="erp-media-evidence-closure-dashboard.php">داشبورد بستن WAVE 2</a>
        <a href="erp-critical-forms-v2-live-preview.php">فهرست پیش‌نمایش</a>
    </nav>
</div>
</body>
</html>

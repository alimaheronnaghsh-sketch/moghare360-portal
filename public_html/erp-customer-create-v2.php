<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Customer Create v2 (Wave 1C)
 * Critical Form v2 — Validation First · No DB read · No auth
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

function wave1c_customer_form_h(string $value): string
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
    <title>ثبت مشتری v2 — MOGHARE360</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap">
    <header class="w1c-banner">
        <h1>ثبت مشتری — Critical Form v2</h1>
        <p>Critical Form v2 — Validation First · نوشتن در پایگاه داده در Wave 1C غیرفعال است</p>
    </header>

    <section class="w1c-card w1c-note">
        پیش‌نمایش کنترل‌شده Wave 1C — پس از اعتبارسنجی موفق، داده پاک‌شده نمایش داده می‌شود؛ درج در DB فعال نیست.
    </section>

    <section class="w1c-card">
        <form class="w1c-form" method="post" action="submit-customer-v2.php">
            <label for="customer_name">نام مشتری <span style="color:#b91c1c;">*</span></label>
            <input type="text" id="customer_name" name="customer_name" maxlength="100" required placeholder="مثال: علی رضایی">

            <label for="mobile">موبایل <span style="color:#b91c1c;">*</span></label>
            <input type="text" id="mobile" name="mobile" inputmode="tel" maxlength="15" required placeholder="09121234567">

            <label for="national_id">کد ملی (اختیاری)</label>
            <input type="text" id="national_id" name="national_id" inputmode="numeric" maxlength="10" placeholder="۱۰ رقم">

            <label for="notes">یادداشت (اختیاری)</label>
            <textarea id="notes" name="notes" maxlength="500" placeholder="یادداشت داخلی"></textarea>

            <button type="submit" class="w1c-btn">اعتبارسنجی و پیش‌نمایش</button>
        </form>
    </section>

    <nav class="w1c-card w1c-links">
        <a href="erp-critical-forms-v2-live-preview.php">فهرست پیش‌نمایش Wave 1C</a>
        <a href="erp-critical-forms-v2-validation-test.php">تست اعتبارسنجی Wave 1B</a>
        <a href="erp-validation-engine-runtime-test.php">تست موتور Wave 1A</a>
    </nav>
</div>
</body>
</html>

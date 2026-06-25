<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Vehicle Create v2 (Wave 1C)
 * Critical Form v2 — Validation First · No DB read · No auth
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>ثبت خودرو v2 — MOGHARE360</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap">
    <header class="w1c-banner">
        <h1>ثبت خودرو — Critical Form v2</h1>
        <p>Critical Form v2 — Validation First · نوشتن در پایگاه داده در Wave 1C غیرفعال است</p>
    </header>

    <section class="w1c-card w1c-note">
        پیش‌نمایش Wave 1C — فیلدهای برند/مدل/کلاس با مقادیر پیش‌فرض پیش‌نمایش ارسال می‌شوند تا اعتبارسنجی کامل شود.
    </section>

    <section class="w1c-card">
        <form class="w1c-form" method="post" action="submit-vehicle-v2.php">
            <label>پلاک <span style="color:#b91c1c;">*</span></label>
            <div class="w1c-plate-row">
                <input type="text" name="plate_left" maxlength="2" required placeholder="۱۲" title="دو رقم">
                <input type="text" name="plate_letter" maxlength="3" required placeholder="ب" title="حرف">
                <input type="text" name="plate_middle" maxlength="3" required placeholder="۳۴۵" title="سه رقم">
                <input type="text" name="plate_right" maxlength="2" required placeholder="۶۷" title="دو رقم">
            </div>

            <label for="vin">VIN (اختیاری)</label>
            <input type="text" id="vin" name="vin" maxlength="17" placeholder="۱۷ کاراکتر">

            <label for="chassis_no">شماره شاسی (اختیاری)</label>
            <input type="text" id="chassis_no" name="chassis_no" maxlength="20">

            <label for="engine_no">شماره موتور (اختیاری)</label>
            <input type="text" id="engine_no" name="engine_no" maxlength="20">

            <label for="notes">یادداشت (اختیاری)</label>
            <textarea id="notes" name="notes" maxlength="500"></textarea>

            <input type="hidden" name="brand_id" value="10">
            <input type="hidden" name="model_id" value="25">
            <input type="hidden" name="vehicle_class" value="sedan">

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

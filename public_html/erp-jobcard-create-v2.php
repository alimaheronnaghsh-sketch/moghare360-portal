<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — JobCard Create v2 (Wave 1C)
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
    <title>ثبت کارت کار v2 — MOGHARE360</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap">
    <header class="w1c-banner">
        <h1>ثبت کارت کار — Critical Form v2</h1>
        <p>Critical Form v2 — Validation First · نوشتن در پایگاه داده در Wave 1C غیرفعال است</p>
    </header>

    <section class="w1c-card w1c-note">
        پیش‌نمایش Wave 1C — شناسه مشتری و خودرو به‌صورت عددی دستی وارد می‌شود؛ بدون جستجوی پایگاه داده.
    </section>

    <section class="w1c-card">
        <form class="w1c-form" method="post" action="submit-jobcard-v2.php">
            <label for="customer_id">شناسه مشتری <span style="color:#b91c1c;">*</span></label>
            <input type="number" id="customer_id" name="customer_id" min="1" required placeholder="مثال: 100">

            <label for="vehicle_id">شناسه خودرو <span style="color:#b91c1c;">*</span></label>
            <input type="number" id="vehicle_id" name="vehicle_id" min="1" required placeholder="مثال: 200">

            <label for="reception_date">تاریخ پذیرش <span style="color:#b91c1c;">*</span></label>
            <input type="date" id="reception_date" name="reception_date" required>

            <label for="odometer">کارکرد (کیلومتر) — اختیاری</label>
            <input type="text" id="odometer" name="odometer" inputmode="numeric" placeholder="125000">

            <label for="complaint_text">شرح شکایت / درخواست <span style="color:#b91c1c;">*</span></label>
            <textarea id="complaint_text" name="complaint_text" maxlength="1000" required placeholder="مثال: صدای غیرعادی موتور"></textarea>

            <label for="notes">یادداشت (اختیاری)</label>
            <textarea id="notes" name="notes" maxlength="500"></textarea>

            <input type="hidden" name="jobcard_type" value="repair">
            <input type="hidden" name="service_category" value="mechanical">

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

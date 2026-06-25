<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Critical Forms v2 Live Preview Index (Wave 1C)
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

function wave1c_index_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$forms = [
    ['href' => 'erp-customer-create-v2.php', 'title' => 'ثبت مشتری v2', 'key' => 'customer_create_v2'],
    ['href' => 'erp-vehicle-create-v2.php', 'title' => 'ثبت خودرو v2', 'key' => 'vehicle_create_v2'],
    ['href' => 'erp-jobcard-create-v2.php', 'title' => 'ثبت کارت کار v2', 'key' => 'jobcard_create_v2'],
];

$tests = [
    ['href' => 'erp-critical-forms-v2-validation-test.php', 'title' => 'تست اعتبارسنجی Wave 1B'],
    ['href' => 'erp-validation-engine-runtime-test.php', 'title' => 'تست موتور Wave 1A'],
];

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Critical Forms v2 Live Preview — Wave 1C</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap" style="max-width:960px;">
    <header class="w1c-banner">
        <h1>Critical Forms v2 — Live Preview (Wave 1C)</h1>
        <p>WAVE 1C validation-first runtime preview · DB writes intentionally not activated · No official production use yet</p>
    </header>

    <section class="w1c-card w1c-note">
        جریان قفل‌شده: UI → Validation Engine → Submit کنترل‌شده → پیش‌نمایش نتیجه (بدون نوشتن DB در Wave 1C)
    </section>

    <section class="m37-sr-ready-panel" style="margin:0;">
        <h2 class="m37-sr-ready-title" style="font-size:1.15rem;">فرم‌های v2</h2>
        <div class="m37-sr-module-grid">
            <?php foreach ($forms as $form): ?>
                <a class="m37-sr-module-card" href="<?= wave1c_index_h($form['href']) ?>">
                    <span class="m37-sr-module-icon">v2</span>
                    <span class="m37-sr-module-title"><?= wave1c_index_h($form['title']) ?></span>
                    <span class="m37-sr-module-sub"><?= wave1c_index_h($form['key']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="m37-sr-ready-panel" style="margin:0;">
        <h2 class="m37-sr-ready-title" style="font-size:1.15rem;">صفحات تست</h2>
        <div class="m37-sr-module-grid">
            <?php foreach ($tests as $test): ?>
                <a class="m37-sr-module-card" href="<?= wave1c_index_h($test['href']) ?>">
                    <span class="m37-sr-module-icon">T</span>
                    <span class="m37-sr-module-title"><?= wave1c_index_h($test['title']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="w1c-card">
        <h2 style="margin:0 0 0.5rem;font-size:1rem;">محدودیت‌های Wave 1C</h2>
        <ul class="m37-sr-boundary-list">
            <li>نوشتن در پایگاه داده عمداً غیرفعال است</li>
            <li>بدون تغییر auth / config / permissions</li>
            <li>بدون استفاده رسمی تولیدی</li>
            <li>مرحله بعد: Wave 1D — فعال‌سازی DB-write پس از تأیید مالک</li>
        </ul>
    </section>
</div>
</body>
</html>

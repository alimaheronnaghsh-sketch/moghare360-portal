<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — JobCard Diagnostic File Upload (Wave 2C)
 * Controlled diagnostic PDF/image only — not camera photo capture
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-diagnostic-file-helper.php';

$stages = moghare360_diagnostic_allowed_stages();
$types = moghare360_diagnostic_allowed_types();

function wave2c_diag_h(string $value): string
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
    <title>ثبت فایل تشخیصی — کارت کار</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w2c-wrap">
    <header class="w1c-banner w2c-banner">
        <h1>ثبت فایل تشخیصی — کارت کار</h1>
        <p>Wave 2C — Controlled diagnostic file binding</p>
    </header>

    <section class="w1c-card w2c-warning">
        <strong>Only controlled diagnostic PDF/image files are accepted.</strong>
        <p style="margin:0.5rem 0 0;font-size:0.88rem;">فقط فایل‌های PDF/تصویر تشخیصی کنترل‌شده پذیرفته می‌شوند — این صفحه جایگزین دوربین عکس نیست.</p>
    </section>

    <section class="w1c-card w1c-form">
        <form method="post" action="submit-jobcard-diagnostic-file.php" enctype="multipart/form-data">
            <label for="jobcard_id">شناسه کارت کار <span style="color:#b91c1c;">*</span></label>
            <input type="number" id="jobcard_id" name="jobcard_id" min="1" required placeholder="مثال: 1">

            <label for="diagnostic_stage">مرحله تشخیصی <span style="color:#b91c1c;">*</span></label>
            <select id="diagnostic_stage" name="diagnostic_stage" required>
                <?php foreach ($stages as $stage): ?>
                    <option value="<?= wave2c_diag_h($stage) ?>"><?= wave2c_diag_h($stage) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="diagnostic_type">نوع گزارش تشخیصی <span style="color:#b91c1c;">*</span></label>
            <select id="diagnostic_type" name="diagnostic_type" required>
                <?php foreach ($types as $type): ?>
                    <option value="<?= wave2c_diag_h($type) ?>"><?= wave2c_diag_h($type) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="diagnostic_file">فایل تشخیصی (PDF / JPG / PNG / WEBP) <span style="color:#b91c1c;">*</span></label>
            <input type="file" id="diagnostic_file" name="diagnostic_file" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp" required>

            <button type="submit" class="w1c-btn">ثبت فایل تشخیصی</button>
        </form>
    </section>

    <nav class="w1c-card w1c-links">
        <a href="erp-jobcard-diagnostic-preview.php?jobcard_id=1">پیش‌نمایش فایل‌های تشخیصی</a>
        <a href="erp-jobcard-camera-capture.php">ثبت تصویر دوربین (جداگانه)</a>
        <a href="erp-critical-forms-v2-live-preview.php">فهرست پیش‌نمایش</a>
    </nav>
</div>
</body>
</html>

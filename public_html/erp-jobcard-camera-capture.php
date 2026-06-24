<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — JobCard Camera Capture (Wave 2A)
 * Camera-only · no file upload input
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'moghare360-camera-media-helper.php';

$stages = moghare360_camera_media_allowed_stages();
$types = moghare360_camera_media_allowed_types();

function wave2a_cam_h(string $value): string
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
    <title>ثبت تصویر دوربین — کارت کار</title>
    <link rel="stylesheet" href="assets/moghare360-ui/moghare360-soft-run-release.css">
</head>
<body style="background:#f5f5f5;margin:0;padding:1.5rem;color:#171717;">
<div class="w1c-wrap w2a-wrap">
    <header class="w1c-banner w2a-banner">
        <h1>ثبت تصویر دوربین — کارت کار</h1>
        <p>Camera-only capture — upload bypass is disabled</p>
    </header>

    <section class="w1c-card w2a-warning">
        <strong>فقط دوربین مستقیم</strong> — آپلود فایل، انتخاب گالری و آدرس خارجی غیرفعال است.
    </section>

    <section class="w1c-card">
        <div class="w2a-camera-box">
            <video id="w2aVideo" class="w2a-video" autoplay playsinline muted></video>
            <canvas id="w2aCanvas" class="w2a-canvas" hidden></canvas>
            <img id="w2aPreview" class="w2a-preview" alt="پیش‌نمایش" hidden>
        </div>
        <p id="w2aCameraStatus" class="w2a-status">در حال درخواست دسترسی دوربین…</p>
        <button type="button" id="w2aCaptureBtn" class="w1c-btn" disabled>گرفتن عکس از دوربین</button>
    </section>

    <section class="w1c-card">
        <form id="w2aForm" class="w1c-form" method="post" action="submit-jobcard-camera-capture.php">
            <label for="jobcard_id">شناسه کارت کار <span style="color:#b91c1c;">*</span></label>
            <input type="number" id="jobcard_id" name="jobcard_id" min="1" required placeholder="مثال: 1">

            <label for="media_stage">مرحله رسانه <span style="color:#b91c1c;">*</span></label>
            <select id="media_stage" name="media_stage" required>
                <?php foreach ($stages as $stage): ?>
                    <option value="<?= wave2a_cam_h($stage) ?>"><?= wave2a_cam_h($stage) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="media_type">نوع رسانه <span style="color:#b91c1c;">*</span></label>
            <select id="media_type" name="media_type" required>
                <?php foreach ($types as $type): ?>
                    <option value="<?= wave2a_cam_h($type) ?>"><?= wave2a_cam_h($type) ?></option>
                <?php endforeach; ?>
            </select>

            <input type="hidden" id="camera_data" name="camera_data" value="">
            <button type="submit" id="w2aSubmitBtn" class="w1c-btn" disabled>ذخیره تصویر دوربین</button>
        </form>
    </section>

    <nav class="w1c-card w1c-links">
        <a href="erp-jobcard-media-preview.php?jobcard_id=1">پیش‌نمایش رسانه</a>
        <a href="erp-critical-forms-v2-live-preview.php">فهرست پیش‌نمایش فرم‌ها</a>
    </nav>
</div>
<script>
(function () {
    const video = document.getElementById('w2aVideo');
    const canvas = document.getElementById('w2aCanvas');
    const preview = document.getElementById('w2aPreview');
    const statusEl = document.getElementById('w2aCameraStatus');
    const captureBtn = document.getElementById('w2aCaptureBtn');
    const submitBtn = document.getElementById('w2aSubmitBtn');
    const cameraData = document.getElementById('camera_data');
    let stream = null;

    async function startCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            statusEl.textContent = 'مرورگر از دوربین پشتیبانی نمی‌کند.';
            return;
        }
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
            video.srcObject = stream;
            statusEl.textContent = 'دوربین فعال است — آماده ثبت تصویر.';
            captureBtn.disabled = false;
        } catch (err) {
            statusEl.textContent = 'دسترسی به دوربین رد شد یا در دسترس نیست.';
        }
    }

    captureBtn.addEventListener('click', function () {
        const width = video.videoWidth || 640;
        const height = video.videoHeight || 480;
        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext('2d');
        if (!ctx) {
            statusEl.textContent = 'خطا در آماده‌سازی canvas.';
            return;
        }
        ctx.drawImage(video, 0, 0, width, height);
        const dataUrl = canvas.toDataURL('image/jpeg', 0.92);
        cameraData.value = dataUrl;
        preview.src = dataUrl;
        preview.hidden = false;
        submitBtn.disabled = false;
        statusEl.textContent = 'تصویر از دوربین گرفته شد — آماده ارسال.';
    });

    window.addEventListener('beforeunload', function () {
        if (stream) {
            stream.getTracks().forEach(function (track) { track.stop(); });
        }
    });

    startCamera();
})();
</script>
</body>
</html>

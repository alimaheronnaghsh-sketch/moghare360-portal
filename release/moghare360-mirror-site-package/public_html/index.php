<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'mirror-layout.php';

mirror_render_head('ورود به MOGHARE360 — رابط آینه', 'index');
?>
<section class="m360-hero">
    <h2>سامانه ERP تعمیرگاهی MOGHARE360</h2>
    <p>پورتال یکپارچه خدمات خودرو — رابط آینه moghareh360.ir. درخواست‌ها به سرور اصلی (Master Server) ارسال می‌شوند؛ هیچ داده‌ای روی هاست Mirror ذخیره نمی‌شود.</p>
</section>

<div class="m360-grid m360-role-grid">
    <a class="m360-card m360-role-card" href="customer-request.php">
        <h3>مشتری</h3>
        <p>ثبت درخواست آنلاین پذیرش، مشاوره و پیگیری</p>
        <span class="m360-badge m360-badge-ok">بدون ذخیره روی هاست</span>
    </a>
    <a class="m360-card m360-role-card" href="staff-login.php">
        <h3>پرسنل</h3>
        <p>ورود پرسنل — احراز هویت از Master Server</p>
        <span class="m360-badge m360-badge-ok">Mirror Auth</span>
    </a>
    <a class="m360-card m360-role-card" href="owner-login.php">
        <h3>مالک سیستم</h3>
        <p>ورود مالک و مدیریت دسترسی کاربران</p>
        <span class="m360-badge m360-badge-warn">Owner Only</span>
    </a>
    <a class="m360-card m360-role-card" href="company-owner-dashboard.php">
        <h3>مالک کمپانی</h3>
        <p>گزارش مدیریتی — داده فقط از Master Server</p>
        <span class="m360-badge m360-badge-ok">Read from Master</span>
    </a>
</div>

<section class="m360-card" style="margin-top:1rem">
    <h3>وضعیت آینه</h3>
    <p><a href="mirror-health.php">بررسی سلامت اتصال به Master Server</a></p>
</section>
<?php mirror_render_foot(); ?>

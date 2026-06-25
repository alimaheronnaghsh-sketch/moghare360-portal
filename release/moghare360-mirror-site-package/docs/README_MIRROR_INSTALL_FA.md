# نصب Mirror Website — moghareh360.ir

## هدف
آپلود `public_html` روی هاست دامنه به‌عنوان **رابط آینه** — بدون دیتابیس و بدون ذخیره داده.

## مراحل
1. ZIP را Extract کنید.
2. محتویات `public_html/` را در root سایت آپلود کنید.
3. `mirror-config.example.php` را به `mirror-config.php` کپی کنید.
4. `MASTER_SERVER_BASE_URL` را به آدرس Master Server (لپ‌تاپ) تنظیم کنید.
5. `mirror-health.php` را باز کنید.

## نقش‌ها
- مشتری: customer-request.php
- پرسنل: staff-login.php
- مالک: owner-login.php
- مالک کمپانی: company-owner-dashboard.php

## PWA
manifest.webmanifest و service-worker.js را حذف نکنید.

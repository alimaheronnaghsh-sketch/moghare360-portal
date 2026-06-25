# راهنمای اجرای سریع — MOGHARE360 Desktop Run Package

## این بسته چیست؟

بسته **اجرای محلی** MOGHARE360 ERP برای ویندوز است. شامل `public_html` امن (بدون config واقعی)، SQL، tools و لانچرهاست.

## پیش‌نیازها

- Windows 10/11
- XAMPP (Apache + PHP)
- SQL Server یا SQL Express
- ODBC Driver for SQL Server

## اجرای سریع

1. فایل ZIP را Extract کنید.
2. `CHECK_REQUIREMENTS.ps1` را اجرا کنید.
3. در صورت نیاز: `INSTALL_LOCAL_COPY.ps1` (کپی امن به `C:\xampp\htdocs\moghare360`)
4. XAMPP → Apache Start
5. SQL Server را روشن کنید
6. `private/erp-config.php` را **خارج از این بسته** طبق راهنما بسازید (این بسته credential ندارد)
7. `START_MOGHARE360.bat` یا `START_MOGHARE360.ps1`

## URL ورود

`http://localhost:8080/moghare360/`

## محدودیت‌ها

- این بسته **Production Installer** نیست
- **SaaS عمومی** فعال نیست
- **داده مشتری واقعی** داخل ZIP نیست
- **private config** داخل ZIP نیست — باید روی سیستم مقصد تنظیم شود

## چه چیزهایی داخل این بسته نیست

- Cloud database
- Host storage
- Payment gateway
- Real customer backups
- Uploads / logs / credentials

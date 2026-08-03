# گزارش پاک‌سازی دادهٔ تست و بسته Runtime تمیز — MOGHARE360 V1

**تاریخ:** 2026-08-03  
**Release HEAD:** `ba37ad87d63dc60310c0b6575108d517b4145026`  
**شاخه:** `feature/workshop-service-sales`

## مجوز مالک

| کلید | مقدار |
|------|--------|
| CURRENT_SITE_BACKUP_REQUIRED | no |
| CURRENT_SITE_FILE_DELETION_AUTHORIZED | yes |
| TEST_TRANSACTION_DATA_RESET_AUTHORIZED | yes |
| DATABASE_DROP_AUTHORIZED | no |
| SCHEMA_DROP_AUTHORIZED | no |
| PERSONNEL_IDENTITY_DELETE_AUTHORIZED | no |
| SYSTEM_OWNER_DELETE_AUTHORIZED | no |

## Active webroot

حذف فایل‌های سایت فعال **انجام نشد**.

کاندیداها:

1. `C:\xampp\htdocs\moghare360` — runtime محلی XAMPP (به `www.moghareh360.ir` بایند نشده)
2. `C:\inetpub\wwwroot` — فقط صفحه پیش‌فرض IIS
3. میزبان DNS `www.moghareh360.ir` → `5.144.129.183` — مسیر فیزیکی روی سرور **نامشخص** / بدون دسترسی تأییدشده

وضعیت: `ACTIVE_WEBROOT_AMBIGUOUS_BLOCKER`

## بسته Runtime

| مورد | مقدار |
|------|--------|
| پوشه | `C:\MOGHARE360\packages\MOGHARE360_V1_RUNTIME_ba37ad87d63d\` |
| ZIP | `C:\MOGHARE360\packages\MOGHARE360_V1_RUNTIME_ba37ad87d63d.zip` |
| SHA256 | `E27A0E09B36F4427C3439DA02F9D69CA6B31DB985F0DFF8822AA6457DACE5AF7` |
| Webroot size | ~10.86 MB |
| Package size | ~11.34 MB |
| محدودیت 1GB | PASS |

## Reset دیتابیس

| قبل | بعد |
|-----|-----|
| customers 43 | 0 |
| vehicles 34 | 0 |
| online requests 64 | 0 |
| JobCards 18 | 0 |
| invoices 8 | 0 |
| payments 3 | 0 |
| core_users 41 | 41 |
| M360-100001 | present |

- DATABASE_DROPPED=no · TABLES_DROPPED=no · SCHEMA_PRESERVED=yes  
- Workshop permissions: 42 ENFORCED / 0 NOT_YET  
- `p360_employees` preserved (=31 rows)  
- شواهد شمارشی: `C:\MOGHARE360\evidence\pre-reset-counts.json` و `post-reset-counts.json` (بدون محتوای خصوصی)

### هشدار کاتالوگ

کشف گسترده جداول `erp_*` / `inv360_*` برخی ردیف‌های مرجع عملیاتی (مثلاً دسته‌بندی/انبار inv360 و چند قیمت‌لیست مالی آزمایشی) را نیز پاک کرد. اسکیما حفظ شده است؛ در صورت نیاز باید از اسکریپت‌های seed مهاجرت دوباره بارگذاری شوند.

## اعتبارسنجی بسته

- PHP lint: 779/779  
- PWA UAT: 35/35  
- بدون `public_html` تو در تو  
- بدون `mirror-config.php` خصوصی در بسته  
- Smoke ایزوله روی `http://127.0.0.1:8080/moghare360_v1_clean/` → HTTP 200

## توقف

`OWNER_GATE_REAL_USER_PILOT_REQUIRED` — پس از تعیین webroot Production و استقرار بستهٔ تأییدشده.

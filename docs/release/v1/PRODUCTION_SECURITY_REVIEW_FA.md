# بازبینی امنیت Production — Phase 7

**محصول:** MOGHARE360 V1  
**فاز:** Phase 7 — Production Preparation  
**زبان سند:** فارسی (RTL) + شناسه‌های فنی انگلیسی

> این چک‌لیست قبل از Go-Live تکمیل شود. هیچ رمز واقعی، کلید API، یا hostname محرمانه در سند ثبت نشود.

---

## ۱. دامنه و غیر‌دامنه

| داخل | خارج |
|------|------|
| پیکربندی IIS/PHP/SQL، مرز web root، کوکی، HTTPS، آپلود، مسدودسازی مسیرها، اسرار در repo | تست نفوذ کامل شخص ثالث، سخت‌سازی کامل OS سازمان |

معماری الزامی: Windows VPS · IIS · SQL Server · یک DB writable (`moghare360_ERP`) · HTTPS · config خصوصی · backup offsite · **بدون XAMPP به‌عنوان Prod**.

---

## ۲. چک‌لیست امنیت (اجباری)

### 2.1 Debug و خطا

| # | کنترل | PASS؟ |
|---|--------|-------|
| S01 | `debug` = false در config production | ☐ |
| S02 | `display_errors_to_browser` = false | ☐ |
| S03 | PHP `display_errors=Off` در محیط Prod | ☐ |
| S04 | خطای کاربر: پیام فارسی عمومی؛ جزئیات فقط در log داخلی | ☐ |

### 2.2 Transport و کوکی

| # | کنترل | PASS؟ |
|---|--------|--------|
| S05 | فقط HTTPS برای ERP عمومی | ☐ |
| S06 | Redirect HTTP→HTTPS | ☐ |
| S07 | Session cookie: Secure | ☐ |
| S08 | Session cookie: HttpOnly | ☐ |
| S09 | SameSite مناسب سیاست (Lax/Strict) | ☐ |
| S10 | بدون سرویس ERP روی HTTP خالص | ☐ |

### 2.3 Config و اسرار

| # | کنترل | PASS؟ |
|---|--------|--------|
| S11 | `erp-config.php` واقعی خارج از web root | ☐ |
| S12 | فقط `erp-config.example.php` در git (placeholders) | ☐ |
| S13 | هیچ پسورد/توکن در repo / artifact عمومی | ☐ |
| S14 | Connection string در HTML/JS/log عمومی چاپ نشود | ☐ |
| S15 | متغیر `MOGHARE360_ERP_CONFIG_PATH` در صورت استفاده صحیح و محدود | ☐ |

### 2.4 مرز HTTP و مسیرها

| # | کنترل | PASS؟ |
|---|--------|--------|
| S16 | `tools/` از HTTP مسدود (IIS deny / معادل `Require all denied`) | ☐ |
| S17 | اسکریپت‌های migration از HTTP مسدود | ☐ |
| S18 | Directory listing خاموش روی سایت | ☐ |
| S19 | مسیرهای `.git` / vendor حساس / sql dump در web سرو نشوند | ☐ |
| S20 | وابستگی Production به XAMPP وجود ندارد | ☐ |

### 2.5 آپلود و ذخیره‌سازی

| # | کنترل | PASS؟ |
|---|--------|--------|
| S21 | اعتبارسنجی نوع/اندازه فایل آپلود | ☐ |
| S22 | Private storage خارج از اجرای مستقیم عمومی یا با deny | ☐ |
| S23 | Quarantine عمومی نیست (PLACEHOLDER_QUARANTINE_PATH) | ☐ |
| S24 | نام فایل ذخیره‌شده قابل حدس ساده برای داده حساس نباشد (در حد سیاست فعلی) | ☐ |

### 2.6 پایگاه‌داده

| # | کنترل | PASS؟ |
|---|--------|--------|
| S25 | فقط `moghare360_ERP` writable برای اپ | ☐ |
| S26 | بدون dual-writable / sync دو‌طرفه مخرب | ☐ |
| S27 | Login اپ حداقل‌دسترسی (نه sysadmin) | ☐ |
| S28 | پورت SQL به اینترنت عمومی باز نیست | ☐ |

### 2.7 Artifact و web root hygiene

| # | کنترل | PASS؟ |
|---|--------|--------|
| S29 | هیچ backup واقعی داخل `public_html` | ☐ |
| S30 | هیچ APK/AAB/keystore داخل web root | ☐ |
| S31 | هیچ `.env` با راز داخل web root | ☐ |
| S32 | Manifest SHA256 با artifact مطابقت دارد | ☐ |

### 2.8 Android TWA / Digital Asset Links (در صورت استفاده)

| # | کنترل | PASS؟ |
|---|--------|--------|
| S33 | فقط فایل example در repo؛ مقادیر Prod با PLACEHOLDER تکمیل روی سرور | ☐ |
| S34 | `assetlinks.json` نهایی فقط پس از تأیید package name و SHA256 گواهی امضا | ☐ |
| S35 | APK امضاشده commit نشود | ☐ |

---

## ۳. نمونه‌های PLACEHOLDER (نه مقادیر واقعی)

```text
PLACEHOLDER_PROD_FQDN
PLACEHOLDER_PRIVATE_CONFIG_PATH
PLACEHOLDER_PRIVATE_STORAGE_PATH
PLACEHOLDER_QUARANTINE_PATH
PLACEHOLDER_SQL_APP_LOGIN
PLACEHOLDER_ANDROID_PACKAGE_NAME
PLACEHOLDER_ANDROID_CERT_SHA256
```

---

## ۴. نتیجه بازبینی

| فیلد | مقدار |
|------|--------|
| Review ID | PLACEHOLDER_SEC_REVIEW_ID |
| Reviewer | PLACEHOLDER_SEC_REVIEWER |
| UTC | PLACEHOLDER_SEC_REVIEW_UTC |
| Failed controls | PLACEHOLDER_SEC_FAILED_IDS |
| Overall | ☐ PASS · ☐ PASS با استثنای مکتوب · ☐ BLOCKED |

استثنا فقط با امضای Owner: PLACEHOLDER_OWNER_APPROVER و تاریخ انقضا PLACEHOLDER_EXCEPTION_EXPIRY.

---

## ۵. مراجع

- `PRODUCTION_PREREQUISITES_FA.md`
- `PRODUCTION_HEALTH_CHECK_FA.md`
- `deployment/iis/README.md`
- `public_html/.well-known/assetlinks.json.example`
- `android-twa/twa-manifest.example.json`
- `docs/deployment/MOGHARE360_V1_ONLINE_TEST_SECURITY_LOCK.md`

# MOGHARE360 — cPanel Clean Deploy Checklist

**Package:** `release/moghare360-cpanel-public-final-clean.zip`  
**Target host path:** `/home2/moghareh/public_html`  
**Build command (local):** `powershell -ExecutionPolicy Bypass -File tools\package-moghare360-cpanel-public-final-clean.ps1`  
**Verify command (local):** `C:\xampp\php\php.exe tools\test-v1-cpanel-clean-deploy-package.php`

---

## قبل از Deploy

- [ ] از فایل **`mirror-config.php`** فعلی روی سرور یک **backup** بگیرید (دانلود یا کپی با نام `mirror-config.php.backup-YYYYMMDD`)
- [ ] در مرورگر خود (و موبایل) **cache** و **service worker** قدیمی را پاک کنید (بخش Emergency Cache Clear پایین)
- [ ] مطمئن شوید ZIP صحیح است: **`moghare360-cpanel-public-final-clean.zip`** (نه mirror-site قدیمی با `public_html/` تو در تو)

---

## مراحل Deploy در cPanel

### 1) Backup تنظیمات

1. File Manager → `public_html/mirror-config.php`
2. Download یا Rename به `mirror-config.php.backup-YYYYMMDD`
3. **هرگز** این فایل را با ZIP جدید overwrite نکنید

### 2) پاک‌سازی cache مرورگر (قبل از upload)

1. Chrome/Edge: DevTools → Application → Service Workers → **Unregister**
2. Application → Storage → **Clear site data**
3. Hard refresh: `Ctrl+Shift+R` (یا `Cmd+Shift+R`)

### 3) Upload ZIP

1. File Manager → `public_html`
2. Upload: `moghare360-cpanel-public-final-clean.zip`
3. **Extract Here** (در همان `public_html` — نه داخل زیرپوشه)

### 4) بررسی ساختار flat (بسیار مهم)

بعد از Extract باید **مستقیم** این فایل‌ها در `public_html` باشند:

```
public_html/index.php
public_html/customer-request.php
public_html/assets/css/mirror.css
public_html/service-worker.js
```

**ممنوع — اگر دیدید اشتباه است:**

```
public_html/public_html/index.php   ← nested اشتباه
public_html/release/*.zip           ← ZIP باقی‌مانده داخل سایت
```

اگر `public_html/public_html/` ساخته شد:
- محتوای داخلی را یک سطح بالا منتقل کنید
- پوشه خالی `public_html/public_html` را حذف کنید

### 5) حفظ mirror-config.php

1. اگر Extract فایل `mirror-config.php` را overwrite کرد → از backup بازگردانید
2. اگر فقط `mirror-config.example.php` آمد → **تغییر ندهید**؛ `mirror-config.php` واقعی باید از قبل روی host باشد
3. در `mirror-config.php` مقدار `MASTER_SERVER_BASE_URL` را دست نزنید مگر در فاز API جداگانه

### 6) حذف ZIP از سرور

- [ ] `moghare360-cpanel-public-final-clean.zip` را از `public_html` **حذف** کنید

### 7) چک فایل‌های حیاتی

| فایل | باید وجود داشته باشد |
|------|----------------------|
| `index.php` | بله — مدل عمومی (خوش آمدید / مشتری / پرسنل) |
| `customer-request.php` | بله |
| `staff-login.php` | بله |
| `owner-login.php` | بله |
| `includes/mirror-layout.php` | بله |
| `assets/css/mirror.css` | بله |
| `assets/css/moghare360-v1-luxury-ui.css` | بله |
| `service-worker.js` | بله |
| `mirror-config.php` | بله (از قبل — backup شده) |
| `api/sync/debug-pending.php` | **نباید** در package عمومی باشد |

### 8) تأیید نسخه CSS/JS

1. باز کردن: `https://moghareh360.ir/customer-request.php`
2. DevTools → Network → `mirror.css` → Status **200**
3. Response باید شامل `max-height: 48px` و `m360-brand-latin` باشد
4. Hard refresh (`Ctrl+Shift+R`)

### 9) تأیید برند و لوگو

- [ ] متن برند: **MOGHAREH360** (نه حروف جدا در RTL)
- [ ] لوگو ارتفاع حدود **۴۸px** دسکتاپ / **۴۰px** موبایل
- [ ] View Source: `<meta charset="UTF-8">` و `<html lang="fa" dir="rtl">`

### 10) اگر هنوز مشکل visual بود — Service Worker

1. DevTools → Application → Service Workers → **Unregister**
2. Application → Cache Storage → حذف cacheهای `moghare360-public-*`
3. صفحه را دوباره بارگذاری کنید
4. cache جدید باید نام **`moghare360-public-v1-final-clean-20260626`** داشته باشد

---

## Emergency Rollback

### بازگشت سریع

1. از backup قبلی `public_html` (اگر دارید) یا فایل‌های کلیدی را restore کنید
2. **`mirror-config.php`** را از `mirror-config.php.backup-YYYYMMDD` بازگردانید
3. Service worker را unregister کنید
4. Cache storage را پاک کنید

### بازگشت جزئی (فقط UI)

1. فقط این فایل‌ها را از backup قبل از deploy برگردانید:
   - `index.php`, `includes/mirror-layout.php`
   - `assets/css/mirror.css`, `assets/css/moghare360-v1-luxury-ui.css`
   - `service-worker.js`
2. `mirror-config.php` را **همیشه** از backup واقعی برگردانید

### علائم deploy اشتباه

| علامت | علت محتمل |
|-------|-----------|
| لوگو خیلی بزرگ | CSS قدیمی cache شده یا `mirror.css` لود نشده |
| MOGHAREH360 حروف جدا | CSS `m360-brand-latin` لود نشده یا charset اشتباه |
| صفحه Master ERP / SQL Server | `index.php` اشتباه (نسخه local نه cPanel) |
| Asset 404 | nested `public_html/public_html` |

---

## Emergency Cache Clear (کاربر نهایی)

**Chrome / Edge (Desktop):**

1. F12 → Application
2. Service Workers → Unregister
3. Cache Storage → Delete All
4. Ctrl+Shift+R

**Safari iOS:**

1. Settings → Safari → Clear History and Website Data (یا فقط برای سایت)
2. صفحه را ببندید و دوباره باز کنید

**PWA نصب‌شده:**

1. اپ را حذف کنید
2. cache مرورگر را پاک کنید
3. دوباره از مرورگر باز کنید

---

## پس از Deploy موفق

- [ ] `mirror-health.php` را باز کنید
- [ ] `customer-request.php` فرم را visual چک کنید
- [ ] `staff-login.php` و `owner-login.php` بدون خطای PHP باز شوند
- [ ] ZIP روی سرور حذف شده باشد
- [ ] هیچ `config.php` / `erp-config.php` در `public_html` نباشد

---

*این checklist فقط راهنمای deploy است — هیچ تغییری روی سرور توسط repo انجام نمی‌شود.*

# راهنمای به‌روزرسانی و Rollback پوستهٔ PWA — MOGHARE360 / MOGHAREH360

**محصول:** MOGHARE360 / MOGHAREH360  
**نسخه سند:** V1  
**زبان:** فارسی (RTL) + شناسه‌های فنی انگلیسی  
**هدف:** به‌روزرسانی امن پوستهٔ استاتیک و بازگشت کنترل‌شده

---

## ۱. محدوده

این سند فقط لایهٔ **PWA / static shell** را پوشش می‌دهد:

| فایل / مسیر | نقش |
|-------------|-----|
| `public_html/service-worker.js` | SW + `CACHE_VERSION` |
| `public_html/manifest.webmanifest` | هویت نصب |
| `public_html/offline.html` | پوستهٔ آفلاین |
| `public_html/assets/js/m360-pwa.js` | ثبت SW / install / update |
| CSS/آیکون‌های allowlist در PRECACHE | پوستهٔ بصری |

شامل نمی‌شود: منطق ERP، migration دیتابیس، یا تغییر `applicationId` اندروید (جز اشاره به هماهنگی نسخه).

---

## ۲. شناسهٔ کش فعلی

```text
CACHE_VERSION = m360-pwa-static-v1-20260803
```

هر انتشار پوسته باید این رشته را به مقدار یکتا و صعودی/تاریخ‌دار جدید تغییر دهد تا کلاینت‌ها کش کهنه را دور بریزند.

---

## ۳. سیاست امنیتی (غیرقابل مذاکره)

**مجاز برای کش:** فقط استاتیک عمومی نسخه‌دار (CSS/JS/icons/manifest/offline).

**ممنوع برای کش:**

- HTML احراز هویت و صفحات `*.php`
- فاکتور، OTP، پروفایل، نشست، آپلود
- `/api/` و هر پاسخ حساس

Rollback هرگز نباید این سیاست را سست کند (مثلاً برگرداندن SW قدیمی که HTML را cache-first می‌کرد ممنوع است اگر از آن نسل عبور کرده‌اید).

---

## ۴. پیش‌نیاز به‌روزرسانی

- [ ] Backup/artifact نسخهٔ فعلی PWA در `release-artifacts/` یا آرشیو انتشار موجود است
- [ ] Production روی **HTTPS** است (Local: `http://127.0.0.1:8080/moghare360/` فقط برای تست)
- [ ] `CACHE_VERSION` جدید با محتوای فایل‌ها هم‌خوان است
- [ ] بدون قرار دادن secret در فایل‌های استاتیک

---

## ۵. روند به‌روزرسانی (Update)

### ۵.۱ آماده‌سازی

1. از شاخه/artifact انتشار، فایل‌های پوسته را آماده کنید.
2. `CACHE_VERSION` را به شناسهٔ جدید تغییر دهید (مثال: `m360-pwa-static-v1-YYYYMMDD`).
3. در صورت تغییر آیکون/CSS، query version در manifest و لینک‌ها را هم‌تراز کنید.

### ۵.۲ استقرار

1. فایل‌ها را روی origin عمومی جایگزین کنید (`public_html` / مسیر معادل cPanel).
2. از یک مرورگر تمیز یا Incognito صفحه را باز کنید.
3. Service Worker جدید باید install → activate شود.
4. Cache قدیمی در رویداد `activate` حذف می‌شود.

### ۵.۳ رفتار کلاینت

- `m360-pwa.js` روی `updatefound` پیام `SKIP_WAITING` می‌فرستد.
- ممکن است `data-m360-pwa-update="ready"` روی `body` ست شود.
- کاربران PWA/TWA با یک بار بستن/باز کردن یا Refresh به پوستهٔ جدید می‌رسند.
- صفحات ERP همچنان network-only هستند.

### ۵.۴ تأیید پس از Update

| بررسی | انتظار |
|-------|--------|
| محتوای `service-worker.js` روی سرور | `CACHE_VERSION` جدید |
| Cache Storage کلاینت | فقط کش جدید |
| آفلاین | فقط `offline.html` |
| درخواست PHP/API | بدون ذخیره در Cache Storage |
| Install / standalone | همچنان کار می‌کند |

---

## ۶. روند Rollback

وقتی استفاده کنید: رگرسیون UI عمومی، شکستن SW، آیکون/manifest معیوب، یا cache thrash.

### ۶.۱ انتخاب artifact

1. آخرین نسخهٔ سالم PWA را از آرشیو انتشار انتخاب کنید.
2. مطمئن شوید مجموعهٔ فایل‌ها کامل است (SW + manifest + offline + m360-pwa.js + دارایی‌های precache).
3. اگر `CACHE_VERSION` rollback با نسخهٔ خرابِ فعلی **یکسان** است، قبل از استقرار یک bump اجباری کوچک روی شناسه بدهید تا کلاینت‌ها حتماً invalidate شوند  
   (یا از شناسهٔ artifact سالم استفاده کنید که با کلاینت‌های آلوده فرق داشته باشد).

### ۶.۲ استقرار Rollback

1. فایل‌های پوستهٔ سالم را روی سرور جایگزین کنید.
2. Smoke test روی HTTPS (یا Local).
3. به اپراتورها دستور Clear site data در صورت گیر کردن کلاینت بدهید.

### ۶.۳ اقدامات کلاینت پس از Rollback

| پلتفرم | اقدام |
|--------|--------|
| Chrome / Edge PWA | بستن اپ → Clear site data origin → باز کردن دوباره |
| Android TWA | Clear cache/data اپ در صورت نیاز → باز کردن |
| تبلت ویندوز | همان Clear site data + باز کردن میانبر |

حذف کامل نصب فقط اگر SW unregister نشود یا identity خراب باشد.

### ۶.۴ تأیید Rollback

- [ ] `CACHE_VERSION` روی سرور = نسخهٔ هدف rollback (یا bump آگاهانه)
- [ ] UI عمومی با baseline سالم مطابقت دارد
- [ ] هیچ دادهٔ حساس در کش نیست
- [ ] login / ERP از شبکه لود می‌شود
- [ ] نسخه در release log ثبت شد

---

## ۷. ریست کش بدون Rollback سرور

برای یک دستگاه مشکل‌دار:

1. Logout
2. Unregister Service Worker (DevTools → Application) یا Clear site data
3. بستن همهٔ تب‌ها/پنجرهٔ PWA
4. باز کردن مجدد origin تا precache دوباره ساخته شود

پیام داخلی پشتیبانی‌شده: `CLEAR_CACHES` از طریق `m360-pwa.js` / postMessage به SW.

---

## ۸. حذف نصب و نصب مجدد (آخرین حلقه)

اگر پس از update/rollback همچنان پوسته خراب است:

1. Uninstall PWA / Clear data TWA
2. Clear browsing data برای origin
3. نصب مجدد از راهنماهای:
   - `PWA_INSTALL_GUIDE_FA.md`
   - `ANDROID_APK_INSTALL_GUIDE_FA.md`
   - `WINDOWS_TABLET_INSTALL_GUIDE_FA.md`

---

## ۹. هماهنگی با APK اندروید

- آپدیت پوستهٔ وب معمولاً **بدون** APK جدید کافی است.
- APK جدید فقط وقتی لازم است که wrapper، host، Asset Links، یا `versionCode` عوض شود.
- Rollback وب ≠ نصب APK قدیمی مگر مشکل از خود TWA باشد.

---

## ۱۰. حداقل نیازمندی‌ها و HTTPS

| محیط | شرط |
|------|------|
| Production update/rollback | HTTPS معتبر |
| Local verify | `http://127.0.0.1:8080/moghare360/` |
| مرورگر | Chrome/Edge با پشتیبانی SW |

بدون HTTPS در Production، توزیع یکنواخت SW و نصب PWA قابل اتکا نیست.

---

## ۱۱. ثبت عملیاتی پیشنهادی

برای هر Update یا Rollback ثبت کنید (بدون secret):

- تاریخ/زمان
- `CACHE_VERSION` قبل و بعد
- فهرست فایل‌های جایگزین‌شده
- نتیجهٔ smoke test (Install / offline / no sensitive cache)
- مسئول اجرا

---

## ۱۲. ارجاعات

- `PWA_INSTALL_GUIDE_FA.md`
- `ANDROID_APK_INSTALL_GUIDE_FA.md`
- `WINDOWS_TABLET_INSTALL_GUIDE_FA.md`
- `ANDROID_BUILD_SIGNING_FA.md`

# راهنمای نصب PWA — ماهین 360° / MAHIN 360°

**محصول:** ماهین 360° / MAHIN 360° (کوتاه: MAHIN360)  
**نسخه سند:** V1  
**زبان:** فارسی (RTL) + شناسه‌های فنی انگلیسی  
**محدوده:** نصب Progressive Web App روی دسکتاپ و موبایل

---

## ۱. خلاصه

PWA پوستهٔ عمومی امن است که فقط دارایی‌های استاتیک نسخه‌دار را کش می‌کند. منطق ERP، API، و احراز هویت روی سرور باقی می‌ماند و در کش PWA ذخیره نمی‌شود.

| مورد | مقدار |
|------|--------|
| Manifest | `public_html/manifest.webmanifest` |
| Service Worker | `public_html/service-worker.js` |
| Cache ID | `m360-pwa-static-v1-20260803` |
| Offline shell | `public_html/offline.html` |
| Client helper | `public_html/assets/js/m360-pwa.js` |
| Local (مثال) | `http://127.0.0.1:8080/moghare360/` |
| Production | **فقط HTTPS** |

---

## ۲. حداقل نیازمندی‌ها

| پلتفرم | نیاز |
|--------|------|
| Chrome (Android / Desktop) | نسخهٔ اخیر پایدار؛ پشتیبانی Service Worker |
| Edge (Desktop / Windows) | نسخهٔ مبتنی بر Chromium با پشتیبانی PWA |
| Safari (iOS) | iOS 16.4+ توصیه‌شده برای Add to Home Screen پایدارتر |
| شبکه | Local: HTTP روی localhost مجاز است؛ Production: **HTTPS اجباری** |
| فضای دیسک | چند مگابایت برای کش استاتیک عمومی |

> **وابستگی HTTPS:** نصب و به‌روزرسانی پایدار PWA در Production بدون گواهی معتبر HTTPS پشتیبانی نمی‌شود. localhost برای تست Local استثنا است.

---

## ۳. سیاست امنیتی کش (حتماً بخوانید)

Service Worker **فقط** دارایی‌های استاتیک تأییدشده را کش می‌کند:

- CSS / JS عمومی / آیکون‌ها / `manifest.webmanifest` / `offline.html`

**هرگز کش نمی‌شود:**

- HTML احراز هویت / صفحات ERP (`*.php`)
- فاکتور، OTP، پروفایل، نشست، آپلود
- مسیرهای `/api/` و پاسخ‌های حساس

در آفلاین فقط پوستهٔ `offline.html` نمایش داده می‌شود — نه دادهٔ کسب‌وکار.

---

## ۴. نصب روی Chrome (دسکتاپ)

1. به آدرس Production HTTPS (یا Local: `http://127.0.0.1:8080/moghare360/`) بروید.
2. از منوی Chrome → **Install app** / **نصب برنامه** را بزنید  
   یا آیکون نصب در نوار آدرس را انتخاب کنید.
3. تأیید کنید؛ میانبر دسکتاپ / منوی Start ایجاد می‌شود.
4. برنامه در حالت `standalone` باز می‌شود (`display-mode: standalone`).

اگر دکمهٔ نصب دیده نشد:

- صفحه را یک‌بار Refresh کنید.
- مطمئن شوید SW ثبت شده (DevTools → Application → Service Workers).
- Manifest معتبر باشد و آیکون‌ها لود شوند.

---

## ۵. نصب روی Microsoft Edge

1. آدرس HTTPS Production (یا Local) را در Edge باز کنید.
2. منوی Edge → **Apps** → **Install this site as an app**.
3. نام نمایشی را تأیید کنید (پیش‌فرض: MAHIN360 / ماهین 360°).
4. از Start Menu یا میانبر دسکتاپ اجرا کنید.

Edge و Chrome هر دو از همان origin و همان cache version استفاده می‌کنند؛ نصب جداگانه روی هر مرورگر کش جدا دارد.

---

## ۶. نصب روی Android (Chrome)

1. در Chrome آدرس HTTPS Production را باز کنید.
2. بنر **Add to Home screen** / **Install app** را بپذیرید،  
   یا منوی ⋮ → **Install app** / **Add to Home screen**.
3. آیکون روی صفحهٔ اصلی ظاهر می‌شود.
4. با ضربه روی آیکون، اپ در حالت standalone باز می‌شود.

> برای استقرار فروشگاهی/کیوسک، APK مبتنی بر TWA (`ir.moghare360.app`) نیز موجود است — راهنمای جدا: `ANDROID_APK_INSTALL_GUIDE_FA.md`.

---

## ۷. میانبر تبلت / حالت کیوسک

### تبلت Android / Windows

- پس از نصب PWA، فقط میانبر اپ را روی Home/Desktop نگه دارید.
- مرورگر را از dock حذف کنید تا کاربر به تب‌های دیگر نرود (توصیهٔ عملیاتی).

### توصیهٔ کیوسک

| سناریو | توصیه |
|--------|--------|
| پیشخوان / پذیرش | PWA یا TWA + قفل کاربری ویندوز/اندروید |
| تبلت ثابت کارگاه | حالت کiosk سیستم‌عامل + میانبر فقط به origin |
| چند کاربر روی یک دستگاه | خروج (logout) اجباری بین شیفت‌ها؛ کش حساس وجود ندارد ولی نشست سرور باید بسته شود |

کیوسک جایگزین احراز هویت سرور نیست؛ فقط سطح UI را محدود می‌کند.

---

## ۸. رفتار به‌روزرسانی

1. با deploy نسخهٔ جدید، `CACHE_VERSION` در `service-worker.js` تغییر می‌کند (مثلاً `m360-pwa-static-v1-YYYYMMDD`).
2. کلاینت (`m360-pwa.js`) به‌روزرسانی SW را تشخیص می‌دهد و `SKIP_WAITING` می‌فرستد.
3. کش قدیمی در `activate` حذف می‌شود؛ فقط cache جدید باقی می‌ماند.
4. صفحات حساس همیشه network-only هستند؛ به‌روزرسانی ERP به کش PWA وابسته نیست.

پس از deploy Production: یک بار بستن و باز کردن PWA یا Refresh سخت کافی است.

---

## ۹. ریست کش / عیب‌یابی پوستهٔ قدیمی

### روش سریع (کاربر)

1. از سامانه خارج شوید (logout).
2. PWA را ببندید.
3. در Chrome/Edge: Settings → Privacy → Clear browsing data برای همان origin  
   یا DevTools → Application → Clear storage.
4. صفحه را دوباره باز کنید تا SW و cache جدید ساخته شود.

### تأیید فنی

| بررسی | انتظار |
|-------|--------|
| Application → Cache Storage | فقط `m360-pwa-static-v1-20260803` (یا نسخهٔ deploy فعلی) |
| Service Worker | Active و کنترل‌کنندهٔ صفحه |
| Network برای `*.php` | بدون Cache Storage hit |

---

## ۱۰. حذف نصب / نصب مجدد

1. Chrome/Edge: `chrome://apps` یا Apps list → Uninstall / Remove.  
   Android: نگه‌داشتن آیکون → Uninstall / Remove from Home + Clear site data در Chrome.
2. داده‌های سایت (Cache / SW) را Clear کنید.
3. دوباره از origin نصب کنید.

حذف PWA دادهٔ سرور/ERP را پاک نمی‌کند؛ فقط پوستهٔ کلاینت را برمی‌دارد.

---

## ۱۱. تأیید نسخه

| منبع | چه چیزی را چک کنید |
|------|---------------------|
| `service-worker.js` | ثابت `CACHE_VERSION` = نسخهٔ اعلام‌شده در release notes |
| DevTools → Cache Storage | نام کش با همان ID |
| `manifest.webmanifest` | `name` / `short_name` / آیکون‌های `?v=...` |
| `body[data-m360-pwa-update]` | پس از update آماده، مقدار `ready` ممکن است ست شود |

Local نمونه: `http://127.0.0.1:8080/moghare360/service-worker.js` را باز کنید و `CACHE_VERSION` را بخوانید.

---

## ۱۲. Rollback

اگر پوستهٔ استاتیک مشکل داشت:

1. فایل‌های PWA نسخهٔ قبلی را از artifact انتشار برگردانید (`service-worker.js`, `manifest.webmanifest`, `offline.html`, `assets/js/m360-pwa.js`, CSS/آیکون‌های مرتبط).
2. `CACHE_VERSION` باید با artifact rollback هم‌خوان باشد تا کلاینت‌ها کش جدید بگیرند.
3. جزئیات کامل: `PWA_UPDATE_ROLLBACK_FA.md`.

---

## ۱۳. چک‌لیست پذیرش نصب

- [ ] HTTPS Production فعال است (یا Local روی `127.0.0.1`)
- [ ] Install از Chrome و/یا Edge موفق است
- [ ] Android Add to Home / Install موفق است
- [ ] حالت standalone باز می‌شود
- [ ] آفلاین فقط `offline.html` نشان می‌دهد
- [ ] صفحات login / invoice / OTP در Cache Storage نیستند
- [ ] پس از deploy، cache version جدید دیده می‌شود

---

## ۱۴. ارجاعات مرتبط

- `ANDROID_APK_INSTALL_GUIDE_FA.md`
- `WINDOWS_TABLET_INSTALL_GUIDE_FA.md`
- `PWA_UPDATE_ROLLBACK_FA.md`
- `ANDROID_BUILD_SIGNING_FA.md`

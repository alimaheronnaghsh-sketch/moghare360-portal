# راهنمای نصب روی تبلت ویندوز — MOGHARE360 / MOGHAREH360

**محصول:** MOGHARE360 / MOGHAREH360  
**نسخه سند:** V1  
**زبان:** فارسی (RTL) + شناسه‌های فنی انگلیسی  
**هدف:** تبلت / دستگاه ثابت Windows برای پیشخوان و کارگاه

---

## ۱. خلاصه

روی ویندوز، مسیر رسمی نصب **PWA از Microsoft Edge یا Google Chrome** است. اپ ویندوزی جدا یا کپی منطق ERP وجود ندارد. همان origin وب (Local یا Production HTTPS) اجرا می‌شود.

| مورد | مقدار |
|------|--------|
| روش نصب | Edge / Chrome → Install as app |
| Manifest | `public_html/manifest.webmanifest` |
| SW cache | `m360-pwa-static-v1-20260803` |
| Local مثال | `http://127.0.0.1:8080/moghare360/` |
| Production | HTTPS اجباری |

---

## ۲. حداقل نیازمندی‌ها

| مورد | حداقل |
|------|--------|
| سیستم‌عامل | Windows 10 نسخهٔ پشتیبانی‌شده یا Windows 11 |
| مرورگر | Microsoft Edge (Chromium) یا Google Chrome به‌روز |
| نمایشگر | تبلت/پنل لمسی یا ماوس؛ orientation آزاد (`any`) |
| شبکه | دسترسی به origin؛ Production فقط HTTPS |
| حساب ویندوز | ترجیحاً حساب محدود برای کیوسک (Standard user) |

---

## ۳. نصب با Microsoft Edge (توصیه‌شده برای ویندوز)

1. Edge را باز کنید و به آدرس Production HTTPS بروید  
   (تست Local: `http://127.0.0.1:8080/moghare360/`).
2. منو ⋯ → **Apps** → **Install this site as an app**.
3. نام **MOGHAREH360** / مقاره۳۶۰ را تأیید کنید.
4. گزینهٔ پین به Taskbar / Start را فعال کنید.
5. پنجرهٔ standalone را ببندید و از میانبر Start/Taskbar دوباره باز کنید.

---

## ۴. نصب با Google Chrome

1. Chrome را باز کنید و origin را لود کنید.
2. منو ⋮ → **Install MOGHAREH360…** / **Install app**  
   یا آیکون نصب در Omnibox.
3. میانبر Desktop و/یا Start Menu را تأیید کنید.
4. اجرا در حالت `standalone` را بررسی کنید.

---

## ۵. میانبر تبلت و چیدمان صفحهٔ اصلی

1. فقط میانبر PWA را روی Desktop / Start پین کنید.
2. مرورگر، File Explorer، و Store را از Taskbar کاربر کیوسک بردارید.
3. در صورت لمسی بودن دستگاه، Scaling ویندوز را روی ۱۰۰٪–۱۲۵٪ تنظیم کنید تا UI ERP خوانا بماند.
4. زبان/کیبورد فارسی را برای ورود داده فعال کنید؛ جهت صفحه RTL از خود وب می‌آید.

---

## ۶. توصیهٔ حالت کیوسک (Windows)

| سطح | اقدام |
|-----|--------|
| پایه | حساب Standard جدا + پین فقط PWA |
| متوسط | Assigned Access / Kiosk mode ویندوز روی Edge/PWA در صورت پشتیبانی نسخهٔ OS |
| پیشرفته | سیاست Group Policy / MDM: قفل URL، منع دانلود، منع DevTools |

نکات عملیاتی:

- بین شیفت‌ها **logout** اجباری در وب.
- قفل صفحهٔ ویندوز با PIN/کلمهٔ عبور دستگاه (جدا از رمز ERP).
- کیوسک جایگزین نقش‌های ERP و HTTPS نیست.

---

## ۷. رفتار به‌روزرسانی

1. با deploy سرور، Service Worker نسخهٔ جدید (`m360-pwa-static-v1-*`) را می‌گیرد.
2. `m360-pwa.js` معمولاً `SKIP_WAITING` را اعمال می‌کند.
3. یک بار بستن و باز کردن اپ نصب‌شده کافی است.
4. منطق ERP و داده‌ها روی سرور به‌روز می‌شوند؛ نیازی به نصب مجدد PWA ویندوز نیست مگر manifest/هویت اپ عوض شود.

---

## ۸. ریست کش

اگر CSS/پوسته کهنه است:

1. از ERP خارج شوید.
2. Edge/Chrome → Settings → Cookies and site permissions → Manage data → origin را حذف کنید  
   یا در پنجرهٔ عادی سایت: DevTools → Application → Clear site data.
3. اپ PWA را ببندید و دوباره باز کنید.
4. Cache Storage باید فقط نسخهٔ فعلی `m360-pwa-static-v1-*` را نشان دهد.

حساس‌ها (auth، فاکتور، OTP، پروفایل، API) نباید در کش باشند.

---

## ۹. حذف نصب / نصب مجدد

### Edge

1. `edge://apps` → اپ را Find → Uninstall.
2. در صورت نیاز site data را Clear کنید.
3. دوباره Install کنید.

### Chrome

1. `chrome://apps` یا Uninstall از Start Menu (کلیک راست روی میانبر).
2. Clear site data برای origin.
3. نصب مجدد از همان URL.

---

## ۱۰. تأیید نسخه

| بررسی | روش |
|-------|-----|
| Cache version | باز کردن `…/service-worker.js` و خواندن `CACHE_VERSION` |
| Manifest | `…/manifest.webmanifest` → name / icons `?v=` |
| حالت نصب | پنجره بدون نوار تب کامل مرورگر (standalone) |
| HTTPS | قفل قفل‌شده در origin Production |

Local: `http://127.0.0.1:8080/moghare360/service-worker.js`

---

## ۱۱. Rollback

در صورت مشکل پوسته:

1. تیم انتشار فایل‌های PWA قبلی را روی سرور برمی‌گرداند.
2. روی تبلت: Clear site data + باز کردن مجدد اپ.
3. راهنمای کامل: `PWA_UPDATE_ROLLBACK_FA.md`.

نصب مجدد PWA ویندوز معمولاً لازم نیست مگر identity/manifest شکسته باشد.

---

## ۱۲. وابستگی HTTPS

| محیط | پروتکل |
|------|---------|
| Local demo | `http://127.0.0.1:…` مجاز |
| Staging / Production | **HTTPS الزامی** برای نصب پایدار و امنیت نشست |

گواهی نامعتبر یا mixed content می‌تواند Install و Service Worker را مختل کند.

---

## ۱۳. چک‌لیست پذیرش تبلت ویندوز

- [ ] Edge یا Chrome به‌روز است
- [ ] Install as app موفق است
- [ ] میانبر Taskbar/Start فقط به PWA اشاره دارد
- [ ] کیوسک/حساب محدود پیکربندی شده (در صورت نیاز عملیاتی)
- [ ] به‌روزرسانی پس از deploy بدون نصب مجدد کار می‌کند
- [ ] ریست کش پوستهٔ قدیمی را برطرف می‌کند
- [ ] دادهٔ حساس در Cache Storage دیده نمی‌شود

---

## ۱۴. ارجاعات

- `PWA_INSTALL_GUIDE_FA.md`
- `PWA_UPDATE_ROLLBACK_FA.md`
- `ANDROID_APK_INSTALL_GUIDE_FA.md` (برای تبلت Android)

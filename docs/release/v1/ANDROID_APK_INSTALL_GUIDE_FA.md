# راهنمای نصب APK اندروید — MOGHARE360 / MOGHAREH360

**محصول:** MOGHARE360 / MOGHAREH360  
**نسخه سند:** V1  
**زبان:** فارسی (RTL) + شناسه‌های فنی انگلیسی  
**پروژه:** `android-twa/` — Trusted Web Activity  
**applicationId:** `ir.moghare360.app`

---

## ۱. خلاصه

APK اندروید یک **پوشش TWA** روی همان PWA/HTTPS است. هیچ منطق ERP تکراری، کلاینت PHP، یا API native در این پروژه وجود ندارد. منبع حقیقت کسب‌وکار همیشه سرور HTTPS است.

| مورد | مقدار |
|------|--------|
| پروژه | `android-twa/` |
| applicationId | `ir.moghare360.app` |
| نام نمایشی | MOGHAREH360 |
| خروجی build | `release-artifacts/android/` |
| PWA cache | `m360-pwa-static-v1-20260803` (سمت وب) |
| Production host | فقط HTTPS (دامنهٔ مالک) |

---

## ۲. حداقل نیازمندی‌ها

| مورد | حداقل |
|------|--------|
| Android | API سطح توصیه‌شده توسط build فعلی (معمولاً Android 8+ / API 26+) |
| Chrome / WebView provider | Chrome به‌روز برای TWA |
| شبکه | دسترسی به origin Production روی HTTPS |
| فضای نصب | وابسته به APK؛ معمولاً کمتر از چند ده مگابایت |
| Digital Asset Links | باید برای دامنهٔ Production پیکربندی شود |

> بدون HTTPS معتبر، TWA به درستی به origin قفل نمی‌شود و تجربهٔ نصب/اجرا ناقص است.

---

## ۳. دریافت APK

1. فقط از کانال رسمی مالک / تیم انتشار فایل بگیرید.
2. مسیر خروجی استاندارد پس از build:
   - Debug: `release-artifacts/android/moghare360-twa-debug.apk`
   - Release signed: زیر `release-artifacts/android/` (نام نسخه در release notes)
3. **هرگز** APK امضاشده یا keystore را در git commit نکنید.
4. قبل از نصب، `versionName` / `versionCode` را با سند انتشار مطابقت دهید.

ساخت و امضا: `ANDROID_BUILD_SIGNING_FA.md`.

---

## ۴. نصب روی دستگاه Android

### روش A — نصب مستقیم (sideload)

1. فایل APK را به دستگاه منتقل کنید (USB / اشتراک امن داخلی).
2. در Settings → Security اجازهٔ نصب از منابع ناشناس/فایل‌منیجر را موقتاً فعال کنید (فقط برای کانال داخلی مطمئن).
3. روی APK ضربه بزنید → Install.
4. پس از نصب، منبع ناشناس را در صورت سیاست امنیتی غیرفعال کنید.
5. اپ `MOGHAREH360` (`ir.moghare360.app`) را باز کنید.

### روش B — ADB (فنی)

```text
adb install -r path\to\moghare360-twa-*.apk
```

`-r` برای ارتقا روی نسخهٔ قبلی با همان امضا است.

### روش C — فروشگاه (در صورت انتشار بعدی)

اگر روی Google Play منتشر شود، از همان listing رسمی نصب کنید؛ sideload لازم نیست.

---

## ۵. تفاوت با نصب PWA از Chrome

| | PWA (Add to Home) | TWA APK |
|--|-------------------|---------|
| شناسه سیستم | سایت در Chrome | `ir.moghare360.app` |
| توزیع | بدون APK | فایل APK/AAB |
| کیوسک سازمانی | خوب | بهتر برای MDM / قفل اپ |
| منطق کسب‌وکار | وب | همان وب (بدون duplicate) |

هر دو به یک origin HTTPS وصل می‌شوند و همان سیاست کش امن PWA را دارند.

---

## ۶. میانبر تبلت و کیوسک

1. پس از نصب، آیکون را روی Home Screen اصلی پین کنید.
2. برای کیوسک کارگاه:
   - Screen pinning / Lock task mode سیستم‌عامل، یا
   - MDM با مجاز کردن فقط `ir.moghare360.app`
3. مرورگر و فروشگاه را از صفحهٔ اصلی حذف/مخفی کنید.
4. بین شیفت‌ها حتماً **logout** وب انجام شود (نشست سرور).

کیوسک جایگزین کنترل دسترسی ERP نیست.

---

## ۷. رفتار به‌روزرسانی

| لایه | نحوهٔ به‌روزرسانی |
|------|-------------------|
| پوستهٔ وب / SW | با deploy HTTPS؛ cache `m360-pwa-static-v1-*` عوض می‌شود |
| APK wrapper | با نصب APK جدید (`versionCode` بالاتر) |
| منطق ERP | فقط سمت سرور؛ در APK کپی نمی‌شود |

پس از آپدیت سرور معمولاً باز کردن مجدد اپ کافی است. اگر پوستهٔ استاتیک کهنه ماند، ریست کش وب (بخش ۹).

---

## ۸. تأیید نسخه

روی دستگاه:

1. Settings → Apps → MOGHAREH360 → نسخه را بخوانید (`versionName`).
2. داخل وب: `service-worker.js` → `CACHE_VERSION`.
3. مطمئن شوید host داخل TWA همان دامنهٔ Production اعلام‌شده است.

روی build machine / artifact:

| فایل/فیلد | انتظار |
|-----------|--------|
| `versionCode` | عدد صحیح صعودی |
| `versionName` | هم‌تراز تگ V1 (مثلاً `1.0.0`) |
| `applicationId` | `ir.moghare360.app` |

---

## ۹. ریست کش / رفع پوستهٔ قدیمی

داخل Chrome مرتبط با TWA یا از تنظیمات سایت:

1. Settings → Apps → MOGHAREH360 → Storage → Clear cache (در صورت وجود).
2. در صورت نیاز Clear data (نشست وب پاک می‌شود؛ دوباره login لازم است).
3. یا در وب: Clear site data برای origin + باز کردن مجدد اپ.
4. تأیید کنید فقط cache استاتیک `m360-pwa-static-v1-*` باقی است و HTML حساس کش نشده.

---

## ۱۰. حذف نصب / نصب مجدد

1. Uninstall اپ از Settings → Apps.
2. در صورت باقی‌ماندن دادهٔ سایت در Chrome، Clear browsing data برای origin.
3. APK نسخهٔ هدف را دوباره نصب کنید.
4. Digital Asset Links و HTTPS را دوباره smoke-test کنید.

Rollback به APK قبلی فقط اگر `versionCode` و امضای همان keystore سازگار باشند؛ جزئیات امضا در `ANDROID_BUILD_SIGNING_FA.md`.

---

## ۱۱. وابستگی HTTPS و Asset Links

- Host باید HTTPS با گواهی معتبر باشد.
- فایل Digital Asset Links روی دامنه باید `ir.moghare360.app` و اثرانگشت امضا را تأیید کند.
- اگر Asset Links ناقص باشد، ممکن است نوار URL/رفتار غیر-fullscreen دیده شود.

---

## ۱۲. سیاست دادهٔ حساس

APK و PWA:

- کش فقط استاتیک عمومی
- بدون کش auth HTML، فاکتور، OTP، پروفایل، API
- بدون ذخیرهٔ رمز یا توکن در پروژهٔ `android-twa`

---

## ۱۳. چک‌لیست پذیرش

- [ ] APK از کانال رسمی است
- [ ] `applicationId` = `ir.moghare360.app`
- [ ] نصب روی دستگاه تست موفق است
- [ ] باز شدن روی HTTPS Production
- [ ] بدون duplicate منطق ERP در کلاینت native
- [ ] logout بین کاربران کیوسک انجام می‌شود
- [ ] نسخه با release notes مطابقت دارد

---

## ۱۴. ارجاعات

- `PWA_INSTALL_GUIDE_FA.md`
- `ANDROID_BUILD_SIGNING_FA.md`
- `PWA_UPDATE_ROLLBACK_FA.md`
- `android-twa/README.md`

# راهنمای Build و Signing اندروید (TWA) — MOGHARE360 / MOGHAREH360

**محصول:** MOGHARE360 / MOGHAREH360  
**نسخه سند:** V1  
**زبان:** فارسی (RTL) + شناسه‌های فنی انگلیسی  
**پروژه:** `android-twa/`  
**applicationId:** `ir.moghare360.app`

---

## ۱. اصول

1. TWA فقط HTTPS PWA را باز می‌کند — **بدون duplicate منطق ERP**.
2. خروجی‌ها فقط زیر `release-artifacts/` (مسیر نادیده‌گرفته‌شده از git).
3. **هرگز** keystore، پسورد، `keystore.properties` واقعی، یا APK/AAB امضاشدهٔ Production را commit نکنید.
4. در این سند هیچ رمز، PIN، یا credential واقعی نوشته نمی‌شود — فقطplaceholders.

---

## ۲. هویت نسخه

| فیلد | سیاست |
|------|--------|
| `applicationId` | ثابت: `ir.moghare360.app` (تغییر فقط با تصمیم مالک + Asset Links جدید) |
| `versionCode` | عدد صحیح **صعودی یکنواخت**؛ هر انتشار فروشگاهی/داخلی +۱ یا بیشتر |
| `versionName` | هم‌تراز تگ انتشار V1 (مثال: `1.0.0`, `1.0.1`) — نمایشی برای انسان |
| نام اپ | `MOGHAREH360` |

### قواعد versionCode / versionName

- `versionCode` هرگز کاهش داده نشود برای همان مسیر ارتقا روی یک دستگاه.
- برای hotfix: `versionCode` افزایش + `versionName` وصله (مثلاً `1.0.1`).
- برای rollback نصب روی دستگاه‌هایی که نسخهٔ بالاتر دارند، معمولاً نیاز به `versionCode` بالاتر از نسخهٔ خراب با کد قبلیِ سالمِ بازسازی‌شده است (یا uninstall اول).
- تگ git و `versionName` را در یادداشت انتشار ثبت کنید (بدون secret).

---

## ۳. پیش‌نیاز ابزار

| ابزار | حداقل |
|------|--------|
| Android Studio / SDK | SDK 34+ توصیه‌شده |
| JDK | 17 |
| PowerShell | برای اسکریپت‌های `android-twa/scripts/` |
| Host | Production HTTPS (مالک) در `gradle.properties` / تنظیمات TWA |

Local PWA برای توسعهٔ وب: `http://127.0.0.1:8080/moghare360/` — TWA Production باید به origin HTTPS اشاره کند.

---

## ۴. Debug UNSIGNED (توسعه / QA داخلی)

هدف: APK قابل نصب روی دستگاه تست بدون کلید انتشار Production.

1. مخزن را Clone/Sync کنید.
2. Host تست/staging را طبق `android-twa/README.md` تنظیم کنید (بدون commit secret).
3. اجرا:

```powershell
pwsh -File android-twa/scripts/build-debug.ps1
```

4. خروجی مورد انتظار:

```text
release-artifacts/android/moghare360-twa-debug.apk
```

5. نصب:

```text
adb install -r release-artifacts\android\moghare360-twa-debug.apk
```

نکات:

- Debug برای فروشگاه/Production نهایی نیست.
- ممکن است با امضای debug سیستم ساخته شود؛ با کلید release جایگزین ارتقا نشود مگر uninstall.
- همچنان هیچ ERP native اضافه نکنید.

---

## ۵. آماده‌سازی Signing (Release) — بدون افشای رمز

### ۵.۱ فایل‌های محلی (gitignore)

1. از نمونه کپی کنید:

```text
keystore.properties.example  →  keystore.properties
```

2. مقادیر را **محلی** پر کنید (نمونه‌های ساختگی زیر — واقعی نگذارید در git/docs):

```properties
# EXAMPLE ONLY — do not use these values in production
storeFile=C:\\secure-local\\moghare360-upload.jks
storePassword=REPLACE_ME
keyAlias=REPLACE_ALIAS
keyPassword=REPLACE_ME
```

3. فایل keystore (`.jks` / `.keystore`) را خارج از repo یا در مسیر امن محلی نگه دارید.
4. تأیید کنید `keystore.properties` و `*.jks` در git status ظاهر نمی‌شوند.

### ۵.۲ Digital Asset Links

پس از مشخص شدن دامنهٔ HTTPS:

1. اثرانگشت گواهی امضای **release** را استخراج کنید (SHA-256).
2. فایل assetlinks روی دامنه را برای `ir.moghare360.app` به‌روز کنید.
3. `app/src/main/res/xml/asset_statements` (یا معادل پروژه) را با host نهایی هم‌تراز کنید.

بدون این مرحله، TWA ممکن است fullscreen/verified نباشد.

---

## ۶. مراحل Build امضاشدهٔ Release

1. `versionCode` و `versionName` را طبق سیاست بخش ۲ تنظیم کنید.
2. Host Production HTTPS را در تنظیمات TWA قفل کنید.
3. `keystore.properties` محلی معتبر باشد.
4. از Android Studio: Build → Generate Signed Bundle / APK  
   یا اسکریپت release پروژه (در صورت افزوده‌شدن) را اجرا کنید.
5. خروجی را **فقط** در این درخت کپی/بنویسید:

```text
release-artifacts/android/
  moghare360-twa-<versionName>-release.apk
  # و/یا
  moghare360-twa-<versionName>-release.aab
```

6. checksum (مثلاً SHA-256 فایل) را در یادداشت انتشار داخلی ثبت کنید — نه پسورد کلید.
7. APK/AAB امضا و keystore را commit/push نکنید.

---

## ۷. تأیید پس از Build

| بررسی | انتظار |
|-------|--------|
| Package name | `ir.moghare360.app` |
| versionCode / versionName | مطابق یادداشت انتشار |
| امضا | certificate release مورد انتظار |
| خروجی مسیر | زیر `release-artifacts/android/` |
| رفتار اپ | باز شدن origin HTTPS؛ بدون منطق ERP native |
| PWA سمت سرور | `CACHE_VERSION` مثلاً `m360-pwa-static-v1-20260803` یا نسخهٔ deploy فعلی |

نصب آزمایشی: راهنمای `ANDROID_APK_INSTALL_GUIDE_FA.md`.

---

## ۸. به‌روزرسانی، Rollback، نصب مجدد (لایهٔ APK)

| سناریو | اقدام |
|--------|--------|
| فقط پوستهٔ وب | deploy PWA؛ لزوماً APK جدید لازم نیست (`PWA_UPDATE_ROLLBACK_FA.md`) |
| باگ wrapper / host | APK جدید با `versionCode` بالاتر |
| بازگشت به APK قبلی روی دستگاه هم‌نسخه | در صورت کاهش versionCode معمولاً uninstall سپس install |
| تعویض keystore | ارتقا روی نصب قبلی ممکن است شکست بخورد؛ برنامه‌ریزی مهاجرت امضا لازم است |

هرگز برای «رفع سریع» کلید یا پسورد را در چت/سند/repo قرار ندهید.

---

## ۹. حداقل نیازمندی‌های دستگاه هدف

- Android سازگار با minSdk پروژه
- Chrome به‌روز برای TWA
- شبکه تا origin HTTPS
- در کیوسک: ترجیح MDM / screen pinning — راهنمای نصب APK

---

## ۱۰. وابستگی HTTPS

| مورد | الزام |
|------|--------|
| آدرس داخل TWA | HTTPS Production |
| Asset Links | روی همان host با HTTPS |
| PWA SW | روی همان origin؛ کش فقط استاتیک امن |

HTTP فقط برای توسعهٔ وب روی localhost است؛ برای بستهٔ انتشار TWA استفاده نشود.

---

## ۱۱. چک‌لیست امنیتی قبل از تحویل

- [ ] هیچ پسورد/کلید واقعی در فایل‌های commit‌شده نیست
- [ ] `keystore.properties` فقط محلی است
- [ ] خروجی زیر `release-artifacts/` است و track نمی‌شود
- [ ] `versionCode` صعودی ثبت شد
- [ ] `versionName` با تگ انتشار هم‌خوان است
- [ ] applicationId = `ir.moghare360.app`
- [ ] هیچ کپی منطق ERP در `android-twa` اضافه نشده
- [ ] یادداشت انتشار بدون credential

---

## ۱۲. ارجاعات

- `android-twa/README.md`
- `ANDROID_APK_INSTALL_GUIDE_FA.md`
- `PWA_INSTALL_GUIDE_FA.md`
- `PWA_UPDATE_ROLLBACK_FA.md`

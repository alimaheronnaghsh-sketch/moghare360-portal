# راهنمای UAT مالک — فروش خدمات کارگاه (Workshop Service Sales)

**محصول:** MOGHARE360 V1  
**فاز:** Phase 5 — Autonomous Delivery (Workshop Service Sales)  
**شاخه مرجع:** `feature/workshop-service-sales`  
**Runtime:** `public_html` → Local base `http://127.0.0.1:8080/moghare360/`  
**Design tokens:** `assets/moghare360-ui/moghare360-design-tokens.css`  
**زبان سند:** فارسی (RTL) + شناسه‌های فنی انگلیسی

---

## ۱. هدف و محدوده

این راهنما مسیر پذیرش کاربری (UAT) مالک را برای زنجیرهٔ کانونیکال **درخواست مشتری → تحویل خودرو** با تمرکز بر **ثبت خط خدمت بدون قیمت**، **قیمت‌گذاری مدیر سالن**، **آماده‌سازی برای فاکتور**، **فاکتور نهایی / تسویه / QC / تحویل / ممیزی** تعریف می‌کند.

### محیط‌های مجاز

| محیط | مجاز برای UAT ترکیبی؟ | توضیح |
|------|------------------------|--------|
| **LOCAL** | بله | مسیر اصلی: `http://127.0.0.1:8080/moghare360/` |
| **STAGING** | بله | فقط در صورت وجود tenant/شرکت تست جدا و دادهٔ برگشت‌پذیر |
| **PRODUCTION** | خیر | ایجاد دادهٔ ترکیبی (synthetic master data)، مشتری جعلی، یا دستکاری پروندهٔ واقعی ممنوع است |

> **قفل سخت:** هیچ دادهٔ تست ترکیبی روی Production ایجاد یا به‌روزرسانی نشود.

### صفحات کانونیکال فروش خدمات کارگاه

| صفحه | مسیر نسبی |
|------|-----------|
| ثبت خط خدمت (بدون مبلغ) | `erp-workshop-service-entry.php` |
| بررسی فنی / قیمت‌گذاری مدیر سالن | `erp-workshop-service-pricing.php` |
| خلاصه خدمات پرونده | `erp-workshop-service-summary.php` |

فاکتور نهایی از مسیر موجود P7:

- `erp-final-invoice-board.php`
- `erp-final-invoice-detail.php`
- `erp-final-invoice-action.php` (فقط POST)

---

## ۲. قوانین دادهٔ تست

1. **نشانه‌گذاری:** همهٔ رکوردهای تست با پیشوند واضح مثل `UAT-WS-` / `M360-UAT-` در عنوان خدمت، نام مشتری تست، یا یادداشت پرونده مشخص شوند.
2. **محدود به شرکت (company-scoped):** فقط داخل همان `company_id` جلسهٔ تست کار کنید؛ دسترسی متقابل شرکت‌ها باید Fail شود.
3. **برگشت‌پذیری (reversible):** پرونده‌ها را تا حد امکان با وضعیت‌های قابل ابطال/بستن کنترل‌شده نگه دارید؛ از پاک‌سازی مخرب DB بدون بکاپ خودداری کنید.
4. **بدون اسرار:** در این سند و شواهد، رمز عبور، توکن خام، OTP واقعی، یا کلید API ثبت نشود.
5. **بدون دادهٔ شخصی واقعی:** از موبایل/نام/پلاک واقعی مشتریان Production استفاده نکنید؛ در اسکرین‌شات‌ها نیز ماسک کنید (بخش ۷).

### پیش‌شرط فنی حداقلی

- Staff login فعال روی Local/Staging
- JobCard معتبر با حداقل یک `work_item` قابل انتخاب
- نقش‌های تست مطابق بخش ۴ (بدون افشای credential)
- Design tokens بارگذاری‌شده در صفحات staff (Luxury Dark Green / RTL)

---

## ۳. بازیگران و نقش‌ها

| نقش (کد) | مسئولیت در این UAT | مسیرهای کلیدی |
|----------|---------------------|----------------|
| **CUSTOMER** (عمومی/OTP) | ثبت درخواست آنلاین؛ تأیید قرارداد/برآورد؛ امضای تحویل | `customer-request.php`, قرارداد/برآورد، `customer-delivery-sign.php` |
| **RECEPTION** | پذیرش درخواست، intake، ایجاد/هدایت JobCard | `erp-reception-online-requests.php`, `erp-reception-intake-file.php`, `erp-reception-jobcards.php` |
| **SERVICE_MANAGER** (مدیر سالن / Hall Manager) | کارتابل سالن، بررسی فنی خط خدمت، قیمت‌گذاری، Ready for Invoice | `erp-hall-cartable.php`, `erp-workshop-service-pricing.php` |
| **TECHNICIAN** | اجرای کار، ثبت خط خدمت **بدون مبلغ**، گزارش کار | `erp-work-execution-*.php`, `erp-workshop-service-entry.php` |
| **QC** | کنترل کیفیت و آمادگی تحویل | `erp-qc-board.php`, `erp-qc-detail.php` |
| **FINANCE** | پیش‌نویس/محاسبه/نهایی‌سازی فاکتور، تسویه | `erp-final-invoice-*.php`, `erp-settlement-*.php` |
| **OWNER / SYSTEM_ADMIN** | نظارت E2E، ممیزی timeline، override کنترل‌شده | داشبورد مدیریت، `erp-jobcard-timeline.php` |

> تکنسین **نباید** بتواند مبلغ را از فرم ثبت خدمت تزریق کند. قیمت فقط از مسیر مدیر سالن (`workshop.service_line.price`).

---

## ۴. مسیرهای دقیق شناخته‌شده (Base Local)

Base: `http://127.0.0.1:8080/moghare360/`

### ورودی و پذیرش

| مرحله | مسیر |
|-------|------|
| درخواست آنلاین مشتری | `/customer-request.php` |
| برد درخواست‌های آنلاین | `/erp-reception-online-requests.php` |
| جزئیات درخواست | `/erp-reception-online-request-detail.php` |
| پذیرش درخواست | `/erp-reception-online-request-accept.php` (POST) |
| پرونده پذیرش / intake | `/erp-reception-intake-file.php?online_request_id={id}` |
| برد قراردادها | `/erp-intake-contracts.php` |
| برد JobCard پذیرش | `/erp-reception-jobcards.php` |
| جزئیات JobCard پذیرش | `/erp-reception-jobcard-detail.php?jobcard_id={id}` |

### سالن / فنی / اجرا

| مرحله | مسیر |
|-------|------|
| کارتابل سالن | `/erp-hall-cartable.php` |
| جزئیات پرونده سالن | `/erp-hall-jobcard-detail.php?jobcard_id={id}` |
| برد فنی | `/erp-technical-board.php` |
| جزئیات فنی | `/erp-technical-jobcard-detail.php?jobcard_id={id}` |
| برد برآورد | `/erp-estimate-board.php` |
| جزئیات برآورد | `/erp-estimate-detail.php` |
| برد اجرای کار | `/erp-work-execution-board.php` |
| جزئیات اجرای کار | `/erp-work-execution-detail.php?jobcard_id={id}` |

### فروش خدمات کارگاه (کانونیکال Phase 5)

| مرحله | مسیر |
|-------|------|
| ثبت خط خدمت | `/erp-workshop-service-entry.php?jobcard_id={id}` |
| قیمت‌گذاری / تأیید فنی | `/erp-workshop-service-pricing.php` یا `?service_line_id={id}` |
| خلاصه خدمات | `/erp-workshop-service-summary.php?jobcard_id={id}` |

### QC → فاکتور → تسویه → تحویل → ممیزی

| مرحله | مسیر |
|-------|------|
| برد QC | `/erp-qc-board.php` |
| جزئیات QC | `/erp-qc-detail.php?jobcard_id={id}` |
| برد فاکتور نهایی | `/erp-final-invoice-board.php` |
| جزئیات فاکتور | `/erp-final-invoice-detail.php?jobcard_id={id}` |
| عملیات فاکتور | `/erp-final-invoice-action.php` (POST + CSRF) |
| جزئیات تسویه | `/erp-settlement-detail.php?jobcard_id={id}` |
| عملیات تسویه | `/erp-settlement-action.php` (POST + CSRF) |
| بررسی تحویل مشتری | `/customer-delivery-review.php` (توکن امن) |
| امضای تحویل | `/customer-delivery-sign.php` |
| Timeline / ممیزی | `/erp-jobcard-timeline.php?jobcard_id={id}` |

---

## ۵. سناریوی E2E گام‌به‌گام (زنجیرهٔ کانونیکال)

وضعیت‌های خط خدمت مورد انتظار (خلاصه):  
`DRAFT` → `SUBMITTED` → `TECHNICALLY_APPROVED` / `PRICING_PENDING` → `PRICED` → `READY_FOR_INVOICE` → `INVOICED`

### گام A — درخواست مشتری

1. با نقش مشتری/عمومی درخواست تست `UAT-WS-…` ثبت کنید (`customer-request.php`).
2. در Reception درخواست را باز و بپذیرید.
3. Intake / قرارداد را تا آمادگی عملیاتی تکمیل کنید (قفل‌های پذیرش و در صورت نیاز OTP مشتری).

**نتیجه مورد انتظار:** پرونده قابل ارجاع به سالن / JobCard ساخته یا قابل تبدیل است.

### گام B — JobCard و سالن

1. JobCard را در `erp-reception-jobcards.php` / جزئیات تأیید کنید.
2. مدیر سالن در `erp-hall-cartable.php` پرونده را تخصیص (تیم/تکنسین) کند.

**نتیجه مورد انتظار:** تکنسین مجاز به اجرای کار است؛ خودتأیید مالی توسط تکنسین ممکن نیست.

### گام C — فنی / برآورد / اجرای کار (حداقل لازم برای ادامه)

1. مسیر فنی و در صورت نیاز برآورد + تأیید مشتری را طی کنید.
2. اجرای کار را تا داشتن حداقل یک `work_item` قابل انتخاب پیش ببرید.

**نتیجه مورد انتظار:** صفحه ثبت خدمت، آیتم کاری قابل انتخاب نشان می‌دهد.

### گام D — ثبت خط خدمت توسط تکنسین (بدون مبلغ)

1. باز کنید:  
   `erp-workshop-service-entry.php?jobcard_id={id}`
2. آیتم کاری، سرفصل فروش، عنوان، شرح، مدت واقعی، محدوده توافق را پر کنید.
3. **ذخیره پیش‌نویس** سپس **ارسال برای بررسی فنی**.
4. عمداً فیلدهای قیمت (`price`, `price_irr`, …) را در صورت امکان تزریق کنید → باید رد شود.

**نتیجه مورد انتظار:** خط در وضعیت `SUBMITTED`؛ هیچ مبلغ ذخیره‌شده از سمت تکنسین؛ در خلاصه برای تکنسین مبالغ مخفی/غیرقابل ویرایش.

### گام E — قیمت‌گذاری مدیر سالن و Ready for Invoice

1. با نقش SERVICE_MANAGER / OWNER باز کنید:  
   `erp-workshop-service-pricing.php`
2. از صف بررسی، خط را باز کنید.
3. **تأیید فنی** (یا برگشت با دلیل — سناریوی منفی جدا).
4. **ثبت مبلغ** به ریال (عدد صحیح).
5. **آماده‌سازی برای فاکتور** → وضعیت `READY_FOR_INVOICE`.
6. خلاصه را در `erp-workshop-service-summary.php?jobcard_id={id}` بررسی کنید (جمع سرور، هشدار خدمات اضافی در صورت وجود).

**نتیجه مورد انتظار:** فقط نقش مجاز مبلغ می‌بیند/می‌نویسد؛ خط آماده تبدیل به فاکتور است.

### گام F — QC

1. از `erp-qc-board.php` وارد جزئیات شوید.
2. کنترل QC را تا آمادگی تحویل (`DELIVERY_READY` / وضعیت‌های QC مرتبط) تکمیل کنید.

**نتیجه مورد انتظار:** JobCard برای مسیر فاکتور نهایی واجد شرایط است.

### گام G — فاکتور نهایی (تبدیل خطوط READY)

1. `erp-final-invoice-board.php` → جزئیات JobCard.
2. `create_draft_invoice` سپس محاسبه/نهایی‌سازی طبق UI مجاز (`calculate_invoice` / `finalize_invoice`).
3. خطوط `READY_FOR_INVOICE` باید به اقلام فاکتور تبدیل و به `INVOICED` برسند.
4. تلاش دوم برای تبدیل تکراری همان خط → نباید ردیف تکراری مالی بسازد (idempotent / skip duplicate).

**نتیجه مورد انتظار:** یک منبع حقیقت برای هر `service_line_id`؛ CSRF روی POST الزامی.

### گام H — تسویه

1. `erp-settlement-detail.php?jobcard_id={id}`
2. وضعیت تسویه را با مبالغ پرداخت‌شده هم‌تراز کنید (`mark_settled` یا مسیر مجاز).
3. بدون تسویه / بدون `MANAGER_RELEASE_APPROVED`، آزادسازی خودرو نباید ممکن باشد.

**نتیجه مورد انتظار:** گیت پرداخت قبل از تحویل برقرار است.

### گام I — تحویل مشتری

1. لینک امن بررسی/امضا (بدون ثبت توکن خام در شواهد).
2. OTP + امضا + تأیید نهایی روی `customer-delivery-sign.php`.
3. در صورت نیاز `release_vehicle` / بستن JobCard از مسیر تسویه/عملیات مجاز.

**نتیجه مورد انتظار:** `DELIVERY_SIGNED` / `VEHICLE_RELEASED` / در نهایت بستن کنترل‌شده.

### گام J — ممیزی (Audit)

1. `erp-jobcard-timeline.php?jobcard_id={id}` و/یا تاریخچه خط خدمت.
2. رویدادهای کلیدی را تطبیق دهید: ایجاد خط، submit، approve، set_price، ready_for_invoice، convert/invoice، settlement، delivery.

**نتیجه مورد انتظار:** زنجیره فقط با رویدادهای ERP اثبات می‌شود (نه مکالمهٔ شفاهی).

---

## ۶. چک‌لیست تست‌های منفی

| # | سناریو | انتظار |
|---|--------|--------|
| N1 | نقش غیرمجاز (مثلاً TECHNICIAN روی pricing POST یا RECEPTION روی FI action) | رد دسترسی / Forbidden |
| N2 | تزریق فیلد قیمت در `erp-workshop-service-entry.php` | رد؛ پیام ممنوعیت مبلغ |
| N3 | مشاهده مبلغ توسط نقش بدون `view_price` / `price` | مبلغ نمایش داده نشود |
| N4 | تبدیل تکراری همان `service_line` به فاکتور | بدون duplicate item مالی |
| N5 | POST فاکتور/تسویه بدون CSRF معتبر | رد درخواست |
| N6 | دسترسی به `jobcard_id` شرکت دیگر (cross-company) | رد scope / داده دیده نشود |
| N7 | تلاش تحویل/آزادسازی با مانده تسویه‌نشده و بدون مجوز مدیریتی | مسدود |
| N8 | Ready for Invoice روی خط بدون قیمت معتبر | Fail / عدم تغییر وضعیت |
| N9 | برگشت خط SUBMITTED با دلیل خالی | Fail اعتبارسنجی |
| N10 | دستکاری مستقیم مبلغ پس از INVOICED از UI قیمت‌گذاری | مسدود / بدون اثر مخرب |

نتایج را در ماتریس پذیرش ثبت کنید:  
`docs/release/v1/UAT_ACCEPTANCE_MATRIX_FA.md`

---

## ۷. ثبت نتایج بدون دادهٔ شخصی در اسکرین‌شات

1. **ماسک اجباری:** نام واقعی، موبایل کامل، پلاک کامل، آدرس، امضای واضح، چهره، توکن URL، OTP.
2. **مجاز برای شواهد:** `jobcard_id`, `service_line_id`, کد وضعیت انگلیسی، نقش تست (نه نام فرد)، برچسب `UAT-WS-…`.
3. **نام فایل شواهد:** `UAT-WS-{step}-{PASS|FAIL}-{YYYYMMDD}.png` — بدون نام مشتری.
4. در ماتریس فقط `evidence ref` (شناسه فایل/پوشهٔ داخلی) بنویسید؛ فایل را در چت عمومی یا commit عمومی با PII نگذارید.
5. Defect ID را در سیستم پیگیری تیم ثبت کنید؛ در ماتریس فقط شناسهٔ تیکت.

### قالب ثبت نتیجهٔ هر گام (Owner)

```
گام: …
محیط: LOCAL | STAGING
نتیجه: □ PASS / □ FAIL
Evidence ref: …
Defect ID: …
یادداشت (بدون PII): …
```

---

## ۸. معیار قبول مالک (خلاصه)

- زنجیرهٔ مثبت E2E از درخواست تا تحویل با خطوط خدمت قیمت‌گذاری‌شده توسط مدیر سالن کامل شود.
- تکنسین نتواند قیمت ثبت/تزریق کند.
- تبدیل به فاکتور نهایی idempotent باشد.
- CSRF روی عملیات مالی P7 برقرار باشد.
- Cross-company و unpaid delivery مسدود باشند.
- شواهد بدون دادهٔ شخصی واقعی باشند.
- هیچ synthetic master data روی Production ایجاد نشده باشد.

---

## ۹. ارجاعات مرتبط

- ماتریس پذیرش: `docs/release/v1/UAT_ACCEPTANCE_MATRIX_FA.md`
- Route map: `docs/release/MOGHARE360_V1_ROUTE_MAP.md` / UI `erp-route-map.php`
- Registry: `public_html/includes/m360-navigation-registry.php`
- Design coverage: `docs/release/v1/DESIGN_SYSTEM_COVERAGE_REPORT.md`

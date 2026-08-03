# ماتریس پذیرش UAT — فروش خدمات کارگاه (Workshop Service Sales)

**محصول:** MOGHARE360 V1 · Phase 5  
**شاخه:** `feature/workshop-service-sales`  
**Base Local:** `http://127.0.0.1:8080/moghare360/`  
**راهنمای مالک:** `docs/release/v1/OWNER_UAT_GUIDE_FA.md`  
**محیط مجاز:** LOCAL / STAGING فقط — بدون synthetic data روی Production  
**نتیجه:** فیلد `result` را مالک پر کند (`□ PASS` / `□ FAIL`)

### ستون‌ها

| ستون | معنی |
|------|------|
| route | مسیر نسبی (زیر base) |
| test actor | نقش تست |
| precondition | پیش‌شرط |
| action | اقدام |
| expected result | نتیجه مورد انتظار |
| result | □ PASS / □ FAIL |
| evidence ref | شناسه شاهد بدون PII |
| severity | Critical / High / Medium / Low |
| defect ID | شناسه نقص (در صورت FAIL) |

---

## A) زنجیرهٔ مثبت کانونیکال (Positive)

| route | test actor | precondition | action | expected result | result | evidence ref | severity | defect ID |
|-------|------------|--------------|--------|-----------------|--------|--------------|----------|-----------|
| `customer-request.php` | CUSTOMER | Local/Staging؛ بدون دادهٔ واقعی Production | ثبت درخواست تست با برچسب `UAT-WS-…` | درخواست ایجاد و در صف Reception دیده می‌شود | □ PASS / □ FAIL | | Critical | |
| `erp-reception-online-requests.php` | RECEPTION | درخواست `UAT-WS-…` موجود | باز کردن لیست و یافتن درخواست | ردیف قابل مشاهده؛ بدون خطای scope | □ PASS / □ FAIL | | Critical | |
| `erp-reception-online-request-detail.php` | RECEPTION | درخواست باز | بررسی جزئیات | جزئیات خوانا؛ آماده پذیرش | □ PASS / □ FAIL | | High | |
| `erp-reception-online-request-accept.php` | RECEPTION | درخواست قابل پذیرش | POST پذیرش | پذیرش موفق؛ پرونده/intake قابل ادامه | □ PASS / □ FAIL | | Critical | |
| `erp-reception-intake-file.php?online_request_id={id}` | RECEPTION | درخواست پذیرفته‌شده | تکمیل intake تا آمادگی عملیاتی | قفل‌های پذیرش برآورده؛ قابل ارجاع سالن | □ PASS / □ FAIL | | Critical | |
| `erp-intake-contracts.php` | RECEPTION / CUSTOMER | قرارداد لازم فعال | تولید/ارسال/تأیید قرارداد طبق مسیر مجاز | قرارداد پذیرفته/قفل‌شده طبق گیت‌ها | □ PASS / □ FAIL | | High | |
| `erp-reception-jobcards.php` | RECEPTION | JobCard ایجاد/تبدیل‌شده | باز کردن برد JobCard | JobCard تست در لیست شرکت خود دیده می‌شود | □ PASS / □ FAIL | | Critical | |
| `erp-reception-jobcard-detail.php?jobcard_id={id}` | RECEPTION | `jobcard_id` معتبر | تأیید وضعیت پذیرش | جزئیات و وضعیت‌ها صحیح | □ PASS / □ FAIL | | High | |
| `erp-hall-cartable.php` | SERVICE_MANAGER | پرونده آماده سالن | تخصیص تیم/تکنسین | تخصیص ثبت می‌شود؛ تکنسین مجاز اجرا | □ PASS / □ FAIL | | Critical | |
| `erp-hall-jobcard-detail.php?jobcard_id={id}` | SERVICE_MANAGER | پرونده سالن | بازبینی پرونده سالن | لینک به خلاصه/اجرا در دسترس | □ PASS / □ FAIL | | Medium | |
| `erp-technical-board.php` | TECHNICIAN / SERVICE_MANAGER | JobCard در مسیر فنی | باز کردن برد فنی | پرونده در صف فنی دیده می‌شود | □ PASS / □ FAIL | | High | |
| `erp-technical-jobcard-detail.php?jobcard_id={id}` | TECHNICIAN / SERVICE_MANAGER | جزئیات فنی | پیشرفت تشخیص/فنی حداقل لازم | وضعیت فنی جلو می‌رود؛ رویداد ثبت می‌شود | □ PASS / □ FAIL | | High | |
| `erp-estimate-board.php` | FINANCE / SERVICE_MANAGER | برآورد لازم | باز کردن برد برآورد | پرونده در صف برآورد (در صورت نیاز مسیر) | □ PASS / □ FAIL | | High | |
| `erp-estimate-detail.php` | FINANCE / SERVICE_MANAGER | برآورد موجود | محاسبه/ارسال برای تأیید مشتری در صورت نیاز | برآورد قابل تأیید مشتری | □ PASS / □ FAIL | | High | |
| `customer-estimate-approval-sign.php` | CUSTOMER | لینک امن برآورد | تأیید OTP/امضا در صورت فعال بودن گیت | تأیید مشتری ثبت می‌شود | □ PASS / □ FAIL | | High | |
| `erp-work-execution-board.php` | TECHNICIAN | کار قابل اجرا | ورود به برد اجرا | JobCard در صف اجرا | □ PASS / □ FAIL | | Critical | |
| `erp-work-execution-detail.php?jobcard_id={id}` | TECHNICIAN | جزئیات اجرا | تکمیل حداقل یک work item قابل فروش | `work_item` برای ثبت خدمت موجود است | □ PASS / □ FAIL | | Critical | |
| `erp-workshop-service-entry.php?jobcard_id={id}` | TECHNICIAN | work item موجود؛ نقش مجاز | ذخیره پیش‌نویس خط خدمت بدون مبلغ | وضعیت `DRAFT`؛ بدون قیمت ذخیره‌شده | □ PASS / □ FAIL | | Critical | |
| `erp-workshop-service-entry.php?jobcard_id={id}` | TECHNICIAN | خط `DRAFT`/`RETURNED` | اقدام `submit` | وضعیت `SUBMITTED`؛ در صف قیمت‌گذاری | □ PASS / □ FAIL | | Critical | |
| `erp-workshop-service-summary.php?jobcard_id={id}` | TECHNICIAN | خط ثبت‌شده | مشاهده خلاصه | خطوط دیده می‌شوند؛ مبلغ برای نقش بدون مجوز مخفی است | □ PASS / □ FAIL | | High | |
| `erp-workshop-service-pricing.php` | SERVICE_MANAGER | خط `SUBMITTED` در صف شرکت | باز کردن صف بررسی | خط در صف همان company دیده می‌شود | □ PASS / □ FAIL | | Critical | |
| `erp-workshop-service-pricing.php?service_line_id={id}` | SERVICE_MANAGER | خط `SUBMITTED` | اقدام `approve` (تأیید فنی) | وضعیت به مسیر قیمت‌گذاری (`TECHNICALLY_APPROVED` / `PRICING_PENDING`) می‌رود | □ PASS / □ FAIL | | Critical | |
| `erp-workshop-service-pricing.php?service_line_id={id}` | SERVICE_MANAGER | خط آماده قیمت | `set_price` با مبلغ ریال صحیح | وضعیت `PRICED`؛ مبلغ فقط برای نقش مجاز | □ PASS / □ FAIL | | Critical | |
| `erp-workshop-service-pricing.php?service_line_id={id}` | SERVICE_MANAGER | خط `PRICED` | `ready` / آماده‌سازی برای فاکتور | وضعیت `READY_FOR_INVOICE` | □ PASS / □ FAIL | | Critical | |
| `erp-workshop-service-summary.php?jobcard_id={id}` | SERVICE_MANAGER / OWNER | خطوط قیمت‌دار | مشاهده جمع سرور | `subtotal` سرور با خطوط priced هم‌خوان؛ هشدار ADDITIONAL در صورت نیاز | □ PASS / □ FAIL | | High | |
| `erp-qc-board.php` | QC | کار اجراشده | باز کردن برد QC | پرونده در صف QC | □ PASS / □ FAIL | | Critical | |
| `erp-qc-detail.php?jobcard_id={id}` | QC | جزئیات QC | تکمیل چک‌ها تا آمادگی تحویل | `DELIVERY_READY` / readiness مرتبط برقرار | □ PASS / □ FAIL | | Critical | |
| `erp-final-invoice-board.php` | FINANCE | JobCard آماده فاکتور | باز کردن برد فاکتور نهایی | پرونده در فیلتر مناسب دیده می‌شود | □ PASS / □ FAIL | | Critical | |
| `erp-final-invoice-detail.php?jobcard_id={id}` | FINANCE | گیت‌های P7 برقرار | `create_draft_invoice` | پیش‌نویس فاکتور ایجاد می‌شود | □ PASS / □ FAIL | | Critical | |
| `erp-final-invoice-action.php` | FINANCE | پیش‌نویس موجود؛ CSRF معتبر | `calculate_invoice` (شامل تبدیل خطوط `READY_FOR_INVOICE`) | اقلام خدمت به فاکتور اضافه؛ خط → `INVOICED` | □ PASS / □ FAIL | | Critical | |
| `erp-final-invoice-action.php` | FINANCE | فاکتور محاسبه‌شده؛ CSRF معتبر | `finalize_invoice` | فاکتور `FINALIZED`؛ توکن تحویل hashed تولید می‌شود | □ PASS / □ FAIL | | Critical | |
| `erp-settlement-detail.php?jobcard_id={id}` | FINANCE | فاکتور نهایی‌شده | باز کردن تسویه و همگام‌سازی | کنترل تسویه فعال؛ مانده صحیح | □ PASS / □ FAIL | | Critical | |
| `erp-settlement-action.php` | FINANCE | پرداخت‌ها طبق محیط تست هم‌تراز | `mark_settled` (یا مسیر مجاز) | `SETTLED`؛ گیت تحویل باز می‌شود | □ PASS / □ FAIL | | Critical | |
| `customer-delivery-review.php` | CUSTOMER | لینک امن معتبر | بررسی خلاصه خدمات/مالی و پذیرش چک‌باکس‌ها | صفحه معتبر؛ بدون افشای توکن در شواهد | □ PASS / □ FAIL | | High | |
| `customer-delivery-sign.php` | CUSTOMER | OTP/امضا آماده | تأیید تحویل با OTP + امضا | `DELIVERY_SIGNED` | □ PASS / □ FAIL | | Critical | |
| `erp-settlement-detail.php?jobcard_id={id}` | FINANCE / OWNER | تسویه+امضا OK | `release_vehicle` / بستن JobCard طبق UI | `VEHICLE_RELEASED` و در صورت تکمیل، بستن کنترل‌شده | □ PASS / □ FAIL | | Critical | |
| `erp-jobcard-timeline.php?jobcard_id={id}` | OWNER | پرونده بسته‌شده یا نزدیک بسته | ممیزی رویدادها | زنجیره audit از خدمت تا تحویل کامل و قابل اثبات است | □ PASS / □ FAIL | | High | |

---

## B) موارد منفی کلیدی (Negative)

| route | test actor | precondition | action | expected result | result | evidence ref | severity | defect ID |
|-------|------------|--------------|--------|-----------------|--------|--------------|----------|-----------|
| `erp-workshop-service-pricing.php` | TECHNICIAN | لاگین تکنسین | GET/POST قیمت‌گذاری | رد نقش / Forbidden؛ قیمت ثبت نشود | □ PASS / □ FAIL | | Critical | |
| `erp-final-invoice-action.php` | TECHNICIAN / RECEPTION | نقش غیر FINANCE | POST عملیات فاکتور | رد نقش؛ فاکتور تغییر نکند | □ PASS / □ FAIL | | Critical | |
| `erp-workshop-service-entry.php?jobcard_id={id}` | TECHNICIAN | فرم ثبت خدمت | POST با فیلدهای تزریقی قیمت (`price` / `price_irr` / …) | `m360_ws_reject_injected_prices`؛ Forbidden؛ مبلغ ذخیره نشود | □ PASS / □ FAIL | | Critical | |
| `erp-workshop-service-summary.php?jobcard_id={id}` | TECHNICIAN | خط priced موجود | مشاهده خلاصه بدون مجوز قیمت | مبالغ نمایش داده نمی‌شوند | □ PASS / □ FAIL | | High | |
| `erp-final-invoice-action.php` | FINANCE | خط قبلاً `INVOICED` روی همین فاکتور | تکرار calculate/convert همان `service_line_id` | بدون ردیف مالی تکراری (idempotent / skip duplicate) | □ PASS / □ FAIL | | Critical | |
| `erp-final-invoice-action.php` | FINANCE | جلسه معتبر | POST بدون `erp_csrf_token` یا با توکن نامعتبر | CSRF reject؛ تغییر وضعیت رخ ندهد | □ PASS / □ FAIL | | Critical | |
| `erp-settlement-action.php` | FINANCE | جلسه معتبر | POST تسویه بدون CSRF معتبر | CSRF reject | □ PASS / □ FAIL | | Critical | |
| `erp-workshop-service-entry.php?jobcard_id={other_company}` | TECHNICIAN / SERVICE_MANAGER | شناسه JobCard شرکت دیگر | دسترسی/ثبت روی object خارج از scope | رد object scope / داده شرکت دیگر دیده نشود | □ PASS / □ FAIL | | Critical | |
| `erp-workshop-service-pricing.php?service_line_id={other_company}` | SERVICE_MANAGER | خط شرکت دیگر | باز کردن/قیمت‌گذاری | رد scope / Forbidden | □ PASS / □ FAIL | | Critical | |
| `erp-settlement-detail.php?jobcard_id={id}` | FINANCE | مانده پرداخت > 0؛ بدون manager release | تلاش `release_vehicle` / تحویل | مسدود؛ unpaid delivery جلوگیری شود | □ PASS / □ FAIL | | Critical | |
| `erp-workshop-service-pricing.php?service_line_id={id}` | SERVICE_MANAGER | خط بدون قیمت معتبر / نه `PRICED` | اقدام `ready` | Fail؛ وضعیت `READY_FOR_INVOICE` نشود | □ PASS / □ FAIL | | High | |
| `erp-workshop-service-pricing.php?service_line_id={id}` | SERVICE_MANAGER | خط `SUBMITTED` | `return` با دلیل خالی | Fail اعتبارسنجی؛ وضعیت عوض نشود | □ PASS / □ FAIL | | Medium | |
| `erp-workshop-service-pricing.php?service_line_id={id}` | SERVICE_MANAGER | خط `INVOICED` | تلاش تغییر مبلغ | مسدود / بدون اثر مخرب روی فاکتور نهایی | □ PASS / □ FAIL | | High | |
| `erp-final-invoice-detail.php?jobcard_id={id}` | FINANCE | JobCard بدون گیت QC/Delivery Ready | `create_draft_invoice` | اقدام مجاز نباشد یا گیت بلوکه کند | □ PASS / □ FAIL | | High | |
| `customer-delivery-sign.php` | CUSTOMER | تسویه ناقص / بلاک تحویل | تلاش تأیید تحویل | تأیید نهایی عملیاتی مجاز نشود یا گیت مالی مانع شود | □ PASS / □ FAIL | | High | |
| `erp-workshop-service-entry.php` | Anonymous / logged-out | بدون staff session | GET صفحه ثبت خدمت | Redirect/Login؛ بدون داده | □ PASS / □ FAIL | | High | |
| `erp-final-invoice-board.php` | Anonymous / logged-out | بدون staff session | GET برد فاکتور | Redirect/Login | □ PASS / □ FAIL | | High | |
| `erp-settlement-action.php` | SERVICE_MANAGER (غیرمجاز مالی در صورت اعمال) | نقش بدون مجوز تسویه | POST `mark_settled` | رد دسترسی | □ PASS / □ FAIL | | High | |

---

## C) ثبت جمع‌بندی مالک

| مورد | مقدار |
|------|--------|
| تاریخ UAT | |
| محیط | □ LOCAL □ STAGING |
| نسخه/شاخه | `feature/workshop-service-sales` |
| تعداد PASS | |
| تعداد FAIL | |
| تصمیم مالک | □ Accepted □ Accepted with defects □ Rejected |
| امضا/نام نقش (بدون credential) | |

> یادآوری: هیچ رمز، OTP، توکن خام، یا دادهٔ شخصی واقعی در این ماتریس یا پیوست‌های commit‌شده قرار نگیرد.

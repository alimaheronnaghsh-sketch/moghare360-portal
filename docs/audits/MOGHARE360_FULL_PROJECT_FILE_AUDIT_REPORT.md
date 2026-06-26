# MOGHARE360 — گزارش جامع ممیزی فایل پروژه (Read-Only Audit)

**تاریخ ممیزی:** ۱۴۰۵/۰۴/۰۵ (۲۰۲۶-۰۶-۲۶)  
**مسیر Repo:** `C:\Users\User\Documents\GitHub\alimaheronnaghsh-sketch\moghare360-portal`  
**مسیر Runtime محلی:** `C:\xampp\htdocs\moghare360`  
**نوع ممیزی:** Read-Only — بدون تغییر کد، SQL، config، package یا deploy  
**نسخه گزارش:** 1.0

---

## A) Executive Summary

### وضعیت فعلی پروژه

MOGHARE360 یک ERP خودرویی چندلایه است که از فازهای ۱ تا ۱۵ (به‌علاوه Apex Phase 0–1 و Waveهای ۱ تا ۹C) ساخته شده است. **هسته ERP داخلی روی SQL Server** با بیش از **۱۷۹ صفحه `erp-*.php`**، **۱۱ endpoint API**، و **۳۲ فایل SQL Server کاننیکال** در repo موجود است. **سایت عمومی Mirror/PWA** برای cPanel جداگانه بسته‌بندی شده (`moghare360-cpanel-public-final.zip` — ۲۲ فایل flat root).

| بعد | وضعیت |
|-----|--------|
| **ساخته‌شده و واقعی** | ERP Master، ماژول‌های Customer/JobCard/Inventory/Purchase/Payment/QC/CRM/HR، Soft Run، Executive Go/No-Go، API پایه، Release packages |
| **Prototype / Demo** | Demo package، Desktop Run package، بخشی از sync API (`debug-pending.php`) |
| **Local-Ready** | Runtime XAMPP (۷۹۵ فایل)، SQL Server `moghare360_ERP`، Master Console، تست‌های smoke ۲۴/۲۴ (طبق signoff) |
| **Production-Ready نیست** | cPanel live نهایی، API دائمی روی VPS، SMS OTP واقعی، owner signoff رسمی، monitoring تولید |

### مهم‌ترین مشکل فعلی

**عدم هم‌راستایی deploy سایت عمومی cPanel با منبع canonical:**  
- `index.php` در repo/runtime = **ورود Master ERP** (با بنر SQL Server)  
- `index.php` در package cPanel = **`cpanel-public-index.php`** (مدل عمومی مشتری/پرسنل)  
- `service-worker.js` در repo هست ولی در runtime sync نشده → ریسک cache قدیمی CSS آنلاین  
- احتمال **nested `public_html/`** یا استخراج نادرست ZIP روی cPanel → لوگو بزرگ / برند LTR خراب / CSS قدیمی

---

## B) Architecture Map

### ۱. SQL Server Canonical DB

| نقش | فایل‌های کلیدی |
|-----|----------------|
| Orchestrator | `public_html/sql/sqlserver/MOGHARE360_V1_CANONICAL_DATABASE.sql` |
| Verify (read-only) | `public_html/sql/sqlserver/MOGHARE360_V1_DATABASE_VERIFY.sql` |
| Extensions | `v1_canonical_extensions.sql`, `v1_saas_activation_foundation.sql`, `v1_post_run_fix_register.sql` |
| Phase/Mission SQL | `phase_1_customer_core_system.sql` … `phase_12_soft_run_pilot.sql`, `mission_15_*.sql` … `mission_30_*.sql` |
| Legacy MySQL (REFERENCE_ONLY) | `public_html/sql/*.sql` (۲۲ فایل — `CREATE TABLE IF NOT EXISTS`) |

**Config:** `private/erp-config.php` (موجود، gitignored — محتوا dump نشده)

### ۲. PHP ERP Master

| نقش | فایل‌های کلیدی |
|-----|----------------|
| Entry | `public_html/index.php`, `erp-v1-master-console.php`, `erp-v1-unit-access-console.php` |
| Helpers | `includes/moghare360-v1-master-console-helper.php`, `moghare360-v1-post-run-control-helper.php` |
| Production control | `erp-v1-production-signoff.php`, `erp-v1-fix-register.php` |

### ۳. Public Mirror Site

| نقش | فایل‌های کلیدی |
|-----|----------------|
| cPanel landing | `cpanel-public-index.php` → در package به `index.php` |
| Pages | `customer-request.php`, `staff-login.php`, `owner-login.php`, `user-access-request.php`, `company-owner-dashboard.php`, `mirror-health.php` |
| Layout | `includes/mirror-layout.php`, `includes/mirror-api-client.php` |
| Config template | `mirror-config.example.php` |
| PWA | `service-worker.js`, `manifest.webmanifest` |

### ۴. API Layer

| Endpoint | مسیر |
|----------|------|
| Customer request | `api/customer/request.php` |
| Access request | `api/access/request.php` |
| Staff login | `api/auth/staff-login.php` |
| Owner login | `api/auth/owner-login.php` |
| Owner dashboard | `api/dashboard/company-owner.php` |
| Mirror health | `api/mirror/health.php` |
| Sync (prototype) | `api/sync/pending.php`, `ack.php`, `config-sync.php`, `health.php`, `debug-pending.php` |

### ۵. Auth / Login Layer

| فایل | نقش |
|------|-----|
| `staff-auth.php` | مرز login پرسنل (LOCKED — تغییر ممنوع) |
| `access-control.php` | مرز permission (LOCKED) |
| `staff-login.php` / `owner-login.php` | UI ورود عمومی |
| `includes/moghare360-saas-config-loader.php` | بارگذاری config SaaS |

### ۶. Access / Permission / Workflow

| فایل | نقش |
|------|-----|
| `user-access-request.php` | درخواست دسترسی |
| `erp-access-*` (۸ صفحه) | مدیریت دسترسی ERP |
| `includes/moghare360-permission-guard.php` (و helpers مرتبط) | Guard |
| Workflow submit routes | `submit-*-workflow.php`, `submit-*-v2.php` |

### ۷. Customer Request

`customer-request.php`, `api/customer/request.php`, `assets/js/customer-form.js`, `assets/js/iran-provinces-cities.js`, `assets/js/vehicle-brand-classes.js`

### ۸. Operation / JobCard

۳۱ فایل `erp-jobcard*`, helpers مرتبط، wave 4/5 dashboards

### ۹. Inventory / Purchase

`erp-inventory*`, `erp-part*`, `erp-purchase*` (۵+۳ فایل)

### ۱۰. Finance / CRM / HR / QC

Payment (۴), CRM (۳), HR (۲), QC/Delivery (۳)

### ۱۱. Release / Packaging

| Package | هدف |
|---------|-----|
| `moghare360-cpanel-public-final.zip` | **cPanel flat public** (۲۲ فایل) |
| `moghare360-mirror-site-package.zip` | Mirror با `public_html/` nested |
| `moghare360-v1-production-installer.zip` | ERP کامل (~۵۰۰ entry) |
| `moghare360-v1-auto-deploy.zip` | Auto deploy |
| `moghare360-desktop-run-package.zip` | Desktop run |
| `moghare360-demo-package.zip` / `local-rc1.zip` | Demo / RC1 |

Scripts: `tools/package-moghare360-cpanel-public-final.ps1`, `package-moghare360-cpanel-mirror-clean.ps1`, `package-moghare360-mirror-site.ps1`

### ۱۲. Local Runtime

`C:\xampp\htdocs\moghare360` — ۷۹۵ فایل، شامل `config.php`, `mirror-config.php` (runtime-only), docs محلی، tools

### ۱۳. cPanel Site

Target: `moghareh360.ir` — deploy از `moghare360-cpanel-public-final.zip` (flat root) + `mirror-config.php` دستی

---

## C) Module-by-Module Review

| ماژول | فایل‌های مرتبط (نمونه) | وضعیت ساخت | وضعیت تست | وابستگی‌ها | ریسک‌ها | کار باقی‌مانده |
|-------|------------------------|------------|-----------|------------|---------|----------------|
| Admin / Master Console | `erp-v1-master-console.php`, helper | COMPLETE | `test-v1-local-master-console.php` | SQL Server, erp-config | بنر SQL در index محلی | جداسازی entry عمومی/داخلی در runtime |
| Auth / Login | `staff-auth.php`, `staff-login.php`, `owner-login.php` | COMPLETE | smoke/signoff | access-control, core_users | تست واقعی owner/staff live | تست login واقعی pre-go-live |
| Access Request | `user-access-request.php`, `api/access/request.php` | COMPLETE | phase tests | permission guard | — | — |
| Permission Guard | helpers + `access-control.php` | COMPLETE | `test-erp-permission-guard.php` | DB roles | LOCKED file | — |
| Workflow / Audit | `submit-*`, audit helpers | COMPLETE | wave 2e, 3d | validation engine | — | — |
| Customer Core | `erp-customer*` (۱۳) | COMPLETE | phase 1, wave 1d (write) | SQL Server | — | — |
| Customer Public Request | `customer-request.php`, API | COMPLETE | visual-hotfix, cpanel package | mirror-config, Master API URL | cPanel→Master API غیرفعال | اتصال API دائمی |
| JobCard | `erp-jobcard*` (۳۱) | COMPLETE | phase 2, wave 4/5 | customer, vehicle | — | — |
| Service Operation | `erp-service-operation*` | COMPLETE | mission 20 tests | jobcard | — | — |
| Parts / Inventory | `erp-inventory*`, `erp-part*` | COMPLETE | phase 4 | purchase | — | — |
| Purchase | `erp-purchase*` | COMPLETE | phase 4 | inventory | — | — |
| Payment / Finance | `erp-payment*`, finance pages | COMPLETE | phase 5 | jobcard | gateway غیرفعال (locked) | V2 |
| CRM | `erp-crm*` | COMPLETE | phase 6 | customer | — | — |
| HR / Internal Admin | `erp-hr*` | COMPLETE | phase 7 | auth | — | — |
| QC / Delivery | `erp-qc*`, `erp-delivery*` | COMPLETE | phase/mission 30 | jobcard | — | — |
| Soft Run | `erp-soft-run*` (۲۲) | COMPLETE | wave 6–8, phase 12 | — | — | closure نهایی عملیاتی |
| Commercial / Productization | phase 10 pages | COMPLETE | phase 10 test | — | — | V2 billing |
| Public Site / Mirror | mirror pages + CSS/JS | COMPLETE (source) | cpanel-public-final test | Master API | deploy/cache mismatch | cPanel live diagnosis |
| API | ۱۱ endpoints | PARTIAL (sync prototype) | production smoke | VPS/SSL | no permanent route | Windows VPS decision |
| Packaging / Release | release/*, ۲۶ ZIP | COMPLETE | package tests | — | duplicate ZIP paths | یک package canonical برای cPanel |
| Local Runtime Sync | `SYNC_PUBLIC_SITE_TO_LOCAL_XAMPP.ps1` | PARTIAL | sync report موجود | — | index.php = Master نه public | sync service-worker |
| cPanel Deploy | cpanel-public-final zip | BUILT | package test | hosting | nested extract, cache | deploy تمیز فردا |

---

## D) File System Analysis

### تعداد تقریبی (بدون `.git/`)

| دسته | تعداد |
|------|-------|
| **کل repo** | ~۵٬۸۵۵ |
| `public_html/` | ۵۲۴ |
| `tools/` | ۱۲۳ (۸۸ تست `test-*.php`) |
| `docs/` | ۱٬۰۸۷ |
| `release/` (شامل کپی package) | ۳٬۸۱۰ |
| `private/` | ۶ |
| `runtime/` (repo) | ۲۳ |
| **Runtime XAMPP** | ۷۹۵ |

### بر اساس پسوند (کل repo)

| نوع | تعداد |
|-----|-------|
| `.md` | ۲٬۸۷۵ |
| `.php` | ۲٬۰۰۲ |
| `.sql` | ۳۳۹ |
| `.css` | ۱۶۱ |
| `.js` | ۴۳ |
| `.ps1` | ۴۸ |
| `.zip` | ۲۶ |
| `.jpg/.png` | ۷۰ |

### فایل‌های حساس (وجود/نقش — بدون dump محتوا)

| فایل | Repo | Runtime | نقش |
|------|------|---------|-----|
| `private/erp-config.php` | EXISTS (gitignored) | n/a | DB credentials |
| `private/production-users.json` | NOT FOUND | n/a | Expected template only |
| `private/production-site-config.json` | NOT FOUND | n/a | Expected template only |
| `public_html/mirror-config.php` | NOT FOUND (correct) | EXISTS | Master API URL — runtime only |
| `public_html/config.php` | NOT FOUND (correct) | EXISTS | ERP local config — runtime only |
| `private/templates/*.template.json` | EXISTS | n/a | Templates only |

### Legacy

- `public_html/sql/*.sql` — MySQL Codex (۲۲ فایل) — **LEGACY_DO_NOT_USE**
- `tools/_legacy_codex_review/` — gitignored reference
- `submit-customer.php`, `submit-service-request.php` — reference per DB lock doc

### Duplicate / مشکوک

- `release/` شامل کپی کامل `public_html` در چند package → source of truth فقط `public_html/` در root repo
- ZIPهای duplicate در `release/` و `public_html/release/`
- `erp-config-loader.php` در production-installer (loader code — not secrets)

---

## E) Runtime vs Source Comparison

### سه مسیر مقایسه‌شده

| مسیر | فایل‌ها | نقش |
|------|---------|-----|
| `public_html/` (repo) | ۵۲۴ | Source canonical |
| `C:\xampp\htdocs\moghare360` | ۷۹۵ | Local runtime (ERP + mirror + docs) |
| `release/_cpanel_public_final_stage/` | ۲۲ | Staging cPanel flat |

### Hash کلیدی (SHA256 ۱۲ char)

| فایل | public_html | runtime | cpanel_stage |
|------|-------------|---------|--------------|
| `customer-request.php` | یکسان | یکسان | یکسان |
| `staff-login.php` | یکسان | یکسان | یکسان |
| `owner-login.php` | یکسان | یکسان | یکسان |
| `mirror-layout.php` | یکسان | یکسان | یکسان |
| `mirror.css` | یکسان | یکسان | یکسان |
| `moghare360-v1-luxury-ui.css` | یکسان | یکسان | یکسان |
| `index.php` | **متفاوت** | = repo (Master) | = cpanel-public-index |
| `service-worker.js` | موجود | **MISSING** | موجود |

### فقط در Runtime (نمونه)

`config.php`, `mirror-config.php`, `docs/*` (phase docs کپی‌شده), `.gitignore`, `api/workflow-transition.php`

### فقط در public_html (نمونه)

`cpanel-public-index.php`, `service-worker.js`, `manifest.webmanifest`, `assets/icons/*`, ZIPهای release داخل `public_html/release/`

### پاسخ سوالات کلیدی

| سوال | پاسخ |
|------|------|
| `customer-request.php` در همه مسیرها؟ | بله — repo, runtime, cpanel package |
| `staff-login.php` / `owner-login.php` نسخه جدید؟ | بله — hash یکسان در سه مسیر mirror |
| CSS/JS sync شده؟ | بله برای mirror.css و luxury-ui و customer-form.js |
| mismatch باعث مشکل cPanel؟ | **بله محتمل** — index اشتباه، SW/cache، nested public_html |

---

## F) cPanel / Online Site Diagnosis Preparation

### چرا لوگو بزرگ شده؟

1. CSS قدیمی cache شده (`service-worker.js` cache `mirror.css` با نام `moghare360-public-shell-v1`)
2. deploy بدون `mirror.css` / `moghare360-v1-luxury-ui.css` (فقط `@import` در mirror.css)
3. قوانین فعلی: `max-height: 48px; max-width: 128px` در هر دو فایل CSS — اگر آنلاین بزرگ است، **CSS قدیمی یا نبود class `m360-public-shell`** محتمل است
4. تصویر خام `assets/brand/moghareh-motors-logo.jpg` بدون CSS constraint

### چرا MOGHAREH360 به حروف نامربوط دیده می‌شود؟

1. کلاس `m360-brand-latin` با `dir="ltr"` و `unicode-bidi: isolate` — بدون CSS، RTL مرورگر حروف را جدا می‌کند
2. charset غیر UTF-8 در response یا فایل ذخیره‌شده با encoding اشتباه
3. فونت fallback بدون `letter-spacing` مناسب

### نقش مؤلفه‌ها

| مؤلفه | نقش |
|--------|-----|
| CSS global (`moghare360-v1-luxury-ui.css`) | متغیرها، shell، logo constraints، brand LTR |
| `mirror.css` | import luxury-ui + plate widget + public overrides |
| charset | `header('Content-Type: text/html; charset=UTF-8')` در mirror-layout |
| service-worker | cache static — **خطر CSS stale** |
| nested `public_html/` | مسیر asset اشتباه → 404 CSS → UI شکسته |
| old assets | پوشه قدیمی روی host اگر پاک نشود |

### چک‌های پیشنهادی فردا (روی cPanel live)

1. View Source → `charset=UTF-8` و لینک `assets/css/mirror.css` با HTTP 200
2. DevTools → computed style لوگو → `max-height: 48px`
3. Unregister service worker + hard refresh
4. Confirm flat extract (نه `public_html/public_html/`)
5. `mirror-config.php` از example کپی شده و `MASTER_SERVER_BASE_URL` صحیح
6. `index.php` = مدل عمومی (نه Master ERP banner)
7. تست `mirror-health.php`

---

## G) Security Review

| بررسی | نتیجه |
|-------|--------|
| Credential در repo/package | `erp-config.php` gitignored؛ ZIPهای cPanel/mirror بدون mirror-config واقعی |
| private config در package | cpanel-public-final: PASS — فقط example |
| mirror-config واقعی در ZIP | NOT FOUND در zip inspect |
| `public_html/config.php` در repo | NOT EXISTS — صحیح |
| MySQL legacy فعال | خیر — locked به SQL Server |
| Old submit routes | legacy files موجود؛ V1 از `api/customer/request.php` |
| خطای فنی در public UI | grep متن ممنوع در mirror pages: **یافت نشد** |
| auth core تغییر کرده؟ | staff-auth/access-control موجود؛ ممیزی diff انجام نشد (read-only) |
| laptop/server/mirror در UI عمومی | در customer/staff/owner/layout: **یافت نشد** (index Master محلی جداگانه دارد) |

---

## H) Test Review

### آمار

- **۸۸** فایل `test-*.php` یکتا در `tools/`
- تست‌های write-capable شناسایی‌شده (اجرا نشد): `test-wave-1d-customer-db-write.php`, `test-wave-1e-vehicle-db-write.php`, `test-wave-1f-jobcard-db-write.php` و تست‌های workflow با DB insert

### دسته‌بندی تست‌ها

| دسته | نمونه | Read-only? |
|------|-------|------------|
| Phase 1–15 | `test-phase-1-customer-core.php` … | عمدتاً file/structure checks |
| Wave 1–9 | validation, media, soft-run, executive | 1d–1f write-capable |
| V1 | `test-v1-canonical-database.php` | verify SQL read |
| V1 public | `test-v1-public-site-visual-hotfix.php` | **read-only** (lint + content) |
| V1 cpanel | `test-v1-cpanel-public-final-package.php` | read-only zip inspect |
| V1 production | `test-v1-production-run-smoke.php` | reported 24/24 PASS |
| Package | `test-phase-mirror-site-package.php` | read-only |

### تست‌های کلیدی pre-release

1. `test-v1-cpanel-public-final-package.php`
2. `test-v1-public-site-visual-hotfix.php`
3. `test-v1-public-site-ux-cleanup.php`
4. `test-v1-production-run-smoke.php`
5. `test-v1-real-run-readiness.php`

---

## I) Release Package Review

| Package | هدف | باید شامل | نباید شامل | ریسک |
|---------|-----|-----------|------------|------|
| `moghare360-cpanel-public-final.zip` | **cPanel public flat** | ۲۲ فایل mirror + PWA | config.php, erp-config, mirror-config واقعی | LOW اگر flat extract |
| `moghare360-mirror-site-package.zip` | Mirror nested | public_html/* | secrets | MEDIUM — nested path |
| `moghare360-cpanel-mirror-clean.zip` | Clean mirror variant | mirror set | secrets | MEDIUM |
| `moghare360-v1-production-installer.zip` | ERP نصب تولید | full ERP + SQL + tools | credentials | MEDIUM — scope بزرگ |
| `moghare360-v1-auto-deploy.zip` | Deploy خودکار | scripts + app | overwrite config | HIGH if misused |
| `moghare360-desktop-run-package.zip` | اجرای دسکتاپ | full copy | — | LOW internal |
| `moghare360-demo-package.zip` | دمو | subset | — | LOW |
| `moghare360-local-rc1.zip` | RC1 محلی | subset | — | LOW |

**پیشنهاد cPanel:** `release/moghare360-cpanel-public-final.zip`  
**پیشنهاد production installer:** `release/moghare360-v1-production-installer.zip` (+ `moghare360-v1-production-final-delivery` bundle)

---

## J) Locked Decisions

### مدیریتی
- فازهای ۱–۱۵ COMPLETED (master doc)
- Production SaaS / Public Portal / Accounting / Payment Gateway: **NOT ACTIVE** بدون mission جدید
- پس از V1 signoff: فقط Fix Register — نه mission جدید

### فنی
- UI → Validation → Workflow → DB → Audit
- SQL Server canonical؛ MySQL legacy reference-only
- Camera direct only — no upload bypass
- Font stack: Vazirmatn, Tahoma, Segoe UI, Arial

### امنیتی
- ممنوعیت تغییر: `staff-auth.php`, `access-control.php`, `staff-login.php`, `config.php`, `private/erp-config.php`
- No credentials in repo/packages
- No destructive DB migration

### SQL
- Single orchestrator: `MOGHARE360_V1_CANONICAL_DATABASE.sql`
- Legacy MySQL: do NOT run

### Auth
- Login boundary locked
- core_users seed via Fix Register post-run

### Public Site
- Mirror فقط به Master API — بدون DB روی host
- متن ممنوع در UI عمومی (laptop, server, mirror, SQL Server, cPanel, …)

### Deployment
- cPanel: flat root از cpanel-public-final
- mirror-config.php دستی از example

### GitHub/Cursor/User workflow
- Commit فقط با درخواست صریح user
- Read-only default برای audit/report

### Scope control
- V2 backlog جدا — Fix Register برای post-run

---

## K) Current Problems

| # | مشکل | وضعیت |
|---|------|--------|
| 1 | cPanel display issue | OPEN — نیاز live diagnosis |
| 2 | Logo oversize online | OPEN — cache/CSS محتمل |
| 3 | MOGHAREH360 encoding/LTR | OPEN — CSS/charset/cache |
| 4 | Online package/deploy uncertainty | OPEN |
| 5 | No permanent API route | OPEN — VPS pending |
| 6 | Local vs online mismatch | OPEN — index.php + SW |
| 7 | Owner access not fully reviewed | OPEN |
| 8 | Production VPS decision pending | OPEN |

---

## L) Analytical Interpretation

| سطح بلوغ | میزان رسیدن | مانده |
|---------|-------------|-------|
| **Prototype** | ۱۰۰٪ — demo packages | — |
| **Local Demo** | ~۹۵٪ — ERP + DB + console | owner review کامل |
| **Soft Run** | ~۹۰٪ — wave 6–9 complete | pilot operational closure |
| **Production Candidate** | ~۷۵٪ — signoff فنی، packages | cPanel live, API, SSL |
| **Production Ready** | ~۴۰٪ | monitoring, SMS, rate limit, training, go-live |

**جمع‌بندی:** پروژه در مرز **Local Demo / Soft Run → Production Candidate** است؛ blocker اصلی **deploy عمومی و زیرساخت API** است نه نبود ماژول ERP.

---

## M) Recommended Next Phases

### ۱. cPanel Live Diagnosis
- **هدف:** ریشه‌یابی UI آنلاین
- **فایل‌ها:** mirror.css, luxury-ui.css, service-worker.js, index.php روی host
- **تست:** DevTools + mirror-health
- **خروجی:** checklist علت (cache/nested/css/charset)

### ۲. Clean cPanel Deploy
- **هدف:** استقرار تمیز flat
- **فایل‌ها:** `moghare360-cpanel-public-final.zip`
- **تست:** `test-v1-cpanel-public-final-package.php`
- **خروجی:** سایت با logo 48px و brand LTR صحیح

### ۳. Owner Local Review
- **هدف:** تأیید owner login و dashboard
- **فایل‌ها:** `owner-login.php`, `company-owner-dashboard.php`, `erp-v1-production-signoff.php`
- **تست:** login واقعی (با seed users)
- **خروجی:** signoff رسمی owner

### ۴. Fix Register Triage
- **هدف:** اولویت‌بندی post-run fixes
- **فایل‌ها:** `erp-v1-fix-register.php`, `v1_post_run_fix_register.sql`
- **تست:** read-only register review
- **خروجی:** لیست P0/P1

### ۵. Production Architecture Decision
- **هدف:** VPS vs laptop Master API
- **فایل‌ها:** docs/release SaaS guides
- **تست:** — 
- **خروجی:** decision record

### ۶. Windows VPS / API Deployment
- **هدف:** API دائمی HTTPS
- **فایل‌ها:** `api/*`, `mirror-config.example.php` MASTER_SERVER_BASE_URL
- **تست:** `test-v1-production-run-smoke.php`
- **خروجی:** health 200 از cPanel به VPS

### ۷. End-to-End Customer Flow
- **هدف:** customer-request → DB → ERP
- **فایل‌ها:** customer-request, api/customer/request.php
- **تست:** insert test (controlled)
- **خروجی:** رکورد در `erp_customer_online_requests`

### ۸. Final Go-Live
- **هدف:** production operational
- **فایل‌ها:** monitoring plan, user guide
- **تست:** full smoke + owner signoff
- **خروجی:** GO decision wave 9C

---

*پایان گزارش — ممیزی read-only بدون تغییر در کد، SQL، config یا package.*

# Runbook استقرار Production — Phase 7

**محصول:** MOGHARE360 V1  
**فاز:** Phase 7 — Production Preparation  
**زبان سند:** فارسی (RTL) + شناسه‌های فنی انگلیسی  
**میزبان هدف:** Windows VPS + IIS + SQL Server (نه XAMPP)

> هیچ رمز، توکن، یا hostname واقعی در این سند ثبت نشود. از `PLACEHOLDER_*` استفاده کنید.

---

## ۰. پیش از شروع

1. تکمیل `PRODUCTION_PREREQUISITES_FA.md` (Go/No-Go).
2. تکمیل `PRODUCTION_SECURITY_REVIEW_FA.md`.
3. تکمیل `PRODUCTION_BACKUP_CHECKLIST_FA.md` (شامل rehearsal در صورت امکان).
4. پر کردن `DEPLOYMENT_MANIFEST_TEMPLATE.md` برای این ریلیز.
5. تأیید مالک: PLACEHOLDER_OWNER_APPROVER · زمان پنجره: PLACEHOLDER_MAINTENANCE_WINDOW

---

## ۱. شناسه استقرار

| فیلد | مقدار |
|------|--------|
| Release tag | PLACEHOLDER_RELEASE_TAG |
| Git commit SHA | PLACEHOLDER_GIT_COMMIT_SHA |
| Manifest path | PLACEHOLDER_MANIFEST_PATH |
| Deploy operator | PLACEHOLDER_DEPLOY_OPERATOR |
| Target FQDN | PLACEHOLDER_PROD_FQDN |
| IIS site name | PLACEHOLDER_IIS_SITE_NAME |
| App pool | PLACEHOLDER_IIS_APPPOOL_NAME |
| DB | `moghare360_ERP` @ PLACEHOLDER_SQL_INSTANCE |

---

## ۲. توالی استقرار (خلاصه)

```text
Freeze writes (اختیاری) → Backup DB+files → Verify backup →
Deploy files (staging folder) → Verify SHA256 → Swap/publish →
Config check (private) → Migrations (ordered) →
IIS recycle → Health check → Smoke → Sign-off / Rollback
```

---

## ۳. گام‌به‌گام

### 3.1 اعلام پنجره و freeze

- [ ] اطلاع به ذی‌نفعان (PLACEHOLDER_STAKEHOLDER_LIST)
- [ ] در صورت نیاز: maintenance page یا قطع موقت write routes
- [ ] تأیید هیچ migration موازی دیگری در حال اجرا نیست

### 3.2 Backup اجباری

از روی سرور (PowerShell؛ مقادیر را جایگزین کنید — قالب‌ها بدون credential):

```powershell
# کپی قالب‌ها به مسیر خصوصی و تکمیل PLACEHOLDER_*
pwsh -File PLACEHOLDER_TOOLS_PATH\backup-db.ps1
```

- [ ] Backup DB `moghare360_ERP` موفق
- [ ] Snapshot فایل‌های `public_html` + `private` config
- [ ] کپی offsite به PLACEHOLDER_BACKUP_OFFSITE_PATH
- [ ] ثبت شناسه backup در manifest: PLACEHOLDER_BACKUP_ID

جزئیات: `PRODUCTION_BACKUP_CHECKLIST_FA.md`

### 3.3 آماده‌سازی بسته ریلیز

- [ ] دریافت artifact از منبع تأییدشده (نه از لپ‌تاپ توسعه‌دهنده بدون hash)
- [ ] تولید/تأیید SHA256:

```powershell
pwsh -File PLACEHOLDER_TOOLS_PATH\sha256-release-manifest.ps1
```

- [ ] مقایسه با `DEPLOYMENT_MANIFEST` — مغایرت = توقف

### 3.4 استقرار فایل‌ها روی IIS

مسیر پیشنهادی:

| نقش | مسیر |
|-----|------|
| Staging | PLACEHOLDER_DEPLOY_STAGING_PATH |
| Live web root | PLACEHOLDER_IIS_SITE_PATH\public_html |
| Private config | PLACEHOLDER_PRIVATE_CONFIG_PATH |

قوانین:

- `private/erp-config.php` را از artifact عمومی رونویسی نکنید مگر عمداً و با backup قبلی.
- پوشه‌های `tools/` و `sql/` و scripts مهاجرت را از سرویس HTTP مسدود نگه دارید (IIS Request Filtering / deny rules).
- APK، backup، `.bak`، `.sql` dump، keystore را داخل web root نگذارید.

مراحل عملی:

1. کپی artifact به staging
2. تأیید ساختار `public_html`
3. تعویض اتمی/سریع (rename swap یا robocopy با تأیید)
4. تنظیم ACL: App Pool فقط write روی storage/log مجاز

جزئیات IIS: `deployment/iis/README.md`

### 3.5 پیکربندی خصوصی

- [ ] وجود `erp-config.php` در PLACEHOLDER_PRIVATE_CONFIG_PATH
- [ ] `environment` = `production`
- [ ] `debug` = `false`
- [ ] `security.display_errors_to_browser` = `false`
- [ ] `database.name` = `moghare360_ERP`
- [ ] `MOGHARE360_ERP_CONFIG_PATH` (در صورت استفاده) صحیح است
- [ ] Cookie secure / HTTPS-only مطابق چک‌لیست امنیت

### 3.6 Migration پایگاه‌داده

**فقط** روی `moghare360_ERP` و طبق `deployment/sql/MIGRATION_ORDER.md`.

- [ ] Backup تازه قبل از migration تأیید شده
- [ ] اجرای اسکریپت‌ها به ترتیب؛ هر شکست = توقف و مراجعه به rollback
- [ ] بدون DROP / TRUNCATE / blanket DELETE مگر مجوز کتبی مالک
- [ ] ثبت نسخه schema در manifest: PLACEHOLDER_SCHEMA_VERSION

### 3.7 بازیافت IIS و سرویس‌ها

```powershell
Import-Module WebAdministration
Restart-WebAppPool -Name 'PLACEHOLDER_IIS_APPPOOL_NAME'
# در صورت نیاز:
# Restart-Website -Name 'PLACEHOLDER_IIS_SITE_NAME'
```

### 3.8 Health check

```powershell
pwsh -File PLACEHOLDER_TOOLS_PATH\health-check.ps1
```

معیارها: `PRODUCTION_HEALTH_CHECK_FA.md`

### 3.9 Smoke پس از استقرار

```powershell
pwsh -File PLACEHOLDER_TOOLS_PATH\post-deploy-smoke.ps1
```

حداقل دستی:

- [ ] HTTPS صفحه ورود staff بارگذاری می‌شود (بدون raw PHP error)
- [ ] یک مسیر read حیاتی هر ماژول اصلی OK
- [ ] آپلود آزمایشی به مسیر خصوصی (نه URL عمومی مستقیم)
- [ ] tools/ و مسیرهای migration از اینترنت 403/404

---

## ۴. Sign-off

| نقش | نام PLACEHOLDER | امضا / تاریخ |
|-----|-----------------|---------------|
| Deploy operator | PLACEHOLDER_DEPLOY_OPERATOR | |
| Technical lead | PLACEHOLDER_TECH_LEAD | |
| Owner | PLACEHOLDER_OWNER_APPROVER | |

نتیجه: ☐ PASS · ☐ PASS با WARNING مستند · ☐ FAIL → Rollback

---

## ۵. در صورت شکست

فوراً به `PRODUCTION_ROLLBACK_FA.md` بروید. deploy ناقص را «درمان دستی DB» نکنید مگر با تأیید DBA.

---

## ۶. پس از Go-Live (۲۴ ساعت اول)

- [ ] مانیتور لاگ‌های PLACEHOLDER_LOG_PATH
- [ ] تأیید job بکاپ روزانه
- [ ] عدم commit اسرار / APK / backup به git
- [ ] ثبت حوادث جزئی در PLACEHOLDER_INCIDENT_LOG_PATH

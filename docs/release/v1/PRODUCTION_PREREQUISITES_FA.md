# پیش‌نیازهای Production — Phase 7

**محصول:** MOGHARE360 V1  
**فاز:** Phase 7 — Production Preparation  
**زبان سند:** فارسی (RTL) + شناسه‌های فنی انگلیسی  
**معماری هدف:** Windows VPS · IIS · SQL Server · HTTPS

> **قفل سخت:** XAMPP به‌عنوان میزبان Production مجاز نیست. اسرار واقعی، hostname واقعی، و رمز در این سند ثبت نشود — فقط `PLACEHOLDER_*`.

---

## ۱. هدف

این سند حداقل پیش‌نیازهای سخت‌افزار، نرم‌افزار، شبکه، پایگاه‌داده، و امنیت را قبل از هر deploy واقعی روی Production تعریف می‌کند.

---

## ۲. معماری مجاز

| جزء | مقدار مجاز | غیرمجاز |
|-----|------------|---------|
| OS | Windows Server روی VPS | XAMPP / Apache محلی به‌عنوان Production |
| Web | IIS (+ FastCGI PHP) | Apache از پکیج XAMPP در Prod |
| DB | SQL Server — **فقط** `moghare360_ERP` writable | دو DB همزمان writable / dual-write |
| TLS | HTTPS اجباری (گواهی معتبر) | HTTP عمومی برای ERP |
| Config | خارج از web root (`private/` یا مسیر امن) | `erp-config.php` داخل `public_html` |
| Backup | محلی + **offsite** | فقط یک کپی روی همان دیسک IIS |

---

## ۳. پیش‌نیاز سخت‌افزار / VPS (حداقل پیشنهادی)

| مورد | PLACEHOLDER / حداقل |
|------|---------------------|
| CPU | PLACEHOLDER_VPS_CPU (پیشنهاد ≥ 4 vCPU) |
| RAM | PLACEHOLDER_VPS_RAM (پیشنهاد ≥ 8 GB) |
| Disk | PLACEHOLDER_VPS_DISK (SSD؛ فضای جدا برای backup) |
| Hostname | PLACEHOLDER_PROD_HOSTNAME |
| Public IP | PLACEHOLDER_PROD_PUBLIC_IP |

---

## ۴. پیش‌نیاز نرم‌افزار

| جزء | نسخه / یادداشت |
|-----|----------------|
| Windows Server | PLACEHOLDER_OS_VERSION |
| IIS | با نقش Web Server + URL Rewrite (در صورت نیاز) |
| PHP | PLACEHOLDER_PHP_VERSION (با پسوندهای ODBC / sqlsrv / mbstring / openssl / gd / fileinfo) |
| SQL Server | PLACEHOLDER_SQL_VERSION |
| ODBC Driver | PLACEHOLDER_ODBC_DRIVER (مثلاً ODBC Driver 17/18 for SQL Server) |
| ASP.NET Core Hosting Bundle | اختیاری — فقط اگر سایت/سرویس .NET جداگانه دارید؛ برای PHP ERP اجباری نیست |
| PowerShell | 5.1+ یا pwsh 7+ برای اسکریپت‌های `tools/production/*.template.ps1` |

جزئیات IIS: `deployment/iis/README.md`

---

## ۵. پایگاه‌داده

| قانون | جزئیات |
|-------|--------|
| نام DB | **فقط** `moghare360_ERP` |
| Writable | یک instance / یک DB writable برای اپلیکیشن |
| کاربر اپ | PLACEHOLDER_SQL_APP_LOGIN — حداقل دسترسی لازم (نه `sa`) |
| کاربر backup | PLACEHOLDER_SQL_BACKUP_LOGIN — جدا از کاربر اپ در صورت امکان |
| Collation | مطابق محیط تأییدشدهٔ UAT/Staging |
| Migration | طبق `deployment/sql/MIGRATION_ORDER.md` — فقط پس از backup تأییدشده |

---

## ۶. مسیرها و جداسازی

| مسیر مفهومی | PLACEHOLDER | قانون |
|-------------|-------------|--------|
| Web root | `PLACEHOLDER_IIS_SITE_PATH\public_html` | فقط فایل‌های قابل سرو عمومی |
| Private config | `PLACEHOLDER_PRIVATE_CONFIG_PATH` | خارج از web root؛ ACL محدود |
| Upload / private storage | `PLACEHOLDER_PRIVATE_STORAGE_PATH` | خارج از web root یا با deny HTTP |
| Quarantine | `PLACEHOLDER_QUARANTINE_PATH` | **عمومی نباشد** |
| Logs | `PLACEHOLDER_LOG_PATH` | خارج از web root |
| Backup local | `PLACEHOLDER_BACKUP_LOCAL_PATH` | خارج از web root |
| Backup offsite | `PLACEHOLDER_BACKUP_OFFSITE_PATH` | کپی دوم خارج از VPS |

متغیر محیطی پیشنهادی برای config:

- `MOGHARE360_ERP_CONFIG_PATH` = مسیر کامل به `erp-config.php` واقعی (خارج از repo commit)

نمونهٔ بدون راز: `private/erp-config.example.php` → کپی به مسیر خصوصی با مقادیر واقعی روی سرور.

---

## ۷. شبکه و TLS

- [ ] DNS: PLACEHOLDER_PROD_FQDN → PLACEHOLDER_PROD_PUBLIC_IP
- [ ] گواهی TLS معتبر نصب روی IIS binding HTTPS
- [ ] HTTP → HTTPS redirect
- [ ] فایروال: فقط 443 (و 80 برای redirect)؛ RDP/SQL فقط از IPهای مدیریتی PLACEHOLDER_ADMIN_CIDR
- [ ] SQL Server پورت عمومی اینترنت **باز نباشد** مگر با تونل/VPN تأییدشده

---

## ۸. حساب‌ها و دسترسی (بدون ثبت رمز)

| نقش | شناسه PLACEHOLDER | یادداشت |
|-----|-------------------|---------|
| IIS App Pool identity | PLACEHOLDER_IIS_APPPOOL_IDENTITY | دسترسی write فقط به storage/log مجاز |
| SQL app login | PLACEHOLDER_SQL_APP_LOGIN | بدون sysadmin |
| Deploy operator | PLACEHOLDER_DEPLOY_OPERATOR | دسترسی RDP/فایل محدود |
| Backup operator | PLACEHOLDER_BACKUP_OPERATOR | دسترسی به مسیر backup |

رمزها فقط در vault امن سازمان: PLACEHOLDER_SECRETS_VAULT_URI

---

## ۹. چک‌لیست Go / No-Go قبل از Deploy

| # | شرط | وضعیت |
|---|------|--------|
| 1 | معماری بدون XAMPP Prod | ☐ |
| 2 | یک DB writable = `moghare360_ERP` | ☐ |
| 3 | HTTPS فعال و معتبر | ☐ |
| 4 | Config خارج از web root | ☐ |
| 5 | Backup محلی + offsite آماده | ☐ |
| 6 | ابزارها/migrations از HTTP مسدود | ☐ |
| 7 | Directory listing خاموش | ☐ |
| 8 | Debug و raw errors خاموش | ☐ |
| 9 | Manifest ریلیز + SHA256 آماده | ☐ |
| 10 | Rollback rehearsal مستند شده | ☐ |

بدون تکمیل این جدول، deploy Production انجام نشود.

---

## ۱۰. مراجع مرتبط

- `docs/release/v1/DEPLOYMENT_RUNBOOK_FA.md`
- `docs/release/v1/PRODUCTION_SECURITY_REVIEW_FA.md`
- `docs/release/v1/PRODUCTION_BACKUP_CHECKLIST_FA.md`
- `docs/release/v1/DEPLOYMENT_MANIFEST_TEMPLATE.md`
- `deployment/iis/README.md`
- `deployment/sql/MIGRATION_ORDER.md`

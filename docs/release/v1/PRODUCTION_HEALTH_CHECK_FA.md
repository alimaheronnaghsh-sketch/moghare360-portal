# Health Check Production — Phase 7

**محصول:** MOGHARE360 V1  
**فاز:** Phase 7 — Production Preparation  
**زبان سند:** فارسی (RTL) + شناسه‌های فنی انگلیسی

> این سند معیارهای سلامت پس از deploy / rollback را تعریف می‌کند. اسکریپت قالب: `tools/production/health-check.template.ps1`

---

## ۱. دامنه

| داخل دامنه | خارج از دامنه |
|------------|----------------|
| HTTPS، IIS، PHP پاسخ، اتصال DB مفهومی، مسیرهای مسدود، هدرهای امنیتی پایه | تست بار کامل، نفوذ کامل، UAT عملکردی کامل |

Base URL: `https://PLACEHOLDER_PROD_FQDN/PLACEHOLDER_APP_BASE_PATH`

---

## ۲. چک‌های اجباری (P0)

| ID | بررسی | معیار PASS |
|----|--------|------------|
| H1 | TLS | گواهی معتبر؛ اتصال HTTPS موفق |
| H2 | صفحه ورود / خانه staff | HTTP 200 یا 302 منطقی؛ بدون متن خام Exception/Stack |
| H3 | `display_errors` / debug | در پاسخ HTML اثر debug روشن دیده نشود |
| H4 | مسدود بودن tools | `https://…/tools/` یا مسیر مهاجرت → 403/404 |
| H5 | Directory listing | روی پوشه‌های حساس listing فعال نباشد |
| H6 | Config در web root | درخواست به مسیرهای config خصوصی → 404/403 |
| H7 | App Pool | حالت Started در IIS |
| H8 | SQL connectivity (سمت سرور) | اپ یا smoke بتواند به `moghare360_ERP` وصل شود (بدون چاپ connection string) |

---

## ۳. چک‌های توصیه‌شده (P1)

| ID | بررسی | معیار |
|----|--------|--------|
| H9 | Redirect HTTP→HTTPS | درخواست HTTP به HTTPS هدایت شود |
| H10 | Secure cookie flags | در پاسخ‌های session: Secure / HttpOnly (در صورت اعمال) |
| H11 | HSTS | در صورت سیاست سازمان، هدر Strict-Transport-Security |
| H12 | Private storage | فایل نمونه در PLACEHOLDER_PRIVATE_STORAGE_PATH از URL مستقیم عمومی قابل دریافت نباشد |
| H13 | Quarantine | PLACEHOLDER_QUARANTINE_PATH عمومی نباشد |
| H14 | Disk space | فضای دیسک backup و logs بالاتر از آستانه PLACEHOLDER_DISK_MIN_FREE_GB |
| H15 | Backup job | آخرین backup موفق جدیدتر از PLACEHOLDER_BACKUP_MAX_AGE_HOURS |

---

## ۴. اجرای خودکار (قالب)

```powershell
pwsh -File PLACEHOLDER_TOOLS_PATH\health-check.ps1 `
  -BaseUrl 'https://PLACEHOLDER_PROD_FQDN/PLACEHOLDER_APP_BASE_PATH' `
  -IisSiteName 'PLACEHOLDER_IIS_SITE_NAME' `
  -AppPoolName 'PLACEHOLDER_IIS_APPPOOL_NAME'
```

خروجی مورد انتظار: کد خروج 0 = همه P0 پاس؛ غیرصفر = FAIL → بررسی Rollback.

---

## ۵. اجرای دستی سریع (مرورگر / curl)

جایگزین PLACEHOLDERها:

```text
GET https://PLACEHOLDER_PROD_FQDN/PLACEHOLDER_APP_BASE_PATH/
GET https://PLACEHOLDER_PROD_FQDN/PLACEHOLDER_APP_BASE_PATH/tools/
GET http://PLACEHOLDER_PROD_FQDN/  (باید redirect به HTTPS)
```

نشانه‌های FAIL فوری:

- صفحه سفید با Warning/Notice PHP
- لیست فایل‌های دایرکتوری
- دانلود `.bak` / `.sql` / config از وب
- 500 مداوم پس از recycle

---

## ۶. ثبت نتیجه

| فیلد | مقدار |
|------|--------|
| Run ID | PLACEHOLDER_HEALTH_RUN_ID |
| UTC | PLACEHOLDER_HEALTH_RUN_UTC |
| Operator | PLACEHOLDER_DEPLOY_OPERATOR |
| P0 result | ☐ PASS · ☐ FAIL |
| P1 notes | PLACEHOLDER_HEALTH_P1_NOTES |
| Linked deploy | PLACEHOLDER_RELEASE_TAG |

---

## ۷. مراجع

- `DEPLOYMENT_RUNBOOK_FA.md`
- `PRODUCTION_SECURITY_REVIEW_FA.md`
- `tools/production/post-deploy-smoke.template.ps1`

# Rollback Production — Phase 7

**محصول:** MOGHARE360 V1  
**فاز:** Phase 7 — Production Preparation  
**زبان سند:** فارسی (RTL) + شناسه‌های فنی انگلیسی

> هدف: بازگردانی سریع و کنترل‌شده به وضعیت پایدار پیش از deploy، بدون از بین بردن شواهد و بدون افشای اسرار.

---

## ۱. محرک‌های Rollback (Trigger)

هر یک از موارد زیر می‌تواند Rollback را فعال کند:

| کد | محرک |
|----|------|
| R1 | Health check حیاتی FAIL پس از deploy |
| R2 | خطای گسترده auth / session / permission |
| R3 | Migration ناقص یا ناسازگاری داده |
| R4 | فساد داده یا write غیرمنتظره روی `moghare360_ERP` |
| R5 | نشت امنیتی (خطای خام، مسیر tools باز، افشای فایل خصوصی) |
| R6 | تصمیم مالک/Lead برای توقف پنجره |

تصمیم‌گیرنده: PLACEHOLDER_ROLLBACK_APPROVER (پیش‌فرض: Owner + Technical lead)

---

## ۲. انواع Rollback

### 2.1 فایل (File rollback)

1. توقف/نگه‌داشت ترافیک write در صورت امکان (maintenance)
2. بازگردانی `public_html` از snapshot پیش‌از-deploy: PLACEHOLDER_FILE_BACKUP_ID
3. **بازنویسی نکردن** کورکورانه `private/erp-config.php` مگر backup config جداگانه تأیید شده باشد
4. Recycle App Pool
5. Health + smoke

### 2.2 پایگاه‌داده (DB rollback)

1. تأیید شناسه backup: PLACEHOLDER_DB_BACKUP_ID
2. اطلاع: همه sessionهای اپ قطع می‌شوند
3. Restore فقط روی `moghare360_ERP` از backup تأییدشده (قالب: `tools/production/restore-db.template.ps1`)
4. **ممنوع:** restore روی DB دوم و dual-write همزمان
5. Post-check: اتصال اپ + چند read حیاتی
6. اگر migration بعد از backup اجرا شده بود، فایل‌ها نیز باید با همان نقطه زمانی هم‌تراز شوند (نسخه کد ↔ نسخه schema)

### 2.3 Config rollback

- بازگردانی `PLACEHOLDER_PRIVATE_CONFIG_PATH\erp-config.php` از کپی امن
- هرگز config بازیابی‌شده را به git commit نکنید

### 2.4 Emergency freeze

- قطع write routes در IIS یا پرچم maintenance
- اعلام داخلی به PLACEHOLDER_STAKEHOLDER_LIST
- بدون پیام‌رسانی عمومی نادرست درباره «حمله» مگر تأیید امنیت

---

## ۳. توالی پیشنهادی اضطراری

```text
Approve rollback → Freeze writes → Capture evidence (logs/screenshot) →
Restore files and/or DB from PLACEHOLDER_* backup IDs →
Recycle IIS → Health check → Smoke → Owner sign-off → Post-mortem
```

### دستورات مفهومی (بدون credential)

```powershell
# 1) Freeze / maintenance — طبق رویه IIS سایت
# 2) Restore DB
pwsh -File PLACEHOLDER_TOOLS_PATH\restore-db.ps1

# 3) Restore files از PLACEHOLDER_FILE_BACKUP_PATH (robocopy/xcopy طبق رویه سازمان)

# 4) Recycle
Import-Module WebAdministration
Restart-WebAppPool -Name 'PLACEHOLDER_IIS_APPPOOL_NAME'

# 5) Verify
pwsh -File PLACEHOLDER_TOOLS_PATH\health-check.ps1
pwsh -File PLACEHOLDER_TOOLS_PATH\post-deploy-smoke.ps1
```

---

## ۴. قوانین سخت

| قانون | توضیح |
|-------|--------|
| یک DB | فقط `moghare360_ERP` — بدون dual-writable |
| Backup قبل از restore تأیید شود | فایل `.bak` خراب = توقف |
| بدون XAMPP | Rollback روی همان معماری IIS/SQL |
| بدون commit اسرار | لاگ rollback بدون پسورد/توکن |
| هم‌ترازی کد/اسکیما | فایل و DB را به یک نقطه زمانی واحد برگردانید |
| شواهد | لاگ‌ها را قبل از پاک‌سازی کپی کنید به PLACEHOLDER_INCIDENT_EVIDENCE_PATH |

---

## ۵. اعتبارسنجی پس از Rollback

- [ ] HTTPS پاسخ 200/302 منطقی روی مسیرهای کلیدی
- [ ] بدون stack trace / raw PHP error در مرورگر
- [ ] Login staff (در صورت فعال بودن) یا صفحه ورود سالم
- [ ] tools/ و migrations از HTTP مسدود
- [ ] Backup پس از rollback سالم است (اختیاری اما توصیه‌شده: backup جدید از وضعیت پایدار)

---

## ۶. فرم ثبت Rollback

| فیلد | مقدار |
|------|--------|
| Incident ID | PLACEHOLDER_INCIDENT_ID |
| Trigger code | R_ |
| Start UTC | PLACEHOLDER_ROLLBACK_START_UTC |
| End UTC | PLACEHOLDER_ROLLBACK_END_UTC |
| File backup used | PLACEHOLDER_FILE_BACKUP_ID |
| DB backup used | PLACEHOLDER_DB_BACKUP_ID |
| Operator | PLACEHOLDER_DEPLOY_OPERATOR |
| Approver | PLACEHOLDER_ROLLBACK_APPROVER |
| Result | ☐ SUCCESS · ☐ PARTIAL · ☐ FAILED |
| Follow-up | PLACEHOLDER_FOLLOWUP_NOTES |

---

## ۷. مراجع

- `DEPLOYMENT_RUNBOOK_FA.md`
- `PRODUCTION_BACKUP_CHECKLIST_FA.md`
- `PRODUCTION_HEALTH_CHECK_FA.md`
- `docs/deployment/MOGHARE360_ROLLBACK_PLAN.md` (برنامهٔ عمومی‌تر مخزن)

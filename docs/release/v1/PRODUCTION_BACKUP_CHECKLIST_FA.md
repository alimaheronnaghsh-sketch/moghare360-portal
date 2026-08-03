# چک‌لیست Backup Production — Phase 7

**محصول:** MOGHARE360 V1  
**فاز:** Phase 7 — Production Preparation  
**زبان سند:** فارسی (RTL) + شناسه‌های فنی انگلیسی

> Backup بدون تأیید قابلیت restore، Backup محسوب نمی‌شود. اسرار و مسیرهای واقعی را در git ننویسید.

---

## ۱. اصول

| اصل | جزئیات |
|-----|--------|
| DB هدف | فقط `moghare360_ERP` |
| قبل از migration/deploy | Backup اجباری و تأییدشده |
| Offsite | کپی دوم خارج از همان VPS: PLACEHOLDER_BACKUP_OFFSITE_PATH |
| رمزنگاری | در صورت سیاست سازمان: PLACEHOLDER_BACKUP_ENCRYPTION_METHOD |
| ممنوع در web root | `.bak`، dump، zip دارای داده واقعی |
| ممنوع در git | فایل backup، APK امضاشده، config واقعی |

---

## ۲. انواع Backup

### 2.1 پایگاه‌داده

- Full backup پیش از هر deploy/migration
- زمان‌بندی روزانه پیشنهاد: PLACEHOLDER_DB_BACKUP_SCHEDULE
- نگهداری: حداقل ۷ روزانه + ۴ هفتگی (قابل تنظیم سازمان)
- قالب اسکریپت: `tools/production/backup-db.template.ps1`
- Restore rehearsal: `tools/production/restore-db.template.ps1` روی محیط غیر Prod در صورت امکان

### 2.2 فایل‌ها

| جزء | مسیر مفهومی | یادداشت |
|-----|-------------|---------|
| Web | PLACEHOLDER_IIS_SITE_PATH\public_html | بدون node_modules اضافی در صورت عدم نیاز |
| Private config | PLACEHOLDER_PRIVATE_CONFIG_PATH | ذخیرهٔ امن جدا؛ ACL محدود |
| Private storage | PLACEHOLDER_PRIVATE_STORAGE_PATH | شامل مدارک/آپلودها |
| Quarantine | PLACEHOLDER_QUARANTINE_PATH | جدا؛ عمومی نباشد |
| Logs (اختیاری) | PLACEHOLDER_LOG_PATH | چرخش و حذف طبق سیاست |

### 2.3 Release snapshot

- Artifact + SHA256 manifest برای هر ریلیز: PLACEHOLDER_RELEASE_TAG
- قالب: `DEPLOYMENT_MANIFEST_TEMPLATE.md` + `sha256-release-manifest.template.ps1`

---

## ۳. چک‌لیست پیش از Deploy

- [ ] فضای دیسک کافی روی مسیر backup محلی
- [ ] دسترسی اپراتور backup: PLACEHOLDER_BACKUP_OPERATOR
- [ ] Job/اسکریپت backup بدون hard-code پسورد در فایل commit‌شده
- [ ] مقصد offsite در دسترس
- [ ] شناسه backup قبلی برای rollback مشخص است

### اجرای مفهومی

```powershell
pwsh -File PLACEHOLDER_TOOLS_PATH\backup-db.ps1
# سپس کپی offsite طبق رویه سازمان به PLACEHOLDER_BACKUP_OFFSITE_PATH
```

ثبت:

| فیلد | مقدار |
|------|--------|
| Backup ID | PLACEHOLDER_BACKUP_ID |
| UTC | PLACEHOLDER_BACKUP_UTC |
| Local path | PLACEHOLDER_BACKUP_LOCAL_PATH |
| Offsite path | PLACEHOLDER_BACKUP_OFFSITE_PATH |
| SHA256 of .bak (اختیاری) | PLACEHOLDER_BACKUP_SHA256 |
| Verified restorable | ☐ Yes · ☐ No · ☐ Deferred |

---

## ۴. تأیید Restore (Rehearsal)

حداقل قبل از Go-Live اول:

- [ ] Restore روی instance غیر Production: PLACEHOLDER_SQL_REHEARSAL_INSTANCE
- [ ] DB نام rehearsal جدا (نه overwrite Prod): مثلاً `PLACEHOLDER_REHEARSAL_DB_NAME`
- [ ] اتصال smoke فقط-خواندنی یا محدود
- [ ] زمان RTO اندازه‌گیری‌شده: PLACEHOLDER_MEASURED_RTO_MINUTES
- [ ] RPO هدف سازمان: PLACEHOLDER_TARGET_RPO_MINUTES

**هرگز** rehearsal را روی Prod با dual-write اجرا نکنید.

---

## ۵. چک‌لیست پس از Deploy

- [ ] Backup جدید از وضعیت پایدار (اختیاری ولی توصیه‌شده)
- [ ] Job روزانه هنوز فعال است
- [ ] آخرین وضعیت backup در مانیتورینگ ثبت شد
- [ ] هیچ فایل backup داخل `public_html` باقی نمانده

---

## ۶. حوادث و بازیابی

اگر backup خراب است:

1. از کپی offsite استفاده کنید
2. اگر هر دو خراب‌اند → توقف deploy / اعلام حادثه PLACEHOLDER_INCIDENT_ID
3. بدون «درمان دستی» schema روی Prod

Rollback: `PRODUCTION_ROLLBACK_FA.md`

---

## ۷. مراجع

- `docs/deployment/MOGHARE360_BACKUP_STRATEGY.md`
- `DEPLOYMENT_RUNBOOK_FA.md`
- `tools/production/backup-db.template.ps1`
- `tools/production/restore-db.template.ps1`

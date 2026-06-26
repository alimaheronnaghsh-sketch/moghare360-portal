# MOGHARE360 — ثبت کار باقی‌مانده و ریسک (Remaining Work & Risk Register)

**تاریخ:** ۲۰۲۶-۰۶-۲۶  
**منبع:** Full Project File Audit (read-only)  
**Repo:** `moghare360-portal`

---

## Risk & Remaining Work Register

| Priority | Area | Issue / Remaining Work | Impact | Required Action | Owner | Blocking? | Suggested Phase |
|----------|------|------------------------|--------|-----------------|-------|-----------|-----------------|
| P0 | cPanel / Public Site | cPanel visual problem — UI آنلاین با source هم‌خوان نیست | برند و اعتماد مشتری؛ نمایش نادرست سایت | Live diagnosis: CSS 200, charset, SW cache, flat extract | Owner + Hosting | **Yes** | 1 — cPanel Live Diagnosis |
| P0 | cPanel / Public Site | Logo size online — لوگو بزرگ‌تر از max 48px | UX ضعیف؛ حس amateur | Deploy `mirror.css` + `luxury-ui.css`؛ unregister SW؛ hard refresh | Hosting / Dev | **Yes** | 2 — Clean cPanel Deploy |
| P0 | cPanel / Public Site | MOGHAREH360 text/encoding/LTR — حروف جدا یا RTL اشتباه | برند خراب | تأیید class `m360-brand-latin` و UTF-8 header؛ پاکسازی cache | Hosting / Dev | **Yes** | 2 — Clean cPanel Deploy |
| P0 | Deployment | Online package/deploy uncertainty — مشخص نیست کدام ZIP استخراج شده | رفتار غیرقابل پیش‌بینی | استفاده صرف از `moghare360-cpanel-public-final.zip` flat؛ حذف نسخه قدیمی host | Owner | **Yes** | 2 — Clean cPanel Deploy |
| P0 | Deployment | Package extraction risk — nested `public_html/public_html/` | Asset 404؛ CSS نبود | Extract flat به document root؛ verify paths | Hosting | **Yes** | 2 — Clean cPanel Deploy |
| P1 | Public Site | service-worker/cache risk — CSS stale با cache v1 | UI قدیمی پس از deploy | Bump CACHE_NAME یا disable SW تا stable؛ then re-enable | Dev | Partial | 1–2 |
| P1 | Config | mirror-config preservation — فایل live نباید overwrite شود | قطع API به Master | Backup قبل deploy؛ فقط example در package | Owner | **Yes** | 2 — Clean cPanel Deploy |
| P1 | Release | Public site final package — canonical package انتخاب و lock | Deploy اشتباه | Lock `moghare360-cpanel-public-final.zip` as sole cPanel artifact | Dev / Owner | No | 2 |
| P1 | Local Runtime | Local vs online mismatch — index.php Master در runtime؛ SW missing | تست محلی ≠ production | Sync SW؛ تست جدا cpanel-public-index locally | Dev | Partial | 3 |
| P1 | API / Infra | cPanel to Master API not active — mirror نمی‌تواند به Master وصل شود | فرم مشتری dead-end | Set MASTER_SERVER_BASE_URL؛ VPS/API live | Owner / Infra | **Yes** | 5–6 |
| P1 | Infra | No permanent API route — API روی laptop موقت است | وابستگی به laptop | Windows VPS decision + HTTPS endpoint | Owner | **Yes** | 5–6 |
| P1 | Infra | VPS decision pending | Blocker معماری production | Decision record: laptop vs VPS vs hybrid | Owner | **Yes** | 5 |
| P2 | Auth | Owner review user — دسترسی owner کامل بررسی نشده | signoff ناقص | Owner login test + dashboard review | Owner | Partial | 3 |
| P2 | Auth | Staff login real test — تست واقعی با core_users seed | ورود پرسنل نامشخص live | Seed users via Fix Register؛ test staff-login | Owner / Ops | Partial | 3 |
| P2 | Auth | Owner login real test | ورود مدیریتی نامشخص live | Test owner-login + company-owner-dashboard | Owner | Partial | 3 |
| P2 | Customer Flow | Customer request local insert test — E2E به DB | عدم اطمینان intake | Controlled insert test (write — manual) | Dev / Ops | Partial | 7 |
| P2 | Security | SMS OTP gateway — غیرفعال / not configured | OTP flow incomplete | Configure gateway or disable UI honestly | Owner | No | 6+ |
| P2 | Security | Login rate limit — not verified production | Brute force risk | Implement/verify rate limit pre-go-live | Dev | No | 6 |
| P2 | Security | Public form security — CSRF/spam/abuse review | Abuse intake | Review api/customer/request.php hardening | Dev | Partial | 7 |
| P2 | Operations | Error handling production — نمایش خطا در public | اطلاعات leak | Ensure generic errors on mirror pages | Dev | No | 6 |
| P2 | Data | SQL backup — routine backup not confirmed | Data loss risk | Establish backup schedule + restore test | Ops / Owner | Partial | 6 |
| P2 | Operations | Production monitoring — plan exists, not live | Blind failures | Activate monitoring per PHASE_14 plan | Ops | No | 6 |
| P3 | Documentation | Final user guide — operational guide for staff | Adoption slow | Write FA user guide from existing docs | Dev / Ops | No | 8 |
| P3 | Operations | Operational training — workshop staff training | Misuse ERP | Training session on soft-run paths | Owner | No | 8 |
| P3 | Scope | V2 backlog separation — billing/gateway/accounting | Scope creep | Keep V2 items only in Fix Register V2_BACKLOG | Owner / Dev | No | 4 |
| P2 | API | api/sync/debug-pending.php exposed | Debug leak | Disable or remove from production packages | Dev | Partial | 6 |
| P2 | Release | Duplicate ZIPs in release/ and public_html/release/ | Wrong artifact picked | Document single download path | Dev | No | 2 |
| P3 | Git / Process | production-users.json not in repo | Expected — template only | Fill from template when approved | Owner | No | 3 |

---

## Risk Heat Summary

| Severity | Count | Examples |
|----------|-------|----------|
| **Critical / P0** | 5 | cPanel UI, logo, brand encoding, deploy uncertainty, nested extract |
| **High / P1** | 8 | API route, VPS, mirror-config, SW cache, package lock |
| **Medium / P2** | 12 | Auth tests, E2E, security hardening, backup |
| **Low / P3** | 4 | Training, user guide, V2 separation |

---

## Blocking Chain for Go-Live

```
cPanel Diagnosis (P0)
    → Clean Deploy + mirror-config (P0/P1)
        → VPS / API Decision (P1)
            → API HTTPS Live (P1)
                → Owner + Staff Real Login (P2)
                    → Customer E2E Insert (P2)
                        → Monitoring + Backup (P2)
                            → Training + User Guide (P3)
                                → Final Go/No-Go (Phase 8)
```

---

## Accepted / Mitigated Risks (from audit)

| Risk | Mitigation already in place |
|------|----------------------------|
| Credentials in ZIP | cpanel-public-final zip inspect: no secrets |
| public_html/config.php in repo | NOT EXISTS — correct |
| MySQL legacy active | Locked to SQL Server canonical |
| Forbidden text in public mirror UI | Grep clean on customer/staff/owner/layout |
| auth core arbitrary change | Locked file policy in master doc |

---

## Owner Actions Required (short list)

1. Confirm which files currently on `moghareh360.ir` (screenshot / FTP listing)
2. Approve use of `moghare360-cpanel-public-final.zip` only
3. VPS vs laptop Master API decision
4. Formal owner signoff on `erp-v1-production-signoff.php`
5. Production user seed approval (from template — not in repo)

---

*ثبت ریسک — read-only audit؛ بدون اجرای تست write یا تغییر config.*

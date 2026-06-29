# MOGHARE360 P11.7.1-A — Staff Home UX Polish Report

**Phase:** P11.7.1-A  
**Date:** 2026-06-26  
**Status:** Complete

---

## 1. Scope Gate Result

Scope gate report: `docs/audit/MOGHARE360_P11_7_1_STAFF_HOME_UX_POLISH_SCOPE_REPORT.md`

**Proceed approved.** All changes limited to Staff Home presentation (helper, page, CSS). No forbidden scope touched.

---

## 2. Included Findings from P11.7.1-0

| Finding | Action taken |
|---------|--------------|
| Raw PHP filenames on workbench cards | Hidden from visible UI; `data-route` attribute only |
| Raw `role_code` in identity + card meta | Persian labels in identity; usage path replaces role meta |
| English KPI labels | Persian: شناسه کاربری، نقش، سطح دسترسی |
| Permission preview on non-admin roles | Removed from RECEPTION, SERVICE_MANAGER, TECHNICIAN, PARTS, FINANCE, QC |
| Detail/action cards show filenames | Guided Persian notes; disabled “راهنمای مسیر” button |
| HR self-service gap | Backlog cards (P15) — no pages |
| Manager reference gap | Backlog card for OWNER/SYSTEM_ADMIN — no engine |

---

## 3. Excluded Findings and Why

| Excluded | Reason |
|----------|--------|
| HR self-service module | P15; requires Auth/permission/schema |
| Manager override engine | Requires audit design; impersonation forbidden |
| Impersonation / act-on-behalf | Security; no safe mechanism |
| Phase 7 HR admin link expansion | Beyond UX polish scope |
| Permission / role seed changes | Forbidden |
| DB schema | Forbidden |
| P12 scope | Forbidden |

---

## 4. Files Changed

| File | Change |
|------|--------|
| `public_html/includes/m360-staff-home-helper.php` | Role labels, usage paths, render polish, backlog cards, permission preview scope |
| `public_html/erp-staff-home.php` | Persian identity labels and role display |
| `public_html/assets/css/m360-staff-home.css` | Route meta styling; removed visible filename styles |
| `docs/audit/MOGHARE360_P11_7_1_STAFF_HOME_UX_POLISH_SCOPE_REPORT.md` | Scope gate |
| `docs/audit/MOGHARE360_P11_7_1_STAFF_HOME_UX_POLISH_REPORT.md` | This report |
| `tools/test-p11-7-1-staff-home-ux-polish.php` | UX regression (35 tests) |
| `tools/test-p11-7-1-no-new-scope.php` | Scope security (14 tests) |

---

## 5. UX Changes

- Workbench cards show **Persian title**, **work description**, **status badge**, and **مسیر استفاده** guidance.
- No visible `erp-*.php` strings in card body or disabled buttons.
- Info cards: “این صفحه از مسیر تابلو یا فهرست باز می‌شود…”
- Note cards: “این عملیات از داخل پرونده انتخاب‌شده انجام می‌شود…”
- Backlog section expanded with documented future items (HR, manager reference).
- Identity section renamed to **اطلاعات کاربر**.

---

## 6. Role Label Changes

| role_code | Persian label |
|-----------|---------------|
| OWNER | مالک / مدیر ارشد |
| SYSTEM_ADMIN | مدیر سیستم |
| SERVICE_MANAGER | مدیر سرویس / سالن |
| TECHNICIAN | تکنسین |
| RECEPTION | پذیرش |
| PARTS | انبار / قطعات |
| FINANCE | مالی |
| QC | کنترل کیفیت |

Function: `m360_staff_home_role_label_fa()`. Database/session `role_code` unchanged.

---

## 7. Identity Label Changes

| Before | After |
|--------|-------|
| user_id | شناسه کاربری |
| role_code (value + label) | نقش (Persian label value) |
| تعداد Permission مؤثر | سطح دسترسی |
| شناسه کاربر (section) | اطلاعات کاربر |

---

## 8. Action/Detail Card Handling

| card_type | Clickable? | User-facing behavior |
|-----------|------------|----------------------|
| nav | Yes (if file exists) | “ورود به صفحه” link |
| info | No | Guided note + disabled “راهنمای مسیر” |
| note | No | Operation note only |
| backlog | No | Backlog message only |

Action endpoints (`*-action.php`) remain non-clickable notes. Filenames stored in `data-route` for developer inspection only.

---

## 9. Backlog Cards Added

**All roles (P15 / security backlog):**

- پروفایل شخصی کارمند
- تغییر رمز کارمند
- درخواست مرخصی
- درخواست اضافه‌کاری
- تکمیل مدارک پرسنلی / عکس پروفایل

**OWNER / SYSTEM_ADMIN only:**

- محیط مرجع مدیر/مالک (Audit required — not raw impersonation)

Existing role-specific backlog cards (finance-center, part-usage-list, technician filter) preserved.

---

## 10. Tests Passed

```
php -l public_html/erp-staff-home.php                          OK
php -l public_html/includes/m360-staff-home-helper.php       OK
php tools/test-p11-7-1-staff-home-ux-polish.php               35/35
php tools/test-p11-7-1-no-new-scope.php                     14/14
php tools/test-p11-7-role-workbench-matrix.php                31/31
php tools/test-p11-7-broken-link-fixes.php                     9/9
php tools/test-p11-7-staff-home-persian-encoding.php          16/16
php tools/test-p11-7-scope-security.php                       10/10
php tools/test-p11-4-4-staff-home-authorization.php           52/52
php tools/test-v1-production-signoff.php                      23/23
```

---

## 11. Browser Validation

Files copied to `C:\xampp\htdocs\moghare360\`.

Unauthenticated access returns HTTP 302 to login (expected). Post-login validation (SERVICE_MANAGER / OWNER):

- No visible raw PHP filenames in card body
- Persian role label in identity card
- Persian identity field labels
- Guided info/note cards without filename buttons
- Safe nav cards still clickable
- P11.7-FIX-A Persian encoding preserved for dept/position

---

## 12. Security Confirmation

- No Auth/Login architecture change
- No password/session logic change
- No permission/role seed change
- No DB schema change
- No workflow state change
- No HR self-service build
- No impersonation
- No manager override engine
- No P12 scope
- No secrets committed

---

## 13. Remaining Gaps

- Manager reference operational path still fragmented (product home / direct URLs).
- HR self-service still P15 backlog only.
- `data-route` attribute visible in HTML source (developer-only; not rendered as user text).
- Permission preview still requires admin guard at page level (workbench link removed for non-admins).

---

## 14. Recommended Next Step

**P15 — HR Self-Service** (profile, password, leave) with scoped permissions, or **P11.8 — Manager Reference Workbench** (navigation bridge + audit design, no impersonation).

---

P11.7.1-A polishes Staff Home into a cleaner Persian role-based employee workbench by hiding technical filenames and raw role codes, clarifying guided workflow paths, and documenting HR/self-service and manager reference access as backlog without changing Auth/Login, permissions, roles, database schema, workflow logic, or P12 scope.

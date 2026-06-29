# MOGHARE360 P11.8-A-FIX-A — Staff Home Route Safety UI Report

**Phase:** P11.8-A-FIX-A  
**Status:** Complete  
**Date:** 2026-06-26

---

## 1. Scope Gate Result

Scope report: `docs/audit/MOGHARE360_P11_8_A_FIX_A_ROUTE_SAFETY_UI_SCOPE_REPORT.md`

Confirmed before implementation:

- Misleading green «موجود» on `info` and `note` cards was a rendering bug in `m360_staff_home_route_status()`, not a permission or routing defect.
- SERVICE_MANAGER reports group exposed `erp-jobcard-timeline.php` as clickable `nav` — incorrect for a JobCard-ID-dependent page.
- No new features, permissions, DB, Auth, or workflow changes required.

---

## 2. Cards Reclassified

| Change | Before | After |
|--------|--------|-------|
| All `info` cards (detail pages) | Badge fell through or note used «موجود» | Badge: **راهنمای مسیر** (slate, not green) |
| All `note` cards (action endpoints) | Badge: **موجود** (green) | Badge: **عملیات داخلی** (purple) |
| SERVICE_MANAGER reports — تایم‌لاین JobCard | `nav` — clickable «ورود به صفحه» | `info` — guided, non-clickable |

No other card metadata reclassification was required; bridge items for timeline/settlement were already `info`.

---

## 3. JobCard Timeline Decision

**File:** `erp-jobcard-timeline.php`

**Finding:** Page requires `jobcard_id` GET parameter. Without it, shows an empty form («شناسه JobCard را وارد کنید.»). It is a single-record read-only timeline, not a fleet board/list.

**Decision:** **Guided/disabled** on Staff Home (`card_type = info`).

- Badge: راهنمای مسیر  
- Description: از مسیر پرونده JobCard باز می‌شود  
- No direct «ورود به صفحه» button

---

## 4. Files Changed

| File | Change |
|------|--------|
| `public_html/includes/m360-staff-home-helper.php` | Status labels, constants, SM timeline reclass, render/CSS classes, diag report button |
| `public_html/assets/css/m360-staff-home.css` | `.is-info`, `.m360-staff-status-guided`, `.m360-staff-status-action` |
| `docs/audit/MOGHARE360_P11_8_A_FIX_A_ROUTE_SAFETY_UI_SCOPE_REPORT.md` | Scope gate (new) |
| `docs/audit/MOGHARE360_P11_8_A_FIX_A_ROUTE_SAFETY_UI_REPORT.md` | This report |
| `tools/test-p11-8-a-fix-a-route-safety-ui.php` | Phase test (new) |

**Not changed:** `erp-staff-home.php`, Auth/Login, SQL, permissions, roles, workflow handlers.

---

## 5. UI Changes

1. **`info` cards:** Badge «راهنمای مسیر», dashed border (`is-info`), disabled «راهنمای مسیر» button, description per spec.
2. **`note` cards:** Badge «عملیات داخلی», no green «موجود», no entry button.
3. **`diag` cards:** Button label «مشاهده گزارش» (was «مشاهده تابلو»).
4. **Board/list `nav`/`ref`/`ref_coord`:** Unchanged — remain clickable with green «موجود» / reference badges.

---

## 6. Tests Passed

| Test | Result |
|------|--------|
| `php -l erp-staff-home.php` | OK |
| `php -l m360-staff-home-helper.php` | OK |
| `test-p11-8-a-fix-a-route-safety-ui.php` | 95/95 |
| `test-p11-8-a-manager-reference-bridge.php` | 14/14 |
| `test-p11-8-a-route-safety.php` | 91/91 |
| `test-p11-8-a-no-impersonation-scope.php` | 9/9 |
| `test-p11-7-1-staff-home-ux-polish.php` | 35/35 |
| `test-p11-7-staff-home-persian-encoding.php` | 16/16 |
| `test-v1-production-signoff.php` | 23/23 |

---

## 7. Browser Validation

Files copied to `C:\xampp\htdocs\moghare360\`.

Validate at: `http://localhost:8080/moghare360/erp-staff-home.php`

**Expected for SERVICE_MANAGER / OWNER:**

- Detail cards (جزئیات فنی، جزئیات اجرا) show **راهنمای مسیر** — not green «موجود»
- Action cards (عملیات فنی/اجرا) show **عملیات داخلی** — not clickable
- Reports **تایم‌لاین JobCard** is guided — no «ورود به صفحه»
- Today boards (تابلوی فنی، QC) remain clickable with green «موجود»
- Manager reference bridge sections unchanged and visible
- No raw PHP filenames or role codes in card body

---

## 8. Security Confirmation

- No Auth/Login change  
- No password/session change  
- No permission/role seed change  
- No DB schema change  
- No workflow change  
- No impersonation  
- No manager override engine  
- No P12 scope  
- No secrets committed  

---

## 9. Remaining Gaps

- `erp-jobcard-part-use.php` remains a clickable `nav`/`ref` entry — it also requires JobCard context at runtime; a future phase could reclassify if browser validation shows similar confusion (out of P11.8-A-FIX-A scope — no behavior change requested).
- HR self-service, impersonation, override engine remain backlog cards (P15 / future phases).

---

P11.8-A-FIX-A aligns Staff Home route safety UI by distinguishing clickable board/list routes from guided detail/action routes without changing Auth/Login, permissions, roles, database schema, workflow logic, impersonation, manager override, or P12 scope.

# MOGHARE360 P11.8-B-A — Operational Shell Implementation Report

**Phase:** P11.8-B-A  
**Status:** Complete  
**Date:** 2026-06-26

---

## 1. Scope Gate Result

Scope report: `docs/audit/MOGHARE360_P11_8_B_A_OPERATIONAL_SHELL_SCOPE_REPORT.md`

**PASS** — UI-only operational shell, read-only responsibility strip, Staff Home `runtime_hold` reclassification. No DB, Auth, permission, workflow, or handler changes.

---

## 2. Existing Structures Reused

| Structure | Reuse |
|-----------|-------|
| Staff Home / Product Home / Route Map | Navigation link targets |
| `core_users.full_name` | User name resolution |
| Jobcard actor columns | Responsibility strip fields |
| Domain status label helpers | `m360_*_status_label()` via shell wrapper |
| `m360_*_allowed_actions()` | Next-action label derivation (read-only) |
| P11.8 manager bridge | Preserved; runtime-not-ready routes demoted |

---

## 3. Files Changed

### New

| File | Purpose |
|------|---------|
| `public_html/includes/m360-operational-shell-helper.php` | Shared nav, breadcrumb, responsibility strip |
| `public_html/assets/css/m360-operational-shell.css` | RTL operational shell styling |
| `tools/test-p11-8-b-a-*.php` | Four phase test suites |
| `docs/audit/MOGHARE360_P11_8_B_A_OPERATIONAL_SHELL_SCOPE_REPORT.md` | Scope gate |
| `docs/audit/MOGHARE360_P11_8_B_A_OPERATIONAL_SHELL_REPORT.md` | This report |

### Modified

| File | Change |
|------|--------|
| `public_html/includes/m360-staff-home-helper.php` | `runtime_hold` card type, `M360_STAFF_HOME_RUNTIME_NOT_READY`, apply on workbench merge |
| `public_html/assets/css/m360-staff-home.css` | Runtime-hold badge/card styling |
| 7 board/list pages | Navigation shell only |
| 8 detail/timeline pages | Navigation shell + responsibility strip |

**Not modified:** Auth/Login, SQL, permissions, action handlers, `erp-staff-home.php` (logic unchanged).

---

## 4. Pages Updated

**Boards (nav only):** reception jobcards, intake contracts, technical board, work execution board, QC board, estimate board, final invoice board.

**Details (nav + strip):** reception jobcard detail, technical jobcard detail, work execution detail, estimate detail, final invoice detail, QC detail, settlement detail, jobcard timeline (when `jobcard_id` set).

---

## 5. Navigation Shell Added

Top bar on all updated pages:

- **بازگشت** — parent board (detail) or Staff Home (boards)
- **میز کار من** → `erp-staff-home.php`
- **صفحه اصلی محصول** → `erp-product-home.php`
- **نقشه مسیرها** → `erp-route-map.php`
- **مسیر جاری** — section title + breadcrumb (میز کار › parent › current)

---

## 6. Responsibility / Status Strip Added

Read-only strip shows (when data exists):

- Document type + JobCard ID
- **وضعیت فعلی** (domain status label)
- **درخواست‌کننده** (customer name)
- **ایجادکننده / مسئول فعلی / ارجاع‌شده / تأییدکننده / آخرین تغییر** — from existing user_id columns via `core_users`
- **اقدام بعدی** — from allowed actions or status heuristics (no new workflow)
- **تاریخچه / رویدادها** link → timeline (hidden on timeline page itself)

Missing values: **ثبت نشده** or **نامشخص** — never invented.

---

## 7. Runtime Routes Reclassified

| Route | Status | Clickable |
|-------|--------|-----------|
| `erp-jobcard-part-use.php` | نیازمند بازبینی عملیاتی | No — `runtime_hold` |
| `erp-payment-tracking.php` | نیازمند بررسی عملیاتی | No — `runtime_hold` |

Applied across PARTS/TECHNICIAN workbench, FINANCE today, OWNER bridge, SERVICE_MANAGER coordination bridge.

Safe empty boards (e.g. `erp-technical-board.php`) remain **موجود** and clickable.

---

## 8. Pages Deferred

| Page | Reason |
|------|--------|
| `erp-reception-online-requests.php` | Not in minimum required list — add in P11.8-B-B |
| `erp-reception-online-request-detail.php` | Same |
| `erp-stock-board.php`, `erp-part-reserve.php` | Nav shell deferred |
| `erp-delivery-control.php` | Mission 30 standalone — not m360 shell yet |
| `erp-jobcard-part-use.php` | Destination fix deferred — Staff Home only demoted |
| `erp-payment-tracking.php` | Load error fix deferred — Staff Home only demoted |

---

## 9. Tests Passed

| Test | Result |
|------|--------|
| PHP lint (shell, staff helper, staff home) | OK |
| `test-p11-8-b-a-operational-shell.php` | 27/27 |
| `test-p11-8-b-a-responsibility-strip.php` | 11/11 |
| `test-p11-8-b-a-runtime-route-readiness.php` | 20/20 |
| `test-p11-8-b-a-scope-security.php` | 13/13 |
| P11.8-A-FIX-A regression | 95/95 |
| P11.8-A bridge + route safety + no impersonation | 79+14+9 |
| P11.7.1 + encoding + V1 signoff | 35+16+23 |

---

## 10. Browser Validation

Copy updated files to `C:\xampp\htdocs\moghare360\` and validate:

- Staff Home: part-use and payment cards show orange runtime-hold badge, disabled button
- Boards: dark nav bar + breadcrumb visible
- Detail pages: responsibility strip above existing content
- No raw PHP filenames in strip/nav body
- No workflow button changes

---

## 11. Security Confirmation

- No Auth/Login change  
- No password/session change  
- No permission/role seed change  
- No DB schema change  
- No SQL migration  
- No workflow change  
- No action handler change  
- No impersonation  
- No manager override engine  
- No HR self-service build  
- No P12 scope  
- No secrets committed  

---

## 12. Remaining Gaps

- Fix `erp-jobcard-part-use.php` productization (separate phase — not P11.8-B-A)
- Fix `erp-payment-tracking.php` load error (Phase 5 pricing engine — separate phase)
- Extend shell to remaining P1 list pages and parts boards
- Optional: reuse P8 `current_stage_label_fa` on all detail strips uniformly

---

## 13. Recommended Next Step

**P11.8-B-B:** Extend operational shell to online requests + parts boards; after destination pages are fixed, remove `runtime_hold` from part-use/payment when browser-validated loadable.

---

P11.8-B-A adds a reusable UI-only operational shell and read-only document responsibility/status display while reclassifying runtime-not-ready Staff Home links, without changing Auth/Login, permissions, roles, database schema, SQL, workflow logic, action handlers, impersonation, manager override, HR self-service, or P12 scope.

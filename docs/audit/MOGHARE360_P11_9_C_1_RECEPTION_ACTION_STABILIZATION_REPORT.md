# MOGHARE360 P11.9-C-1 — Reception Action Stabilization Report

**Phase:** P11.9-C-1  
**Status:** COMPLETE  
**Date:** 2026-07-04  
**Scope gate:** `MOGHARE360_P11_9_C_1_RECEPTION_ACTION_STABILIZATION_SCOPE_REPORT.md`

---

## 1. Scope Gate Result

**PASS** — Online reception action stabilization only. No Auth/DB/OTP architecture changes.

---

## 2. Files Changed

| File | Change |
|------|--------|
| `public_html/includes/m360-reception-helper.php` | Single CSRF HTML helper, Persian error pages, convert gate detection, filter labels/counts |
| `public_html/erp-reception-online-request-detail.php` | One `$csrfInputHtml` for all forms; convert gate panel |
| `public_html/erp-reception-online-request-accept.php` | CSRF via helper; Persian CSRF page; convert gate redirect |
| `public_html/erp-reception-online-requests.php` | Active filter label, counts, clearer empty state |
| Scope + implementation reports + 3 tests | Created |

**Not present (no change):** `reject.php`, `review.php`, `convert.php` — all actions remain in `accept.php`.

---

## 3. CSRF Root Cause

Detail page rendered **four** forms, each calling `erp_csrf_input('online_request_reception')`. Each call invoked `erp_csrf_create_token()`, **overwriting** the session token. Only the **last** form’s hidden input matched the session — earlier actions failed with plain text `ERP security validation failed.`

---

## 4. CSRF Fix Applied

- Added `m360_reception_csrf_input_html()` — generates token **once** via output buffering
- Detail page: `$csrfInputHtml = m360_reception_csrf_input_html()` reused in all four forms
- Accept handler: `m360_reception_csrf_is_valid()` + `m360_reception_render_action_error_page('csrf', …)` — **no** `erp_csrf_require_valid()` raw exit

---

## 5. Action Behavior After Fix

| Action | Method | CSRF | Success | Failure |
|--------|--------|------|---------|---------|
| علامت‌گذاری در حال بررسی | POST | Shared token | Persian flash redirect | CSRF page or flash |
| پذیرش درخواست | POST | Shared token | Persian flash redirect | CSRF page or flash |
| رد درخواست | POST | Shared token | Persian flash redirect | CSRF page or flash |
| تبدیل به کارت کار | POST | Shared token | Success flash + jobcard id | Gate panel or flash |

GET on `accept.php` → redirect to list (unchanged).

---

## 6. Filter UX Result

- Explicit **فیلتر فعال** line with current Persian label
- Per-status **count badges** via lightweight `GROUP BY` query
- Stronger **active** pill styling + `aria-current="page"`
- Empty state names active filter
- Labels: همه، جدید، در انتظار بررسی، در حال بررسی، پذیرفته‌شده، تبدیل به کارت کار، رد شده
- **No new status codes** — existing DB model unchanged

**Note:** If all records share one status (test/MIRROR pollution), counts clarify why filters look similar.

---

## 7. Convert To JobCard Gate Result

- OTP prerequisite **not bypassed** — `m360_online_req_payload_otp_verified()` unchanged
- On OTP/vehicle/customer prerequisite failure → redirect `?gate=convert` with Persian panel:
  - **Title:** تبدیل به کارت کار نیازمند تکمیل پیش‌نیاز است
  - **Text:** وضعیت تأیید مشتری و اطلاعات پذیرش…
- Successful conversion still uses existing `m360_reception_convert_to_jobcard()` pipeline

---

## 8. Persian Error Handling

| Condition | UX |
|-----------|-----|
| Invalid/expired CSRF | Standalone Persian page — اعتبار امنیتی درخواست نامعتبر یا منقضی شده است |
| Convert prerequisite | Detail gate panel — تبدیل به کارت کار نیازمند تکمیل پیش‌نیاز است |
| Other failures | Existing Persian flash messages on detail |

**Removed from normal flow:** raw `ERP security validation failed.`

---

## 9. Tests Passed

| Test | Result |
|------|--------|
| `php -l` reception PHP files | PASS |
| `test-p11-9-c-1-reception-csrf-actions.php` | PASS — 20/20 |
| `test-p11-9-c-1-reception-filter-ux.php` | PASS — 15/15 |
| `test-p11-9-c-1-reception-scope-security.php` | PASS — 6/6 |
| `test-v1-production-signoff.php` | PASS — 23/23 |

---

## 10. Browser Validation

Deploy to XAMPP and login as `demo.reception`:

1. Open `erp-reception-online-requests.php?status=ALL` — verify active filter + counts
2. Open a test request detail
3. Submit **علامت‌گذاری در حال بررسی** — expect Persian flash, no English security error
4. Submit **پذیرش درخواست** — same
5. Submit **رد درخواست** on safe test row — same
6. Submit **تبدیل به کارت کار** — success **or** Persian gate panel (OTP not bypassed)

---

## 11. What Was Not Changed

- Auth/Login architecture, `staff-auth.php`, `access-control.php`
- Permissions, roles, departments, positions
- DB schema, SQL migrations
- Workflow architecture beyond accept redirects
- OTP core / bypass / fake OTP
- Shared `includes/erp-csrf.php`
- Private files, secrets, P12 scope
- Full reception workbench, offline admission, HR, defects, photos, contracts UI

---

## 12. Remaining Backlog

- Unified Reception Workbench (P11.9-C-2+)
- Offline walk-in admission wizard
- Complete intake file editor on detail
- Defect/category selection, photo integration at intake
- HR shortcuts (P15)
- Customer response panel
- Demo/MIRROR row filtering policy (optional)
- Operational shell on P1 list (green MOGHAREH360)

---

## 13. Recommended Next Phase

**P11.9-C-2** — Reception workbench shell (card dashboard linking existing routes) after browser validation of C-1 actions.

---

## Security Confirmation

- No Auth/Login architecture change
- No login behavior change
- No staff-auth.php / access-control.php change
- No permission/role/department/position change
- No DB schema / SQL migration change
- No workflow architecture change
- No OTP bypass or fake OTP
- No private file change
- No secrets committed
- No P12 scope

---

P11.9-C-1 stabilizes online reception request actions by fixing the multi-form CSRF issue, improving Persian error handling, clarifying filter UX, and validating the controlled convert-to-JobCard gate without changing Auth/Login architecture, permissions, roles, departments, positions, database, SQL, workflow architecture, OTP behavior, users, private files, secrets, or P12 scope.

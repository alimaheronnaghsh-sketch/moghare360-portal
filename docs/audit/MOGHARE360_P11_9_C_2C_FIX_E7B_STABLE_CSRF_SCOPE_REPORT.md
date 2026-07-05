# MOGHARE360 P11.9-C-2C-FIX-E7B — Stable CSRF Scope Report

## 1. E7A Cause Confirmed

`erp_csrf_input()` → `erp_csrf_create_token()` **overwrites** `$_SESSION['erp_csrf_tokens'][$scope]` on every call. Each new GET of a reception intake page regenerates the session token while older tabs retain stale HTML tokens → CSRF validation failure (CAUSE_D).

## 2. Reception Intake CSRF Render Call Sites

| Location | Mechanism |
|----------|-----------|
| `erp-reception-intake-file.php` line 34 | `$csrfInputHtml = m360_reception_csrf_input_html()` once per page |
| All wizard step forms | Reuse `$csrfInputHtml` |
| Documents step (3 forms) | Same cached HTML |
| `erp-reception-online-request-detail.php` | Same helper (C-1 fix pattern) |

## 3. Global `erp_csrf_create_token()` Usage Outside Reception

Used by: login APIs (`owner_login`, `staff_login`), v1 signoff, and many module `erp_csrf_input()` wrappers (HR, CRM, FI, etc.). **Must not change create behavior globally** — those flows may rely on forced rotation per render.

## 4. Minimal Safe Fix

Add **`erp_csrf_get_or_create_token($scope)`** in `includes/erp-csrf.php`:
- Returns existing non-empty session token for scope
- Creates only when missing
- Leaves `erp_csrf_create_token()` unchanged for forced rotation

Update **`m360_reception_csrf_input_html()`** to use get-or-create via `m360_reception_csrf_token_value()`.

## 5. Global CSRF Architecture

No breaking change. Additive helper only. Validation unchanged (`erp_csrf_validate_token`).

## 6. Files to Modify

| File | Change |
|------|--------|
| `includes/erp-csrf.php` | Add `erp_csrf_get_or_create_token()` |
| `public_html/includes/m360-reception-helper.php` | Stable token render + improved intake CSRF recovery URL |
| `public_html/erp-reception-intake-save.php` | Pass recovered `active_step` on CSRF fail |
| `public_html/includes/m360-reception-workbench-helper.php` | `active_step`/`return_step` hidden fields; contract prepare section |
| `public_html/assets/css/moghare360-v1-luxury-ui.css` | Optional input contrast |
| `tools/test-p11-9-c-2c-fix-e7b-*.php` | Six tests |
| Audit reports | Scope + final |

## 7. OTP / Auth / DB Confirmation

No OTP, Auth/Login, staff-auth, access-control, DB schema, or permission changes required.

**Scope gate: PROCEED**

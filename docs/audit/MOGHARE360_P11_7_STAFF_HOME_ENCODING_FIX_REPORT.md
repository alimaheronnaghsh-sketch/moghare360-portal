# MOGHARE360 P11.7-FIX-A — Staff Home Persian Identity Encoding Fix

**Phase:** P11.7-FIX-A  
**Date:** 2026-06-26  
**Status:** Complete

---

## 1. Root Cause

Static Persian UI labels in `erp-staff-home.php` render correctly because they are UTF-8 in source files. Department and position values come from SQL Server via ODBC on Windows and were passed directly to `htmlspecialchars()` without byte-sequence normalization. When ODBC returns Windows-1256 or UTF-16LE bytes that are not valid UTF-8, PHP substitutes `?` characters — observed as `????? ??????`.

Access Management (P11.4.1) already fixed this pattern with `m360_access_text_from_odbc()`; Staff Home did not apply the same normalization after P11.7 workbench consolidation.

---

## 2. Files Changed

| File | Change |
|------|--------|
| `public_html/includes/m360-staff-home-helper.php` | Added `m360_staff_home_text_from_odbc()`, `m360_staff_home_h_db()`; normalize identity fields in `m360_staff_home_load_context()` |
| `public_html/erp-staff-home.php` | Identity KPI values use `m360_staff_home_h_db()` for DB-derived text |
| `tools/test-p11-7-staff-home-persian-encoding.php` | New encoding/regression test |
| `docs/audit/MOGHARE360_P11_7_STAFF_HOME_ENCODING_FIX_REPORT.md` | This report |

No CSS, Auth, permission, role seed, schema, or workflow files modified.

---

## 3. Encoding Helper Added/Reused

**Added locally** (minimal duplicate of P11.4.1 logic; no `require` of access-management-helper):

- `m360_staff_home_text_from_odbc($value)` — preserves valid UTF-8; converts UTF-16LE (BOM-stripped) and Windows-1256 / CP1256 / ISO-8859-6; idempotent; no double-encoding
- `m360_staff_home_h_db($value)` — normalize then `htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`

Existing `m360_staff_home_h()` unchanged for static UI and English system codes.

---

## 4. Fields Fixed

| Field | Source | Normalization |
|-------|--------|---------------|
| `full_name` / display name | DB (`core_users`) | `text_from_odbc` in load + `h_db` on render |
| `department_name` / واحد | DB (`dept_name`) | same |
| `position_name` / سمت | DB (`position_name`) | same |

**Unchanged (escape only):** `role_code`, `username`, `user_id`, static Persian labels, workbench card titles.

---

## 5. Tests Passed

```
php -l public_html/includes/m360-staff-home-helper.php     OK
php -l public_html/erp-staff-home.php                      OK
php tools/test-p11-7-staff-home-persian-encoding.php       16/16 PASS
php tools/test-p11-7-role-workbench-matrix.php             31/31 PASS
php tools/test-p11-7-scope-security.php                   10/10 PASS
php tools/test-p11-4-4-staff-home-authorization.php        52/52 PASS
php tools/test-v1-production-signoff.php                   23/23 PASS
```

---

## 6. Browser Validation Result

Fix copied to `C:\xampp\htdocs\moghare360\`. Unauthenticated request to `http://localhost:8080/moghare360/erp-staff-home.php` returns **HTTP 302** to login (expected) with no PHP fatal/warning.

**Post-login check (manual):** After staff login, identity card should show readable Persian for واحد and سمت when DB holds CP1256/UTF-16 Persian text; no `????` placeholders; workbench groups and buttons unchanged.

---

## 7. Security Confirmation

- No Auth/Login change
- No password/session logic change
- No permission/role seed change
- No DB schema change
- No workflow state change
- No P12 scope
- No secrets committed

---

## 8. Remaining Gaps

- Characters outside CP1256 / ISO-8859-6 (e.g. Persian Yeh U+06CC) may still display incorrectly if stored only in legacy single-byte encodings without UTF-16 source; full fix would require DB collation/Unicode storage (out of scope).
- Browser identity-card Persian display requires an authenticated session with profile rows containing Persian dept/position data.

---

P11.7-FIX-A fixes Persian staff identity rendering in Staff Home by normalizing SQL Server / ODBC text before safe UTF-8 output, without changing Auth/Login, permissions, roles, database schema, workflow logic, or P12 scope.

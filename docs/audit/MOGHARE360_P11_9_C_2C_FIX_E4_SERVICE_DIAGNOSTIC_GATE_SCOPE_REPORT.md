# MOGHARE360 P11.9-C-2C-FIX-E4 — Service Diagnostic Gate Scope Report

**Phase:** P11.9-C-2C-FIX-E4  
**Date:** 2026-07-05  
**Status:** Scope gate PASSED — no schema change

---

## 1. Service Step Fields Rendered (Pre-Fix E2 Wizard)

- `service_primary` (select only)
- `service_path_clear` (select 1/0)
- `service_path_note` (textarea)
- **Missing:** `diagnostic_subcategories[]` checkboxes for route `diag`

---

## 2. POST Field Names (Pre-Fix)

| Field | Used by save |
|-------|----------------|
| `service_primary` | Yes |
| `service_diag_sub[]` | Expected when diag — **not posted (UI missing)** |
| `service_path_clear` | Yes |
| `service_path_note` | Yes |

---

## 3. Payload Keys Written by `save_service_classification`

Top-level: `reception_service_primary`, `reception_service_diag_sub`, `fault_service_path_clear`, `service_path_clear`, `service_path_note`

Nested: `reception_intake.service_classification.main`, `diagnostic_categories`, `service_path_clear` (bool)

---

## 4. Wizard/Gate Validation Keys Read

| Concern | Reader |
|---------|--------|
| Route | `formValues.service_primary`, nested `main` |
| Diag subs | `reception_service_diag_sub`, nested `diagnostic_categories` |
| Path clear | `fault_service_path_clear`, `service_path_clear`, nested bool |
| Wizard complete (E2) | route + path 0/1 only — **did not require diag subs or path=true** |
| Gate | `m360_rw_parse_service_classification()` → `registered`, `fault_path_clear` |

---

## 5. Why Browser Shows Errors

| Message | Cause |
|---------|-------|
| حداقل یک زیردسته عیب‌یابی انتخاب کنید | Save validates `service_diag_sub[]` for route `diag`, but E2 wizard UI removed checkbox inputs |
| مسیر انتخابی قطع است (owner paraphrase) | Gate/flash when `service_path_clear=false` or wizard allowed step complete with path=0; user could save route without clear path and remain blocked |

---

## 6. Selected Subcategory: Posted? Saved? Read?

**Not posted** — UI regression in E2 wizard (root cause). Save never received subs → validation failure.

---

## 7. `service_path_clear` Value Mismatch

Stored as `'1'/'0'` top-level strings and nested boolean. Gate normalizes via `in_array(..., ['1','yes','true'])`. Mismatch was not primary bug; wizard treated `path=0` as step-complete.

---

## 8. Files to Modify

- `public_html/includes/m360-reception-workbench-helper.php`
- `public_html/erp-reception-intake-file.php`

**Not modified:** OTP files, Auth, schema, staff-auth, access-control

---

## 9. Confirmations

No OTP/Auth/DB/schema/permission change required.

**Scope gate:** PROCEED

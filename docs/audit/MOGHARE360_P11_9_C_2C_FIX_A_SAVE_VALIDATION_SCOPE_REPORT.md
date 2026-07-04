# MOGHARE360 P11.9-C-2C-FIX-A — Save Validation Scope Report

**Phase:** Intake save validation type safety + controlled error handling  
**Date:** 2026-07-04  
**Verdict:** **PROCEED — bug fix only, no schema/Auth change**

---

## 1. Which form/action caused the TypeError?

**Action:** `save_documents_and_cost`  
**Endpoint:** `erp-reception-intake-save.php`  
**Function chain:** `m360_rw_intake_process_save` → `m360_rw_intake_apply_action` → `m360_rw_intake_validate_text`

---

## 2. Which field produced integer value 10101010?

**Field:** `cost_agreement` (توافق هزینه)  
**Operator entered:** numeric cost value `10101010` in the documents/cost form.

**Root cause (not POST type alone):** Validation loop used an inverted associative array:

```php
foreach ([$costAgreement => 'توافق هزینه', ...] as $val => $lbl)
```

PHP casts numeric string keys to **integer** array keys. The foreach variable `$val` became **int 10101010** (the array key), not the field text. That int was passed to `m360_rw_intake_validate_text(string $value)` → **TypeError**.

---

## 3. Why did CLI tests pass but browser POST failed?

| Reason | Detail |
|--------|--------|
| Test payload shape | CLI tests passed string `'10101010'` directly to `validate_text` or used `trim((string)$post[...])` before the broken foreach |
| Broken foreach not covered | No test simulated numeric `cost_agreement` through `apply_action` with the inverted key=>label array |
| Strict typing | Browser path hit the foreach bug; unit tests bypassed it |

---

## 4. Validation functions requiring hardening

| Function | Fix |
|----------|-----|
| `m360_rw_intake_validate_text` | Accept `mixed`; normalize via `m360_rw_intake_normalize_scalar_text` |
| `m360_rw_intake_validate_mileage` | Already mixed-aware |
| `m360_rw_intake_validate_fuel` | Changed to `mixed` + normalize |
| `m360_rw_intake_apply_action` | **Remove inverted foreach**; use `post_scalar` + `validate_text_fields` |

---

## 5. Action types that may pass int/float/bool/null

| Action | Fields at risk |
|--------|----------------|
| `save_documents_and_cost` | `cost_agreement` (primary failure), notes |
| `save_vehicle_identity` | `mileage` (number input) |
| `save_condition_notes` | textarea (usually string) |
| `save_temporary_reception` | notes |
| `save_reception_confirmation` | confirmation_note |

All now normalized through `m360_rw_intake_post_scalar` / `normalize_scalar_text`.

---

## 6. Endpoint error handling

- `erp-reception-intake-save.php`: `try/catch (Throwable)` → redirect with `M360_RW_INTAKE_SAVE_GENERIC_ERROR_FA`
- `m360_rw_intake_process_save`: inner wrapper catches Throwable → same Persian message
- No stack trace, no internal paths shown to user

---

## 7. Confirm no DB/Auth/permission/workflow/OTP/schema change

**Confirmed.** Validation and error-handling fix only.

---

## Scope gate decision

**GO** — Fix validation loop + scalar normalization + controlled catch.

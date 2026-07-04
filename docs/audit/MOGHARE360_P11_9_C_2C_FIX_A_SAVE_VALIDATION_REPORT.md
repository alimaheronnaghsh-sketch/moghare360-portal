# MOGHARE360 P11.9-C-2C-FIX-A — Save Validation Report

**Phase:** P11.9-C-2C-FIX-A  
**Date:** 2026-07-04  
**Browser Validation:** **PENDING OPERATOR**  
**Commit Eligibility:** **NOT_ELIGIBLE_UNTIL_OPERATOR_BROWSER_PASS**

---

## 1. Scope Gate Result

**GO** — Bug fix only. See `docs/audit/MOGHARE360_P11_9_C_2C_FIX_A_SAVE_VALIDATION_SCOPE_REPORT.md`.

---

## 2. Browser Fatal Root Cause

**TypeError** in `m360_rw_intake_validate_text()` when saving documents/cost with numeric `cost_agreement` = `10101010`.

**Primary bug:** Inverted foreach in `save_documents_and_cost`:

```php
foreach ([$costAgreement => 'توافق هزینه', ...] as $val => $lbl)
```

Numeric cost became an **integer array key**; `$val` passed to validator was **int 10101010**, not the field value string.

---

## 3. Validation Type Safety Fix

**File:** `public_html/includes/m360-reception-workbench-helper.php`

- `m360_rw_intake_normalize_scalar_text(mixed)` — string/int/float/bool/null → string; array/object → controlled error
- `m360_rw_intake_validate_text(mixed)` — uses normalizer
- `m360_rw_intake_validate_fuel(mixed)` — uses normalizer
- `save_documents_and_cost` — uses `post_scalar` + `validate_text_fields` (broken foreach removed)
- `save_reception_confirmation` — uses `post_scalar` for note

---

## 4. Endpoint Controlled Error Handling

**File:** `public_html/erp-reception-intake-save.php`

- `try/catch (Throwable)` around save
- Redirect to intake with Persian message:  
  `خطا در ذخیره اطلاعات پذیرش. مقدار واردشده معتبر نیست یا نیاز به بررسی دارد.`
- No stack trace or internal paths in browser

**Helper:** `m360_rw_intake_process_save` wraps inner logic with same catch.

---

## 5. Browser POST Coverage Added

**File:** `tools/test-p11-9-c-2c-fix-a-save-validation.php`

Covers scalar types, array/object rejection, numeric cost/mileage through `apply_action`, endpoint catch, regression of C-2C tests.

---

## 6. Tests Passed

| Test | Result |
|------|--------|
| `test-p11-9-c-2c-fix-a-save-validation.php` | PASS |
| `test-p11-9-c-2c-intake-write-runtime.php` | PASS |
| `test-p11-9-c-2c-intake-write-security.php` | PASS |
| `test-p11-9-c-2c-intake-write-process.php` | PASS |
| `test-p11-9-c-2c-intake-write-payload.php` | PASS |
| `test-p11-9-c-2c-scope-security.php` | PASS |
| `test-v1-production-signoff.php` | PASS |
| PHP lint (helper, intake, save) | PASS |

---

## 7. Browser Validation Status

**PENDING OPERATOR**

Retest on XAMPP after copy:

1. Save documents/cost with `cost_agreement` = `10101010`
2. Save vehicle mileage as number
3. Save classification, condition, confirmation
4. GET `erp-reception-intake-save.php` → redirect, no fatal

---

## 8. What Was Not Changed

- No Auth/Login architecture change
- No login behavior change
- No `staff-auth.php` change
- No `access-control.php` change
- No permission/role change
- No DB schema change
- No SQL migration
- No workflow architecture change
- No OTP bypass
- No fake OTP
- No automatic JobCard creation
- No private file change
- No secrets committed
- No P12 scope
- No C-2D scope

---

## 9. Commit Eligibility

**NOT_ELIGIBLE_UNTIL_OPERATOR_BROWSER_PASS**

---

P11.9-C-2C-FIX-A fixes intake save validation type safety and controlled browser error handling so numeric scalar values cannot trigger raw TypeError/Fatal output during reception intake saves, while preserving OTP truthfulness, C-2C scope, and the no-automatic-JobCard boundary without changing Auth/Login architecture, permissions, roles, database schema, SQL migrations, workflow architecture, OTP behavior, private files, secrets, or P12 scope.

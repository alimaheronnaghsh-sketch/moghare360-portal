# MOGHARE360 P11.9-C-2C-FIX-E6A — SQL Truth Wizard Scope Report

## 1. SQL Truth for Request 18

| Area | Status |
|------|--------|
| OTP | `otp_verified = 1` — complete |
| Vehicle | Top-level + `reception_intake.vehicle` — plate, vin, brand, model, mileage, fuel — complete |
| Photos | `reception_intake.photos` — 6/6, `is_complete=true` — complete |
| Documents | `diagnostic_status`, `cost_agreement` exist; `contract_status` NULL — **incomplete** |
| Signature/Lock | `customer_signature` NULL, `intake_lock` NULL — **incomplete** |

## 2. Wizard Function Returning Vehicle as Incomplete

**Before fix:** `m360_rw_intake_wizard_step_is_complete('vehicle')` used `$formValues` only (via `m360_rw_intake_form_values`), which could miss canonical nested vehicle when top-level keys differed or when `resolve_active_step` forced `furthest` to an early step without re-reading SQL truth consistently.

**Central issue:** Step completion was fragmented across `wizard_step_is_complete`, `section_is_complete` (with `section_status` shortcut), and documents requiring `diagnostic_pdf` file path only — not aligned with SQL/payload truth.

## 3. Stale section_status

`m360_rw_intake_section_is_complete()` previously returned `true` immediately if `section_status[section].completed=true`, without verifying canonical data. Conversely, vehicle section only checked `plate` non-empty — inconsistent with wizard vehicle step requiring five fields.

**Fix:** Canonical step-state wins; `section_status` no longer drives routing.

## 4. Wrong Step Key / Mapping

No wrong step key mapping found. Step 2 = `vehicle` in `m360_rw_intake_stepper_definition()`.

## 5. Final/Signature Silent Backjump

`m360_rw_intake_resolve_active_step()` returned `furthest` when requested step index ≠ furthest index, without blocking backward jumps to canonically complete early steps. Any incorrect `furthest=vehicle` caused silent backjump.

## 6. contract_status=NULL Mapping

Documents step previously required `contract.run_at` only — not `contract_status` field from SQL. NULL `contract_status` correctly marks documents incomplete, but missing `diagnostic_status` acceptance could also mark documents incomplete at wrong priority. **Must not** map to vehicle/photos.

## 7. Files Modified

- `public_html/includes/m360-reception-workbench-helper.php` — step-state, routing, section truth
- `public_html/erp-reception-intake-file.php` — blocker messages, signature checklist
- `tools/diagnose-p11-9-c-2c-wizard-state.php`
- `tools/test-p11-9-c-2c-fix-e6a-*.php` (6 files)
- Audit reports

## 8. DB / Auth / OTP Confirmation

No DB schema change. No OTP/Auth/permission changes required.

**Scope gate: PROCEED**

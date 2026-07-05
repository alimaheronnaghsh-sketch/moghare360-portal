# MOGHARE360 P11.9-C-2C-FIX-E6A — SQL Truth Wizard Routing Report

## 1. Scope Gate Result

**PROCEED** — Wizard routing fix uses existing `request_payload_json` and row fields only. No DB schema, OTP, Auth, or permission changes.

## 2. SQL Truth Findings (Request 18)

- OTP: `otp_verified=1` — complete
- Vehicle: canonical `reception_intake.vehicle` + top-level fields — complete
- Photos: `reception_intake.photos` 6/6 `is_complete=true` — complete
- Documents: `diagnostic_status` + `cost_agreement` present; `contract_status` NULL — **incomplete**
- Signature: no `customer_signature`, no `intake_lock` — **incomplete**

Real blockers are documents (contract) and signature — not vehicle or photos.

## 3. Wrong Backjump Root Cause

Step completion was split across `wizard_step_is_complete`, `section_is_complete` (with `section_status` shortcut), and documents logic that required `diagnostic_pdf` file path only. `resolve_active_step()` returned `furthest` from the first incomplete step without preventing backward jumps when canonical data proved earlier steps complete.

Any miscomputed `furthest=vehicle` caused silent redirect to step 2.

## 4. Canonical Step-State Function

Added `m360_rw_intake_get_wizard_step_state($payload, $requestRow)` returning per-step `complete`, `reason`, `missing_fields`, plus `first_incomplete`, `resolved_active_step`, and `blocker_message`.

## 5. Vehicle Complete Handling

`m360_rw_intake_vehicle_canonical()` reads `reception_intake.vehicle` with top-level fallbacks. `m360_rw_intake_vehicle_step_complete()` requires plate, brand, model, mileage, fuel_level. Stale `section_status` no longer drives routing.

## 6. Photo Complete Handling

Photos step uses existing E5 `m360_rw_intake_photos_complete()` — canonical 6/6 truth preserved.

## 7. Documents Contract Routing

`m360_rw_intake_documents_step_state()` marks complete when diagnostic (status or PDF), cost agreement, and contract (run_at / approved / contract_status) exist. NULL `contract_status` → documents incomplete → routes to `documents` with message:

> وضعیت قرارداد ثبت نشده است؛ ابتدا قرارداد پذیرش را اجرا/تأیید کنید.

## 8. Signature Blocking Checklist

Signature step shows checklist (قرارداد / امضای مشتری / تأیید نهایی پذیرشگر). `first_incomplete=signature` when documents complete but not locked.

## 9. Diagnostic Tool

`tools/diagnose-p11-9-c-2c-wizard-state.php --online_request_id=18` prints step completion table without secrets or raw payload.

## 10. Regression Freeze

OTP helper untouched. Service gate, E5 photos, post-signature lock guard, no automatic JobCard — all preserved.

## 11. Tests Passed

All E6A tests (6 files) + E2 + E4 + E5 loop + V1 signoff — **PASS**.

## 12. Browser Validation Status

**PENDING OWNER UAT** — XAMPP copy applied. Validate:

- http://localhost:8080/moghare360/erp-reception-intake-file.php?online_request_id=18
- Must NOT jump to vehicle (step 2) or photos
- Must show documents (contract blocker) or signature with checklist
- Repeat for request 20

## 13. What Was Not Changed

OTP, service diagnostic gate logic, E5 canonical photos, Auth/Login, permissions, DB schema, automatic JobCard.

## 14. Commit Eligibility

**NOT_ELIGIBLE_UNTIL_BROWSER_NO_BACKJUMP_AND_DOCUMENTS_SIGNATURE_PASS**

---

P11.9-C-2C-FIX-E6A fixes the wizard backjump by aligning step routing with SQL/payload truth: OTP, vehicle, and photos are treated complete when canonical data proves completion, stale section status cannot override canonical state, documents/signature blockers are shown explicitly instead of silently redirecting to vehicle or photos, and frozen OTP, service gate, canonical six-photo completion, post-signature lock, secret safety, Auth/Login, permissions, database schema, workflow boundaries, and no-automatic-JobCard are preserved.

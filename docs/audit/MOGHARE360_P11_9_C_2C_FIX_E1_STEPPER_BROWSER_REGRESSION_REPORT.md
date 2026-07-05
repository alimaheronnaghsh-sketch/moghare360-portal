# MOGHARE360 P11.9-C-2C-FIX-E1 — Stepper Browser Regression Final Report

**Phase:** P11.9-C-2C-FIX-E1  
**Date:** 2026-07-04  
**Commit Eligibility:** `NOT_ELIGIBLE_UNTIL_BROWSER_STEPPER_AND_OTP_PASS`

---

## 1. Scope Gate Result

Scope report: `docs/audit/MOGHARE360_P11_9_C_2C_FIX_E1_STEPPER_BROWSER_REGRESSION_SCOPE_REPORT.md`

**PASS** — Browser UX regression fix only. No OTP architecture, Auth, DB, or permission changes.

---

## 2. OTP Send Regression Root Cause

1. **UI mislabeling:** Payload `otp.status=failed` displayed as «ارسال ناموفق» even after successful send redirect (`otp_sent=1&ok=1`).
2. **Incomplete mobile resolution:** Send/verify used only `request.mobile`, not corrected payload mobile.
3. **Send form placement:** OTP send was nested inside `show_form` edit-lock path; moved to dedicated OTP operational block with explicit `return_step_hidden('otp')`.

Canonical `m360_otp_send()` was not removed; provider wiring and D1A auth header remain intact.

---

## 3. OTP Send Fix

- `m360_rw_intake_resolve_mobile_for_otp()` — unified mobile source.
- `m360_rw_intake_otp_ui_status()` — honors `otp_sent` + `ok` flash hints.
- `m360_rw_intake_process_send_otp()` — loads helper first, checks persist result, maps provider categories (config_missing, invalid_token, provider_fail, sent).
- OTP step UI: single block with mobile, send, verify — no Edit click required.
- Success redirect: `active_step=otp&otp_sent=1&ok=1#step-otp` with message «پیامک OTP ارسال شد.»

**Debug category (no secrets):** `category` field in send result — `sent|config_missing|invalid_token|provider_fail|persist_fail|invalid_mobile`.

---

## 4. True Single Active Step Rendering

**Server-side guards:** Each step wrapped in `if ($activeStep === 'step'):` — full operational forms render only for the active step.

**Non-active steps:** `m360_rw_intake_render_step_compact_summary()` — title, status, key summary, «رفتن به این مرحله» link.

**`m360_rw_intake_stepper_section_ui_state()`:** Non-active steps force `show_form=false`.

No reliance on CSS-only `display:none` for hiding full forms.

---

## 5. Step Routing

Unchanged redirect map from FIX-E, verified:
- Send OTP success/fail → `otp`
- Verify success → `vehicle`; fail → `otp`
- Vehicle → `condition`; condition → `service`; service → `referral`; referral → `photos`
- Photos → stays `photos` until 6/6, then `documents`
- Documents complete → `final`

---

## 6. No-Tomar UI Cleanup

- Inactive steps: compact single-row cards (not full forms).
- Active step: emphasized card with operational forms only.
- Raw payload: collapsed under «جزئیات فنی / فقط برای بررسی» (vehicle step only, when active).
- Service taxonomy tree removed from main flow.
- Gate checklist only inside `final` active step.
- Single OTP send + single OTP verify form (no duplicates).

---

## 7. Tests Passed

| Test | Result |
|---|---|
| test-p11-9-c-2c-fix-e1-otp-send-regression.php | 12/12 PASS |
| test-p11-9-c-2c-fix-e1-true-single-step-render.php | 23/23 PASS |
| test-p11-9-c-2c-fix-e1-step-routing.php | 8/8 PASS |
| test-p11-9-c-2c-fix-e1-no-tomar.php | 9/9 PASS |
| test-p11-9-c-2c-fix-e1-scope-security.php | 15/15 PASS |
| D1A / D1 / FIX-C photo / signoff | PASS |

---

## 8. Browser Validation Status

**PENDING** — Operator must copy to XAMPP and validate:

- `http://localhost:8080/moghare360/erp-reception-intake-file.php?online_request_id=18&active_step=otp#step-otp`
- `http://localhost:8080/moghare360/erp-reception-intake-file.php?online_request_id=20&active_step=otp#step-otp`

Expected: one full OTP step, compact inactive steps, Send OTP works, OTP input visible, no tomar scroll, no auto JobCard.

---

## 9. What Was Not Changed

- OTP architecture / IPPanel auth / canonical helpers
- staff-auth.php, access-control.php
- DB schema / SQL migrations
- Auth/Login / roles / permissions
- Automatic JobCard / C-2D scope

---

## 10. Commit Eligibility

**NOT_ELIGIBLE_UNTIL_BROWSER_STEPPER_AND_OTP_PASS**

---

P11.9-C-2C-FIX-E1 fixes the browser regressions introduced by the stepper UX by restoring canonical OTP send behavior, keeping OTP input visible, rendering only one full active operational step server-side, converting inactive steps into compact summaries, preserving guided step redirects, and maintaining OTP integrity, Gate truth, six-photo rule, secret safety, Auth/Login, permissions, database schema, workflow, and the no-automatic-JobCard boundary.

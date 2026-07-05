# MOGHARE360 P11.9-C-2C-FIX-E3 — Restore D1B OTP Report

**Phase:** P11.9-C-2C-FIX-E3  
**Date:** 2026-07-05

---

## 1. Scope Gate Result

**PASSED.** E2 introduced OTP UI/helper regressions without changing canonical OTP architecture. Fixes are limited to reception bridge + wizard OTP block.

See: `docs/audit/MOGHARE360_P11_9_C_2C_FIX_E3_RESTORE_D1B_OTP_SCOPE_REPORT.md`

---

## 2. D1B Stable OTP Baseline

- Canonical `m360_otp_send` / `m360_otp_verify`
- Visible send + verify on OTP step without edit click
- Payload `otp_verified` as Gate truth
- Success flash must override stale `failed` badge

---

## 3. E2 Regression Root Cause

1. **Mobile resolver priority inverted** — DB mobile preferred over `mobile_correction`
2. **Verify bug** — `$existing` referenced before payload parse
3. **Split OTP UI** — verify nested behind extra guard; not single D1B block
4. **Stale failed UI** — flash success required both `otp_sent=1` and `ok=1`; message text not honored

Real IPPanel failures still return `M360_OTP_MSG_SMS_FAILED` — not masked.

---

## 4. Duplicate OTP Path Cleanup

- Added single renderer: `m360_rw_intake_render_otp_wizard_block()`
- One send form, one verify form, one mobile resolver
- `m360_rw_intake_otp_show_verify_form()` delegates to `otp_ui_status`

---

## 5. Restored Send OTP Flow

- POST + CSRF + `return_active_step=otp`
- Calls `m360_otp_send($mobile)` only
- Does not set `otp_verified`
- Persists safe `reception_intake.otp` metadata (`sent` / `failed` / `config_missing`)
- Success redirect `#step-otp` with `otp_sent=1`
- Failure category via `m360_rw_intake_classify_otp_send_failure()`

---

## 6. Restored Verify OTP Flow

- Fixed payload parse order
- Calls `m360_otp_verify()`
- Success → payload `otp_verified=1`, wizard advances to vehicle
- Failure stays on OTP step

---

## 7. Wizard/Lock Preservation

- E2 true wizard unchanged
- Post-signature lock + save guards unchanged
- OTP send/verify routed before general lock guard; blocked only when intake locked

---

## 8. Tests Passed

| Test | Result |
|------|--------|
| test-p11-9-c-2c-fix-e3-restore-d1b-otp-flow.php | 10/10 PASS |
| test-p11-9-c-2c-fix-e3-no-duplicate-otp-path.php | 8/8 PASS |
| test-p11-9-c-2c-fix-e3-otp-ui-state.php | 8/8 PASS |
| test-p11-9-c-2c-fix-e3-wizard-lock-regression.php | 8/8 PASS |
| test-p11-9-c-2c-fix-e3-scope-security.php | 7/7 PASS |
| test-p11-9-c-2c-fix-d1a-ippanel-auth-diagnostics.php | 12/12 PASS |
| test-p11-9-c-2c-fix-d1-reception-verify-bridge.php | 8/8 PASS |
| test-p11-9-c-2c-fix-e2-true-wizard.php | 17/17 PASS |
| test-p11-9-c-2c-fix-c-photo-six.php | 13/13 PASS |
| test-v1-production-signoff.php | 23/23 PASS |

---

## 9. Browser Validation Status

**NOT RUN in this session.** Copy to XAMPP and verify requests 18 and 20:

`http://localhost:8080/moghare360/erp-reception-intake-file.php?online_request_id=18`

---

## 10. What Was Not Changed

Auth/Login, staff-auth, access-control, roles, DB schema, IPPanel auth header, private config, OTP architecture, automatic JobCard, C-2D.

---

## 11. Commit Eligibility

**NOT_ELIGIBLE_UNTIL_BROWSER_OTP_AND_WIZARD_PASS**

---

P11.9-C-2C-FIX-E3 restores the stable D1B reception OTP behavior inside the E2 true wizard by consolidating duplicate OTP state paths, reusing only the canonical m360_otp_send and m360_otp_verify helpers, keeping the OTP input visible without edit-click friction, preventing stale failed status from overriding successful sends, and preserving the E2 wizard, post-signature lock, Gate truth, six-photo rule, secret safety, Auth/Login, permissions, database schema, OTP integrity, and the no-automatic-JobCard boundary.

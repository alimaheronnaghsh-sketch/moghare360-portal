# MOGHARE360 P11.9-C-2C-FIX-E7B — Stable CSRF Report

## 1. Scope Gate Result

E7A root cause confirmed. Additive `erp_csrf_get_or_create_token()` plus reception-only reuse. No global Auth/OTP/DB changes. **PROCEED** completed.

## 2. E7A Diagnostic Cause Confirmed

`erp_csrf_create_token()` overwrote `online_request_reception` on every page GET via `erp_csrf_input()`. Multi-tab and stale-tab HTML tokens diverged from session.

## 3. Stable CSRF Implementation

**`includes/erp-csrf.php`**
- Added `erp_csrf_get_or_create_token($form_key)` — returns existing session token or creates once
- Preserved `erp_csrf_create_token()` for forced rotation (login APIs, other modules)

**`m360-reception-helper.php`**
- Added `m360_reception_csrf_token_value()` using get-or-create
- `m360_reception_csrf_input_html()` now embeds stable token without calling overwrite path

## 4. Multi-tab Behavior

Second render (simulated Tab B) returns the same token as Tab A. Validation accepts Tab A token after Tab B render. Token not consumed on successful validate.

## 5. Documents Forms Updated

All three documents POST forms use shared stable `$csrfInputHtml`.

`m360_rw_intake_return_step_hidden()` now emits:
- `active_step`
- `return_step`
- `return_active_step`
- section anchor via existing helper

Prepare contract form adds `return_section=section-contract`.

## 6. Invalid CSRF Recovery

On CSRF fail, `erp-reception-intake-save.php` recovers step from POST (`return_active_step`, `return_step`, `active_step`).

Error page shows Persian message and back link:
`erp-reception-intake-file.php?online_request_id={id}&active_step={step}#step-{step}`

Missing request id → `erp-reception-workbench.php`.

## 7. Contract Flow Regression

E7 customer contract flow preserved: receptionist prepares link only, customer accepts with OTP, documents gate unchanged, no JobCard.

## 8. Session Safety Notes

- Save endpoint and intake file both call `m360_reception_require_staff()` → `erp_auth_context_start()` → `session_start()` before CSRF validate/render.
- CSRF validate uses same `$_SESSION['erp_csrf_tokens']` bucket as get-or-create.
- No headers sent before session start on save path.
- No session id or cookie values printed in UI or reports.

## 9. Tests Passed

| Test | Result |
|------|--------|
| test-p11-9-c-2c-fix-e7b-stable-csrf-token.php | PASS 10/10 |
| test-p11-9-c-2c-fix-e7b-multitab-csrf.php | PASS 6/6 |
| test-p11-9-c-2c-fix-e7b-documents-forms-csrf.php | PASS 10/10 |
| test-p11-9-c-2c-fix-e7b-invalid-csrf-recovery.php | PASS 10/10 |
| test-p11-9-c-2c-fix-e7b-contract-flow-regression.php | PASS 8/8 |
| test-p11-9-c-2c-fix-e7b-scope-security.php | PASS 11/11 |
| test-p11-9-c-2c-fix-e7-customer-contract-page.php | PASS 11/11 |
| test-p11-9-c-2c-fix-e7-documents-routing.php | PASS 13/13 |
| test-p11-9-c-2c-fix-e7-csrf-invalid-request.php | PASS 5/5 |
| test-p11-9-c-2c-fix-e6a-no-vehicle-backjump.php | PASS 7/7 |
| test-v1-production-signoff.php | PASS 23/23 |

## 10. Browser Validation Status

XAMPP copy completed. Manual UAT required for Tests A–C (prepare link, multi-tab, customer accept).

Status: **PENDING BROWSER UAT**

## 11. What Was Not Changed

OTP frozen stack, customer contract business model, wizard routing logic, service gate, photos, Auth/Login, staff-auth, access-control, DB schema, JobCard conversion.

## 12. Commit Eligibility

**NOT_ELIGIBLE_UNTIL_BROWSER_CONTRACT_LINK_AND_ACCEPTANCE_PASS**

---

P11.9-C-2C-FIX-E7B stabilizes reception intake CSRF by reusing an existing session-scope token instead of regenerating and overwriting it on every page render, preventing stale-tab and multi-tab token drift across documents forms while preserving customer-side contract acceptance, frozen OTP, service gate, photo completion, no-backjump routing, post-signature lock, Auth/Login boundaries, permissions, database schema, workflow boundaries, and no-automatic-JobCard behavior.

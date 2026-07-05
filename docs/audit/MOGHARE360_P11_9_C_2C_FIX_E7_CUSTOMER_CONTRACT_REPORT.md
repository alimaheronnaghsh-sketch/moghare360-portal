# MOGHARE360 P11.9-C-2C-FIX-E7 — Customer Contract Report

## 1. Scope Gate Result

Scope report confirms no DB schema, Auth, OTP, or permission changes required. Contract acceptance stored in existing `request_payload_json.reception_intake.contract`. **PROCEED** approved.

## 2. Blueprint Process Correction

Contract review/acceptance moved from receptionist UI to customer-facing tokenized page. Receptionist prepares link; customer reads and digitally accepts. Software confirmation tied to verified OTP identity via `m360_online_req_payload_otp_verified()`.

## 3. Receptionist-side Contract Button Removed

Removed `اجرای قرارداد پذیرش` and `ثبت تأیید قرارداد` forms from documents step. Replaced with `m360_rw_intake_render_documents_contract_staff_block()` showing:
- Contract status label
- `ایجاد لینک مطالعه و امضای قرارداد مشتری` (`prepare_customer_contract_review`)
- One-time customer review link after prepare

`run_intake_contract` / `approve_intake_contract` return error in `apply_action`.

## 4. Customer Contract Review Page

**File:** `public_html/customer-intake-contract-review.php`

- GET: token validation, read-only contract summary, OTP gate for acceptance form
- POST: checkbox + OTP verified + token validation → persist acceptance
- No staff login, no OTP codes, no base64 photo dump

## 5. OTP Verified Identity Use

Customer acceptance blocked unless `m360_online_req_payload_otp_verified($requestRow)` is true. Frozen OTP send/verify unchanged. Acceptance method: `otp_verified_identity_and_customer_confirmation`.

## 6. Contract Acceptance Payload

```json
reception_intake.contract = {
  "status": "customer_accepted",
  "review_token_hash": "...",
  "review_token_created_at": "...",
  "review_token_expires_at": "...",
  "customer_accepted_at": "...",
  "customer_accepted_mobile": "...",
  "acceptance_method": "otp_verified_identity_and_customer_confirmation",
  "acceptance_ip": "...",
  "acceptance_user_agent": "..."
}
```

Mirror: `reception_intake.documents.contract_status = "customer_accepted"`

## 7. Documents Routing Fix

Documents complete only when diagnostic + cost + `customer_accepted`. Save without acceptance stays on documents with message: «قرارداد پذیرش هنوز توسط مشتری مطالعه و تأیید نشده است.» No backjump to vehicle/photos. After acceptance, redirect advances to signature.

## 8. CSRF Invalid Request Fix

`erp-reception-intake-save.php` POST-only. CSRF failure with `context='intake'` shows controlled message and back link to `active_step=documents#step-documents`.

## 9. Regression Freeze

Preserved: frozen OTP, E4 service gate, E5 six-photo completion, E6A no-silent-backjump, E2 post-signature lock, no automatic JobCard.

## 10. Tests Passed

| Test | Result |
|------|--------|
| test-p11-9-c-2c-fix-e7-customer-contract-page.php | PASS (11/11) |
| test-p11-9-c-2c-fix-e7-contract-token-security.php | PASS (5/5) |
| test-p11-9-c-2c-fix-e7-documents-routing.php | PASS (13/13) |
| test-p11-9-c-2c-fix-e7-csrf-invalid-request.php | PASS (5/5) |
| test-p11-9-c-2c-fix-e7-regression-freeze.php | PASS (7/7) |
| test-p11-9-c-2c-fix-e7-scope-security.php | PASS (15/15) |
| test-p11-9-c-2c-fix-e2-true-wizard.php | PASS (17/17) |
| test-p11-9-c-2c-fix-e4-service-diagnostic-gate.php | PASS (14/14) |
| test-p11-9-c-2c-fix-e5-photo-loop-regression.php | PASS (6/6) |
| test-p11-9-c-2c-fix-e6a-no-vehicle-backjump.php | PASS (7/7) |
| test-p11-9-c-2c-fix-e6a-documents-contract-routing.php | PASS (8/8) |
| test-v1-production-signoff.php | PASS (23/23) |

## 11. Browser Validation Status

XAMPP copy completed. Manual browser UAT required:

**Staff:** `http://localhost:8080/moghare360/erp-reception-intake-file.php?online_request_id=18&active_step=documents#step-documents`

**Customer:** open generated link after prepare

Repeat for request 20.

Status: **PENDING BROWSER UAT**

## 12. What Was Not Changed

- m360-otp-helper.php, m360-otp-config-loader.php, OTP UI, IPPanel config
- send_customer_otp / verify_customer_otp
- staff-auth.php, access-control.php, roles/permissions
- DB schema, SQL migrations
- JobCard conversion / automatic JobCard creation
- P12 scope

## 13. Commit Eligibility

**NOT_ELIGIBLE_UNTIL_BROWSER_CUSTOMER_CONTRACT_ACCEPTANCE_PASS**

---

P11.9-C-2C-FIX-E7 moves contract acceptance out of the receptionist UI and into a customer-facing tokenized contract review page, requires verified OTP identity before customer digital acceptance, stores contract acceptance in the intake payload without DB schema changes, keeps documents routing on the real contract blocker instead of jumping to vehicle/photos, handles expired CSRF safely, and preserves frozen OTP, service gate, six-photo completion, no-silent-backjump routing, post-signature lock, Auth/Login, permissions, database schema, workflow boundaries, and the no-automatic-JobCard boundary.

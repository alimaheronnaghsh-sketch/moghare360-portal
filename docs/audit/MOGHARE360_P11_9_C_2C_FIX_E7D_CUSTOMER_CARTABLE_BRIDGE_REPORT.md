# MOGHARE360 P11.9-C-2C-FIX-E7D — Customer Cartable Bridge Report

## 1. Scope Gate Result

**PROCEED** — OPTION_3 hybrid bridge implemented without DB schema change, customer auth, or JobCard conversion. See scope report for E7C cause confirmation and request 18 blockers.

## 2. Blueprint Alignment Decision

Contract flow reframed from raw public link to **customer cartable task** semantics:

- Staff assigns مأموریت قرارداد to customer cartable (not receptionist approval)
- Customer accepts only on cartable page with OTP-verified identity
- Staff continues to signature after customer acceptance
- Temporary token URL labeled for V1 RC local testing only

## 3. Hybrid Customer Cartable Payload

Added `reception_intake.customer_cartable.contract_task` with status, assignment metadata, hashed access token, expiry, and acceptance audit fields.

Compatibility mirrors maintained:

- `reception_intake.contract.status` → `pending_customer_review` / `customer_accepted`
- `reception_intake.contract.review_token_hash` (+ created/expiry timestamps)
- `reception_intake.documents.contract_status` → `customer_pending_review` / `customer_accepted`

Raw token never persisted in payload (transient session one-time display only).

## 4. Prerequisite Gate

`m360_rw_intake_contract_cartable_prerequisites($payload, $requestRow)` checks:

1. OTP verified (row mobile + otp flag)
2. Vehicle complete
3. Condition complete
4. Service classification complete
5. Referral complete
6. Photos 6/6
7. Diagnostic present
8. Cost agreement present

When not ready: no token generation, Persian blocker checklist on documents, link to first blocker step.

## 5. Token Persistence Fix

`prepare_customer_contract_review` now:

1. Gates on prerequisites
2. Generates base64url token + SHA-256 hash
3. Persists hash to cartable + contract paths
4. Sets pending statuses (48h expiry)
5. Self-validates resolver before success
6. Stores one-time raw token in staff session for temporary URL display

Request row mobile/OTP injected from DB row via `_rw_request_*` post context in `process_save`.

## 6. Customer Cartable Page

`customer-intake-contract-review.php` reframed:

- Title/copy: **کارتابل مشتری / مأموریت قرارداد پذیرش**
- Token param: `t` with `token` alias
- Validates cartable or compatibility contract hash + expiry
- Shows summary, contract terms, OTP status
- POST acceptance writes cartable + contract + documents mirror fields

## 7. Staff Documents Routing

Four UI states on documents:

| State | Behavior |
|-------|----------|
| Not ready | Blocker checklist + first-blocker link |
| Ready | Button: ایجاد مأموریت قرارداد در کارتابل مشتری |
| Pending | Active task status + temporary local cartable URL |
| Accepted | تأیید شده توسط مشتری; save routes to signature when complete |

`resolve_active_step`: explicit `active_step=documents` stays on documents (no silent vehicle jump).

Save message when pending: «قرارداد هنوز توسط مشتری در کارتابل تأیید نشده است.»

## 8. Request 18 Handling

Live request 18 (`09128166648`) remains **not_ready** until staff completes vehicle, photos, and documents prerequisites. Expected browser behavior:

- Documents shows blocker checklist (no invalid customer link)
- After prerequisites complete → assign succeeds → temporary cartable URL
- Customer acceptance → staff refresh shows تأیید شده توسط مشتری → save → signature

No fake completion applied to live data.

## 9. Diagnostic Tool

`tools/diagnose-p11-9-c-2c-fix-e7d-customer-cartable.php --online_request_id=18|20`

Outputs prerequisites, blockers, cartable status, hash prefix (no secrets).

## 10. Tests Passed

| Test | Result |
|------|--------|
| test-p11-9-c-2c-fix-e7d-prerequisite-gate.php | PASS (9/9) |
| test-p11-9-c-2c-fix-e7d-cartable-token-persistence.php | PASS (6/6) |
| test-p11-9-c-2c-fix-e7d-cartable-page.php | PASS (8/8) |
| test-p11-9-c-2c-fix-e7d-staff-routing.php | PASS (9/9) |
| test-p11-9-c-2c-fix-e7d-customer-acceptance.php | PASS (7/7) |
| test-p11-9-c-2c-fix-e7d-scope-security.php | PASS (11/11) |
| test-p11-9-c-2c-fix-e7b-stable-csrf-token.php | PASS |
| test-p11-9-c-2c-fix-e7-customer-contract-page.php | PASS |
| test-p11-9-c-2c-fix-e6a-no-vehicle-backjump.php | PASS |
| test-p11-9-c-2c-fix-e7-documents-routing.php | PASS |
| test-p11-9-c-2c-fix-e7-contract-token-security.php | PASS |
| test-v1-production-signoff.php | PASS (23/23) |

## 11. Browser Validation Status

XAMPP copy completed for `m360-reception-workbench-helper.php` and `customer-intake-contract-review.php`.

**Case A (request 18 incomplete):** Ready for manual UAT at  
`http://localhost:8080/moghare360/erp-reception-intake-file.php?online_request_id=18&active_step=documents#step-documents`

**Case B (after prerequisites):** Ready for assign → cartable → accept → signature flow.

**Commit Eligibility:** `NOT_ELIGIBLE_UNTIL_BROWSER_CARTABLE_FLOW_PASS` — automated tests pass; browser cartable acceptance flow requires manual confirmation.

## 12. What Was Not Changed

- OTP implementation, helpers, config, UI
- Service diagnostic gate logic (read-only for prerequisites)
- Photo canonical save logic (read-only for prerequisites)
- staff-auth.php, access-control.php, roles/permissions
- DB schema, SQL migrations
- JobCard conversion
- Customer auth/login architecture
- P12 / C-2D scope

## 13. Commit Eligibility

**NOT_ELIGIBLE_UNTIL_BROWSER_CARTABLE_FLOW_PASS**

---

P11.9-C-2C-FIX-E7D aligns the contract process with the owner-approved customer-cartable blueprint by replacing raw public-link semantics with a hybrid payload-backed customer cartable task, blocking assignment until live intake prerequisites are complete, persisting and self-validating the customer access token before showing any temporary V1 RC access URL, keeping staff on documents with exact blockers instead of silent vehicle/photo jumps, allowing customer acceptance only with verified OTP identity, returning accepted contracts to staff for signature, and preserving frozen OTP, service gate, six-photo completion, no-backjump routing, post-signature lock, Auth/Login boundaries, permissions, database schema, private secret safety, workflow boundaries, and no-automatic-JobCard behavior.

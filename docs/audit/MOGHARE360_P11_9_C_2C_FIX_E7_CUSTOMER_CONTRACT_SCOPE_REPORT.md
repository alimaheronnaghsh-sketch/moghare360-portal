# MOGHARE360 P11.9-C-2C-FIX-E7 — Customer Contract Scope Report

## 1. Current Documents Step Actions (Before Fix)

| Action | Actor | Behavior |
|--------|-------|----------|
| `save_diagnostic_pdf` | Receptionist | Upload diagnostic PDF |
| `run_intake_contract` | Receptionist | Generated contract text and marked contract run — **process-invalid** |
| `approve_intake_contract` | Receptionist | Checkbox approval on behalf of customer — **process-invalid** |
| `save_documents_and_cost` | Receptionist | Save cost agreement; could advance wizard even without valid customer acceptance |

## 2. «اجرای قرارداد پذیرش» Wrong Routing

The button posted `action_type=run_intake_contract` to `erp-reception-intake-save.php`. Redirect logic mapped contract actions to `documents`, but `run_intake_contract` did not set `customer_accepted`. When combined with stale `section_status` or incomplete CSRF/session state, browser UAT showed step 2 / invalid request instead of staying on documents with a clear contract blocker.

## 3. CSRF / Invalid Request on erp-reception-intake-save.php

Staff forms require `erp_csrf_token`. Expired or missing CSRF previously rendered a generic dead error without a path back to the documents step anchor. FIX-E7 adds `context='intake'` CSRF handling with redirect link to `erp-reception-intake-file.php?online_request_id={id}&active_step=documents#step-documents`.

## 4. contract_status Write Path (Before)

Receptionist actions wrote `contract.run_at`, `contract.status=DRAFT`, and optional `customer_contract_approved` checkbox — all on staff side. SQL `contract_status` could remain NULL while staff believed contract was done.

## 5. Why Receptionist-side Approval Is Process-invalid

Blueprint requires: customer reads contract → customer digitally accepts/signs → software confirmation tied to verified OTP identity. Receptionist cannot legally or procedurally accept on customer's behalf.

## 6. Proposed Customer-side Contract Review Flow

1. Receptionist prepares link (`prepare_customer_contract_review`) — stores hashed token, status `prepared`.
2. Customer opens tokenized page (`customer-intake-contract-review.php?t=...`).
3. Customer reviews summary (mobile, vehicle, service, photos, diagnostic, cost, terms).
4. If OTP verified, customer checks acceptance and submits.
5. Payload writes `contract.status=customer_accepted` and mirrors `documents.contract_status`.
6. Staff documents step completes; wizard advances to signature.

## 7. OTP Verified Identity Reuse

Uses existing `m360_online_req_payload_otp_verified($requestRow)` and frozen OTP flow. Customer acceptance calls `m360_rw_intake_apply_customer_contract_acceptance()` which blocks if OTP not verified. No new OTP send/verify implementation.

## 8. Exact Files to Modify

| File | Change |
|------|--------|
| `public_html/includes/m360-reception-workbench-helper.php` | Contract helpers, prepare action, customer accept, documents gate, staff block render |
| `public_html/erp-reception-intake-file.php` | Replace receptionist contract buttons with customer link block |
| `public_html/customer-intake-contract-review.php` | **New** customer contract page |
| `public_html/erp-reception-intake-save.php` | Intake CSRF context (already wired) |
| `public_html/includes/m360-reception-helper.php` | Intake CSRF back link (already wired) |
| `tools/test-p11-9-c-2c-fix-e7-*.php` | Six new tests |
| `docs/audit/MOGHARE360_P11_9_C_2C_FIX_E7_*.md` | Scope + final reports |

## 9. DB / Auth / OTP / Schema Confirmation

**No DB schema change required.** Contract acceptance stored in existing `request_payload_json.reception_intake.contract`. No Auth/Login, staff-auth, access-control, OTP helper, or SQL migration changes.

**Scope gate: PROCEED**

# MOGHARE360 P11.9-C-2C-FIX-E7C-DIAG — Contract Flow Runtime and Blueprint Alignment Report

## 1. Scope

Diagnostic-only inspection of three browser UAT failures after FIX-E7/E7B:

1. Customer contract URL opens but shows **«لینک مطالعه قرارداد نامعتبر یا منقضی شده است»**
2. Staff contract action may return to **step 2 / vehicle**
3. Owner blueprint correction: contract should flow through **customer cartable**, not a raw public link

No code, token, routing, OTP, DB, or cartable implementation changes were made in this phase.

## 2. Files Inspected

| File | Purpose |
|------|---------|
| `public_html/customer-intake-contract-review.php` | Customer token page (GET/POST `t`) |
| `public_html/includes/m360-reception-workbench-helper.php` | Token generate/hash/validate, prepare action, persist, redirect, wizard resolve |
| `public_html/erp-reception-intake-file.php` | Staff documents UI, active_step GET |
| `public_html/erp-reception-intake-save.php` | POST save + redirect |
| `public_html/customer-login.php`, `customer-profile.php` | Existing customer portal/session |
| `public_html/customer-intake-contract.php` | Legacy token contract page (`token` param) |
| `tools/diagnose-p11-9-c-2c-wizard-state.php` | Read-only runtime wizard diagnostic |
| `docs/audit/MOGHARE360_P11_9_C_2C_FIX_E7A_DIAG_DOCUMENTS_CSRF_REPORT.md` | Prior CSRF diagnostic |

## 3. Current Runtime Failure Summary

| Symptom | Static + runtime read-only finding |
|---------|-------------------------------------|
| Invalid customer link | Error string matches **hash validation failure** in `m360_rw_intake_contract_validate_review_token()` (line 2805–2806), not expiry message (2813 uses different text). |
| Request 18 live DB | `review_token_hash` **MISSING** — no prepared contract token persisted. Any URL token fails hash check. |
| Step 2 / vehicle jump | For request 18, `first_incomplete_step=vehicle`; `resolve_active_step(['active_step'=>'documents'])` returns **`vehicle`**, not documents. |
| Blueprint mismatch | E7 implemented **public token page**; owner wants **customer cartable task assignment** inside customer portal workflow. |

## 4. Token Failure Cause Classification

### Inspection matrix

| Check | Finding |
|-------|---------|
| **1. Storage path** | Staff writes `reception_intake.contract.review_token_hash` in `prepare_customer_contract_review` (`apply_action`). Customer reads same path in `m360_rw_intake_contract_validate_review_token()`. **Paths match.** |
| **2. Query param** | Staff URL: `customer-intake-contract-review.php?t=` via `m360_rw_intake_contract_review_url()`. Customer reads `$_GET['t']` / `$_POST['t']`. **No `token` alias** (legacy `customer-intake-contract.php` uses `token=` — different page). |
| **3. URL encoding** | Token is **base64url** (`+/` → `-_`, padding stripped). URL built with `rawurlencode()`. **Design is safe**; corruption possible only via manual copy/truncation. |
| **4. Hash algorithm** | Both sides: `hash('sha256', trim($rawToken))`. **Same algorithm and normalization.** |
| **5. Expiry** | Stored as ISO UTC `review_token_expires_at` (`gmdate('Y-m-d\TH:i:s\Z')`, +72h). Expired tokens use message **«مهلت مطالعه قرارداد به پایان رسیده است»** — **not** the message reported in UAT. |
| **6. Request status filter** | Customer page: `m360_online_req_fetch_by_id()` — **no status filter**. `UNDER_REVIEW` allowed. |
| **7. JSON SQL resolver** | Customer page loads full row, parses JSON in PHP. **No SQL JSON_VALUE token lookup.** No SQL-path mismatch in current code. |
| **8. Raw token in payload** | Raw token only in transient `_contract_review_token_once` (stripped before persist) + staff PHP session one-time display. **Not persisted in payload by design.** |

### Root cause codes (ranked)

| Code | Verdict | Evidence |
|------|---------|----------|
| **CAUSE_T2** | **PRIMARY** | Live request 18: `review_token_hash` **absent** in `request_payload_json`. Validator fails at empty/mismatch hash → exact UAT message. |
| **CAUSE_T9** | **PRIMARY (related)** | Without persisted hash, generated staff link cannot validate even if staff UI showed a session token once. Indicates prepare did not successfully persist (never run, CSRF block pre-E7B, or persist error). |
| **CAUSE_T3** | **SECONDARY** | Re-clicking **prepare** regenerates token and **overwrites** hash; older links fail hash check. Relevant after first successful prepare. |
| **CAUSE_T4** | Unlikely | base64url + rawurlencode design correct. |
| **CAUSE_T5** | Ruled out | Wrong error text for expiry path. |
| **CAUSE_T6** | Ruled out | No status gate on customer page. |
| **CAUSE_T7** | N/A | No SQL JSON token search implemented. |
| **CAUSE_T8** | Ruled out | Code does not persist raw token. |
| **CAUSE_T1** | Ruled out | Both sides use `t`. |
| **CAUSE_T10** | Partial | Runtime DB read confirms missing hash; full token-in-URL vs DB cross-check still needs browser DevTools (without logging token). |

**Diagnostic conclusion:** UAT invalid link is **not** primarily a parameter-name bug. It is **hash absent or hash/token pair mismatch** at validation time. For request 18 right now: **hash absent**.

## 5. Staff Redirect / Step 2 Cause Classification

### Action types (documents step)

| UI action | `action_type` |
|-----------|---------------|
| ایجاد لینک مطالعه و امضای قرارداد مشتری | `prepare_customer_contract_review` |
| ذخیره مستندات و ادامه | `save_documents_and_cost` |
| بارگذاری PDF دیاگ | `save_diagnostic_pdf` |

No separate `request/send link` action exists beyond `prepare_customer_contract_review`.

### Redirect behavior after POST (success)

`m360_rw_intake_save_redirect_url()` → `m360_rw_intake_redirect_active_step()`:

| Action | Default next step | Documents-complete override |
|--------|-------------------|----------------------------|
| `prepare_customer_contract_review` | `documents` | Stays `documents` unless documents step fully complete → `signature` |
| `save_documents_and_cost` | `documents` | Same |
| `save_diagnostic_pdf` | `documents` | Same |

Hidden fields (E7B): `active_step`, `return_step`, `return_active_step`, `return_section` emitted via `m360_rw_intake_return_step_hidden('documents')`. Prepare form also sets `return_section=section-contract`.

POST redirect **does preserve** `active_step=documents` when handler runs successfully.

### GET intake page (`erp-reception-intake-file.php`)

After redirect, page load calls:

```php
m360_rw_intake_resolve_active_step($_GET, $request, $payloadData, $formValues)
```

This is **independent** of POST redirect and can **override** `active_step=documents` in the URL.

### Runtime request 18 (read-only DB)

```
first_incomplete_step: vehicle
resolved_active_step(documents request): vehicle
photos_complete: no
contract_status: (empty)
review_token_hash: MISSING
```

Vehicle incomplete because canonical vehicle missing **brand, model, mileage, fuel_level** (plate `39ه498-13` present). Wizard resolver returns **furthest incomplete = vehicle** when user requests documents but prior steps fail `priorComplete` check.

### Redirect cause codes

| Code | Verdict | Evidence |
|------|---------|----------|
| **CAUSE_R5** | **PRIMARY** | Documents requested via GET, but wizard truth marks **vehicle** (and photos, etc.) incomplete → resolver returns `vehicle` (step 2). |
| **CAUSE_R2** | **PRIMARY** | `resolve_active_step()` runs on every intake file GET after contract prepare redirect. |
| **CAUSE_R1** | Partial | POST redirect preserves documents; **GET resolver** then moves user to vehicle. |
| **CAUSE_R3** | Unlikely | return_step fields present in forms (E7B). |
| **CAUSE_R4** | Ruled out | Unknown action returns error, not vehicle. |
| **CAUSE_R6** | Possible amplifier | Live payload may have stale `section_status` vs canonical; E6A mitigates but live request 18 data incomplete beyond section_status. |
| **CAUSE_R7** | — | Live SQL truth for request 18 differs from earlier fixture-based assumptions (vehicle/photos not complete in DB). |

## 6. Current Contract Flow vs Corrected Blueprint

| Aspect | Current E7/E7B | Owner-corrected blueprint |
|--------|----------------|---------------------------|
| Staff action | Generate public URL + one-time staff display | Assign contract task to **customer cartable** |
| Customer access | Anonymous token page, no login | Customer enters **customer cartable**, reviews/accepts there |
| Identity gate | Staff-side OTP verified; customer page checks `otp_verified` | Customer acceptance tied to verified OTP identity (unchanged principle) |
| Return path | Staff refreshes intake file | File returns to receptionist queue after customer action |
| After acceptance | Staff continues documents → signature | Receptionist continues referral / JobCard gate |

**Gap:** Current implementation is a **public token bridge**, not a cartable task workflow.

## 7. Customer Cartable Existing Capability Check

| # | Question | Answer |
|---|----------|--------|
| 1 | Customer cartable / portal exists? | **Partial.** `customer-login.php`, `customer-profile.php`, `customer-request-status.php`, legacy `customer-intake-contract.php` (DB contract token). **No dedicated intake contract cartable task UI.** |
| 2 | Customer identity/session? | **Yes.** Customer login via mobile OTP (`customer-login.php`, session). Separate from staff ERP session. |
| 3 | Customer cartable table/queue? | **No dedicated table** found for intake contract tasks. Staff cartables exist (inventory, dashboard tiles). |
| 4 | Customer pending tasks route? | **No** route for `online_request_reception` intake contract tasks. |
| 5 | `customer-intake-contract-review.php` | **Public token page only** (`t` param, no customer login). |
| 6 | Temporary cartable substitute? | **De facto yes** — token page acts as external task surface, but **not** integrated with customer login/cartable UX or owner language. |
| 7 | Missing for true cartable | Task queue model, customer-authenticated task list, assign/notify action from staff, status sync back to staff workbench, cartable UI copy/flow. |
| 8 | New DB schema required? | **True production cartable:** likely **yes** (task queue and/or customer notification). **Payload-only simulation:** **no**. |
| 9 | Customer auth required? | **True cartable:** **yes** (login/session). Token page currently bypasses this. |
| 10 | OTP architecture impact? | **Must stay frozen.** Customer cartable should **reuse** staff-verified OTP state in payload, not new OTP send/verify. |
| 11 | Scope boundary | Payload cartable simulation fits **C-2C extension**. Full customer cartable foundation likely **separate approved phase** (P12 or pre-C-2D). |

## 8. Request 18 / 09128166648 Snapshot

Read-only runtime via ODBC diagnostic (2026-06-26):

| Field | Value |
|-------|-------|
| Request exists | **Yes** (`online_request_id=18`) |
| `request_status` | `UNDER_REVIEW` |
| `otp_verified` (row) | `1` |
| Mobile | `09128166648` |
| `customer_id` | empty |
| `vehicle_id` | empty |
| Contract object in payload | **No meaningful contract object** (no status, no hash) |
| `review_token_hash` | **MISSING** |
| `review_token_created_at` / `expires_at` | empty |
| Expired | **Not inferable** (no expiry stored) |
| `documents.contract_status` | empty |
| `customer_cartable` object | **No** |
| Active blocker | **Vehicle step** (then condition, service, referral, photos, documents, signature) |
| Expected next step (wizard truth) | **`vehicle`** (not documents) |
| Expected next step (user intent) | **`documents`** — **conflicts with wizard truth** |

### SQL for owner/DBA verification (do not log tokens)

```sql
SELECT
  online_request_id,
  request_status,
  mobile,
  otp_verified,
  customer_id,
  vehicle_id,
  JSON_VALUE(request_payload_json, '$.reception_intake.contract.status') AS contract_status,
  CASE WHEN JSON_VALUE(request_payload_json, '$.reception_intake.contract.review_token_hash') IS NULL
       THEN 'MISSING' ELSE LEFT(JSON_VALUE(request_payload_json, '$.reception_intake.contract.review_token_hash'), 8) END AS hash_prefix,
  JSON_VALUE(request_payload_json, '$.reception_intake.contract.review_token_created_at') AS token_created,
  JSON_VALUE(request_payload_json, '$.reception_intake.contract.review_token_expires_at') AS token_expires,
  JSON_VALUE(request_payload_json, '$.reception_intake.documents.contract_status') AS doc_contract_status
FROM dbo.m360_online_service_requests
WHERE online_request_id = 18;
```

(Adjust table name if environment uses alias from `m360_online_req_table()`.)

## 9. Security and Secret Risk Notes

| Item | Status |
|------|--------|
| OTP helpers frozen | Required — **do not modify** in next fix without owner approval |
| `m360-otp-helper.php` / `m360-otp-config-loader.php` | Git status shows **modified** in working tree (~104 changed files). **Must be reviewed before any commit**; ensure no secrets/private runtime config included |
| `private/m360-otp-config.example.php` | Inspect before commit; example only |
| Raw contract token | Must never be committed/logged/printed in reports |
| JobCard | No automatic creation in contract flow |
| DB schema | No change in this diagnostic |

## 10. Recommended Correct Workflow Option

### Recommendation: **OPTION_3 — HYBRID** (with owner approval)

**Why not OPTION_1 alone:** Pure payload simulation without cartable UX still feels like a public link; owner explicitly rejected raw public flow.

**Why not OPTION_2 now:** Real customer cartable needs customer-authenticated task queue, likely new tables/routes, and owner approval — too large for silent C-2C fix.

**OPTION_3 — HYBRID (recommended interim for V1 RC):**

1. Staff action language: **«ارسال قرارداد به کارتابل مشتری»** (not «لینک عمومی»).
2. Payload adds `reception_intake.customer_cartable.contract_task`:
   - `status`: `pending_customer_review` → `customer_accepted`
   - `assigned_at`, `assigned_mobile`, optional `review_token_hash` reference
3. Customer entry:
   - **Short term:** token URL opens page reframed as **«کارتابل مشتری — تأیید قرارداد پذیرش»** (same security, cartable semantics).
   - **Medium term:** same task surfaced in `customer-profile.php` task list after customer login (mobile match + OTP-verified intake).
4. Acceptance still requires **`otp_verified`** in payload; no receptionist approval; no new OTP system.
5. Explicitly documented as **temporary bridge** until OPTION_2.

## 11. Recommended Next Fix Phase

**FIX-E7C-IMPLEMENT (or FIX-E8)** — two tracks:

### Track A — Runtime blockers (must fix regardless of blueprint)

1. **Persist + verify prepare:** After prepare, assert `review_token_hash` present before showing link; surface error if persist failed.
2. **Token validation UX:** Distinguish «never prepared» vs «hash mismatch» vs «expired» for support.
3. **Wizard routing for request 18:** Reconcile live payload (vehicle/photos incomplete in DB) vs staff expectation; either complete missing canonical fields or allow documents anchor when OTP+plate+photos truth satisfied per owner rule.

### Track B — Blueprint alignment (owner approval)

1. Payload `customer_cartable.contract_task` model (OPTION_3).
2. Reframe customer page + staff copy as cartable task.
3. Optional: list pending contract task in `customer-profile.php` for logged-in mobile.
4. Plan OPTION_2 as separate phase if owner wants full authenticated cartable without token.

**STOP_REQUIRED_FOR_OWNER_APPROVAL** before OPTION_2 (new tables, customer auth architecture changes, permissions).

## 12. Files That May Be Changed Next

| File | Likely change |
|------|---------------|
| `m360-reception-workbench-helper.php` | Persist verification, cartable payload, wizard truth for request 18 |
| `customer-intake-contract-review.php` | Cartable framing, clearer errors, optional customer session hook |
| `erp-reception-intake-file.php` | Staff copy/buttons, cartable status display |
| `customer-profile.php` | Optional pending contract task list (OPTION_3) |
| `docs/audit/*` | Implementation + UAT reports |

## 13. Files That Must Not Be Changed

- `m360-otp-helper.php`, `m360-otp-config-loader.php`, private OTP config (frozen unless owner waives)
- `send_customer_otp` / `verify_customer_otp` flows
- `staff-auth.php`, `access-control.php`, roles/permissions
- DB schema / SQL migrations (unless OPTION_2 approved)
- Automatic JobCard creation
- P12 scope without approval

## 14. STOP / GO Decision

| Area | Decision |
|------|----------|
| Public token fix only (hash persist + routing) | **GO** — payload-only, no schema |
| OPTION_3 hybrid cartable simulation | **GO with owner UX approval** — payload-only |
| OPTION_2 real customer cartable | **STOP — owner approval required** |
| Commit current ~104 files | **STOP** — browser UAT fail + OTP file changes + secret review incomplete |

## 15. Final Diagnostic Conclusion

Browser failures have **two separate root causes**:

1. **Invalid customer link:** `review_token_hash` is **missing** on live request 18, so validation correctly fails with the hash-mismatch message. Secondary risk: regenerating prepare invalidates prior tokens.

2. **Vehicle step jump:** POST redirect targets documents, but **GET `resolve_active_step()`** returns **`vehicle`** because live wizard truth marks vehicle (and photos, etc.) incomplete — not only contract.

Additionally, **E7 public token flow does not match owner blueprint** for customer cartable assignment. Recommended path: **OPTION_3 HYBRID** for V1 RC plus runtime fixes for hash persistence and wizard truth on request 18.

**Commit Eligibility:** `NOT_ELIGIBLE_DIAGNOSTIC_ONLY`

---

P11.9-C-2C-FIX-E7C-DIAG performs a no-code diagnostic of the invalid customer contract token, documents-step redirect back to vehicle, and mismatch between the current public token link implementation and the corrected customer-cartable blueprint, then recommends the controlled next fix path without changing OTP, Auth/Login, database schema, private config, JobCard behavior, or runtime code.

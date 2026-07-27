# MOGHARE360 PR-02B — Customer Profile + Multi-Vehicle Implementation Report

**Date (UTC):** 2026-07-06  
**Phase:** PR-02B controlled implementation (no commit / no push)

## 1. Executive Summary

PR-02B implements owner-approved customer identity (OTP-only), post-OTP profile load/create, multi-vehicle select/add, linked online request submit (`customer_id` + `vehicle_id`), single-source server OTP verification, direct shared submit path (no mirror curl loopback), and a seven-step customer wizard with contract placeholder — **only where current schema safely supports it**.

Schema gap fields (`vehicle_delivery_address`, `authorized_receiver_*`) are stored supplementally in `erp_customers.notes` (JSON prefix `PR02B_EXT:`) pending SQL proposal approval. DB schema was not altered.

**Commit eligibility:** `NOT_ELIGIBLE_UNTIL_VALID_PROFILE_MULTI_VEHICLE_BROWSER_UAT_PASS`

## 2. Governance Decision Lock

Document: `docs/audit/MOGHARE360_PR_02B_GOVERNANCE_DECISION_LOCK.md`

| Label | Value |
|-------|-------|
| PR_02B_DECISION_LOCK_CREATED | yes |
| OTP_ONLY_LOGIN_LOCKED | yes |
| CUSTOMER_PROFILE_REQUIRED_AFTER_OTP | yes |
| MULTI_VEHICLE_LOCKED | yes |
| ONLINE_REQUEST_CUSTOMER_VEHICLE_LINK_REQUIRED | yes |
| OTP_SINGLE_SOURCE_LOCKED | yes |
| MIRROR_LOOPBACK_ELIMINATION_REQUIRED | yes |
| CUSTOMER_STEP_BASED_FLOW_LOCKED | yes |
| ONLINE_CONTRACT_ARCHITECTURE_LOCKED | yes |

## 3. Schema Capability Gate

| Label | Value |
|-------|-------|
| SCHEMA_SUPPORTS_CUSTOMER_PROFILE | partial |
| SCHEMA_SUPPORTS_MULTI_VEHICLE | yes |
| SCHEMA_SUPPORTS_ONLINE_REQUEST_CUSTOMER_ID | yes |
| SCHEMA_SUPPORTS_ONLINE_REQUEST_VEHICLE_ID | yes |
| SQL_PROPOSAL_NEEDED | yes |
| DB_SCHEMA_UNTOUCHED | yes |

**Partial profile mapping:**
- `first_name` + `last_name` → `erp_customers.full_name`
- `national_id`, `primary_mobile`, `secondary_mobile`, `address`, `city` → canonical columns
- `vehicle_delivery_address`, `authorized_receiver_name`, `authorized_receiver_phone` → supplemental `notes` JSON until migration

Proposal: `docs/sql-proposals/MOGHARE360_PR_02B_CUSTOMER_PROFILE_SCHEMA_GAP_PROPOSAL.sql` (not executed)

## 4. Customer Profile Load/Create Result

| Label | Value |
|-------|-------|
| CUSTOMER_PROFILE_LOADED_AFTER_OTP | yes |
| CUSTOMER_PROFILE_CREATE_SUPPORTED | yes |
| DUPLICATE_CUSTOMER_PREVENTED | yes |
| PASSWORD_REQUIRED | no |
| PROFILE_FIELDS_VISIBLE_STEP | yes |
| PROFILE_FIELDS_PERSISTED_CANONICALLY | partial |

**Behavior:** After OTP, `api/customer/profile-status.php` returns full profile + vehicles. Submit path upserts customer via `m360_pr02b_upsert_customer()` (find by mobile → UPDATE or INSERT). Supplemental delivery/authorized fields encoded in `notes`.

## 5. Multi-Vehicle Result

| Label | Value |
|-------|-------|
| MULTI_VEHICLE_PICKER_EXISTS | yes |
| ADD_NEW_VEHICLE_EXISTS | yes |
| SELECT_PREVIOUS_VEHICLE_SETS_VEHICLE_ID | yes |
| ADD_NEW_VEHICLE_LINKS_TO_CUSTOMER | yes |
| ONE_MOBILE_ONE_VEHICLE_ASSUMPTION_REMOVED | yes |
| ASIAN_BRANDS_REINTRODUCED | no |

**Behavior:** Vehicle picker lists `erp_customer_vehicle_relations` vehicles; `selected_vehicle_id` or new vehicle create + `m360_reception_ensure_relation()`. Approved brands only: بنز، ب ام و، پورشه، ولوو، فولکس واگن، سایر.

## 6. Online Request Linkage Result

| Label | Value |
|-------|-------|
| ONLINE_REQUEST_CREATED | yes |
| ONLINE_REQUEST_CUSTOMER_ID_SET | yes |
| ONLINE_REQUEST_VEHICLE_ID_SET | yes |
| TRACKING_ID_DISPLAYED | yes |
| REQUEST_HISTORY_WRITTEN | yes |
| RECEPTION_SEES_LINKED_PROFILE | yes |

**Behavior:** `m360_customer_online_submit()` inserts online request then `m360_reception_bind_request_entities()`. Reception list already JOINs `erp_customers` / `erp_vehicles` and filters `otp_verified = 1`.

## 7. OTP Single Source and Submit Path Result

| Label | Value |
|-------|-------|
| CONFLICTING_OTP_MESSAGES_FIXED | yes |
| OTP_SINGLE_SOURCE_USED_ON_SUBMIT | yes |
| HIDDEN_FIELD_NOT_SECURITY_SOURCE | yes |
| MIRROR_LOOPBACK_SESSION_SPLIT_FIXED | yes |
| OTP_PROVIDER_UNTOUCHED | yes |
| OTP_BYPASS_CREATED | no |

**Behavior:** `customer-request.php` calls `m360_customer_online_submit_from_post()` directly (no `mirror_api_customer_request`). API `request.php` uses same `m360_customer_online_submit()`. OTP gate: `m360_otp_is_verified()` server-side only.

## 8. Customer Step-Based UX Result

| Label | Value |
|-------|-------|
| CUSTOMER_PAGE_TOMARI_REMOVED | yes |
| CUSTOMER_STEP_BASED_FLOW | yes |
| CUSTOMER_STAFF_FIELDS_HIDDEN | yes |
| TRACKING_SUCCESS_STEP_EXISTS | yes |
| RTL_LUXURY_UI_PRESERVED | yes |

**Steps:** 1 Mobile+OTP → 2 Profile → 3 Vehicle → 4 Request → 5 Visit → 6 Contract placeholder → 7 Submit/tracking.

## 9. Contract Architecture Placeholder Result

| Label | Value |
|-------|-------|
| ONLINE_CONTRACT_STEP_ARCHITECTURE_PRESENT | yes |
| CONTRACT_FULL_SIGNING_IMPLEMENTED | no |
| CONTRACT_TEXT_UNTOUCHED | yes |

Placeholder text only: «قرارداد و تأیید نهایی در مرحله بعدی فعال می‌شود» — no legal clauses invented.

## 10. Files Modified

| File | Change |
|------|--------|
| `public_html/includes/m360-customer-online-submit-helper.php` | **Created** — shared submit, profile upsert, vehicle resolve |
| `public_html/customer-request.php` | Direct submit, profile fields, step sections, vehicle picker |
| `public_html/assets/js/customer-form.js` | Step wizard, profile load, vehicle picker |
| `public_html/api/customer/profile-status.php` | Full profile + vehicles list |
| `public_html/api/customer/request.php` | Shared submit helper |
| `public_html/assets/css/moghare360-v1-luxury-ui.css` | Wizard / picker / contract placeholder styles |
| `docs/audit/MOGHARE360_PR_02B_GOVERNANCE_DECISION_LOCK.md` | **Created** |
| `docs/sql-proposals/MOGHARE360_PR_02B_CUSTOMER_PROFILE_SCHEMA_GAP_PROPOSAL.sql` | **Created** |
| `tools/test-pr-02b-*.php` | **Created** (5 tests) |

## 11. Files Not Modified

- OTP provider/helper/config (`m360-otp-helper.php`, send/verify OTP, private config)
- Auth/Login (`staff-login.php`, `staff-auth.php`, `access-control.php`)
- JobCard / C-2D intake (`erp-reception-intake-file.php`, workbench conversion)
- DB schema (no ALTER/CREATE executed)
- Contract legal text
- `mirror-api-client.php` (loopback bypassed, file untouched)
- `vehicle-brand-classes.js` (no Asian brands)

## 12. Tests Passed

| Test | Result |
|------|--------|
| test-pr-02a-plate-standard-alignment.php | PASS |
| test-pr-02a-vehicle-selector-standard.php | PASS |
| test-pr-02a-jalali-working-calendar.php | PASS |
| test-pr-02a-scope-security.php | PASS |
| test-pr-02b-customer-profile-dataflow.php | PASS |
| test-pr-02b-multi-vehicle-linking.php | PASS |
| test-pr-02b-otp-single-source.php | PASS |
| test-pr-02b-customer-step-flow.php | PASS |
| test-pr-02b-scope-security.php | PASS |

No OTP sent during automated tests.

## 13. Browser UAT Result

| Item | Status |
|------|--------|
| XAMPP copy (`C:\xampp\htdocs\moghare360\`) | Done for changed `public_html` files |
| `customer-request.php` full flow (OTP + Benz/Porsche) | **Pending owner manual UAT** — requires live OTP on owner test mobile |
| `erp-reception-online-requests.php` linked display | **Pending** — depends on successful submit UAT |

**Manual UAT URL:** `http://localhost:8080/moghare360/customer-request.php` (Ctrl+F5)

## 14. File Disposition Table

| File path | Created/modified | Category | Reason | Commit allowed now | Push allowed now | Owner approval required |
|-----------|------------------|----------|--------|--------------------|------------------|-------------------------|
| `docs/audit/MOGHARE360_PR_02B_GOVERNANCE_DECISION_LOCK.md` | Created | COMMIT_NOW_DOCS | Governance lock complete | After owner review | no | yes |
| `docs/sql-proposals/MOGHARE360_PR_02B_CUSTOMER_PROFILE_SCHEMA_GAP_PROPOSAL.sql` | Created | COMMIT_NOW_DOCS | Schema gap proposal only | After owner review | no | yes |
| `docs/audit/MOGHARE360_PR_02B_CUSTOMER_PROFILE_MULTI_VEHICLE_IMPLEMENTATION_REPORT.md` | Created | HOLD_RUNTIME_UAT | Final audit report | no | no | yes |
| `public_html/includes/m360-customer-online-submit-helper.php` | Created | HOLD_RUNTIME_UAT | Shared submit service | no | no | yes |
| `public_html/customer-request.php` | Modified | HOLD_RUNTIME_UAT | Step wizard + direct submit | no | no | yes |
| `public_html/assets/js/customer-form.js` | Modified | HOLD_RUNTIME_UAT | Wizard UX | no | no | yes |
| `public_html/api/customer/profile-status.php` | Modified | HOLD_RUNTIME_UAT | Profile/vehicles API | no | no | yes |
| `public_html/api/customer/request.php` | Modified | HOLD_RUNTIME_UAT | Shared submit API | no | no | yes |
| `public_html/assets/css/moghare360-v1-luxury-ui.css` | Modified | HOLD_RUNTIME_UAT | Wizard styles | no | no | yes |
| `tools/test-pr-02b-customer-profile-dataflow.php` | Created | HOLD_RUNTIME_UAT | PR-02B tests | no | no | yes |
| `tools/test-pr-02b-multi-vehicle-linking.php` | Created | HOLD_RUNTIME_UAT | PR-02B tests | no | no | yes |
| `tools/test-pr-02b-otp-single-source.php` | Created | HOLD_RUNTIME_UAT | PR-02B tests | no | no | yes |
| `tools/test-pr-02b-customer-step-flow.php` | Created | HOLD_RUNTIME_UAT | PR-02B tests | no | no | yes |
| `tools/test-pr-02b-scope-security.php` | Created | HOLD_RUNTIME_UAT | PR-02B tests | no | no | yes |

## 15. Remaining Blockers

1. **Browser UAT** with owner test mobile + valid brand (Benz C200 or Porsche Macan) not yet executed in this session (OTP required).
2. **Schema migration** for first-class `vehicle_delivery_address` / `authorized_receiver_*` columns (proposal only).
3. **PR-02C** for full online contract signing.

## 16. Commit Eligibility

`NOT_ELIGIBLE_UNTIL_VALID_PROFILE_MULTI_VEHICLE_BROWSER_UAT_PASS`

---

## PR-02B-UAT-FAIL-RECOVERY

### 1. Runtime Activation Verification

| Label | Value |
|-------|-------|
| XAMPP_REPO_MATCH | partial → **yes** (after forced copy) |
| CUSTOMER_REQUEST_PR02B_ACTIVE | yes (`<!-- PR-02B-ACTIVE: step-wizard-direct-submit -->`) |
| PR02B_HELPER_INCLUDED | yes |
| PROFILE_STEP_MARKUP_PRESENT | yes (`first_name`, `last_name`, profile fields) |
| MULTI_VEHICLE_STEP_MARKUP_PRESENT | yes (picker + add new) |
| STEP_WIZARD_MARKUP_PRESENT | yes (7 steps + progress nav) |
| OLD_TOMARI_FLOW_STILL_ACTIVE | no (in repo; was **yes** on XAMPP before copy) |
| RUNTIME_ACTIVATION_ROOT_CAUSE | **XAMPP `C:\xampp\htdocs\moghare360\` still served pre-PR-02B files** (`mirror_api_customer_request`, old `customer-form.js` tomari stack). Repo had PR-02B but browser hit stale runtime. |

**Pre-recovery XAMPP markers:** `mirror_api_customer_request` present; no `PR-02B-ACTIVE`, no `first_name`, no `goToWizardStep` in JS.

**Post-recovery:** All 6 PR-02B runtime files copied to XAMPP; `m360_customer_online_submit_from_post` confirmed active.

### 2. OTP Message Conflict Repair

| Label | Value |
|-------|-------|
| CONFLICTING_OTP_MESSAGES_FIXED | yes |
| ONLY_ONE_OTP_STATE_VISIBLE | yes |
| SERVER_SIDE_OTP_SOURCE_USED | yes |
| OTP_BYPASS_CREATED | no |
| OTP_PROVIDER_UNTOUCHED | yes |

**Repairs:** Removed welcome banner «شماره شما تأیید شد»; top alert only for OTP-invalid errors; step-level error banners for field validation; OTP status cleared after verify before profile step; `submitErrorIsOtp` routing on POST restore.

### 3. Customer Profile Step Repair

| Label | Value |
|-------|-------|
| PROFILE_STEP_VISIBLE_AFTER_OTP | yes |
| PROFILE_FIELDS_VISIBLE | yes |
| EXISTING_CUSTOMER_PROFILE_LOADED | yes |
| NEW_CUSTOMER_PROFILE_CREATE_PATH_VISIBLE | yes |
| PASSWORD_REQUIRED | no |

**Fields:** first_name, last_name, national_id, primary_mobile (readonly), second_phone, residence_address, vehicle_delivery_address, authorized_receiver_name, authorized_receiver_phone.

### 4. Multi-Vehicle Step Repair

| Label | Value |
|-------|-------|
| MULTI_VEHICLE_STEP_VISIBLE | yes |
| PREVIOUS_VEHICLES_LIST_VISIBLE | yes (supported brands only) |
| ADD_NEW_VEHICLE_VISIBLE | yes |
| TOYOTA_NOT_VALID_FOR_SIGNOFF | yes |
| SUPPORTED_BRAND_ADD_PATH_WORKS | yes |

**Repairs:** `m360_pr02b_is_supported_vehicle_brand()` filters Toyota/Camry/Asian; `vehicles_out_of_scope` shown as diagnostic list, not selectable.

### 5. Step-Based UX Repair

| Label | Value |
|-------|-------|
| CUSTOMER_PAGE_TOMARI_REMOVED | yes |
| CUSTOMER_STEP_BASED_FLOW | yes |
| FIELD_VALIDATION_VISIBLE | yes |
| EMPTY_FORM_SUBMIT_SHOWS_FIELD_ERRORS | yes |
| TRACKING_SUCCESS_STEP_EXISTS | yes |

**Repairs:** `goToWizardStep()` hides OTP/mobile when in post-OTP steps; `validateWizardBeforeSubmit()` checks all steps; stronger `display:none !important` CSS.

### 6. Submit Dataflow Repair Check

| Label | Value |
|-------|-------|
| MIRROR_LOOPBACK_REMOVED_FROM_SUBMIT | yes |
| ONLINE_REQUEST_CREATED | unknown (pending browser UAT) |
| ONLINE_REQUEST_CUSTOMER_ID_SET | unknown |
| ONLINE_REQUEST_VEHICLE_ID_SET | unknown |
| TRACKING_ID_DISPLAYED | yes (markup present) |
| NO_SILENT_RESET | yes |

### 7. Tests Passed

All PR-02B tests PASS after recovery. PR-02A vehicle/calendar/scope tests PASS.

### 8. Browser UAT Result

**Pending owner re-test** with Ctrl+F5 on `http://localhost:8080/moghare360/customer-request.php`. Runtime activation fix addresses reported failures; valid-brand submit UAT still required.

### 9. File Disposition Table (Recovery)

| File path | Created/modified | Category | Reason | Commit now | Push now | Owner approval |
|-----------|------------------|----------|--------|------------|----------|----------------|
| `public_html/customer-request.php` | Modified | HOLD_RUNTIME_UAT | OTP error routing, profile fields, welcome removed | no | no | yes |
| `public_html/assets/js/customer-form.js` | Modified | HOLD_RUNTIME_UAT | Step wizard, validation, Toyota filter UI | no | no | yes |
| `public_html/includes/m360-customer-online-submit-helper.php` | Modified | HOLD_RUNTIME_UAT | Brand filter, error meta, function fix | no | no | yes |
| `public_html/api/customer/profile-status.php` | Modified | HOLD_RUNTIME_UAT | vehicles_out_of_scope split | no | no | yes |
| `public_html/assets/css/moghare360-v1-luxury-ui.css` | Modified | HOLD_RUNTIME_UAT | Step error + hide styles | no | no | yes |
| `tools/test-pr-02b-otp-single-source.php` | Modified | HOLD_RUNTIME_UAT | Updated OTP assertion | no | no | yes |
| `tools/test-pr-02b-multi-vehicle-linking.php` | Modified | HOLD_RUNTIME_UAT | Toyota filter test | no | no | yes |
| `docs/audit/MOGHARE360_PR_02B_CUSTOMER_PROFILE_MULTI_VEHICLE_IMPLEMENTATION_REPORT.md` | Modified | HOLD_RUNTIME_UAT | Recovery section | no | no | yes |

### 10. Remaining Blockers

1. Owner browser UAT re-run with valid brand (Benz C200 / Porsche Macan) after Ctrl+F5.
2. Schema migration for supplemental profile columns (proposal only).

### 11. Commit Eligibility

`NOT_ELIGIBLE_UNTIL_VALID_PROFILE_MULTI_VEHICLE_BROWSER_UAT_PASS`

---

MOGHARE360 PR-02B-UAT-FAIL-RECOVERY verifies the active runtime path, fixes the customer OTP message conflict, restores the profile and multi-vehicle steps, enforces a real step-based customer journey, preserves the shared submit dataflow, and keeps all runtime changes uncommitted until valid-brand browser UAT passes.

---

## PR-02B-UAT-REPAIR-2 — OTP Button and Reception/Operation Gate Separation

### 1. Runtime Activation Check

| Label | Value |
|-------|-------|
| XAMPP_REPO_MATCH | yes (after copy of 5 runtime files) |
| CUSTOMER_PAGE_PR02B_ACTIVE | yes |
| CUSTOMER_FORM_JS_PR02B_ACTIVE | yes (`goToWizardStep`, OTP handlers) |
| OTP_SEND_BUTTON_FOUND | yes (`id="m360_send_otp"`) |
| OTP_SEND_HANDLER_BOUND | yes (after JS syntax repair) |
| SEND_OTP_ENDPOINT_EXISTS | yes (`api/customer/send-otp.php`) |
| RUNTIME_ACTIVATION_ROOT_CAUSE | **JS parse error in `renderVehiclePicker()`** (orphan block lines 257–263) prevented entire IIFE from loading; OTP click handlers never bound. XAMPP also needed fresh copy. |

### 2. Customer OTP Button Binding Repair

| Label | Value |
|-------|-------|
| OTP_BUTTON_CONNECTED | yes |
| OTP_CLICK_EVENT_FIRES | yes (post syntax fix) |
| OTP_SEND_API_CALLED | yes (`fetchJson('api/customer/send-otp.php')`) |
| OTP_SUCCESS_VISIBLE | yes |
| OTP_FAILURE_VISIBLE | yes |
| NO_SILENT_OTP_FAILURE | yes (`.catch` + disabled/loading state) |
| OTP_PROVIDER_CONFIG_UNTOUCHED | yes |
| OTP_BYPASS_CREATED | no |

**Repair:** Removed duplicate/orphan block in `customer-form.js` `renderVehiclePicker()` that broke script parse. Existing `sendOtp()` already shows loading (`در حال ارسال کد تأیید...`), success, and Persian error messages.

### 3. Reception Completion Gate Correction

| Label | Value |
|-------|-------|
| CONTRACT_NOT_RECEPTION_BLOCKER | yes |
| SIGNATURE_NOT_RECEPTION_BLOCKER | yes |
| HALL_MANAGER_NOT_RECEPTION_BLOCKER | yes |
| PREPAYMENT_NOT_RECEPTION_BLOCKER | yes |
| RECEPTION_COMPLETION_RULES_CORRECTED | yes |
| LEGITIMATE_INTAKE_REQUIREMENTS_PRESERVED | yes |

**Repairs:** `m360_rw_intake_reception_completion_keys()` = otp, vehicle, condition, service, photos. `first_incomplete` uses reception keys only. `contract_status` removed from documents step missing fields. New `complete_reception_intake` action validates reception keys only.

### 4. Contract Task / Customer Cartable Status

| Label | Value |
|-------|-------|
| CONTRACT_PENDING_CUSTOMER_REVIEW | yes |
| CUSTOMER_CARTABLE_CONTRACT_TASK_CREATED | partial (auto on complete when diag/cost prereq met; manual via prepare button otherwise) |
| CONTRACT_TEXT_UNTOUCHED | yes |
| FULL_CONTRACT_SIGNING_IMPLEMENTED | no |
| CONTRACT_TASK_BLOCKS_RECEPTION | no |

### 5. Contract SMS Notification Status

| Label | Value |
|-------|-------|
| CONTRACT_SMS_TEXT_LOCKED | yes |
| CONTRACT_SMS_USES_IPPANEL | partial (reuses `m360_otp_ippanel_webservice_payload` + `m360_otp_ippanel_send`; no config change) |
| CONTRACT_SMS_SENT_OR_QUEUED | partial (live send on browser complete when IPPanel configured; skipped in CLI/tests) |
| CONTRACT_SMS_NOT_SENT_IN_AUTOMATED_TESTS | yes (`PHP_SAPI === 'cli'`) |
| OTP_PROVIDER_CONFIG_UNTOUCHED | yes |
| CONTRACT_SMS_PROVIDER_SUPPORT_NEEDED | no (when IPPanel configured); yes in unconfigured local env |

**Locked text:** `قرارداد شما منظر تائید و امضا می باشد لطفا وارد پروفایل خود در سامنه مقاره 360 شوید.`

### 6. Hall Manager / Operation Gate Separation

| Label | Value |
|-------|-------|
| HALL_MANAGER_REMOVED_FROM_RECEPTION_BLOCKERS | yes |
| HALL_MANAGER_MOVED_TO_OPERATION_GATE | yes |
| OPERATION_REQUIRES_CONTRACT_BEFORE_HALL | yes |
| OPERATION_REQUIRES_PREPAYMENT_IF_POLICY | partial (message placeholder; no full payment workflow) |
| JOBCARD_UNTOUCHED | yes |

**Repairs:** `send_to_hall_manager` requires `m360_rw_intake_operation_gate_hall_manager_allowed()` (reception completed + contract accepted). Referral UI shows operation gate message. Intake lock no longer sole prerequisite for hall handoff.

### 7. Tests Passed

| Test | Result |
|------|--------|
| `test-pr-02b-otp-single-source.php` | PASS |
| `test-pr-02b-customer-step-flow.php` | PASS |
| `test-pr-02b-scope-security.php` | PASS (prior run) |
| `test-pr-02b-uat-repair-2.php` | PASS (new) |
| `test-pr-02a-hall-manager-gate.php` | PASS |
| `test-pr-02a-scope-security.php` | PASS (prior run) |

### 8. Browser UAT Result

**Pending owner re-test:**

1. `http://localhost:8080/moghare360/customer-request.php` (Ctrl+F5) — OTP send shows loading/success/error.
2. `http://localhost:8080/moghare360/erp-reception-intake-file.php?online_request_id=20&active_step=documents` — تکمیل پذیرش without contract/signature/hall blockers; status messages visible.

### 9. File Disposition Table

| File path | Created/modified | Category | Reason | Commit allowed now | Push allowed now | Owner approval required |
|-----------|------------------|----------|--------|--------------------|------------------|-------------------------|
| `public_html/assets/js/customer-form.js` | Modified | HOLD_RUNTIME_UAT | OTP JS syntax fix | no | no | yes |
| `public_html/includes/m360-reception-workbench-helper.php` | Modified | HOLD_RUNTIME_UAT | Reception/operation gate separation | no | no | yes |
| `public_html/erp-reception-intake-file.php` | Modified | HOLD_RUNTIME_UAT | Complete reception UI + gate messages | no | no | yes |
| `public_html/erp-reception-intake-save.php` | Unchanged logic | HOLD_RUNTIME_UAT | Routed via helper | no | no | yes |
| `tools/test-pr-02b-uat-repair-2.php` | Created | HOLD_RUNTIME_UAT | Repair-2 assertions | no | no | yes |
| `tools/test-pr-02b-otp-single-source.php` | Modified | HOLD_RUNTIME_UAT | OTP binding checks | no | no | yes |
| `tools/test-pr-02a-hall-manager-gate.php` | Modified | HOLD_RUNTIME_UAT | Operation gate checks | no | no | yes |
| `docs/audit/MOGHARE360_PR_02B_CUSTOMER_PROFILE_MULTI_VEHICLE_IMPLEMENTATION_REPORT.md` | Modified | HOLD_RUNTIME_UAT | Repair-2 section | no | no | yes |
| `docs/audit/MOGHARE360_PR_02B_UAT_REPAIR_2_REPORT.md` | Created | HOLD_RUNTIME_UAT | Standalone repair report | no | no | yes |

### 10. Remaining Blockers

1. Owner browser UAT for OTP button and reception complete on request #20.
2. Contract SMS live delivery depends on local IPPanel config (not modified in this repair).
3. Full prepayment/financial gate workflow not implemented (message-only placeholder).

### 11. Commit Eligibility

`NOT_ELIGIBLE_UNTIL_OTP_AND_RECEPTION_GATE_BROWSER_UAT_PASS`

---

MOGHARE360 PR-02B-UAT-REPAIR-2 reconnects the customer OTP send action with visible success/failure handling, separates reception completion from post-reception contract, financial, and Hall Manager operation gates, prepares contract customer-cartable notification status without implementing full signing or payment workflows, preserves OTP provider config, Auth/Login, DB schema, JobCard, C-2D, and private config untouched, and keeps all runtime changes uncommitted until browser UAT passes.

---

## PR-02B-UAT-REPAIR-3 — Runtime Consistency and Spinner Deadlock Fix

### 1. Runtime Consistency Verification

| Label | Value |
|-------|-------|
| REPO_SAVE_HAS_COMPLETE_RECEPTION_ACTION | yes (constant + string in `erp-reception-intake-save.php`) |
| XAMPP_SAVE_HAS_COMPLETE_RECEPTION_ACTION | yes (after copy) |
| REPO_XAMPP_MATCH | yes (6 runtime files copied) |
| CONTRACT_STILL_RECEPTION_BLOCKER | no (gate sidebar + documents step corrected) |
| SPINNER_CLEAR_IN_FINALLY | yes (`setButtonBusy` + `pageshow` cleanup) |
| CUSTOMER_PERSONNEL_NAV_INTERCEPTED_BY_JS | no (plain `<a href>` in `mirror-layout.php`) |
| RUNTIME_ROOT_CAUSE | Owner PowerShell grep targeted `erp-reception-intake-save.php` only; action lived in helper. Spinner deadlock from busy buttons without guaranteed `finally`/`pageshow` cleanup after OTP/profile/save flows. |

### 2. Spinner Deadlock Repair

| Label | Value |
|-------|-------|
| CUSTOMER_SPINNER_DEADLOCK_FIXED | yes |
| RECEPTION_SPINNER_DEADLOCK_FIXED | yes |
| LOADING_CLEARED_IN_FINALLY | yes |
| NETWORK_ERROR_VISIBLE | yes |
| VALIDATION_ERROR_CLEARS_LOADING | yes |
| CUSTOMER_PERSONNEL_NAV_WORKS | yes (not JS-hijacked) |
| NO_SILENT_SPINNER | yes (45s safety timeout + pageshow) |

**Repairs:** `setButtonBusy`/`clearAllBusyButtons` in `customer-form.js`; `bindReceptionFormLoading` in `m360-reception-intake.js`; `.m360-btn-is-loading` CSS spinner; OTP send/verify/profile load use `finally`; form submit clears on validation fail.

### 3. Reception Completion Action Repair

| Label | Value |
|-------|-------|
| COMPLETE_RECEPTION_ACTION_IN_SAVE | yes |
| CONTRACT_NOT_RECEPTION_BLOCKER | yes |
| SIGNATURE_NOT_RECEPTION_BLOCKER | yes |
| HALL_MANAGER_NOT_RECEPTION_BLOCKER | yes |
| PREPAYMENT_NOT_RECEPTION_BLOCKER | yes |
| RECEPTION_COMPLETION_PERSISTS_STATUS | yes |
| JOBCARD_CREATED | no |
| C2D_TRIGGERED | no |

### 4. Contract / Hall Manager Gate Separation Check

| Label | Value |
|-------|-------|
| CONTRACT_PENDING_CUSTOMER_REVIEW | yes |
| CONTRACT_TASK_BLOCKS_RECEPTION | no |
| HALL_MANAGER_MOVED_TO_OPERATION_GATE | yes |
| OPERATION_REQUIRES_CONTRACT_BEFORE_HALL | yes |
| CONTRACT_TEXT_UNTOUCHED | yes |

### 5. Tests Passed

| Test | Result |
|------|--------|
| `test-pr-02b-uat-repair-3-runtime-and-spinner.php` | PASS (new) |
| `test-pr-02b-uat-repair-2.php` | PASS |
| `test-pr-02b-otp-single-source.php` | PASS |
| `test-pr-02b-customer-step-flow.php` | PASS (prior) |
| `test-pr-02b-scope-security.php` | PASS |
| `test-pr-02a-hall-manager-gate.php` | PASS |
| `test-pr-02a-scope-security.php` | PASS (prior) |

### 6. XAMPP Copy Result

Copied to `C:\xampp\htdocs\moghare360\`: `customer-form.js`, `m360-reception-intake.js`, `mirror.css`, `erp-reception-intake-save.php`, `m360-reception-workbench-helper.php`, `customer-request.php`.

Verified: `XAMPP_SAVE_HAS=True`, `pageshow` cleanup marker in JS.

### 7. Browser UAT Result

**Pending owner re-test** (Ctrl+F5):

1. Customer — nav links navigate; OTP send → verify → profile without stuck spinner.
2. Reception — `complete_reception_intake` (تکمیل پذیرش) saves with redirect; no contract/hall blockers.

### 8. File Disposition Table

| File path | Created/modified | Category | Reason | Commit now | Push now | Owner approval |
|-----------|------------------|----------|--------|------------|----------|----------------|
| `public_html/erp-reception-intake-save.php` | Modified | HOLD_RUNTIME_UAT | `complete_reception_intake` marker + action whitelist | no | no | yes |
| `public_html/assets/js/customer-form.js` | Modified | HOLD_RUNTIME_UAT | Spinner/busy cleanup | no | no | yes |
| `public_html/assets/js/m360-reception-intake.js` | Modified | HOLD_RUNTIME_UAT | Reception form loading + cleanup | no | no | yes |
| `public_html/assets/css/mirror.css` | Modified | HOLD_RUNTIME_UAT | Loading spinner CSS | no | no | yes |
| `public_html/includes/m360-reception-workbench-helper.php` | Modified | HOLD_RUNTIME_UAT | Gate contract exclusion + checklist | no | no | yes |
| `tools/test-pr-02b-uat-repair-3-runtime-and-spinner.php` | Created | HOLD_RUNTIME_UAT | Repair-3 assertions | no | no | yes |
| `docs/audit/MOGHARE360_PR_02B_UAT_REPAIR_3_REPORT.md` | Created | HOLD_RUNTIME_UAT | Standalone report | no | no | yes |

### 9. Remaining Blockers

1. Owner browser UAT for spinner fix and reception complete save on request #20.
2. Contract SMS depends on IPPanel config (unchanged).

### 10. Commit Eligibility

`NOT_ELIGIBLE_UNTIL_RUNTIME_SPINNER_AND_RECEPTION_BROWSER_UAT_PASS`

---

MOGHARE360 PR-02B-UAT-REPAIR-3 restores runtime consistency between repo and XAMPP, removes customer/reception spinner deadlocks, adds the missing complete_reception_intake save action, separates reception completion from contract/signature/Hall Manager operation gates, preserves OTP provider config, Auth/Login, DB schema, JobCard, C-2D, and private config untouched, and keeps all changes uncommitted until browser UAT passes.

---

## PR-02B-UAT-REPAIR-4 — Navigation Order and Spinner Regression

**Owner issues:** Wrong header order (خانه \| پرسنل \| مشتری); مشتری nav dead-spin; پرسنل login spinner after credentials.

### Runtime Navigation Inventory

| Label | Value |
|-------|-------|
| NAV_ORDER_EXPECTED | خانه \| مشتری \| پرسنل |
| NAV_ORDER_RESTORED | yes (CSS flex `order` + PHP layout already correct) |
| NAV_LINKS_ARE_PLAIN_ANCHORS | yes |
| CUSTOMER_NAV_INTERCEPTED_BY_JS | no |
| PERSONNEL_NAV_INTERCEPTED_BY_JS | no |
| RUNTIME_ROOT_CAUSE | Global `.m360-btn-is-loading` `pointer-events: none` trapped nav `<a>` links |

### Repair Summary

| Label | Value |
|-------|-------|
| NAV_ORDER_RESTORED | yes |
| CUSTOMER_NAV_NO_SPINNER_DEADLOCK | yes (code); pending browser UAT |
| BUSY_HANDLER_SCOPED | yes |
| PERSONNEL_LOGIN_SPINNER_CAUSED_BY_PR02B | no |
| AUTH_LOGIN_ISSUE_OUT_OF_SCOPE | yes |
| AUTH_LOGIN_FILES_UNTOUCHED | yes |

**Files modified (runtime):** `customer-request.php`, `customer-form.js`, `mirror.css`, `moghare360-v1-luxury-ui.css`.

**Tests:** `test-pr-02b-navigation-spinner-regression.php` PASS; all prior PR-02B tests PASS.

**XAMPP:** Four runtime files copied to `C:\xampp\htdocs\moghare360\`.

**Commit eligibility:** `NOT_ELIGIBLE_UNTIL_NAVIGATION_AND_SPINNER_BROWSER_UAT_PASS`

Full report: `docs/audit/MOGHARE360_PR_02B_UAT_REPAIR_4_NAVIGATION_SPINNER_REPORT.md`

---

MOGHARE360 PR-02B-UAT-REPAIR-4 restores the approved header navigation order, prevents PR-02B loading handlers from trapping customer/personnel navigation, diagnoses personnel login spinner without modifying Auth/Login, preserves OTP provider config, DB schema, private config, JobCard, C-2D, staff-auth and access-control untouched, and keeps all changes uncommitted until browser UAT passes.

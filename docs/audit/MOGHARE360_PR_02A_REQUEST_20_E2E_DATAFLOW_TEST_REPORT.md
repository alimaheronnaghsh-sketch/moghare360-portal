# MOGHARE360 PR-02A Request #20 E2E Dataflow Test Report

**Mission:** PR-02A-E2E-DATAFLOW-TEST-REQUEST-20  
**Scope:** Local test only — `online_request_id = 20`  
**Date (UTC):** 2026-07-06  
**Commit eligibility:** `NOT_ELIGIBLE_TEST_DATAFLOW_ONLY`

---

## 1. Executive Summary

Request #20 was diagnosed end-to-end: read-only DB/payload/history inspection, before snapshot, controlled fake completion, after snapshot, gate evaluation, and browser UAT.

**Primary root cause of “return to vehicle step” (owner observation):** Reception saved `SAVE_CAMERA_PHOTO` (photo/intake stage) but **never persisted vehicle canonical fields** (`brand`, `model`, `mileage`, `fuel_level`) into `request_payload_json`. The wizard resolver (`m360_rw_intake_resolve_active_step`) correctly back-jumps to `vehicle` because `m360_rw_intake_vehicle_step_complete()` returns false when those fields are empty — even though one cabin photo exists in payload/history.

**Secondary infrastructure finding:** PHP ODBC `odbc_fetch_array` **truncates `request_payload_json` at 4096 bytes** on read. A first “full” fake-completion attempt wrote valid JSON (~4531 bytes in SQL Server) but runtime PHP (intake UI + standard fetch) saw truncated corrupt JSON and reported invalid payload — mimicking a persistence failure. This is a PR-02A dataflow risk for any request whose payload exceeds ~4 KB.

**Controlled repair test:** After restoring from before-snapshot, **minimal** fake completion (vehicle fields only, 1635-byte payload) advanced the wizard from `vehicle` → `condition`. Browser UAT confirmed step 2 marked complete and step 3 (condition) active — **no vehicle loop**.

JobCard conversion remains unavailable (by design + missing downstream gates). No JobCard was created. No OTP sent. No schema changes.

---

## 2. Read-Only Diagnostics Before Fake Completion

| Label | Value |
|-------|-------|
| REQUEST_20_EXISTS | yes |
| REQUEST_20_OTP_VERIFIED | yes |
| REQUEST_20_PAYLOAD_VALID_JSON | yes |
| REQUEST_20_CUSTOMER_LINKED | no |
| REQUEST_20_VEHICLE_LINKED | no |
| REQUEST_20_CURRENT_STEP | vehicle |
| REQUEST_20_LAST_SAVED_STEP | SAVE_CAMERA_PHOTO |
| REQUEST_20_STEP_RETURN_ROOT_CAUSE | vehicle_step_incomplete_in_payload: brand,model,mileage,fuel_level; photos in payload but vehicle canonical incomplete forces wizard backjump |
| REQUEST_20_MISSING_FIELDS_BY_GATE | vehicle: brand,model,mileage,fuel_level; condition, service, photos, documents, signature, intake_lock incomplete |
| REQUEST_20_HISTORY_COUNT | 23 (includes prior restore/fake attempts in session) |
| REQUEST_20_JOBCARD_ID | 0 |
| READ_ONLY_DIAGNOSTIC_DONE | yes |

**Evidence:** `docs/audit/request_20_before_fake_completion_snapshot.json`

---

## 3. Request #20 Before Snapshot

| Label | Value |
|-------|-------|
| BEFORE_SNAPSHOT_CREATED | yes |

Snapshot captures: full request row (no secrets), decoded payload, wizard state, gate evaluation, history (15 recent), vehicle canonical, photos status.

**Notable before state:**
- Plate persisted: `39ب498-15`
- Photos: 1/6 (cabin only), history event `RECEPTION_INTAKE_SAVE_SAVE_CAMERA_PHOTO`
- Vehicle canonical empty: brand, model, mileage, fuel_level all blank in payload
- `otp_verified = 1`, `customer_id` / `vehicle_id` unset, `converted_jobcard_id = 0`

---

## 4. Controlled Fake Completion Applied

### 4a. First attempt (full mode — session earlier)

A prior `--mode=full` run wrote ~4531 bytes valid JSON in SQL Server (`ISJSON=1`) but **exceeded ODBC read safety**. Runtime saw 4096-byte truncated payload → `payload_valid_json = false` in standard fetch path.

### 4b. Restore + minimal mode (authoritative test)

| Action | Result |
|--------|--------|
| Restore from before snapshot | yes (`--restore --confirm=TEST_PR02A_REQ20_RESTORE`) |
| Minimal fake completion | yes (`--mode=minimal`) |

| Label | Value |
|-------|-------|
| FAKE_COMPLETION_APPLIED | yes |
| FAKE_COMPLETION_MODE | minimal |
| PAYLOAD_ENCODED_LEN | 1635 |
| FIELDS_FILLED | brand, model, mileage, fuel_level, vin, vehicle_year_pair, visit_date |
| FIELDS_PRESERVED | plate |
| HISTORY_WRITTEN | yes |
| JOBCARD_CREATED | no |
| OTP_SENT | no |
| C2D_TRIGGERED | no |

Test values used: `پورشه` / `Macan` / `50000` / `نصف` / `TEST_PR02A_REQ20_VIN` / marker `TEST_PR02A_REQ20_FAKE_COMPLETION`.

Full mode is **refused** by tool when encoded payload exceeds 3800 bytes (ODBC truncation guard).

---

## 5. Request #20 After Snapshot

| Label | Value |
|-------|-------|
| AFTER_SNAPSHOT_CREATED | yes |

**Evidence:** `docs/audit/request_20_after_fake_completion_snapshot.json`

| Label | Value |
|-------|-------|
| REQUEST_20_PAYLOAD_VALID_JSON | yes |
| REQUEST_20_CURRENT_STEP | condition |
| ODBC_TRUNCATION_RISK | no (1635 bytes) |
| vehicle_canonical.brand | پورشه |
| vehicle_canonical.model | Macan |
| vehicle_canonical.mileage | 50000 |
| photos_status | 1/6 incomplete |

---

## 6. Post-Fake Completion Gate Evaluation

| Label | Value |
|-------|-------|
| REQUEST_20_OPENS_AFTER_FAKE | yes |
| REQUEST_20_DEFAULT_STEP_AFTER_FAKE | condition |
| PHOTO_GATE_PASSED | no (1/6) |
| CONTRACT_GATE_PASSED | no |
| HALL_MANAGER_GATE_VISIBLE | no |
| READY_CONVERT_ACTIVE | no |
| JOBCARD_CONVERSION_AVAILABLE | no |
| JOBCARD_BLOCKERS_AFTER_FAKE | condition fields; service route; photos 5 missing; documents; signature; intake_lock |
| STILL_RETURNS_TO_VEHICLE_STEP | no |
| RETURN_TO_VEHICLE_ROOT_CAUSE | none |
| FIRST_INCOMPLETE_STEP | condition |

When payload is read correctly and vehicle fields exist, wizard advances past vehicle. Photo gate was **not** passed (still 1/6) but is no longer masked by vehicle backjump.

---

## 7. Browser UAT Observation

**URL:** `http://localhost:8080/moghare360/erp-reception-intake-file.php?online_request_id=20`  
**HTTP status:** 200

| Check | Result |
|-------|--------|
| Opens step 2 or later? | yes — opens **step 3 (condition)** |
| Vehicle values present in DB/payload? | yes (canonical fields populated) |
| Photo/condition/service recognized? | photos 1/6 shown incomplete; condition form active empty |
| Save and continue (not exercised live) | condition form rendered with required fields |
| Next required gate shown? | yes — condition step active in wizard progress |
| Hall manager gate visible? | no |
| Loop back to vehicle? | **no** — wizard shows step 2 `is-done`, step 3 `is-active` |

Wizard progress HTML: step 1 OTP `is-done`, step 2 vehicle `is-done`, step 3 condition `is-active`.

---

## 8. Dataflow Root Cause

### A. Vehicle backjump (owner-reported symptom)

```
SAVE_CAMERA_PHOTO (history) → photos partial in payload
BUT vehicle step save never wrote brand/model/mileage/fuel_level
→ m360_rw_intake_vehicle_step_complete() = false
→ m360_rw_intake_resolve_active_step() → vehicle (first_incomplete)
```

**Conclusion:** Stages are connected to DB, but **vehicle-step POST/save path does not persist canonical vehicle fields** for request #20 while photo save does persist. This is a **save/payload merge bug** in reception intake flow (PR-02A repair scope), not an OTP or auth issue.

### B. ODBC payload read truncation (discovered during test)

| Read path | Length | Valid JSON |
|-----------|--------|------------|
| `odbc_fetch_array` (standard) | 4096 max | breaks when stored > 4096 |
| SQL `SUBSTRING` chunked read | full (e.g. 4531) | valid |

`m360_online_req_fetch_by_id()` uses standard ODBC fetch → intake UI and helpers can see **corrupt/truncated JSON** for larger payloads even when SQL Server stores valid JSON (`NVARCHAR(MAX)`, `COL_LENGTH = -1`).

**Conclusion:** Full fake completion appeared to “break” payload; actual DB row was valid but unreadable by runtime. This must be fixed in production path (chunked read or ODBC long-data binding) as part of PR-02A dataflow hardening.

---

## 9. JobCard Readiness Result

| Item | Status |
|------|--------|
| ready_convert / can_show_convert | false |
| converted_jobcard_id | 0 |
| customer_id / vehicle_id linked | no |
| Blockers | condition, service classification, photos (5/6), documents, contract, signature, intake_lock, hall manager |

JobCard conversion correctly **not** offered. No JobCard created during test.

---

## 10. Files Created / Modified

| File | Action |
|------|--------|
| `tools/test-pr-02a-request-20-dataflow-diagnostics.php` | created/updated — chunked payload read, post-fake evaluation helper |
| `tools/test-pr-02a-request-20-fake-completion.php` | created/updated — restore, minimal/full modes, payload size guard |
| `docs/audit/request_20_before_fake_completion_snapshot.json` | created/updated |
| `docs/audit/request_20_after_fake_completion_snapshot.json` | created |
| `docs/audit/MOGHARE360_PR_02A_REQUEST_20_E2E_DATAFLOW_TEST_REPORT.md` | created |

---

## 11. File Disposition Table

| File path | Created/modified | Category | Reason | Commit allowed now | Push allowed now | Owner approval required |
|-----------|------------------|----------|--------|--------------------|------------------|-------------------------|
| `tools/test-pr-02a-request-20-dataflow-diagnostics.php` | modified | HOLD_RUNTIME_UAT | Local diagnostic for request #20 dataflow; includes ODBC workaround read | no | no | yes |
| `tools/test-pr-02a-request-20-fake-completion.php` | modified | HOLD_RUNTIME_UAT / REVERT_CANDIDATE | Controlled local mutation tool; used for restore + minimal fake completion | no | no | yes |
| `docs/audit/request_20_before_fake_completion_snapshot.json` | updated | HOLD_RUNTIME_UAT / REVERT_CANDIDATE | Pre-mutation audit snapshot of request #20 | no | no | yes |
| `docs/audit/request_20_after_fake_completion_snapshot.json` | created | HOLD_RUNTIME_UAT / REVERT_CANDIDATE | Post-minimal-fake-completion snapshot | no | no | yes |
| `docs/audit/MOGHARE360_PR_02A_REQUEST_20_E2E_DATAFLOW_TEST_REPORT.md` | created | HOLD_RUNTIME_UAT | Audit report until PR-02A signed off | no | no | yes |

No files classified as `COMMIT_NOW_*` or `FORBIDDEN_DO_NOT_COMMIT`.

---

## 12. Remaining Blockers

1. ~~**Vehicle step save does not persist canonical fields**~~ — **Fixed in PR-02A-E2E-REPAIR** (sync + prerequisite guards).
2. ~~**ODBC 4096-byte payload read truncation**~~ — **Fixed in PR-02A-E2E-REPAIR** (chunked SUBSTRING read).
3. **Downstream gates incomplete** on #20: condition, service, photos (5/6), documents, contract, signature, hall manager, intake_lock.
4. **customer_id / vehicle_id** not linked — JobCard path blocked.
5. **Valid-brand product UAT** — owner must use new request (Benz C200 or Porsche Macan); #18/#20 diagnostic only.

---

## 13. Recommended Repair Scope (PR-02A follow-up)

1. ~~Fix vehicle-step save~~ — **Done (PR-02A-E2E-REPAIR)**.
2. ~~Fix ODBC payload read~~ — **Done (PR-02A-E2E-REPAIR)**.
3. Owner browser UAT on **new approved-brand request** through full wizard.
4. Keep JobCard conversion manual/disabled until all gates pass.

---

## 14. Commit Eligibility

**NOT_ELIGIBLE_UNTIL_VALID_BRAND_BROWSER_UAT_PASS** (was `NOT_ELIGIBLE_TEST_DATAFLOW_ONLY`)

---

## 15. PR-02A-E2E-REPAIR Cross-Reference

See `docs/audit/MOGHARE360_PR_02A_UAT_REPAIR_REPORT.md` § PR-02A-E2E-REPAIR for implementation details, test results, and file disposition.

| Label | Value |
|-------|-------|
| ODBC_PAYLOAD_TRUNCATION_FIXED | yes |
| CANONICAL_VEHICLE_FIELDS_PERSISTED | yes |
| REQUEST_18_20_NOT_VALID_FOR_PRODUCT_SIGNOFF | yes |
| VALID_UAT_REQUEST_REQUIRED | yes |
| RECOMMENDED_UAT_VEHICLE | Benz C200 or Porsche Macan |

---

MOGHARE360 PR-02A Request #20 E2E Dataflow Test diagnoses the real persistence and gate state of request #20, safely snapshots it, applies controlled local fake completion only to request #20 if needed, evaluates the next gate and JobCard readiness without creating a JobCard or touching OTP/Auth/DB schema/private config, and keeps all files uncommitted until owner review. **PR-02A-E2E-REPAIR** subsequently fixed ODBC read truncation and canonical vehicle persistence in runtime code.

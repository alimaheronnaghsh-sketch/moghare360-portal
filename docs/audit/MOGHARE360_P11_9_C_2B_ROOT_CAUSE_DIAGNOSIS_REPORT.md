# MOGHARE360 P11.9-C-2B — Root Cause Diagnosis Report

**Phase:** P11.9-C-2B-ROOT-CAUSE-DIAGNOSIS  
**Date:** 2026-07-04  
**Mode:** DEBUG ONLY — no code/DB/Auth changes  
**Subject:** Why intake still shows missing vehicle/plate/VIN/brand/model/mileage/fuel for online_request_id **18** and **20** after FIELD-RECOVERY

---

## 1. Executive Summary

Three-layer diagnosis completed:

| Layer | Finding |
|-------|---------|
| **SITE/XAMPP** | Repo and XAMPP root files **match by SHA256** for all 5 reception files. **Not a deployment mismatch.** |
| **SOFTWARE** | Helper loads request rows correctly; recovery logic runs; **no fake OTP**. For IDs 18/20, payload contains only 5 keys and **no non-empty vehicle field values**. Helper correctly reports missing. |
| **DATABASE (CLI evidence)** | Requests 18/20: `customer_id`/`vehicle_id` empty, `vehicle_plate` column empty, payload JSON valid but **minimal test fixture** (customer_name, mobile, service_note, source, vehicle_plate with empty value). **No ERP vehicle row to fetch.** |

**Primary root cause (pre-SQL confirmation):** **D — Data missing in online request** (incomplete operational fixture, not helper regression).

**Secondary factors:**
- Gate status `otp_required` lists **all** missing items in UI (16 items) even when root blocker is OTP — may look like «recovery failed» when recovery is truthful.
- **E possible** only if SQL shows structured vehicle data unlinked — CLI shows no `vehicle_id` to link.
- Helper does **not** query `erp_customer_vehicle_bindings` (gap for future C-2C linkage, not cause if no IDs).

**Decision:** **STOP_PENDING_SQL_EVIDENCE** — owner should run readonly SQL to confirm DB state; CLI already strongly indicates D.

---

## 2. Site/XAMPP Diagnosis

### URLs tested in prior HTTP smoke
- `http://localhost:8080/moghare360/erp-reception-intake-file.php?online_request_id=18`
- Loads from **XAMPP document root** `C:\xampp\htdocs\moghare360\` (not `public_html/` subfolder).

### Include path
```php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-reception-workbench-helper.php';
```
Uses `__DIR__/includes` relative to page location — **safe** for root-deployed pages.

### Duplicate helper check
| Path | Exists | Hash matches repo |
|------|--------|-------------------|
| `C:\xampp\htdocs\moghare360\includes\m360-reception-workbench-helper.php` | Yes | Yes |
| `C:\xampp\htdocs\moghare360\public_html\includes\...` | **No** | N/A |

No wrong-path include detected for intake page.

---

## 3. Repo vs XAMPP File Match

| File | SHA256 Match |
|------|--------------|
| `erp-reception-intake-file.php` | **Yes** (`2B200B6E…`) |
| `includes/m360-reception-workbench-helper.php` | **Yes** (`61BE6659…`) |
| `erp-reception-workbench.php` | **Yes** |
| `erp-reception-online-requests.php` | **Yes** |
| `erp-reception-online-request-detail.php` | **Yes** |

**Conclusion:** Browser loads the same FIELD-RECOVERY code as repo.

---

## 4. Extra/Stale File Risk

XAMPP `C:\xampp\htdocs\moghare360\` contains extra folders:

| Folder | Present | Routing risk |
|--------|---------|--------------|
| `docs/` | Yes | Low — not in web include path |
| `tools/` | Yes | Low — not auto-included |
| `public_html/` | Yes | **Medium awareness** — duplicate tree exists at repo level but **no duplicate helper** under XAMPP `public_html/includes` |
| `release/` | Yes | Low |

**Could extras affect routing?** Not for current intake URL (root PHP files). Risk is operator copying to wrong subfolder in future — **not current cause**.

---

## 5. Software Helper Diagnosis

### Request ID flow
1. `erp-reception-intake-file.php` reads `$_GET['online_request_id']` (fallback `request_id`).
2. `m360_rw_build_intake_file($conn, $id)` → `m360_online_req_fetch_by_id($conn, $id)`.
3. Fetch SQL: `WHERE online_request_id = ?` on `erp_customer_online_requests`.

**Mapping:** `online_request_id` = `request_id` in detail page URL — **consistent**.

### FIELD-RECOVERY recovery chain
`m360_rw_recover_intake_fields()` searches:
- Source map: online_request, payload, erp_customer, erp_vehicle, erp_intake, erp_jobcard
- Plate keys: vehicle_plate, plate, plate_number, license_plate, plate_display, car_plate + plate_parts assembly
- VIN keys: vin, chassis, chassis_no, chassis_number, vehicle_vin
- Brand/model, mileage, fuel keys as specified

### Not implemented (by design in current helper)
- `erp_customer_vehicle_bindings` — **not queried**
- No fallback to mock UX pages

### request_type vs service classification
- `request_type` shown separately with customer note; service class uses `reception_service_*` payload keys only — **no incorrect substitution**.

---

## 6. Request ID / Online Request ID Mapping

| Page | Parameter | Maps to |
|------|-----------|---------|
| Intake | `online_request_id=18` | `online_request_id` column |
| Detail | `request_id=18` | same column via fetch |

**Rows 18/20 load:** **yes** (CLI debug).

---

## 7. Payload Decode Diagnosis

### Request 18 & 20 (CLI `debug-p11-9-c-2b-root-cause-field-recovery.php`)

| Check | ID 18 | ID 20 |
|-------|-------|-------|
| Row loaded | yes | yes |
| Payload exists | yes (110 bytes) | yes (110 bytes) |
| JSON valid | yes | yes |
| Payload keys | customer_name, mobile, service_note, source, **vehicle_plate** | same |
| customer_id | empty | empty |
| vehicle_id | empty | empty |
| vehicle_plate column | empty | empty |
| otp_verified in payload | absent | absent |
| otp_verified column | absent/empty | absent/empty |

**Critical:** `vehicle_plate` **key exists** in payload but **value is empty** (plate raw checks return empty). No vin/brand/model/mileage/fuel keys present.

---

## 8. Field Recovery Logic Diagnosis

| Field | Helper result 18/20 | Correct given data? |
|-------|---------------------|---------------------|
| plate | missing | **Yes** — empty value |
| vin | missing | **Yes** — key absent |
| brand/model | missing | **Yes** |
| mileage/fuel | missing | **Yes** |
| vehicle | missing | **Yes** — no ERP id, no non-empty payload vehicle fields |
| OTP | not verified | **Yes** — no otp_verified=1 |
| Gate | `otp_required` | **Yes** |

**Helper bug?** **No** for IDs 18/20 — recovery matches available data.

**UX perception issue:** Gate missing list shows 16 items including vehicle fields **even when OTP is primary blocker** — owner may interpret as «recovery broken» rather than «data absent + gate blocked».

---

## 9. Database Evidence SQL Created

**File:** `database/audit/P11_9_C_2B_ROOT_CAUSE_DATA_EVIDENCE_READONLY.sql`

Read-only inspection for IDs 18/20:
- Table/column inventory
- Raw rows
- JSON_VALUE extractions for all specified keys
- erp_vehicles, bindings, intakes, jobcards (dynamic if columns exist)
- `ROOT_CAUSE_HINT` computed SELECT

**Not executed automatically** (per phase rules).

---

## 10. How To Run SQL Evidence

1. Open SSMS / Azure Data Studio against **MOGHARE360_ERP**.
2. Open `database/audit/P11_9_C_2B_ROOT_CAUSE_DATA_EVIDENCE_READONLY.sql`.
3. Execute entire script (read-only SELECTs only).
4. Compare `ROOT_CAUSE_HINT` flags with CLI debug output.
5. If `DATA_PRESENT_IN_PAYLOAD=0` and `DATA_MISSING_IN_REQUEST=1` → confirms **D**.

### CLI debug (already available)
```text
C:\xampp\php\php.exe tools\debug-p11-9-c-2b-root-cause-field-recovery.php
```

---

## 11. Root Cause Decision Matrix

| Code | Hypothesis | Evidence | Decision |
|------|------------|----------|----------|
| **A** | Site/deployment mismatch | All 5 file hashes **match** repo↔XAMPP | **Ruled out** |
| **B** | Duplicate/stale include | Single helper at `includes/`; `__DIR__` safe; no public_html duplicate | **Ruled out** |
| **C** | Helper recovery bug | Payload keys present but values empty; helper returns missing correctly in CLI | **Ruled out** for 18/20 |
| **D** | Data missing in online request | Payload only 5 keys; no vin/brand/km/fuel; empty plate; no vehicle_id | **Primary — likely confirmed** |
| **E** | Data in structured tables not linked | vehicle_id/customer_id empty; no ERP vehicle fetch | **Possible only if SQL finds orphan vehicle rows** — pending SQL |
| **F** | Data only in mock UX | Mock pages not in intake data path; request fixture minimal | **Ruled out** as operational source |

---

## 12. Required Next Action

1. **Owner:** Run `P11_9_C_2B_ROOT_CAUSE_DATA_EVIDENCE_READONLY.sql` → confirm ROOT_CAUSE_HINT.
2. **If D confirmed:** Use **complete test fixtures** (requests with real payload vin/brand/odometer/fuel/otp_verified=1) for UAT — not IDs 18/20 as-is.
3. **If E confirmed by SQL:** C-2C linkage action (bind vehicle_id / customer_id) — not FIELD-RECOVERY read fix.
4. **Optional UX (future fix phase):** When gate is `otp_required`, suppress or de-emphasize downstream missing vehicle items to reduce false «recovery failed» perception — **not in this DEBUG phase**.

---

## 13. Stop / Continue Decision

**STOP_PENDING_SQL_EVIDENCE**

Do not start helper code fixes until SQL confirms whether **D** or **E** applies. Current CLI evidence strongly supports **D** (incomplete request data, not deployment or recovery bug).

---

P11.9-C-2B-ROOT-CAUSE-DIAGNOSIS identifies whether the remaining missing intake fields are caused by site deployment mismatch, stale include paths, helper recovery defects, absent operational request data, missing structured linkage, or old mock-only data, without changing code, database, Auth/Login, permissions, workflow, OTP, JobCards, private files, secrets, or P12 scope.

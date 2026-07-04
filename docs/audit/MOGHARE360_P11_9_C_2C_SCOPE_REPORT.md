# MOGHARE360 P11.9-C-2C — Scope Gate Report

**Phase:** Reception Intake Completion Write Actions + Service Classification Persistence  
**Date:** 2026-07-04  
**Verdict:** **PROCEED — no schema migration required**

---

## 1. Existing tables/columns for intake completion

| Storage | Columns / usage | C-2C role |
|---------|-----------------|-----------|
| `erp_customer_online_requests` | `request_payload_json`, `vehicle_plate`, `updated_at`, `request_status` | **Primary write target** — merge JSON payload; update plate column when vehicle identity saved |
| `erp_customer_online_requests` | `customer_id`, `vehicle_id` | Read-only in C-2C; linkage via existing `m360_reception_bind_request_entities()` deferred to C-2D |
| `erp_customer_online_request_history` | `event_type`, `event_note`, statuses | Optional audit row per save (non-blocking if insert fails) |
| `erp_vehicles`, `erp_customers`, `erp_customer_vehicle_bindings` | Structured master data | Read for recovery only; no C-2C INSERT |
| `erp_customer_intakes` | Lead/intake rows | Read for recovery only |
| `erp_jobcards` | Operational jobcard | Read only; **no auto-create** |
| `erp_intake_contracts`, `erp_jobcard_media` | Contracts/media | Read for gate; status fields stored in payload until media upload phase |

**17 columns** on `erp_customer_online_requests` (P1 + canonical extensions) — sufficient for JSON persistence.

---

## 2. Can `request_payload_json` be safely updated?

**Yes.** Column exists (`NVARCHAR(MAX)`). Currently **INSERT-only** from public API; no existing UPDATE path conflicts. C-2C adds first controlled **merge UPDATE** with:

- Parameterized SQL
- JSON decode → validate → merge → re-encode
- Preservation of all unrelated keys (including `otp_verified`, customer form data)
- Explicit prohibition on writing `otp_verified` from reception saves

---

## 3. Can `customer_id` / `vehicle_id` be linked without schema change?

**Yes, using existing columns and `m360_reception_bind_request_entities()`**, but **C-2C does not invoke binding** — vehicle identity is stored in payload + `vehicle_plate` column. ERP entity binding is **C-2D** handoff scope.

---

## 4. Table usage matrix

| Table | C-2C |
|-------|------|
| `erp_customer_online_requests` | **WRITE** payload + plate |
| `erp_customer_online_request_history` | **WRITE** optional audit |
| `erp_customers` | READ |
| `erp_customer_phones` | READ (if present) |
| `erp_vehicles` | READ |
| `erp_customer_vehicle_bindings` | READ (future linkage) |
| `erp_customer_intakes` | READ |
| `erp_jobcards` | READ — no INSERT |
| `erp_intake_contracts` | READ |
| `erp_jobcard_media` | READ |

---

## 5. Fields storable now (no migration)

All C-2C fields via `request_payload_json` top-level keys (recovery-compatible) + nested `reception_intake`:

- Vehicle: plate, vin, brand, model, mileage, fuel_level
- Condition: belongings, visible_damage, initial_vehicle_condition
- Service classification: primary, diag subs, service_path_clear, service_path_note
- Temporary reception: status, reason, expert_review_note, request_more_info_note
- Documents: photo_status, diagnostic_status, contract_status, cost_agreement, cost_agreement_note
- Confirmation: reception_final_confirmation, confirmation_note, confirmed_at

Also `vehicle_plate` column when plate saved.

---

## 6. Fields requiring later structured reporting foundation

- Photo file uploads → `erp_vehicle_photo_records` / `erp_jobcard_media`
- Signed contract PDF → `erp_intake_contracts` workflow
- Diagnostic file binary → jobcard media pipeline
- ERP vehicle/customer binding → structured tables
- JobCard conversion → C-2D
- Management KPI / revenue analytics → P12+

---

## 7. Safe writes in C-2C

| Action | Safe |
|--------|------|
| `save_vehicle_identity` | Yes |
| `save_condition_notes` | Yes |
| `save_service_classification` | Yes |
| `save_temporary_reception` | Yes (payload; reject also uses existing status UPDATE) |
| `save_documents_and_cost` | Yes |
| `save_reception_confirmation` | Yes (does not fake OTP or convert) |

---

## 8. Writes blocked until later phase

| Action | Blocked until |
|--------|---------------|
| `otp_verified` write | Never from reception — customer OTP flow only |
| Automatic JobCard INSERT | C-2D |
| `customer_id` / `vehicle_id` binding | C-2D (optional) |
| Media binary upload | Media capture phase |
| Contract generation/sign | P1.5 contract workflow |
| Auth/permission changes | Out of scope |

---

## 9. DB schema migration

**Not required.** No new tables. No new columns. No SQL migration files.

---

## 10. Auth / Login / permissions / workflow / OTP

**No changes required or permitted:**

- `staff-auth.php` — untouched
- `access-control.php` — untouched
- Roles, permissions, departments, positions — untouched
- OTP core — untouched; no bypass, no fake verification

Staff session + existing `m360_reception_require_staff()` + CSRF purpose `online_request_reception` sufficient.

---

## 11. Automatic JobCard conversion

**Not included.** Conversion remains on gated `convert_to_jobcard` action in `erp-reception-online-request-accept.php`, shown only when gate status is `ready_convert`. C-2C saves do not invoke conversion.

---

## Scope gate decision

**GO** — Implement C-2C intake write endpoint and forms using existing `request_payload_json` + optional history rows.

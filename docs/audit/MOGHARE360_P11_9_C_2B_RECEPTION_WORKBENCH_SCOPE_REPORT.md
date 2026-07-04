# MOGHARE360 P11.9-C-2B — Reception Workbench Scope Gate Report

**Phase:** P11.9-C-2B  
**Date:** 2026-07-04  
**Mode:** Scope gate before implementation  
**Product:** MOGHARE360 V1 RC

---

## Scope Gate Result: **CONTINUE**

No DB schema changes, Auth/Login changes, role/permission changes, OTP bypass, or destructive SQL required. Implementation proceeds by aggregating existing foundations.

---

## 1. Existing Reception Entry Pages

| Page | Role today |
|------|------------|
| `public_html/erp-reception-online-requests.php` | Primary P1 online request list (filters, table) |
| `public_html/erp-reception-online-request-detail.php` | P1 detail + POST actions via accept handler |
| `public_html/erp-reception-jobcards.php` | P2 reception JobCard board |
| `public_html/erp-staff-home.php` | Role-based staff landing; RECEPTION group links to P1/P2/P1.5 |

Additional related (reuse, not replace): `erp-reception-online-request-accept.php`, `erp-reception-jobcard-detail.php`, `erp-intake-contracts.php`.

---

## 2. Online Request Data Source

**Table:** `dbo.erp_customer_online_requests`

| Column / field | Usage in code |
|----------------|---------------|
| `request_payload_json` | Parsed by `m360_online_req_parse_payload()` |
| `customer_id` | Entity link; JOIN to `erp_customers` |
| `vehicle_id` | Entity link; JOIN to `erp_vehicles` |
| `converted_jobcard_id` | Convert state |
| `otp_verified` | Column probe; OTP also in payload |
| `request_status` | Workflow filter + gate |

**History:** `erp_customer_online_request_history` via `m360_reception_fetch_history()`.

---

## 3. Intake Data Source

**Table:** `dbo.erp_customer_intakes` (documented in domain maps; exists on canonical DB per Phase 9 tests)

**Code usage:** `erp-customer-core-helper.php`, `erp-crm-helper.php` — duplicate checks by mobile/plate; no dedicated reception intake UI today.

**C-2B approach:** Read-only fetch by `mobile` or `customer_id` when table exists; display if found; no writes.

---

## 4. Customer / Vehicle Data Sources

| Table | Evidence |
|-------|----------|
| `erp_customers` | JOIN in `m360_reception_list_requests()` |
| `erp_customer_phones` | Domain map; optional read if exists |
| `erp_vehicles` | JOIN in reception list; `m360_reception_ensure_vehicle()` |
| `erp_customer_vehicle_bindings` | Domain map (active bindings) |
| `erp_customer_vehicle_relations` | `m360_reception_ensure_relation()` |

---

## 5. Photo / Media Data Sources

| Table | Scope |
|-------|-------|
| `erp_vehicle_photo_records` | Vehicle-domain photos |
| `erp_jobcard_media` | JobCard camera/diagnostic media (Wave 2) |

**Existing routes:** `erp-jobcard-camera-capture.php`, `erp-jobcard-diagnostic-file.php`, `erp-jobcard-media-preview.php`.

---

## 6. Contract Data Sources

| Table | Usage |
|-------|-------|
| `erp_intake_contracts` | P1.5 — `m360-intake-contract-helper.php` |
| `erp_intake_contract_signatures` | Customer sign flow |
| `erp_intake_contract_events` | Contract audit |
| `erp_customer_contracts` | Legacy/customer domain (read if exists) |
| `erp_customer_contract_acceptances` | Legacy (read if exists) |

---

## 7. JobCard Data Source

| Object | Usage |
|--------|-------|
| `erp_jobcards` | Convert target; P2 reception columns |
| `vw_m360_owner_jobcard_pipeline` | Owner pipeline view (read if exists) |

**Write path (reuse only):** `m360_reception_convert_to_jobcard()` → `moghare360_jobcard_v2_write()`.

---

## 8. Existing Helper Files

| Helper | Purpose |
|--------|---------|
| `includes/m360-reception-helper.php` | P1 list, convert, CSRF, entity ensure |
| `includes/m360-online-request-helper.php` | Payload parse, status constants, fetch by id |
| `includes/m360-reception-jobcard-helper.php` | P2 board/detail |
| `includes/m360-intake-contract-helper.php` | P1.5 contracts |
| `includes/m360-staff-home-helper.php` | Staff home workbench matrix |
| `includes/erp-customer-core-helper.php` | `customer_core_db()`, table exists |
| `includes/moghare360-camera-media-helper.php` | Camera stages/types |
| `includes/moghare360-jobcard-evidence-gate-helper.php` | JobCard media reads |
| CSRF | `m360_reception_csrf_*` in reception helper |

**New helper (C-2B):** `includes/m360-reception-workbench-helper.php` — decode, gate, KPI counts, related entity reads.

---

## 9. request_payload_json — Known Keys (from code)

**Source:** `public_html/api/customer/request.php` build logic.

| Key | Persian concept |
|-----|-----------------|
| `customer_name` | نام مشتری |
| `mobile` | موبایل |
| `vehicle_plate` | پلاک |
| `service_note` | شرح درخواست |
| `national_id` | کد ملی |
| `province`, `city` | استان/شهر |
| `brand`, `vehicle_brand` | برند |
| `model`, `vehicle_model` | مدل |
| `vehicle_class` | کلاس خودرو |
| `vehicle_year_pair` | سال |
| `vin` | VIN / شاسی |
| `odometer_km` | کیلومتر |
| `request_type` | نوع درخواست |
| `visit_date` | تاریخ مراجعه |
| `plate_display`, `plate_parts` | پلاک |
| `address` | آدرس |
| `otp_verified` | تأیید OTP |
| `customer_flow` | جریان مشتری |

**Possible extended keys (decoder fallback):** `fuel_level`, `belongings`, `visible_damage`, `defect_category`, `defect_code`, `photos`, `diagnostic`, `diag`, `cost_agreement`, `contract`, `terms`, `customer_confirmation`, `notes`, `chassis`, `mileage`, `complaint`.

**Decoder:** `m360_online_req_parse_payload()` — invalid JSON → empty array; C-2B adds Persian label map + raw key fallback section.

---

## 10. Intake Completion Field Sources

| Field | Primary source | Fallback |
|-------|----------------|----------|
| نام مشتری | online_request.customer_name | payload, erp_customers |
| موبایل | online_request.mobile | payload, erp_customer_phones |
| پلاک | online_request.vehicle_plate | payload |
| VIN | payload.vin | erp_vehicles |
| برند/مدل | payload | erp_vehicles |
| کیلومتر | payload.odometer_km | erp_jobcards.odometer |
| سطح سوخت | payload.fuel_level | erp_jobcards.fuel_level |
| لوازم | payload.belongings | — |
| آسیب ظاهری | payload.visible_damage | — |
| شرح شکایت | service_note | payload |
| عیب/دسته | payload.defect_* / request_type | — |
| عکس | erp_vehicle_photo_records / erp_jobcard_media | payload.photos |
| دیاگ | erp_jobcard_media (diagnostic) | payload |
| قرارداد | erp_intake_contracts | — |
| توافق هزینه | payload.cost_agreement / terms | — |
| OTP | payload / column | — |
| JobCard | converted_jobcard_id | erp_jobcards |

---

## 11. Routes to Reuse (no duplicate modules)

- `erp-reception-online-requests.php`
- `erp-reception-online-request-detail.php`
- `erp-reception-online-request-accept.php` (convert POST)
- `erp-reception-jobcards.php`
- `erp-reception-jobcard-detail.php`
- `erp-intake-contracts.php` / `erp-intake-contract-detail.php`
- `erp-customer-vehicle-workbench.php` / `erp-vehicle-detail-ux.php`
- `erp-jobcard-camera-capture.php` / `erp-jobcard-diagnostic-file.php`
- `erp-jobcard-detail.php`
- `erp-hr-dashboard.php` / `erp-employee-profile.php` (HR shortcuts if exist)

---

## 12. Missing Routes — New Shell Pages

| New page | Purpose |
|----------|---------|
| `erp-reception-workbench.php` | Reception staff dashboard (card grid + KPIs) |
| `erp-reception-intake-file.php` | Intake completion shell per `online_request_id` |

Walk-in: **no new write flow** — workbench card with controlled Persian placeholder + link to `erp-jobcard-create-ux.php` (guide) and future C-2C note.

---

## 13. Files Needed for Implementation

**Create:**
- `public_html/erp-reception-workbench.php`
- `public_html/erp-reception-intake-file.php`
- `public_html/includes/m360-reception-workbench-helper.php`
- `docs/audit/MOGHARE360_P11_9_C_2B_RECEPTION_WORKBENCH_REPORT.md`
- `tools/test-p11-9-c-2b-reception-workbench.php`
- `tools/test-p11-9-c-2b-intake-completion-shell.php`
- `tools/test-p11-9-c-2b-scope-security.php`

**Modify (allowed):**
- `public_html/erp-staff-home.php` — promote میز کار پذیرش
- `public_html/erp-reception-online-requests.php` — تکمیل پرونده link
- `public_html/erp-reception-online-request-detail.php` — intake link
- `public_html/assets/css/moghare360-v1-luxury-ui.css` — reception workbench styles

---

## 14. Files Forbidden from Modification

- `staff-auth.php`, `access-control.php`
- Auth/Login pages and architecture
- Permission / role / department / position models
- `database/migrations/*`, SQL files
- OTP helpers and architecture
- Workflow action handler cores (except safe link-only touches on allowed pages)
- Private files, secrets, production config
- P12 scope

---

## Stop Conditions — None Triggered

| Condition | Status |
|-----------|--------|
| DB schema change required | **No** — read existing tables only |
| New tables required | **No** |
| Auth/Login change | **No** |
| OTP bypass | **No** |
| Destructive SQL | **No** |
| Workflow rewrite | **No** |

**Decision:** Proceed with C-2B implementation.

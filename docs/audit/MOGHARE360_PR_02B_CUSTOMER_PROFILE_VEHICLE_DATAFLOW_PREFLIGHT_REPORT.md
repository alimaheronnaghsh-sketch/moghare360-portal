# MOGHARE360 PR-02B — Customer Profile + Multi-Vehicle + OTP State Preflight

**Mission:** PR-02B-CUSTOMER-PROFILE-VEHICLE-DATAFLOW-PREFLIGHT  
**Scope:** Discovery only — no runtime, schema, or config changes  
**Date (UTC):** 2026-07-06  
**Commit eligibility:** `NOT_ELIGIBLE_PREFLIGHT_ONLY`

---

## 1. Executive Summary

MOGHARE360 already has canonical **customer** and **vehicle** tables (`erp_customers`, `erp_vehicles`, `erp_customer_vehicle_relations`) and online intake (`erp_customer_online_requests`). However, the **public customer online journey does not yet behave like a true OTP-only customer portal with durable profile, multi-vehicle selection, and service history.**

Key findings:

1. **OTP message conflict** — The UI can show «مشتری گرامی، شماره شما تأیید شد» (client, post-verify) while a later API/submit call returns «شماره موبایل تأیید نشده است» because OTP truth is split across **session**, **hidden field**, **token**, **payload**, and **DB column** with no single coordinator.
2. **Profile is partial** — Customer page collects name, national_id (optional), province/city, address, postal_address, extra_contact, job, birth date. It does **not** collect first/last name separately, second phone, delivery address, or authorized receiver fields. Profile is stored primarily in **online request payload**, not `erp_customers`, until JobCard convert path runs.
3. **Multi-vehicle schema yes, UX no** — DB supports many vehicles per customer via `erp_customer_vehicle_relations`, but the customer page shows only a **text hint** for the last vehicle (`TOP 1`); there is no picker, no history panel, no “add another vehicle” flow.
4. **Submit creates online request, not full profile** — `api/customer/request.php` → `m360_online_req_insert()` creates `erp_customer_online_requests` and links existing `customer_id`/`vehicle_id` when resolvable; it does **not** INSERT new `erp_customers` or `erp_vehicles` on submit.
5. **UI shape gap** — Customer page uses progressive disclosure inside **one long POST form** (steps 1–5 in a single scroll after OTP). Reception intake is a **true wizard** with step navigation and gate enforcement. Customer journey is simpler than reception but still **tomari-like** when profile + vehicle + request sections are all visible.
6. **Reception** reads canonical ERP rows only when `customer_id`/`vehicle_id` are set; intake wizard writes mostly to **payload** until C-2D/convert binding.

**STOP/GO:** **STOP implementation** in this phase. **GO** to owner-approved **PR-02B-REPAIR** after decisions in §11.

---

## 2. Customer Profile Data Model

### Evidence sources

| Artifact | Path |
|----------|------|
| Customer/vehicle schema | `public_html/sql/sqlserver/mission_15_customer_vehicle_foundation.sql` |
| Online request P1 columns | `database/migrations/P1_online_request_intake.sql` |
| Online insert/resolve | `public_html/includes/m360-online-request-helper.php` |
| Profile lookup API | `public_html/api/customer/profile-status.php` |
| Staff customer write | `public_html/includes/moghare360-customer-v2-write-helper.php` |
| Vehicle relations | `erp_customer_vehicle_relations` (M15 junction) |
| Phase 1 alternate binding | `erp_customer_vehicle_bindings` (`phase_1_customer_core_system.sql`) |

### Report labels

| Label | Value |
|-------|-------|
| CUSTOMER_PROFILE_TABLE_EXISTS | **yes** — `dbo.erp_customers` |
| CUSTOMER_PROFILE_FIELDS | `customer_id`, `customer_code` (unique), `customer_type`, `full_name`, `national_id`, `primary_mobile`, `secondary_mobile`, `email`, `address`, `city`, `notes`, `lifecycle_state`, audit columns; optional runtime `company_id` |
| CUSTOMER_UNIQUE_KEY | **`customer_code` only (DB unique)**. `primary_mobile` has **non-unique index** — same mobile can map to multiple customers (`TOP 1 ORDER BY customer_id DESC` resolve) |
| CUSTOMER_CAN_HAVE_MULTIPLE_VEHICLES | **yes** — via `erp_customer_vehicle_relations` (many rows per `customer_id`; `is_primary_owner` flag) |
| VEHICLE_TABLE_EXISTS | **yes** — `dbo.erp_vehicles` |
| VEHICLE_CUSTOMER_LINK_EXISTS | **yes** — `erp_customer_vehicle_relations` (`customer_id`, `vehicle_id`). **No** `customer_id` on `erp_vehicles` row |
| ONLINE_REQUEST_CUSTOMER_ID_EXISTS | **yes** — P1 `customer_id BIGINT NULL` on `erp_customer_online_requests` |
| ONLINE_REQUEST_VEHICLE_ID_EXISTS | **yes** — P1 `vehicle_id BIGINT NULL` |
| ONLINE_REQUEST_LINKED_TO_CUSTOMER | **partial** — linked at insert **only if** `m360_online_req_resolve_customer_id()` finds existing row; new customers get `customer_id = NULL`, `profile_required = true` in payload |
| ONLINE_REQUEST_LINKED_TO_VEHICLE | **partial** — linked at insert **only if** `m360_online_req_resolve_vehicle_id()` finds plate (+ customer when known); new vehicles not created on submit |

### Online request + history

| Table | Exists | Role |
|-------|--------|------|
| `erp_customer_online_requests` | yes | Intake row: mobile, plate, status, `request_payload_json`, `otp_verified`, nullable FKs |
| `erp_customer_online_request_history` | yes | Audit events (`m360_online_req_write_history`, reception intake saves) |

### Customer portal pages (staff vs public)

| Page | Audience | ERP-backed |
|------|----------|------------|
| `customer-request.php` | Public online | Online request + payload; profile lookup via API |
| `erp-customer-detail-ux.php` | Staff | Full customer + vehicles + jobcards |
| `erp-customer-profile.php` | Staff | Phase 1 intakes + legacy bindings |
| `customer-profile.php` | Legacy | MySQL staging — **not** canonical |

---

## 3. OTP State and Message Conflict

### OTP state layers

| Layer | Mechanism | Set when | Read when |
|-------|-----------|----------|-----------|
| **Session** | `otp_verified_phone`, `otp_verified_at`, `otp_verified_token` | `m360_otp_verify()` in `m360-otp-helper.php` | `m360_otp_is_verified($phone)` — phone match + token + 1h TTL |
| **Hidden field** | `#mobile_verified` value `1`/`0` | JS `setSubmitEnabled()` after verify; PHP render from session | JS submit guard; not trusted server-side alone |
| **Token in POST** | `otp_verified_token` in form payload | Session at verify time | `api/customer/request.php` `hash_equals` vs session |
| **Payload** | `otp_verified: 1` in `request_payload_json` | `m360_online_req_insert()` forces on insert | `m360_online_req_payload_otp_verified()` |
| **DB column** | `erp_customer_online_requests.otp_verified` | Insert = 1 when column exists | Reception list filter + payload dual gate |

### Message map

| Persian text | Where | When shown |
|--------------|-------|------------|
| **«شماره موبایل تأیید نشده است.»** | `api/customer/request.php` L25–31, `profile-status.php` L21–22, `m360_otp_require_verified_mobile()` | Server: session missing/expired, phone mismatch, or token `hash_equals` fail |
| **«مشتری گرامی، شماره شما تأیید شد.»** | `customer-form.js` `loadProfileAndShowForm()` L243–245; POST restore L467–469 | Client: after **successful** `verify-otp.php` + `profile-status.php` when `customer_exists === true` |
| **«برای ثبت درخواست، ابتدا شماره موبایل خود را با کد پیامکی تأیید کنید.»** | `customer-request.php` L145; `customer-form.js` L562 | Page POST or client submit when session/hidden not verified |
| **«شماره موبایل تأیید شد»** (no «مشتری گرامی») | `customer-form.js` OTP step success | Immediately after verify, before profile load |

### Report labels

| Label | Value |
|-------|-------|
| OTP_SESSION_VERIFIED | `m360_otp_is_verified($mobile)` — session keys + 1h TTL |
| HIDDEN_MOBILE_VERIFIED | `#mobile_verified` — client-only gate; PHP renders from session on page load |
| PAYLOAD_OTP_VERIFIED | Set to `1` on successful insert only |
| DB_OTP_VERIFIED | Column `otp_verified = 1` on insert when column exists |
| CONFLICTING_OTP_MESSAGES_ROOT_CAUSE | **Multi-layer OTP without single coordinator.** Client welcome reflects **successful verify + profile-status** in browser session. Submit/profile API re-checks **server session** (and token). Failure paths: (1) **loopback mirror** does not forward session cookie when `MASTER_SERVER_BASE_URL` host ≠ current `HTTP_HOST` (`mirror-api-client.php` L85–91); (2) session expiry (1h) or mobile change resets flow; (3) `otp_verified_token` mismatch; (4) user sees welcome from JS while a **subsequent** `profile-status` or submit fails with API wording |
| OTP_STATE_SINGLE_SOURCE_OF_TRUTH | **None today.** Closest: `m360_otp_is_verified()` for live actions; persisted requests use `m360_online_req_payload_otp_verified()` (payload OR row) |
| REPAIR_RECOMMENDATION | Unify OTP proof for submit (direct insert or guaranteed session-forwarding); single user-facing verified state; optional server-rendered welcome only after both verify **and** profile-status succeed; document token+session contract |

---

## 4. Customer Profile Flow

### Fields on customer page (`customer-request.php` § profile)

| Owner-asked field | On page? | Notes |
|-------------------|----------|-------|
| first_name / last_name | **no** | Single `full_name` only |
| national_id | **yes** (optional) | |
| second_phone | **no** | `erp_customers.secondary_mobile` exists but not collected |
| residence_address | **partial** | `address` + `province`/`city` |
| vehicle_delivery_address | **no** | |
| authorized_receiver_name / phone | **no** | Not found in codebase |
| job_title, birth_date, postal_address, extra_contact_info | **yes** | Extra vs owner minimum list |

### Persistence path

| Label | Value |
|-------|-------|
| PROFILE_FIELDS_PRESENT_ON_CUSTOMER_PAGE | **partial** — subset of owner list; no split name, no delivery/authorized receiver |
| PROFILE_FIELDS_SAVED_TO_DB | **partial** — `customer_name` column + full profile in `request_payload_json`. **`erp_customers` not written** on public submit |
| PROFILE_FIELDS_ONLY_IN_PAYLOAD | **yes** for new customers — province, city, address, national_id, birth_date, job_title, etc. in JSON payload |
| EXISTING_CUSTOMER_PROFILE_LOADED_AFTER_OTP | **partial** — `profile-status.php` returns masked `full_name` + `last_vehicle` hint; does **not** hydrate form fields from `erp_customers` |
| CUSTOMER_HISTORY_VISIBLE | **no** — no jobcard/service history on customer page |
| CUSTOMER_PREVIOUS_VEHICLES_VISIBLE | **no** — only text hint `آخرین خودرو: …`; no list or select |
| REPAIR_RECOMMENDATION | Add OTP-gated customer portal step: load/create `erp_customers` row; split or map profile fields; persist to canonical table before or atomically with online request |

---

## 5. Multi-Vehicle Flow

| Label | Value |
|-------|-------|
| ONE_CUSTOMER_MULTIPLE_VEHICLES_SUPPORTED | **partial** — schema **yes** (`erp_customer_vehicle_relations`); public UX **no** |
| CUSTOMER_CAN_SELECT_PREVIOUS_VEHICLE | **no** — hint only; must re-enter vehicle step fields |
| CUSTOMER_CAN_ADD_NEW_VEHICLE | **partial** — implicit by entering new plate each request; no explicit “add vehicle” |
| SYSTEM_ASSUMES_ONE_VEHICLE_PER_MOBILE | **partial** — `profile-status` uses `TOP 1` vehicle; resolve picks one plate match |
| LATEST_VEHICLE_OVERWRITE_RISK | **yes** — each online request stores one `vehicle_plate`; no merge into vehicle registry until convert; duplicate customers per mobile possible |
| REPAIR_RECOMMENDATION | After OTP: show vehicle list from relations; allow select existing or add new; bind `vehicle_id` on request insert |

---

## 6. Customer Online Submit Dataflow

### Trace

```
customer-request.php (single POST form)
  → customer-form.js (OTP AJAX: send-otp, verify-otp, profile-status)
  → POST customer-request.php (server validates m360_otp_is_verified)
  → mirror_api_customer_request() → curl POST /api/customer/request.php
  → m360_otp_is_verified() + otp_verified_token check
  → m360_online_req_insert()
      → resolve customer_id (existing mobile only)
      → resolve vehicle_id (existing plate/relation only)
      → INSERT erp_customer_online_requests + payload otp_verified=1
      → history row (ONLINE_REQUEST_CREATED)
  → success panel: online_request_id tracking
  → reception: m360_reception_list_requests() filters otp_verified=1
```

**Contract step:** Not on customer page. Contract/customer cartable is **reception intake wizard** (`prepare_customer_contract_review`, C-2C), not customer submit.

### Report labels

| Label | Value |
|-------|-------|
| CUSTOMER_SUBMIT_CREATES_ONLINE_REQUEST | **yes** |
| CUSTOMER_SUBMIT_CREATES_OR_LINKS_CUSTOMER | **partial** — links if exists; **does not create** `erp_customers` |
| CUSTOMER_SUBMIT_CREATES_OR_LINKS_VEHICLE | **partial** — links if plate match; **does not create** `erp_vehicles` |
| REQUEST_ID_DISPLAYED | **yes** — success panel with `online_request_id` |
| TRACKING_STATE_RELIABLE | **partial** — ID shown on success; submit reliability weakened by mirror/session path (PR-02A finding) |
| RECEPTION_VISIBILITY_AFTER_OTP | **yes** when row inserted with `otp_verified=1` and list filter passes |
| DATAFLOW_ROOT_CAUSE | Online intake is **request-centric**, not **profile-centric**: payload carries customer/vehicle data; canonical ERP entities deferred to **JobCard convert** (`m360_reception_ensure_customer`, `m360_reception_ensure_vehicle`, `m360_reception_bind_request_entities`) |
| REPAIR_RECOMMENDATION | PR-02B: upsert customer profile on first OTP-verified submit; vehicle registry + relation on each request; eliminate fragile mirror loopback for localhost |

---

## 7. Customer UI Flow Shape

### Customer page structure (`customer-request.php`)

| Step | Section ID | Content |
|------|------------|---------|
| 1 | `m360_step_mobile` | Mobile + send OTP |
| 2 | `m360_step_otp` | 6-digit code |
| 3 | `m360_step_welcome` | Welcome message |
| 4 | `m360_section_profile` | Profile (new customers only) |
| 5 | `m360_section_vehicle` | Brand/model/plate/VIN/km |
| 6 | `m360_section_request` | Request type, visit date, description, submit |

All steps live in **one** `<form method="post">`. After OTP, sections 4–6 can be **visible together** (long scroll). No stepper nav like reception. No separate contract step.

### Reception (`erp-reception-intake-file.php`)

True wizard: `m360_rw_intake_resolve_active_step()`, progress bar, gate enforcement, staff-only technical fields.

### Report labels

| Label | Value |
|-------|-------|
| CUSTOMER_PAGE_IS_TOMARI | **yes** (when post-OTP sections expanded — single-page stacked form; owner “tomari” concern valid) |
| CUSTOMER_PAGE_STEP_BASED | **partial** — progressive disclosure, not isolated wizard pages |
| RECEPTION_PAGE_STEP_BASED | **yes** |
| CUSTOMER_STAFF_RESPONSIBILITY_SEPARATED | **partial** — separate pages, but customer still enters full vehicle technical detail; staff completes intake/contract/hall manager |
| CUSTOMER_TECHNICAL_FIELDS_VISIBLE | **yes** — plate widget, VIN, odometer, brand/class (same class of fields as reception vehicle step) |
| REPAIR_RECOMMENDATION | Refactor to **discrete customer journey**: OTP → profile confirm → vehicle pick/add → request summary → visit date → **customer-facing contract acknowledgment** → submit/tracking; hide staff gates |

### Owner target journey (gap)

| Target step | Current state |
|-------------|---------------|
| OTP | Implemented |
| Customer profile | Partial (new only, payload-heavy) |
| Select/add vehicle | Missing picker |
| Request | Implemented |
| Visit date | Implemented |
| Contract | **Reception only**, not customer online |
| Submit/tracking | Partial (tracking on success) |

---

## 8. Reception Relationship to Customer/Vehicle

| Label | Value |
|-------|-------|
| RECEPTION_LOADS_CUSTOMER_PROFILE | **partial** — `m360_rw_fetch_customer_row()` only if `request.customer_id > 0`; else payload/request row |
| RECEPTION_LOADS_CUSTOMER_VEHICLES | **partial** — `m360_rw_fetch_vehicle_row()` if `vehicle_id > 0`; else payload recovery |
| RECEPTION_CAN_COMPLETE_PROFILE | **no** — intake wizard is vehicle/condition/service/photos/documents; no customer profile edit step |
| RECEPTION_CAN_COMPLETE_VEHICLE | **yes** — `save_vehicle_identity` persists canonical vehicle fields to payload |
| RECEPTION_WRITES_CANONICAL_CUSTOMER | **partial** — only on **JobCard convert** via `m360_reception_ensure_customer()` → `moghare360_customer_v2_write()` |
| RECEPTION_WRITES_CANONICAL_VEHICLE | **partial** — convert path `m360_reception_ensure_vehicle()`; intake saves **payload only** |

### Field resolution priority (`m360_rw_intake_source_map`)

1. Online request row  
2. Payload JSON  
3. `erp_customers`  
4. `erp_vehicles`  
5. Legacy intake / jobcard  

When `customer_id`/`vehicle_id` null (typical new online request), reception works from **payload**, not canonical profile.

### REPAIR_RECOMMENDATION (reception)

- Bind `customer_id`/`vehicle_id` earlier (C-2D / PR-02B), not only at convert  
- Optional reception read-only customer profile panel from `erp_customers` when linked  
- Avoid overwriting canonical profile when completing vehicle in intake

---

## 9. Root Cause Summary

| # | Symptom | Root cause |
|---|---------|------------|
| 1 | Conflicting OTP messages | Client success (verify + profile-status) ≠ server session on submit/API; mirror cookie gap; split vocabulary |
| 2 | No durable customer profile after OTP | Submit writes **online request + payload**; `erp_customers` create deferred to convert |
| 3 | Multi-vehicle not usable online | Schema supports relations; UI/API return **one** last vehicle hint only |
| 4 | Customer feels like long form | Single POST form with stacked visible sections post-OTP |
| 5 | Reception vs customer mismatch | Reception step wizard + payload-first when FKs empty; customer does not feed canonical entities |
| 6 | Service history not visible | No customer portal history view; history lives in staff ERP/jobcard modules |

---

## 10. Recommended Repair Scope (PR-02B-REPAIR — proposed, not implemented)

1. **OTP unification** — Single verified state; fix mirror/session for localhost; align user messages  
2. **Customer profile upsert** — After OTP, load or create `erp_customers` by mobile; map form fields; optional `erp_customer_phones`  
3. **Multi-vehicle UX** — List relations; select existing or add new vehicle; store `vehicle_id` on request  
4. **Customer step wizard** — Replace tomari single form with discrete steps matching owner journey (include customer contract acknowledgment before submit)  
5. **Submit path hardening** — Direct `m360_online_req_insert()` or guaranteed session forward; atomic customer+vehicle+request  
6. **Early FK binding** — Set `customer_id`/`vehicle_id` on insert when profile/vehicle resolved (extend PR-02A/C-2D)  
7. **Customer history (phase 2)** — Read-only prior requests/jobcards after OTP (staff data, customer-safe view)  

**Out of scope for PR-02B:** DB schema change, OTP helper rewrite, password login, JobCard auto-create, private config.

---

## 11. Owner Decisions Required

1. **Profile field set** — Confirm minimum vs optional fields (authorized receiver, delivery address, split name?)  
2. **Mobile uniqueness** — Enforce one `erp_customers` per mobile per company, or keep resolve-latest?  
3. **When to create canonical customer** — On first OTP verify, first submit, or reception convert?  
4. **Multi-vehicle UX** — Select from list vs always enter plate?  
5. **Customer contract** — Online acknowledgment before submit, or reception-only (current)?  
6. **Service history scope** — Show prior online requests only, or jobcards too?  
7. **Loopback vs direct insert** — Eliminate mirror curl for localhost customer submit?  

---

## 12. File Disposition Table

| File path | Created/modified | Category | Reason | Commit allowed now | Push allowed now | Owner approval required |
|-----------|------------------|----------|--------|------------------|------------------|-------------------------|
| `docs/audit/MOGHARE360_PR_02B_CUSTOMER_PROFILE_VEHICLE_DATAFLOW_PREFLIGHT_REPORT.md` | Created | COMMIT_NOW_DOCS | Discovery-only audit; no runtime changes | yes | no | yes |

No runtime, test, or config files created or modified.

---

## 13. STOP / GO Decision

| Decision | Value |
|----------|-------|
| **STOP** | Implementation in this preflight phase (completed) |
| **GO** | Owner-approved **PR-02B-REPAIR** after §11 decisions |
| **Blockers** | OTP single source of truth; profile/vehicle canonical binding; customer wizard UX; mirror submit reliability |
| **Commit eligibility** | `NOT_ELIGIBLE_PREFLIGHT_ONLY` (report may be committed as docs-only when owner approves) |

---

MOGHARE360 PR-02B Customer Profile Vehicle Dataflow Preflight discovers whether customers have real OTP-only profiles, multiple vehicles, linked online requests, reliable submit/tracking state, and a customer-friendly step-based online journey before any implementation.

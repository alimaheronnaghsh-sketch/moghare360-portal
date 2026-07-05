# MOGHARE360 PR-02-PREFLIGHT — Customer / Reception Base Data Standards Discovery

**Mission ID:** PR-02-PREFLIGHT  
**Type:** Discovery only — **no implementation**  
**Date:** 2026-07-05  
**Commit Eligibility:** `NOT_ELIGIBLE_PREFLIGHT_ONLY`  
**Prerequisites accepted:** PR-00 Program Reset, PR-01D OTP nested hygiene, canonical OTP path locked

---

## 1. Executive Summary

This preflight inventories all customer online intake and staff reception intake variants, compares them against owner base-data rules (plate, vehicle allowlist, calendar, symptom vs technical routing, hall-manager assignment), and proposes canonical sources **without changing any runtime file**.

**Key findings:**

| Area | Customer (active) | Reception (active) | Aligned? |
|------|-------------------|--------------------|----------|
| **Primary page** | `customer-request.php` | `erp-reception-intake-file.php` | Different roles ✓ |
| **Iranian plate** | Digit/letter **select** widget | Text **input** widget (`plate_iran_2_digits`) | **NO** |
| **Brand/model/year** | JS dropdown (`vehicle-brand-classes.js`) + PHP year list | Free-text `brand` / `model`; no year field in vehicle step | **NO** |
| **Approved brands** | Blueprint lists Toyota/Lexus/Kia/Hyundai/BYD/Lucano/Chery | Not enforced; JS still has luxury brands (Benz/BMW/…) | **NO** |
| **Calendar** | Server-rendered 30-day visit calendar | No equivalent picker; reads `visit_date` from online payload | **NO** |
| **Technical routing** | Symptom/request only; no technician/dept fields | Staff service classification + referral **team** (not technician) | Partial |
| **Hall manager gate** | N/A (customer) | Referral step + intake lock + `ready_convert` gate; **no explicit “send to hall manager”** action | Partial |
| **18-clause contract** | Not on `customer-request.php`; separate cartable flow | Intake contract assignment step exists | Exists elsewhere |
| **Repo ↔ XAMPP** | **MATCH** on sampled active files | **MATCH** | Root runtime canonical |

**STOP:** Owner approval required before PR-02 implementation. Canonical customer-site standards are the proposed alignment source for plate UI/JS/CSS and visit calendar — **not** a redesign of plate segment order.

---

## 2. Canonical Docs Read

| # | Document | Read | Notes used in this report |
|---|----------|------|---------------------------|
| 1 | `docs/00_CANONICAL/MOGHARE360_MASTER_PRODUCT_BLUEPRINT.md` | ✅ | Brand allowlist; customer vs staff journey; no free-text brands in production |
| 2 | `docs/00_CANONICAL/MOGHARE360_DATABASE_GAP_MATRIX.md` | ✅ | Missing `erp_vehicle_brands`/`models`; payload overuse; JobCard partial |
| 3 | `docs/00_CANONICAL/MOGHARE360_RUNTIME_CLEANUP_PLAN.md` | ✅ | Active vs deprecated customer/reception files |
| 4 | `docs/00_CANONICAL/MOGHARE360_EXECUTION_ROADMAP.md` | ✅ | Milestone 1 intake deliverables; C-2D forbidden |
| 5 | `docs/00_CANONICAL/MOGHARE360_CURSOR_EXECUTION_RULES.md` | ✅ | Preflight-before-code; forbidden areas |
| 6 | `docs/audit/MOGHARE360_PR_00_PROGRAM_RESET_CONTROL_PACK_REPORT.md` | ✅ | Program reset scope baseline |
| 7 | `docs/audit/MOGHARE360_PR_01D_XAMPP_NESTED_PUBLIC_HTML_OTP_HYGIENE_REPORT.md` | ✅ | Active runtime root `C:\xampp\htdocs\moghare360\` |

---

## 3. Customer Form Inventory

### 3.1 Variant count

| Bucket | Count | Files |
|--------|------:|-------|
| **Active canonical online intake** | **1** | `public_html/customer-request.php` |
| **Active supporting JS** | **3** | `customer-form.js`, `vehicle-brand-classes.js`, `iran-provinces-cities.js` |
| **Active API (customer-request flow)** | **4** | `api/customer/send-otp.php`, `verify-otp.php`, `profile-status.php`, `request.php` |
| **Related customer pages (not primary intake)** | **6** | `customer-request-status.php`, `customer-login.php`, `customer-profile.php`, `customer-intake-contract.php`, `customer-intake-contract-review.php`, `customer-intake-contract-sign.php` |
| **Legacy mirror intake** | **2** | `customer-service-request.php`, `customer-contract.php` (requires `config.php` / MySQL) |
| **Archive duplicates** | **6** | Under `release/_cpanel_*`, `release/moghare360-mirror-site-package`, `dist/moghare360-v1-local-demo-rc` |
| **Total distinct customer form/intake touchpoints** | **22** | |

**CUSTOMER_FORM_VARIANT_COUNT (logical):** **10** active/legacy pages + **6** archive copies of `customer-request.php` = **16** file variants; **1** active browser intake.

### 3.2 Active browser runtime (`http://localhost:8080/moghare360/customer-request.php`)

| Item | Value |
|------|-------|
| **Apache document root** | `C:\xampp\htdocs\moghare360\` (root, not nested `public_html/`) |
| **Active PHP** | `customer-request.php` |
| **Repo SHA256** | `37909b3307d2c3ca804e8ca7a1afb73d1beaae14c021c277f8f6a07b5dd28d0d` |
| **XAMPP SHA256** | **MATCH** |

### 3.3 JS loaded (browser)

| Script | Purpose |
|--------|---------|
| `assets/js/iran-provinces-cities.js` | Province/city cascade |
| `assets/js/vehicle-brand-classes.js` | Brand → model/class dropdown population |
| `assets/js/customer-form.js?v={filemtime}` | OTP steps, plate sync, calendar, form visibility, validation |

Loaded from ```630:636:public_html/customer-request.php``` via `mirror_render_foot()` chain.

### 3.4 CSS loaded (browser)

Via `includes/mirror-layout.php` → `mirror_render_head()`:

| Stylesheet | OTP/plate/calendar relevance |
|------------|---------------------------|
| `assets/css/mirror.css` | Plate widget, server calendar, OTP panels |
| `assets/css/moghare360-v1-luxury-ui.css` | Luxury shell (partial plate responsive rules) |

### 3.5 API endpoints used

| Phase | Endpoint | Method |
|-------|----------|--------|
| OTP send | `api/customer/send-otp.php` | POST JSON |
| OTP verify | `api/customer/verify-otp.php` | POST JSON |
| Profile probe | `api/customer/profile-status.php` | POST JSON |
| Final submit | **`customer-request.php`** (form POST) | POST — calls `mirror_api_customer_request()` server-side; `api/customer/request.php` is the backend target via mirror API client |

### 3.6 Duplicate / legacy customer forms

| File | Status | Risk |
|------|--------|------|
| `customer-service-request.php` | Legacy MySQL + `config.php`; Mercedes/BMW lookups | **HIGH** — wrong brands, not canonical |
| `customer-login.php` | Posts to stubbed `send-otp.php` | Deprecated entry |
| `customer-contract.php` | Posts to stubbed `send-contract-otp.php` | Deprecated contract path |
| `customer-request-status.php` | Status viewer | Secondary |
| Archive `release/` / `dist/` copies | Non-runtime | Low |

### 3.7 Canonical candidate

**`public_html/customer-request.php`** + **`assets/js/customer-form.js`** + **`assets/css/mirror.css`** (plate + calendar) + **`vehicle-brand-classes.js`** (must be **replaced/updated** to owner allowlist in PR-02 — not current canonical data).

### 3.8 UX model

| Question | Answer |
|----------|--------|
| Step-based or scroll/tomari? | **Step-based** — sections `m360_step_mobile` → `m360_step_otp` → `m360_step_welcome` → `m360_section_profile` → `m360_section_vehicle` → `m360_section_request`; JS toggles `m360-step--hidden` |
| Staff-only fields? | **No** — customer fields only (profile, vehicle, request type, visit date, description). No technician, department, workshop, or internal routing fields |

---

## 4. Reception Form Inventory

### 4.1 Variant count

| Bucket | Count | Files |
|--------|------:|-------|
| **Active canonical intake wizard** | **1** | `erp-reception-intake-file.php` |
| **Active save handler** | **1** | `erp-reception-intake-save.php` |
| **Active workbench hub** | **1** | `erp-reception-workbench.php` |
| **Active helpers** | **2** | `m360-reception-workbench-helper.php`, `m360-reception-helper.php` |
| **Active JS** | **1** | `m360-reception-intake.js` |
| **Deprecated reception list/detail** | **3** | `erp-reception-online-requests.php`, `erp-reception-online-request-detail.php`, `erp-reception-online-request-accept.php` |
| **JobCard reception pages (out of PR-02 scope)** | **3** | `erp-reception-jobcards.php`, `erp-reception-jobcard-detail.php`, `erp-reception-jobcard-action.php` |
| **Total reception/intake touchpoints** | **12** | |

**RECEPTION_FORM_VARIANT_COUNT (logical):** **5** active intake chain files + **3** legacy list/detail + **3** JobCard pages + **1** workbench = **12**.

### 4.2 Active staff-facing intake

| Item | Value |
|------|-------|
| **Entry** | Staff auth → `erp-reception-workbench.php` → open online request → `erp-reception-intake-file.php?online_request_id={id}` |
| **Active PHP** | `erp-reception-intake-file.php` |
| **Save POST** | `erp-reception-intake-save.php` |
| **Repo ↔ XAMPP hash** | `b6ddcc587153f49735f4209fa7a3dcca448db16966769967dbe1ee47b9c30529` — **MATCH** |

### 4.3 JS / CSS (reception intake)

| Asset | Path |
|-------|------|
| CSS | `assets/css/moghare360-v1-luxury-ui.css` only |
| JS | `assets/js/m360-reception-intake.js` (defer) — plate preview only |

**Note:** Reception intake does **not** load `mirror.css` or `customer-form.js`.

### 4.4 Wizard steps (reception)

From `m360_rw_intake_stepper_definition()` / intake-file switch:

`otp` → `vehicle` → `condition` → `service` → `referral` → `photos` → `documents` → `signature` → `locked_summary`

Stepper URL-driven (`active_step`); not a single scroll form.

### 4.5 Same base standards as customer?

| Standard | Match? |
|----------|--------|
| Plate widget | **NO** — different control type and field names |
| Brand/model/year | **NO** — reception free text; customer controlled selects |
| Visit calendar | **NO** — customer captures; reception displays recovered `visit_date` in gate/summary only |
| OTP | Shared helper (`m360_otp_*`) but separate UI blocks |

### 4.6 Brand / model / year / class (reception)

| Field | Reception behavior |
|-------|-------------------|
| Brand | `m360_rw_intake_form_field('برند', 'brand', …, 'text', true)` — **free text** |
| Model | `m360_rw_intake_form_field('مدل', 'model', …, 'text', true)` — **free text** |
| Year | Label mapped in helper (`vehicle_year_pair`) but **no year input** on vehicle step form |
| Class | Not separate; combined as model text |

### 4.7 Plate (reception)

Rendered by `m360_rw_intake_render_plate_widget()` — uses `iran-plate-widget` CSS class but **text inputs** for segments; region field name `plate_iran_2_digits` (customer uses `plate_region_digit_*` + hidden `plate_region_2_digits`).

### 4.8 Technician / assistant assignment (reception)

| Check | Result |
|-------|--------|
| Direct technician pick in intake-file | **NO** — grep: no `technician` / `assistant` / `تکنسین` in intake-file or workbench-helper intake UI |
| Referral team assignment | **YES** — receptionist selects `referral_team_id` from hardcoded teams (`team_1`, `team_electrical`, …) |
| Send-to-hall-manager explicit action | **NO** — not found as named action/button |

### 4.9 Send-to-hall-manager gate

| Mechanism | Exists? |
|-----------|---------|
| Named “ارسال به مدیر سالن” / `send_to_hall` | **NO** |
| `sign_and_lock_intake` | **YES** — locks intake after signature checklist |
| Gate `ready_convert` | **YES** — prerequisites checklist before JobCard conversion (C-2D forbidden) |
| `SERVICE_MANAGER` role in staff home | **YES** — conceptual; not wired as intake submit gate |
| Workflow doc `INTAKE_TO_DELIVERY_WORKFLOW.md` | **YES** — مدیر سالن appears at JobCard stage, not intake completion |

**HALL_MANAGER_GATE_EXISTS:** **partial** — intake lock + referral + convert gate exist; explicit hall-manager handoff UI **missing**.

---

## 5. Iranian Plate Standard Discovery

> **Rule honored:** Customer online implementation is the only candidate source. Segment order is documented as implemented — **not** redesigned here.

### 5.1 Customer online plate (canonical candidate)

| Layer | File | Detail |
|-------|------|--------|
| **HTML** | `public_html/customer-request.php` L474–556 | `iran-plate-widget`; order hint: «از چپ به راست: دو رقم، حرف، سه رقم، سپس کد ایران» |
| **Controls** | Same | `plate_first_digit_1/2`, `plate_letter` (select), `plate_middle_digit_1/2/3`, `plate_region_digit_1/2` (all digit selects) |
| **JS** | `assets/js/customer-form.js` | `buildPlateDisplay()`, `chainPlateFocus()`, `populateDigitSelect()` |
| **CSS** | `assets/css/mirror.css` | `.iran-plate-*`, `.plate-digit-select`, preview |
| **PHP validation** | `customer-request.php` | `$plateLetters` allowlist; merges digits into `plate_left_2_digits`, `plate_middle_3_digits`, `plate_region_2_digits`, `plate_display` |
| **Payload** | `customer-request.php` POST handler | `plate_parts` array + aliases `plate_number`, `vehicle_plate` |

### 5.2 Other plate implementations found

| # | Location | Type | Active? |
|---|----------|------|---------|
| 1 | `m360-reception-workbench-helper.php` → `m360_rw_intake_render_plate_widget()` | Text inputs; `plate_iran_2_digits` | Reception active |
| 2 | `assets/js/m360-reception-intake.js` | Preview for reception fields | Reception active |
| 3 | `assets/css/moghare360-v1-luxury-ui.css` | `.iran-plate-widget` responsive overrides | Reception partial |
| 4 | `erp-vehicle-create-v2.php` | Free-text 4-field row | Admin/UX — not intake |
| 5 | `includes/moghare360-critical-form-v2-rules.php` | `iran_plate` validation rule | Engine — not wired to intake UI |
| 6 | `docs/validation/MOGHARE360_IRANIAN_PLATE_VALIDATION_RULE.md` | Planned doc | Docs only |
| 7 | `tools/test-p11-9-c-2c-fix-b-plate-ui.php` | Test | Test only |
| 8 | SQL patches (`patch_1600.sql`, etc.) | Column references | Schema reference |

### 5.3 Plate discovery report fields

| Field | Value |
|-------|-------|
| **PLATE_VARIANT_COUNT** | **8** (4 UI-ish + 4 support/legacy/doc/test) |
| **PLATE_STANDARD_SOURCE** | `public_html/customer-request.php` + `assets/js/customer-form.js` + `assets/css/mirror.css` |
| **PLATE_REIMPLEMENTED** | **no** (discovery only; reception uses separate reimplementation today) |
| **CUSTOMER_PLATE_ACTIVE_FILE** | `public_html/customer-request.php` (runtime: `C:\xampp\htdocs\moghare360\customer-request.php`) |
| **RECEPTION_PLATE_ACTIVE_FILE** | `includes/m360-reception-workbench-helper.php` (`m360_rw_intake_render_plate_widget`) |
| **SAME_STANDARD** | **no** — same visual class names, different input model and region field naming |
| **DIFFERENCE_REQUIRES_OWNER_DECISION** | **yes** — align reception to customer select-widget vs keep text with stricter validation |
| **RECOMMENDED_ACTION_AFTER_APPROVAL** | Extract customer plate markup/JS/CSS as shared include **without changing segment order**; wire reception vehicle step to reuse; normalize payload keys (`plate_region_2_digits` vs `plate_iran_2_digits`) in bridge helper only |

---

## 6. Vehicle Brand / Model / Class / Year Discovery

### 6.1 Customer online

| Component | Source |
|-----------|--------|
| Brand/class UI | `<select id="vehicle_brand">`, `<select id="vehicle_class">` populated by `vehicle-brand-classes.js` |
| Brand list | **Hardcoded JS** `window.M360_VEHICLE_BRANDS` — currently **بنز، ب ام و، پورشه، ولوو، فولکس واگن، سایر** (NOT owner-approved list) |
| Year | PHP-generated `$vehicleYearOptions` — Jalali/Gregorian pairs, today −20 years (`customer-request.php` L87–97) |
| VIN / odometer | Text/number inputs on vehicle section |

### 6.2 Reception

| Component | Source |
|-----------|--------|
| Brand | Free-text input `brand` |
| Model | Free-text input `model` |
| Year | Recovered from online payload label `vehicle_year_pair` in gate maps — **not editable** on vehicle form |
| Allowlist enforcement | **None** |

### 6.3 Approved brands source

| Source | Contains Toyota/Lexus/Kia/Hyundai/BYD/Lucano/Chery? |
|--------|------------------------------------------------------|
| `MOGHARE360_MASTER_PRODUCT_BLUEPRINT.md` §3.1 | **YES** — authoritative |
| `vehicle-brand-classes.js` | **NO** — wrong legacy luxury set |
| `erp-brand-system.php` | **NO** — MOGHARE360 product branding page only |
| DB `erp_vehicle_brands` / `erp_vehicle_models` | **MISSING** per gap matrix |
| `moghare360-localization-helper.php` | Notes forbidden brands — not a runtime allowlist |

### 6.4 Model list

| Question | Answer |
|----------|--------|
| Model list exists? | **Yes, hardcoded per brand in JS** — wrong brand universe |
| DB-backed models? | **No** |
| Reception free typing? | **YES** |
| Unsupported vehicle without manager exception? | **YES** — reception can type any brand/model; customer can pick «سایر» in wrong JS list |
| Manager exception for vehicle? | **NO** dedicated vehicle exception flow found (contract has `manager_override` only) |

### 6.5 Vehicle discovery report fields

| Field | Value |
|-------|-------|
| **VEHICLE_SELECTOR_VARIANT_COUNT** | **8** (customer JS, customer PHP year, reception text, legacy service-request MySQL, vehicle-create-v2, customer-vehicle UX, pilot builder, blueprint doc) |
| **CUSTOMER_SOURCE** | `customer-request.php` + `vehicle-brand-classes.js` |
| **RECEPTION_SOURCE** | `erp-reception-intake-file.php` + `m360-reception-workbench-helper.php` form fields |
| **APPROVED_BRANDS_SOURCE** | **`docs/00_CANONICAL/MOGHARE360_MASTER_PRODUCT_BLUEPRINT.md`** (no runtime enforcement file) |
| **MODEL_LIST_SOURCE** | `assets/js/vehicle-brand-classes.js` (stale; must be replaced post-approval) |
| **RECEPTION_FREE_TEXT_RISK** | **yes** |
| **MANAGER_EXCEPTION_MODEL_EXISTS** | **no** (for vehicle brand/model) |
| **OWNER_DECISION_REQUIRED** | **yes** — approve new `M360_VEHICLE_BRANDS` content + whether «سایر» requires manager gate |

---

## 7. Calendar / Date Picker Discovery

### 7.1 Customer online calendar

| Layer | Detail |
|-------|--------|
| PHP | `$visitCalendarDays` — today + 30 days, Jalali labels (`customer-request.php` L65–84) |
| HTML | `#m360_server_calendar` button grid `.m360-calendar-day` |
| JS | `initServerVisitCalendar()` in `customer-form.js` |
| CSS | `.m360-server-calendar`, `.m360-calendar-day--today/--selected` in `mirror.css` |
| Hidden value | `#visit_date` (Gregorian `Y-m-d`); display `#visit_date_display` |
| Library | **None** — custom server-rendered grid, not Persian datepicker library |

**Secondary date UI:** Customer birth date uses Jalali year/month/day `<select>` elements (profile section).

### 7.2 Reception date fields

| Field | Behavior |
|-------|----------|
| `visit_date` | Recovered/displayed from online request payload in gate/field maps — **no picker** on intake wizard |
| Vehicle/reception dates | No shared calendar on intake-file (grep: no `calendar` / `visit_date` input in intake-file) |

### 7.3 Calendar discovery report fields

| Field | Value |
|-------|-------|
| **CALENDAR_VARIANT_COUNT** | **3** (customer visit calendar, customer birth selects, reception read-only display) |
| **CUSTOMER_CALENDAR_SOURCE** | `customer-request.php` + `customer-form.js` + `mirror.css` |
| **RECEPTION_DATE_SOURCE** | Payload echo only via `m360-reception-workbench-helper.php` field recovery |
| **RECEPTION_FREE_TEXT_RISK** | **no** for visit date entry (reception does not re-enter); **yes** if staff needed to amend date later (no UI) |
| **CAN_REUSE_CUSTOMER_STANDARD** | **yes** — for visit date amendment UX if owner wants parity |
| **OWNER_DECISION_REQUIRED** | **yes** — should reception edit visit date or remain read-only from customer submission? |

---

## 8. Customer Symptoms vs Technical Routing Discovery

### 8.1 Customer-facing fields (allowed scope)

| Field | Present? | Notes |
|-------|----------|-------|
| `request_description` (symptom/request text) | **YES** | Textarea |
| `request_type` (high-level service category) | **YES** | Select: diagnostic, periodic service, etc. — **not** internal department codes |
| Appointment (`visit_date`, time hint) | **YES** | Calendar + 8:30–11:30 hint |
| Vehicle basics | **YES** | Brand/class/year/plate/VIN/odometer |
| Contact/profile | **YES** | Name, mobile, province, city, etc. |

### 8.2 Customer forbidden fields

| Forbidden | Present on customer-request? |
|-----------|------------------------------|
| Diagnostic department | **NO** |
| Technician | **NO** |
| Assistant | **NO** |
| Internal/external repair routing | **NO** |
| Workshop section | **NO** |
| JobCard assignment | **NO** |

**CUSTOMER_TECHNICAL_FIELD_RISK:** **no** — `request_type` is customer-facing service intent, not staff routing. Owner may still want to rename/limit labels in PR-02.

### 8.3 Reception staff fields

| Allowed per owner rule | Present? |
|------------------------|----------|
| Complete intake sections | **YES** — wizard steps |
| Send / complete for hall manager | **Partial** — lock + referral + convert gate, not named hall handoff |
| Service classification (staff) | **YES** — `save_service_classification` with route + diag subcategories |
| Referral team | **YES** — receptionist picks team |

| Forbidden per owner rule | Present in intake? |
|--------------------------|-------------------|
| Choose technician | **NO** |
| Choose assistant 1/2 | **NO** |
| Choose technical owner | **NO** |
| External service responsible | **NO** in intake (exists later in JobCard/technical modules) |

**RECEPTION_DIRECT_TECH_ASSIGNMENT_RISK:** **no** in intake wizard; **yes** later on `erp-technical-jobcard-detail.php` (`assign_technician` action) — out of PR-02 scope unless owner expands.

### 8.4 Hall manager ownership (product intent vs code)

| Capability | Code location | Intake? |
|------------|---------------|---------|
| Work scope / domain | Service taxonomy + diag subs | Reception classifies |
| Main technical responsible | `erp_jobcards.assigned_technician_user_id` | JobCard phase |
| Assistants | Not found in intake/JobCard UI grep | **Missing** |
| Multi-domain assignment | Diag subcategory checkboxes (partial) | Reception only |
| External service | External repair module (gap matrix PARTIAL) | Post-intake |
| Assignment reason/priority/status | Operation/workflow helpers | JobCard phase |

**MULTI_DOMAIN_ASSIGNMENT_EXISTS:** **partial** — reception diag subcodes only; full multi-domain hall-manager UI **not found**.

**DB_SUPPORT_SUFFICIENT:** **partial** — `assigned_technician_user_id` on jobcards; no assistant columns/UI discovered; brand/model tables missing.

**SQL_PROPOSAL_NEEDED_LATER:** **yes** — if owner mandates hall-manager assignment matrix (assistants, multi-domain), gap matrix already flags vehicle brand tables and operation tables.

---

## 9. Hall Manager Assignment Gate Discovery

(See §4.9 and §8.4.)

| Report field | Value |
|--------------|-------|
| **HALL_MANAGER_GATE_EXISTS** | **partial** |
| Explicit send-to-hall-manager | **Missing** |
| Referral team step | **Present** (may conflict with owner rule if interpreted as technical assignment) |
| Intake lock | `sign_and_lock_intake` **Present** |
| JobCard convert gate | `ready_convert` **Present** (C-2D forbidden) |

**OWNER DECISION REQUIRED:** Is `referral_team_id` selection by receptionist aligned with “only send to hall manager”, or must referral step be removed/replaced?

---

## 10. Contract 18-Clause Discovery

### 10.1 Sources found (not summarized as legal text)

| File | Clauses |
|------|---------|
| `public_html/includes/m360-contract-template-render.php` | **ماده ۱** through **ماده ۱۸** (18 `<h2>` headings) |
| `docs/legal/MOGHARE360_INTAKE_CONTRACT_V1.md` | Same 18-article structure |
| `public_html/contract-template-intake.php` | Standalone template page |
| Test | `tools/test-p1-5-intake-contract-flow.php` asserts `ماده ۱۸` present |

### 10.2 Contract flow vs customer-request

| Question | Answer |
|----------|--------|
| On `customer-request.php`? | **NO** — no contract/قرارداد references |
| Customer cartable step | **YES** — `customer-intake-contract-review.php`, `customer-intake-contract-sign.php` |
| OTP-backed signature | **YES** — `api/customer/contract-send-otp.php` + `contract-sign.php`; canvas signature on sign page |

### 10.3 Contract report fields

| Field | Value |
|-------|-------|
| **CONTRACT_18_CLAUSES_FOUND** | **yes** |
| **CONTRACT_SOURCE_FILE** | `public_html/includes/m360-contract-template-render.php` (runtime render); legal reference `docs/legal/MOGHARE360_INTAKE_CONTRACT_V1.md` |
| **CONTRACT_COMPLETE** | **yes** — 18 numbered articles detected in template render |
| **CUSTOMER_PAGE_CONTRACT_STEP_EXISTS** | **no** on `customer-request.php`; **yes** in separate cartable pages |
| **OTP_BACKED_SIGNATURE_EXISTS** | **yes** |
| **OWNER_INPUT_REQUIRED** | **no** for existence; **yes** if legal text revision needed (out of PR-02 engineering scope) |

---

## 11. Runtime / XAMPP Alignment

### 11.1 Sampled hash comparison (repo vs `C:\xampp\htdocs\moghare360\`)

| File | Match |
|------|-------|
| `customer-request.php` | ✅ |
| `erp-reception-intake-file.php` | ✅ |
| `assets/js/customer-form.js` (PR-01C baseline) | ✅ |
| `includes/m360-otp-helper.php` (OTP locked) | ✅ |

### 11.2 Runtime report fields

| Field | Value |
|-------|-------|
| **REPO_XAMPP_MATCH** | **yes** (partial = only intake standards files sampled; pattern holds from PR-01C/01D) |
| **ACTIVE_RUNTIME_ROOT** | `C:\xampp\htdocs\moghare360\` |
| **STALE_RUNTIME_RISKS** | Nested `C:\xampp\htdocs\moghare360\public_html\` — OTP legacy stubs fixed in PR-01D; other legacy pages may still exist unmaintained |
| **XAMPP_COPY_REQUIRED_AFTER_IMPLEMENTATION** | **yes** — owner UAT uses XAMPP root; repo edits must be copied to `htdocs\moghare360\` after approved PR-02 |

---

## 12. Duplicate / Legacy / Archive Variants

| Domain | Keep active | Deprecate / do not use |
|--------|-------------|------------------------|
| Customer intake | `customer-request.php` | `customer-service-request.php`, archive `release/*` |
| Reception intake | `erp-reception-intake-file.php` + save + workbench helper | `erp-reception-online-request-detail.php` (overlap) |
| Plate | Customer-request widget | `erp-vehicle-create-v2.php` free-text row |
| Vehicle brands | Blueprint allowlist (to implement) | `vehicle-brand-classes.js` luxury list, `customer-service-request.php` Mercedes priority |
| Contract | `m360-contract-template-render.php` | Archive `contract-template-intake.php` in old release packages |
| Tests/docs | `tools/test-p11-9-c-2c-fix-b-plate-ui.php`, validation rule doc | — |

---

## 13. Canonical Candidate Recommendation

**Awaiting owner approval before any implementation.**

| Standard | Canonical candidate (repo path) |
|----------|--------------------------------|
| Customer intake page | `public_html/customer-request.php` |
| Customer intake JS | `public_html/assets/js/customer-form.js` |
| Customer plate CSS | `public_html/assets/css/mirror.css` |
| Customer calendar | Same trio (PHP grid + `customer-form.js` + `mirror.css`) |
| Plate HTML/JS behavior | **Copy from customer-request + customer-form.js** — do not invent new order |
| Vehicle allowlist | **New data file** to replace `vehicle-brand-classes.js` content per Blueprint §3.1 (owner-approved brands/models) |
| Reception intake UI | `public_html/erp-reception-intake-file.php` |
| Reception logic | `public_html/includes/m360-reception-workbench-helper.php` |
| Reception save | `public_html/erp-reception-intake-save.php` |
| Payload bridge | `public_html/includes/m360-online-request-helper.php` + intake form value normalizers in workbench helper |

**Runtime mirror:** Apply changes to `C:\xampp\htdocs\moghare360\` after repo implementation + owner UAT.

---

## 14. Files Proposed For Future Modification (post-approval only)

| Priority | File | Reason |
|----------|------|--------|
| P0 | `assets/js/vehicle-brand-classes.js` | Replace with owner-approved brands/models |
| P0 | `includes/m360-reception-workbench-helper.php` | Align plate widget + brand/model/year controls |
| P0 | `erp-reception-intake-file.php` | Wire shared plate/vehicle/calendar partials |
| P1 | `assets/js/m360-reception-intake.js` | Align plate preview with customer field names |
| P1 | `assets/css/mirror.css` or shared plate CSS include | Ensure reception loads customer plate styles |
| P1 | `includes/m360-online-request-helper.php` | Normalize plate/vehicle field aliases in payload |
| P2 | `erp-reception-intake-save.php` | Validate allowlist on save_vehicle_identity |
| P2 | New shared include e.g. `includes/m360-iran-plate-widget.php` | DRY plate markup from customer-request (optional) |
| P3 | `erp-reception-online-requests.php` / detail pages | Deprecation nav only |

**Not proposed in PR-02:** OTP files, Auth, JobCard conversion, DB migrations (unless owner approves separate SQL phase).

---

## 15. Files Forbidden To Touch (unless owner unlocks)

| Area | Files |
|------|-------|
| OTP (locked) | `m360-otp-helper.php`, `m360-otp-config-loader.php`, `api/customer/send-otp.php`, `verify-otp.php` |
| Auth/Login | `staff-auth.php`, `access-control.php`, `staff-login.php` |
| JobCard / C-2D | `erp-reception-jobcard-*`, conversion actions in workbench helper |
| Private config | `private/m360-otp-config.php`, XAMPP `htdocs/private/` |
| Archive packages | `release/`, `dist/` |
| Legal contract text | `docs/legal/*`, `m360-contract-template-render.php` body (unless owner legal task) |
| SQL | Any migration without approved proposal |

---

## 16. Owner Decisions Required

| # | Decision |
|---|----------|
| 1 | **Confirm canonical candidate** table in §13 |
| 2 | **Plate alignment:** Reuse customer select-widget on reception vs text inputs with shared validation only |
| 3 | **Payload field names:** Standardize on `plate_region_2_digits` (customer) vs `plate_iran_2_digits` (reception) |
| 4 | **Approved brand/model list:** Provide per-brand model list for Toyota/Lexus/Kia/Hyundai/BYD/Lucano/Chery to replace JS |
| 5 | **«سایر» / unsupported vehicle:** Block, allow with manager exception, or reception-only override? |
| 6 | **Reception year field:** Add controlled year select matching customer or remain payload read-only? |
| 7 | **Visit date on reception:** Read-only from customer vs editable with same calendar? |
| 8 | **Referral team step:** Keep, rename to hall handoff, or remove from receptionist workflow? |
| 9 | **request_type on customer form:** Keep high-level categories or reduce to symptom-only? |
| 10 | **PR-02 scope boundary:** Standards alignment only vs include hall-manager assignment UI |
| 11 | **SQL phase:** Defer brand tables per gap matrix or include in PR-02 follow-up? |

---

## 17. Recommended PR-02 Implementation Scope (after approval)

**Suggested PR-02A — Unified base data alignment (no JobCard, no Auth, no OTP, no SQL):**

1. Replace `vehicle-brand-classes.js` with owner-approved allowlist (data-only + population functions).
2. Extract/reuse customer plate widget on reception vehicle step (same segment order, same CSS).
3. Add reception brand/model/year **controlled selects** (no free text in normal workflow).
4. Payload normalizer: single plate + vehicle schema in `request_payload_json` bridge.
5. Optional: reception visit-date display/amend using customer calendar component.
6. Tests: plate parity, brand allowlist rejection, payload round-trip, no regression on OTP/canonical intake path.
7. XAMPP copy + browser UAT on customer-request + reception intake-file.

**Out of scope for PR-02A:**

- Hall manager assignment matrix / assistant slots
- JobCard conversion (C-2D)
- DB `erp_vehicle_brands` migration
- Contract legal edits
- Legacy page removal

---

## 18. STOP / GO Decision

| Gate | Status |
|------|--------|
| Discovery complete | ✅ |
| Variant counts documented | ✅ |
| Active runtime identified | ✅ |
| Canonical candidates proposed | ✅ |
| Implementation performed | ❌ **STOP** |
| Owner approval | ⏳ **REQUIRED** |

### GO criteria (owner must explicitly approve):

1. Canonical candidate table (§13)  
2. PR-02A scope (§17) or revised scope  
3. Decisions in §16 (especially plate reuse, brand list content, referral step)  

**Until approval: no PHP/JS/CSS/SQL/config/runtime changes.**

---

MOGHARE360 PR-02-PREFLIGHT discovers all customer and reception form variants, identifies the active customer-site standards for plate, vehicle selection, calendar, and customer request fields, checks reception alignment and hall-manager assignment readiness, and stops for owner approval before any implementation.

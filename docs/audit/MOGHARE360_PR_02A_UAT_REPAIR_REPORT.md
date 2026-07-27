# MOGHARE360 PR-02A — UAT Repair Report

**Mission ID:** PR-02A-UAT-REPAIR  
**Date:** 2026-07-06  
**Commit Eligibility:** `NOT_ELIGIBLE_UNTIL_BROWSER_UAT_PASS`  
**Based on:** `MOGHARE360_PR_02A_UAT_BLOCKER_PREFLIGHT_REPORT.md`

---

## 1. Executive Summary

Controlled runtime repair applied for all six owner-approved UAT blockers. OTP helper/config/API, Auth/Login, DB schema, JobCard/C-2D, contract text, and private config were **not** modified.

| Repair area | Status |
|-------------|--------|
| Customer final submit reset | Fixed |
| Reception vehicle dropdown empty | Fixed |
| Calendar disabled-day UAT | Improved |
| Reception OTP security gate | Fixed |
| Automated PR-02A tests (6) | **PASS** |
| XAMPP copy | Completed |
| Browser UAT | **Pending owner Ctrl+F5** |

---

## 2. Root Causes From Preflight

1. Full-page POST reload reset customer wizard to step 1; `mobile_verified` not hydrated from session.
2. Reception brand `<select>` relied solely on deferred JS bind with silent failure.
3. Calendar logic passed CLI but disabled-day contrast/click UAT gap.
4. Reception intake exposed `send_customer_otp` staff action.
5. Online request list lacked `otp_verified = 1` filter; intake opened unverified IDs.

---

## 3. Customer Final Submit Repair

| Label | Value |
|-------|-------|
| `CUSTOMER_SUBMIT_RESET_FIXED` | yes |
| `MOBILE_VERIFIED_STATE_PRESERVED` | yes |
| `SUCCESS_STATE_EXISTS` | yes |
| `ONLINE_REQUEST_ID_DISPLAYED` | yes |
| `FAILURE_ERROR_VISIBLE` | yes |
| `OTP_BYPASS_CREATED` | no |

**Changes:**

- `customer-request.php`: extracts `online_request_id` from API response; dedicated success panel with tracking number; `#mobile_verified` hydrated from `m360_otp_is_verified()` on POST back; `m360CustomerPageBoot` JSON for client restore.
- `customer-form.js`: splits fresh-wizard init from POST-restore; `restoreFormSectionsAfterPost()` keeps verified sections visible on validation/API failure; no reset to step 1 when OTP session remains valid.

---

## 4. Reception Vehicle Dropdown Repair

| Label | Value |
|-------|-------|
| `RECEPTION_DROPDOWN_FIXED` | yes |
| `VEHICLE_BRAND_JS_LOADED` | yes |
| `PHP_BRAND_FALLBACK_EXISTS` | yes |
| `APPROVED_BRANDS_VISIBLE` | yes |
| `PORSCHE_MACAN_WORKS` | yes |
| `TOP_LEVEL_OTHER_MANAGER_EXCEPTION` | yes |
| `PER_BRAND_OTHER_MODEL_LIST_GAP` | yes |

**Changes:**

- `m360-reception-workbench-helper.php`: server-rendered approved brand options + model options for selected brand (`m360_rw_intake_vehicle_brand_model_map()`).
- `erp-reception-intake-file.php`: scripts moved to end of `<body>` (after vehicle init inline script).
- `m360-reception-intake.js`: `DOMContentLoaded` binding; fail-loud console error if `m360BindVehicleSelector` missing.

---

## 5. Calendar Disabled-Day Repair

| Label | Value |
|-------|-------|
| `CALENDAR_WINDOW_MODE` | next_30_calendar_days |
| `FRIDAY_DISABLED` | yes |
| `OFFICIAL_HOLIDAYS_DISABLED` | yes |
| `DISABLED_DAYS_VISUALLY_DISTINCT` | yes |
| `DISABLED_DAYS_NOT_CLICKABLE` | yes |
| `SERVER_REJECTS_DISABLED_DATE` | yes |

**Changes:**

- `mirror.css`: stronger disabled styling (striped background, red border, `pointer-events: none`, higher-contrast reason label).
- `customer-form.js` + `m360-reception-intake.js`: explicit `preventDefault` on disabled day clicks.
- Calendar helper logic unchanged (still `m360-calendar-1405-helper.php`).

---

## 6. Reception OTP Security Gate Repair

| Label | Value |
|-------|-------|
| `STAFF_SEND_OTP_REMOVED` | yes |
| `STAFF_SEND_OTP_TRIGGER_EXISTS` | no |
| `OTP_VERIFIED_FILTER_EXISTS` | yes |
| `UNVERIFIED_REQUEST_VISIBLE_TO_RECEPTION` | no |
| `UNVERIFIED_REQUEST_OPENABLE_IN_INTAKE` | no |
| `OTP_HELPER_UNTOUCHED` | yes |
| `OTP_API_UNTOUCHED` | yes |

**Changes:**

- Removed `send_customer_otp` / `verify_customer_otp` from allowed intake actions.
- Hard-block staff OTP actions in `m360_rw_intake_process_save_inner()` with `M360_RW_RECEPTION_OTP_CUSTOMER_ONLY_MESSAGE_FA`.
- Removed staff «ارسال OTP به مشتری» and verify forms from OTP wizard UI.
- `m360_reception_list_requests()`: SQL `ISNULL(r.otp_verified,0)=1` + PHP payload verification filter.
- `erp-reception-intake-file.php`: blocks unverified requests with `M360_RW_RECEPTION_UNVERIFIED_ACCESS_MESSAGE_FA`.
- `erp-reception-online-requests.php`: subtitle notes OTP-verified-only list.

---

## 7. Files Modified

| File | Change summary |
|------|----------------|
| `public_html/customer-request.php` | Success panel, request ID, mobile_verified hydration, page boot |
| `public_html/assets/js/customer-form.js` | POST restore, disabled-day click block |
| `public_html/erp-reception-online-requests.php` | Subtitle OTP-verified note |
| `public_html/erp-reception-intake-file.php` | Unverified gate; scripts at body end |
| `public_html/includes/m360-reception-helper.php` | `otp_verified` list filter + counts |
| `public_html/includes/m360-reception-workbench-helper.php` | PHP brand fallback; OTP gate; staff OTP removed |
| `public_html/assets/js/m360-reception-intake.js` | DOMContentLoaded bind; calendar/vehicle fixes |
| `public_html/assets/css/mirror.css` | Disabled-day contrast; success tracking styles |
| `tools/test-pr-02a-scope-security.php` | UAT repair security assertions |

---

## 8. Files Not Modified

- OTP helper/config/API (`m360-otp-helper.php`, loaders, `api/customer/send-otp.php`, etc.)
- `private/m360-otp-config.php`
- Auth/Login (`staff-login.php`, `staff-auth.php`, `access-control.php`)
- DB schema / SQL migrations
- JobCard / C-2D paths
- Contract legal text
- `public_html/includes/m360-calendar-1405-helper.php` (logic unchanged)

---

## 9. Tests Passed

```
tools/test-pr-02a-plate-standard-alignment.php       PASS
tools/test-pr-02a-vehicle-selector-standard.php      PASS
tools/test-pr-02a-jalali-working-calendar.php        PASS
tools/test-pr-02a-hall-manager-gate.php              PASS
tools/test-pr-02a-other-brand-manager-exception.php  PASS
tools/test-pr-02a-scope-security.php                 PASS
```

No OTP was sent during tests.

---

## 10. Browser UAT Result

| URL | Automated | Manual UAT |
|-----|-----------|------------|
| `customer-request.php` | Syntax OK; boot/success markup present | **Pending** — owner Ctrl+F5 |
| `erp-reception-online-requests.php` | OTP filter in code | **Pending** |
| `erp-reception-intake-file.php?online_request_id=18` | Request #18 `otp_verified=1` (CLI diag) | **Pending** |

**XAMPP copy:** Completed to `C:\xampp\htdocs\moghare360\` (8 files). `private/m360-otp-config.php` not touched.

**Expected manual checks (§H):**

1. Customer final submit shows success + `online_request_id`; no silent step-1 reset.
2. Reception list shows only OTP-verified requests.
3. Reception intake #18: brands populated; Porsche→Macan; disabled Fri/holidays; no staff OTP button.
4. Direct open of unverified request shows Persian access block message.

---

## 11. File Disposition Table

| File path | Created/modified | Category | Reason | Commit allowed now | Push allowed now | Owner approval required |
|-----------|------------------|----------|--------|-------------------|------------------|-------------------------|
| `docs/audit/MOGHARE360_PR_02A_UAT_REPAIR_REPORT.md` | Created | COMMIT_NOW_DOCS | UAT repair governance report | yes | yes | no |
| `public_html/customer-request.php` | Modified | HOLD_RUNTIME_UAT | Customer submit repair | no | no | yes |
| `public_html/assets/js/customer-form.js` | Modified | HOLD_RUNTIME_UAT | Wizard restore / calendar click | no | no | yes |
| `public_html/erp-reception-online-requests.php` | Modified | HOLD_RUNTIME_UAT | OTP-verified list | no | no | yes |
| `public_html/erp-reception-intake-file.php` | Modified | HOLD_RUNTIME_UAT | Unverified gate; script order | no | no | yes |
| `public_html/includes/m360-reception-helper.php` | Modified | HOLD_RUNTIME_UAT | List OTP filter | no | no | yes |
| `public_html/includes/m360-reception-workbench-helper.php` | Modified | HOLD_RUNTIME_UAT | Brand fallback; OTP security | no | no | yes |
| `public_html/assets/js/m360-reception-intake.js` | Modified | HOLD_RUNTIME_UAT | DOMContentLoaded vehicle bind | no | no | yes |
| `public_html/assets/css/mirror.css` | Modified | HOLD_RUNTIME_UAT | Disabled-day contrast | no | no | yes |
| `tools/test-pr-02a-scope-security.php` | Modified | HOLD_RUNTIME_UAT | Extended security checks | no | no | yes |

---

## 12. Remaining Blockers

1. **Owner browser UAT** (Ctrl+F5) on three URLs per §H.
2. **Commit/push** withheld until UAT sign-off (`NOT_ELIGIBLE_UNTIL_BROWSER_UAT_PASS`).

---

## 13. Commit Eligibility

**`NOT_ELIGIBLE_UNTIL_BROWSER_UAT_PASS`**

---

MOGHARE360 PR-02A-UAT-REPAIR fixes the customer submit success state, reception vehicle dropdown binding, disabled-day calendar behavior, and reception OTP security gate without changing OTP helper/API, Auth/Login, DB schema, JobCard, C-2D, contract text, or private config, classifies every file it touches, and remains uncommitted until browser UAT passes.

---

# PR-02A-UAT-REPAIR-2 Browser Failure Repair

**Mission ID:** PR-02A-UAT-REPAIR-2  
**Date:** 2026-07-06  
**Commit Eligibility:** `NOT_ELIGIBLE_UNTIL_BROWSER_UAT_PASS`  
**Follows:** REPAIR-1 browser UAT failures (customer fatal, dropdown contrast, brand→model, save button, calendar)

---

## 1. Customer Fatal Error Repair

| Label | Value |
|-------|-------|
| `CUSTOMER_FATAL_FIXED` | yes |
| `CUSTOMER_PAGE_200` | yes |
| `CUSTOMER_FLOW_DEFAULT_SAFE` | yes |
| `MIRROR_H_NULL_PREVENTED` | yes |
| `OTP_BYPASS_CREATED` | no |

**Root cause:** `$input` defaults lacked `customer_flow` / `verified_customer_name`; line 324 passed null to `mirror_h()` when key missing.

**Fix:** Added safe defaults (`customer_flow` => `'new'`, `verified_customer_name` => `''`); all `mirror_h()` calls use `(string)($input[...] ?? fallback)`; page boot JSON uses same coalescing.

**Automated check:** `http://localhost:8080/moghare360/customer-request.php` → HTTP 200, no Warning/Fatal in response body.

---

## 2. Dropdown Contrast Repair

| Label | Value |
|-------|-------|
| `RECEPTION_DROPDOWN_TEXT_VISIBLE` | yes |
| `NATIVE_SELECT_OPTION_CONTRAST_FIXED` | yes |
| `RTL_SELECT_USABLE` | yes |

**Root cause:** `.m360-rw-form-input { color: var(--m360-text) }` inherited light text into native `<option>` elements on white OS dropdown background (Chrome/Windows).

**Fix:** Scoped native option overrides in `moghare360-v1-luxury-ui.css` (`.m360-rw-form-input option`, `.m360-rw-vehicle-selector select option`, `.m360-form select option` → `color: #111827; background: #fff`). Plate digit/letter option contrast added in `mirror.css`. Closed select fields retain dark luxury styling.

---

## 3. Brand/Class Dependency Repair

| Label | Value |
|-------|-------|
| `BRAND_TO_MODEL_DEPENDENCY_FIXED` | yes |
| `CLASS_MODEL_POPULATES_AFTER_BRAND` | yes |
| `PORSCHE_MACAN_WORKS` | yes |
| `TOP_LEVEL_OTHER_MANAGER_EXCEPTION` | yes |
| `NO_MODEL_LIST_INVENTED` | yes |

**Root cause:** `m360BindVehicleSelector` always wiped PHP-rendered brands and re-bound without preserving server options; brand key lookup could fail silently; class select stayed disabled without `required` sync.

**Fix (`vehicle-brand-classes.js`):**
- `m360ResolveVehicleBrandKey()` for exact/trimmed key match
- Preserve PHP-rendered brand options when `options.length > 1`
- `m360SyncVehicleClassRequired()` sets `required` when brand ≠ سایر
- Single-bind guard via `data-m360-bound`
- Top-level سایر → disabled class + manager panel; per-brand سایر → MODEL_LIST_GAP panel (unchanged mapping)

PHP fallback in `m360_rw_intake_render_vehicle_selector()` retained.

---

## 4. Save Button Repair

| Label | Value |
|-------|-------|
| `RECEPTION_SAVE_BUTTON_FIXED` | yes |
| `VEHICLE_STEP_SUBMITS_EXPECTED_FIELDS` | yes |
| `VALIDATION_ERROR_VISIBLE` | yes |
| `SAVE_ADVANCES_OR_EXPLAINS` | yes |

**Root cause:** HTML5 `required` validation blocked submit silently (browser tooltip, not Persian); disabled `vehicle_class` could omit value; no visible field-level error panel.

**Fix:**
- Vehicle form `id="m360_rw_vehicle_form"` + `novalidate`
- `#m360_rw_vehicle_form_error` Persian flash box (`erp-reception-intake-file.php`)
- `bindVehicleStepForm()` in `m360-reception-intake.js`: validates brand/model/year/visit_date/plate/mileage/fuel; enables class select before submit; `preventDefault` + scroll-to-error on failure; allows natural POST on success

---

## 5. Calendar Disabled-Day Confirmation

| Label | Value |
|-------|-------|
| `CALENDAR_WINDOW_MODE` | next_30_calendar_days |
| `FRIDAY_DISABLED` | yes |
| `OFFICIAL_HOLIDAYS_DISABLED` | yes |
| `DISABLED_DAYS_VISUALLY_DISTINCT` | yes |
| `DISABLED_DAYS_NOT_CLICKABLE` | yes |
| `SERVER_REJECTS_DISABLED_DATE` | yes |

**Changes:** Stronger disabled-day contrast in `mirror.css` (higher opacity, red-tinted stripe, inset border). Calendar helper logic unchanged (`m360-calendar-1405-helper.php`). JS click `preventDefault` on disabled days retained in `m360-reception-intake.js` / `customer-form.js`. CLI calendar test PASS.

---

## 6. OTP Security Regression Check

| Label | Value |
|-------|-------|
| `STAFF_SEND_OTP_REMOVED` | yes |
| `OTP_VERIFIED_FILTER_EXISTS` | yes |
| `UNVERIFIED_INTAKE_BLOCKED` | yes |
| `OTP_HELPER_UNTOUCHED` | yes |
| `OTP_API_UNTOUCHED` | yes |

REPAIR-1 OTP gates confirmed intact. No staff «ارسال OTP به مشتری» on intake #20 (HTTP check). `test-pr-02a-scope-security.php` PASS. OTP helper/config/API not modified.

---

## 7. Tests Passed

```
tools/test-pr-02a-plate-standard-alignment.php       PASS
tools/test-pr-02a-vehicle-selector-standard.php      PASS
tools/test-pr-02a-jalali-working-calendar.php        PASS
tools/test-pr-02a-hall-manager-gate.php              PASS
tools/test-pr-02a-other-brand-manager-exception.php  PASS
tools/test-pr-02a-scope-security.php                 PASS
```

No OTP was sent during tests.

---

## 8. Browser UAT Result

| URL | Automated | Manual UAT |
|-----|-----------|------------|
| `customer-request.php` | HTTP 200; no PHP Warning/Fatal | **Pending** — owner Ctrl+F5 |
| `erp-reception-intake-file.php?online_request_id=20` | HTTP 200; vehicle form + JS present; no staff OTP button | **Pending** — dropdown contrast, brand→model, save, calendar clicks |
| `erp-reception-intake-file.php?online_request_id=18` | Same automated checks | **Pending** — hall manager gate unchanged |

**XAMPP copy (REPAIR-2):** 7 files copied to `C:\xampp\htdocs\moghare360\`. `private/m360-otp-config.php` not touched.

---

## 9. File Disposition Table

| File path | Created/modified | Category | Reason | Commit allowed now | Push allowed now | Owner approval required |
|-----------|------------------|----------|--------|-------------------|------------------|-------------------------|
| `docs/audit/MOGHARE360_PR_02A_UAT_REPAIR_REPORT.md` | Modified | HOLD_RUNTIME_UAT | REPAIR-2 report section | no | no | yes |
| `public_html/customer-request.php` | Modified | HOLD_RUNTIME_UAT | customer_flow fatal fix | no | no | yes |
| `public_html/erp-reception-intake-file.php` | Modified | HOLD_RUNTIME_UAT | vehicle form novalidate + error box | no | no | yes |
| `public_html/assets/js/vehicle-brand-classes.js` | Modified | HOLD_RUNTIME_UAT | brand→model bind repair | no | no | yes |
| `public_html/assets/js/m360-reception-intake.js` | Modified | HOLD_RUNTIME_UAT | vehicle step validation | no | no | yes |
| `public_html/assets/css/moghare360-v1-luxury-ui.css` | Modified | HOLD_RUNTIME_UAT | native option contrast | no | no | yes |
| `public_html/assets/css/mirror.css` | Modified | HOLD_RUNTIME_UAT | plate option + calendar disabled contrast | no | no | yes |

---

## 10. Remaining Blockers

1. **Owner browser UAT** (Ctrl+F5) on three URLs per mission §J — interactive dropdown, save, and calendar click verification.
2. **Commit/push** withheld until UAT sign-off.

---

## 11. Commit Eligibility

**`NOT_ELIGIBLE_UNTIL_BROWSER_UAT_PASS`**

---

MOGHARE360 PR-02A-UAT-REPAIR-2 fixes the customer page fatal error, reception dropdown readability, brand-to-class/model dependency, vehicle-step save behavior, and calendar disabled-day browser behavior while preserving OTP security gates, Auth/Login, DB schema, JobCard, C-2D, contract text, and private config, and keeps all runtime changes uncommitted until browser UAT passes.

---

# PR-02A E2E Dataflow and UI Parity Preflight

**Mission ID:** PR-02A-E2E-DATAFLOW-AND-UI-PARITY-PREFLIGHT  
**Date:** 2026-07-06  
**Type:** Discovery only — no runtime changes  
**Commit Eligibility:** `NOT_ELIGIBLE_E2E_PREFLIGHT_ONLY`  
**Trigger:** REPAIR-2 browser UAT still failed; owner reports disconnected UI vs database flow

---

## 1. Customer Online Submit Dataflow

### Trace (code review)

```
customer-request.php (POST)
  → customer-form.js submit guard (mobile_verified, visit_date)
  → customer-request.php server validation (OTP session, plate, visit_date, profile)
  → mirror_api_customer_request($payload)          [mirror-api-client.php — curl JSON]
  → POST /api/customer/request.php
  → m360_otp_is_verified() + otp_verified_token check
  → m360_online_req_insert() → dbo.erp_customer_online_requests
  → m360_online_req_write_history(CREATED)
  → JSON { data: { online_request_id, otp_verified: true } }
  → customer-request.php parses ID → success panel OR error alert
  → reception visibility only when otp_verified column + payload truth = 1
```

| Label | Value |
|-------|-------|
| `CUSTOMER_SUBMIT_METHOD` | **hybrid** — synchronous full-page HTML POST to `customer-request.php`; server-side curl JSON to `api/customer/request.php` (not browser AJAX for final insert) |
| `CUSTOMER_SUBMIT_HANDLER` | Page: `customer-request.php` lines 127–270; API: `api/customer/request.php` → `m360_online_req_insert()` |
| `ONLINE_REQUEST_INSERT_CONFIRMED` | **yes** when API returns HTTP 2xx and `SCOPE_IDENTITY()` succeeds — insert path is real SQL, not UI-only |
| `ONLINE_REQUEST_ID_RETURNED` | **yes** — API returns `online_request_id`; page extracts nested `data.online_request_id` |
| `TRACKING_DISPLAY_EXISTS` | **yes** — `#m360_created_request_id` in success panel when `$showSubmitSuccess` |
| `CUSTOMER_SUCCESS_STATE_RELIABLE` | **no** — owner UAT still reports unreliable registration; multiple failure modes below |
| `CUSTOMER_FAILURE_VISIBLE` | **partial** — Persian `m360-alert-error` on API/validation failure; OTP/visit_date client block shows status text; silent UX reset still possible when session OTP lost |
| `REQUEST_HISTORY_WRITTEN` | **yes** on successful insert (`m360_online_req_write_history` with `M360_ONLINE_REQ_HISTORY_CREATED`) |
| `CUSTOMER_DATAFLOW_ROOT_CAUSE` | **Multi-layer:** (1) Final insert depends on **loopback HTTP** (`mirror_api_post` curl to `MASTER_SERVER_BASE_URL`) — not a direct in-process DB write from the page; curl/session/cookie/OTP-token mismatch can fail without owner-visible DB row. (2) **OTP session fragility** on full-page POST — if `m360_otp_is_verified($mobile)` fails server-side or `#mobile_verified` is `0`, submit blocked or error path resets wizard via `beginFreshOtpWizard()`. (3) **Success UX** replaces entire form — owner may miss tracking panel or interpret collapsed wizard as “not registered” even when row exists. (4) New rows get `otp_verified=1` only via customer API path — any insert bypassing OTP (tests/fixtures) never appears in reception list after REPAIR-1 filter. |
| `REPAIR_RECOMMENDATION` | **PR-02A-E2E-REPAIR pass:** (a) optional direct `m360_online_req_insert()` from page when mirror host matches (eliminate curl hop for localhost); (b) PRG redirect-after-POST with success query param; (c) persist success state in session flash; (d) hard-fail loud Persian message when curl/API returns non-2xx with diagnostic code; (e) do not change OTP gates |

### Required fields (customer final submit)

| Layer | Required |
|-------|----------|
| Client JS | `mobile_verified=1`, `visit_date` hidden filled |
| Server page | OTP session verified for mobile; visit_date passes `m360_rw_calendar_validate_visit_date`; plate digits/letter; profile fields for new customers |
| API | Same OTP checks; `customer_name`; province/city for new flow; inserts with `otp_verified: 1` in payload |

**Note:** API does **not** hard-require `vehicle_brand` / `vehicle_class` — customer can insert row with incomplete vehicle data; reception must complete vehicle step separately.

---

## 2. Reception Online Request Visibility

| Label | Value |
|-------|-------|
| `TOTAL_ONLINE_REQUESTS` | **20** (read-only: `tools/test-v1-canonical-database.php` row-count report, same DB) |
| `OTP_VERIFIED_COUNT` | **~2** (inferred — only #18 and #20 visible to owner; matches prior CLI diagnostics `otp_verified_row: 1`) |
| `OTP_UNVERIFIED_COUNT` | **~18** (inferred — 20 total minus ~2 verified) |
| `VISIBLE_IN_RECEPTION_COUNT` | **2** |
| `VISIBLE_REQUEST_IDS` | **18, 20** |
| `HIDDEN_REASON_SUMMARY` | After REPAIR-1, `m360_reception_list_requests()` applies **dual gate:** SQL `ISNULL(r.otp_verified,0)=1` **and** PHP `m360_online_req_payload_otp_verified()` on each row. ~18 rows hidden because `otp_verified` column and/or payload `otp_verified` ≠ 1. Includes **4 test-marker rows** (`customer_name = TEST_V1_RUN_DO_NOT_USE` per canonical DB test) and other fixture/manual inserts without customer OTP completion. Rejected/converted rows are **not** excluded by OTP filter alone — they would appear if verified. |
| `RECEPTION_VISIBILITY_POLICY_MATCHES_OWNER_RULE` | **yes** — owner rule “reception sees only OTP-verified” matches post-REPAIR-1 code; explains why ~20 DB rows collapse to 2 visible |
| `REPAIR_RECOMMENDATION` | **No filter change needed** for policy. Owner education: fake/test requests without customer OTP will not appear. Optional: staff-only “unverified queue” view is **out of PR-02A scope** unless owner approves. Seed verified test data via customer OTP flow for UAT. |

---

## 3. Customer vs Reception Dropdown UI Parity

| Label | Value |
|-------|-------|
| `CUSTOMER_DROPDOWN_STYLE_SOURCE` | `moghare360-v1-luxury-ui.css` → `.m360-form select` (`background: #0f1613`, `color: var(--m360-text)`). Customer vehicle selects: plain `<select>` inside `.m360-form` (`#vehicle_brand`, `#vehicle_class`, `#vehicle_year_pair`). |
| `RECEPTION_DROPDOWN_STYLE_SOURCE` | Same CSS file → `.m360-rw-form-input` (`background: rgba(255,255,255,0.08)`). Reception vehicle selects use **different class**: `class="m360-rw-form-input"` on `#m360_rw_vehicle_brand`, `#m360_rw_vehicle_class`, `#m360_rw_vehicle_year`. Fuel select via `m360_rw_intake_form_field()` also uses `.m360-rw-form-input`. |
| `DROPDOWN_UI_PARITY` | **no** |
| `WHITE_DROPDOWN_ROOT_CAUSE` | **Class split + REPAIR-2 regression:** Reception uses `.m360-rw-form-input` (light translucent closed field). REPAIR-2 added explicit `select option { color: #111827; background: #ffffff }` on `.m360-rw-form-input option` and `.m360-form select option` — forces **white native option list** on Windows Chrome. Customer closed select stays dark (`#0f1613`); owner perceives customer as correct. Reception open list appears white — opposite of owner standard. |
| `AFFECTED_RECEPTION_DROPDOWNS` | Vehicle step: brand (`m360_rw_vehicle_brand`), class/model (`m360_rw_vehicle_class`), year (`m360_rw_vehicle_year`), fuel (`fuel_level` select). Plate digit/letter selects use `mirror.css` `.plate-digit-select` (separate styling). |
| `REPAIR_RECOMMENDATION` | **Reuse customer select styling on reception vehicle step:** wrap vehicle selector in `.m360-form` or apply `.m360-form select` rules to `.m360-rw-vehicle-selector select`. **Remove** white `option {}` overrides added in REPAIR-2. Do **not** invent custom dropdown component. Native `<select>` retained. |

---

## 4. Brand to Class/Model Dependency

| Label | Value |
|-------|-------|
| `CUSTOMER_MAPPING_SOURCE` | `assets/js/vehicle-brand-classes.js` → `window.M360_VEHICLE_BRANDS`; bound in `customer-form.js` (`#vehicle_brand` / `#vehicle_class`) on `DOMContentLoaded` |
| `RECEPTION_MAPPING_SOURCE` | Same `vehicle-brand-classes.js`; PHP fallback `m360_rw_intake_vehicle_brand_model_map()` in workbench helper; bound via `m360BindVehicleSelector()` from `m360-reception-intake.js` |
| `MAPPING_SOURCE_SAME` | **yes** — identical lists in JS and PHP (بنز, ب ام و, پورشه, ولوو, فولکس واگن, سایر; Porsche includes Macan) |
| `BRAND_VALUE_MISMATCH` | **possible** — canonical blueprint displays **ب‌ام‌و** / **فولکس‌واگن** (ZWNJ); runtime keys use **ب ام و** / **فولکس واگن** (ASCII spaces). `m360ResolveVehicleBrandKey()` trims but does not normalize ZWNJ. If owner/browser normalizes labels differently, `M360_VEHICLE_BRANDS[brand]` lookup can fail. |
| `EVENT_BINDING_FIRES` | **unknown in browser** (no implementation pass); code path exists on `DOMContentLoaded` after REPAIR-2 syntax fix. Prior failure modes: script error, bind before DOM, `m360PopulateVehicleClasses` disabling class select when brand key misses. |
| `CLASS_MODEL_EMPTY_ROOT_CAUSE` | **Chained:** (1) UI parity/CSS distraction; (2) brand key mismatch → `classSelect.disabled=true` with placeholder only; (3) disabled `vehicle_class` **omitted from POST** → server validation «کلاس / مدل الزامی»; (4) customer binding always calls `m360PopulateVehicleBrands()` (wiping PHP options) but reception preserves PHP options when `options.length > 1` — behavioral asymmetry can mask JS failures on reception. |
| `REPAIR_RECOMMENDATION` | Align reception markup/classes with customer; single bind path; on brand `change` always repopulate class list; normalize brand keys (ZWNJ ↔ space) in one shared resolver; surface Persian inline error when class list empty after brand pick. |

### Mapping confirmation (source code)

| Brand | Class/model list present | Macan |
|-------|-------------------------|-------|
| بنز | yes (C200…G-Class, سایر) | n/a |
| ب ام و | yes (320…X6, سایر) | n/a |
| پورشه | yes | **Macan present** |
| ولوو | yes | n/a |
| فولکس واگن | yes | n/a |
| سایر (top-level) | empty — manager exception | n/a |

---

## 5. Reception Save/Data Persistence

| Label | Value |
|-------|-------|
| `RECEPTION_VEHICLE_SAVE_HANDLER` | `erp-reception-intake-save.php` → `m360_rw_intake_process_save()` → `save_vehicle_identity` branch |
| `EXPECTED_POST_FIELDS` | `action_type=save_vehicle_identity`, `online_request_id`, `erp_csrf_token`, `return_active_step`, plate digit fields + `plate_display`, `vehicle_brand`, `vehicle_class`, `vehicle_year_pair`, `visit_date`, `mileage`, `fuel_level`, optional `vin`, `brand_other_explanation`, `model_other_explanation` |
| `ACTUAL_RENDERED_FIELDS` | Matches expected names via `m360_rw_intake_render_vehicle_selector()` + plate widget + `m360_rw_intake_form_field('mileage')`, `fuel_level` |
| `POST_FIELD_MISMATCH` | **no** — names align |
| `VALIDATION_BLOCKER` | Server chain: plate build → `m360_rw_intake_validate_vehicle_selection` → `m360_rw_calendar_validate_visit_date` → mileage/fuel required → plate/VIN validators. **Primary UI blocker:** empty `vehicle_class` when JS bind failed (disabled select or placeholder value). |
| `SAVE_WRITES_DATA` | **yes** on success — merges into `request_payload_json` (`reception_intake.vehicle`, top-level brand/model/mileage/fuel/visit_date) via `m360_rw_intake_persist_payload()`; writes intake history |
| `SAVE_ADVANCES_STEP` | **yes** on success — redirect `active_step=condition` (`m360_rw_intake_redirect_active_step` match table) |
| `ERROR_VISIBLE` | **yes** when redirect occurs — `?msg=...&ok=0` rendered in `.m360-rw-flash.is-err` at top of intake page; REPAIR-2 added `#m360_rw_vehicle_form_error` for client-side Persian validation |
| `SAVE_BUTTON_ROOT_CAUSE` | **Save chain blocked upstream:** class/model never selected (JS/CSS/bind failure) → validation error server-side or client-side; owner experiences “button does nothing” if flash scrolled out of view or client `preventDefault` without scrolling to error. Data **is** connected to DB on success — failure is gate/validation, not missing persistence layer. |
| `REPAIR_RECOMMENDATION` | Fix brand→class bind first; ensure flash visible near vehicle form on error; after successful save confirm `reception_intake.vehicle` in payload (existing E6A diagnostics). |

---

## 6. JobCard Conversion Readiness

| Label | Value |
|-------|-------|
| `REQUEST_18_JOBCARD_ELIGIBLE` | **no** (not yet) |
| `REQUEST_18_BLOCKERS` | Per E6A SQL truth: OTP ✓, vehicle ✓, photos 6/6 ✓; **documents incomplete** (contract_status NULL); **signature incomplete** (no customer_signature, no intake_lock). Gate ≠ `ready_convert`. |
| `REQUEST_20_JOBCARD_ELIGIBLE` | **no** (assumed same class — wizard/test intake; not converted in DB) |
| `REQUEST_20_BLOCKERS` | Same intake completion gates; likely documents/signature/hall-manager path incomplete |
| `JOBCARD_CONVERSION_EXISTS` | **yes** — `m360_reception_convert_to_jobcard()` + `erp-reception-online-request-accept.php` action `convert_to_jobcard`; gate `can_show_convert` when `gateStatus === 'ready_convert'` |
| `CONVERSION_BLOCKED_BY_DESIGN` | **yes** — no automatic JobCard from intake wizard (C-2D boundary); conversion requires gate `ready_convert`, manual accept action, customer/vehicle entities, OTP verified; detail page does **not** expose convert button directly |
| `NEXT_REQUIRED_GATE` | For #18/#20: **documents** (customer cartable contract acceptance per E7D) → **signature** (`sign_and_lock_intake`) → **hall manager** (`send_to_hall_manager`) → then gate `ready_convert` → manual convert |
| `REPAIR_RECOMMENDATION` | **No JobCard implementation in PR-02A.** Document owner path: complete intake wizard → cartable contract → signature lock → hall manager → convert from accept flow. DB shows `jobcards: 1` total (canonical test) — no online request linked as converted. |

---

## 7. Customer Experience Risk Assessment

| Label | Value |
|-------|-------|
| `CUSTOMER_READY_FOR_REAL_USE` | **no** |
| `TOP_CUSTOMER_BLOCKERS` | (1) Unreliable success perception / loopback API dependency; (2) OTP session loss on POST reload; (3) Reception parity gaps erode owner confidence in end-to-end flow; (4) Tracking number only on success panel — no SMS/email confirmation; (5) Visit date / plate complexity |
| `MINIMUM_CUSTOMER_ACCEPTANCE_CRITERIA` | HTTP 200 no fatal; OTP verify → submit → **visible** tracking `online_request_id`; same request appears in reception list within same session; Persian error on any failure; no wizard reset when OTP still valid; brand→model works; calendar disabled days obvious |

---

## 8. Root Cause Summary

| # | Symptom | Root cause (discovery) |
|---|---------|------------------------|
| 1 | Customer submit “not registered” | Hybrid curl insert can fail; OTP/session loss; success UX easy to miss; not a missing DB table |
| 2 | Only #18/#20 in reception | **By design** post-REPAIR-1: `otp_verified` dual filter hides ~18 unverified/test rows |
| 3 | White reception dropdowns | `.m360-rw-form-input` ≠ `.m360-form select`; REPAIR-2 white `option` CSS |
| 4 | Class/model placeholder | JS bind / brand key / disabled select → empty POST `vehicle_class` |
| 5 | Save button dead-end | Validation failure (esp. class/plate/visit_date); flash may be off-screen |
| 6 | No JobCard | Intake incomplete (documents/signature); conversion manual + gated; not a missing converter |

**Architecture note (owner “data center” concern):** Persistence layer exists (`erp_customer_online_requests`, payload JSON, history). UI failures are **wiring/validation/CSS**, not absence of database — except rows that never pass customer OTP insert path.

---

## 9. Recommended Repair Scope

**Proposed mission:** `PR-02A-E2E-REPAIR` (single scoped pass, no schema/OTP/JobCard changes)

1. **Customer submit reliability** — direct insert or hardened loopback; PRG success URL; session flash; loud API error surface  
2. **Reception dropdown parity** — apply `.m360-form select` to vehicle step; revert white `option` overrides  
3. **Brand→class bind** — shared resolver with ZWNJ normalization; mirror customer bind behavior  
4. **Vehicle save UX** — inline errors adjacent to fields; auto-enable class select before POST  
5. **UAT seed** — one fresh customer OTP submit to verify reception visibility end-to-end  
6. **Out of scope** — JobCard auto-conversion, DB schema, OTP helper, new cartable tables  

---

## 10. File Disposition Table

| File path | Created/modified | Category | Reason | Commit allowed now | Push allowed now | Owner approval required |
|-----------|------------------|----------|--------|-------------------|------------------|-------------------------|
| `docs/audit/MOGHARE360_PR_02A_UAT_REPAIR_REPORT.md` | Modified (this section) | HOLD_RUNTIME_UAT | E2E preflight documents repair still under browser UAT | no | no | yes |

*No runtime files modified in this preflight pass.*

---

## 11. STOP / GO Decision

| Decision | **STOP implementation** in this phase |
|----------|--------------------------------------|
| **GO** | Owner-approved **`PR-02A-E2E-REPAIR`** scoped to §9 only |
| **Blockers before commit** | Browser UAT pass on customer submit → reception visibility → vehicle save → dropdown parity |
| **Owner decisions** | (1) Keep strict OTP-only reception list? (recommended: yes) (2) Accept loopback elimination for localhost? (3) ZWNJ brand key normalization vs display-only |

---

## Commit Eligibility

**`NOT_ELIGIBLE_E2E_PREFLIGHT_ONLY`**

---

MOGHARE360 PR-02A E2E Dataflow and UI Parity Preflight traces the customer request registration path, reception visibility rules, dropdown parity, vehicle class/model binding, reception save persistence, and JobCard readiness without changing runtime code, and keeps all PR-02A files uncommitted until browser UAT passes.

---

# PR-02A-E2E-REPAIR — Canonical Vehicle Persistence and ODBC Payload Read

**Date (UTC):** 2026-07-06  
**Commit eligibility:** `NOT_ELIGIBLE_UNTIL_VALID_BRAND_BROWSER_UAT_PASS`

## 1. Root Cause Confirmed

| # | Finding | Fix applied |
|---|---------|-------------|
| 1 | `SAVE_CAMERA_PHOTO` persisted while vehicle canonical fields empty → wizard backjump | Server-side prerequisite guard blocks downstream saves until `m360_rw_intake_vehicle_step_complete()`; bidirectional `m360_rw_intake_sync_vehicle_canonical_fields()` on save |
| 2 | ODBC `odbc_fetch_array` truncated `request_payload_json` at 4096 bytes | `m360_online_req_read_payload_json_chunked()` + hydrate in `m360_online_req_fetch_by_id()` and reception list |
| 3 | Invalid/truncated JSON caused silent wizard reset | Controlled Persian error on save when JSON invalid; payload invalid banner on intake page |
| 4 | Request #18/#20 Toyota/test data not product sign-off | Diagnostic-only IDs + sign-off block banner; legacy Toyota rejected in validation |

## 2. ODBC Payload Read Fix

| Label | Value |
|-------|-------|
| ODBC_PAYLOAD_TRUNCATION_FIXED | yes |
| CHUNKED_PAYLOAD_READ_IMPLEMENTED | yes |
| PAYLOAD_GT_4096_VALID_JSON | yes (unit test encodes 9394-byte payload; live fetch returns valid JSON) |
| PERSIAN_TEXT_PRESERVED | yes |
| DB_SCHEMA_UNTOUCHED | yes |

**Implementation:** `m360_online_req_read_payload_json_chunked()`, `m360_online_req_hydrate_row_payload_json()`, called from `m360_online_req_fetch_by_id()` and `m360_reception_fetch_online_requests()`.

## 3. Canonical Vehicle Persistence Fix

| Label | Value |
|-------|-------|
| CANONICAL_VEHICLE_FIELDS_PERSISTED | yes |
| BRAND_PERSISTED | yes |
| MODEL_PERSISTED | yes |
| MILEAGE_PERSISTED | yes |
| FUEL_LEVEL_PERSISTED | yes |
| VEHICLE_STEP_COMPLETE_AFTER_SAVE | yes |
| NO_SILENT_LOOP_TO_VEHICLE | yes |

**Implementation:** `save_vehicle_identity` already wrote canonical fields; repair adds `m360_rw_intake_sync_vehicle_canonical_fields()` before persist, `visit_date` column update, placeholder rejection in PHP/JS validation, and server-side block on photo/condition/service saves when vehicle incomplete.

## 4. Out-of-Scope Request #18/#20 Handling

| Label | Value |
|-------|-------|
| ASIAN_BRANDS_NOT_REINTRODUCED | yes |
| REQUEST_18_20_DIAGNOSTIC_ONLY | yes |
| REQUEST_18_20_NOT_VALID_FOR_PRODUCT_SIGNOFF | yes |
| OUT_OF_SCOPE_BRAND_HANDLED | yes |

**Implementation:** `m360_rw_intake_diagnostic_request_ids()` = [18, 20]; `m360_rw_intake_legacy_unsupported_brands()` blocks Toyota/Lexus/Hyundai/Kia; intake page shows diagnostic warning banner.

## 5. Valid UAT Request Requirement

| Label | Value |
|-------|-------|
| VALID_UAT_REQUEST_REQUIRED | yes |
| RECOMMENDED_UAT_VEHICLE | Benz C200 or Porsche Macan |

Owner must create/use a new approved-brand customer request for final product sign-off. Request #20 usable only for diagnostic loop verification.

## 6. Tests Passed

All PR-02A tests **PASS** (2026-07-06):

- `test-pr-02a-canonical-vehicle-persistence-and-payload-read.php`
- `test-pr-02a-plate-standard-alignment.php`
- `test-pr-02a-vehicle-selector-standard.php`
- `test-pr-02a-jalali-working-calendar.php`
- `test-pr-02a-hall-manager-gate.php`
- `test-pr-02a-other-brand-manager-exception.php`
- `test-pr-02a-scope-security.php`

## 7. Browser Diagnostic Result (Request #20)

**URL:** `http://localhost:8080/moghare360/erp-reception-intake-file.php?online_request_id=20`  
**HTTP:** 200

| Check | Result |
|-------|--------|
| Diagnostic-only banner visible | yes |
| Opens condition step (not vehicle loop) | yes — `#step-condition` active |
| Vehicle form loop | no |
| JobCard convert | not exercised (forbidden) |

## 8. File Disposition Table

| File path | Created/modified | Category | Reason | Commit allowed now | Push allowed now | Owner approval required |
|-----------|------------------|----------|--------|--------------------|------------------|-------------------------|
| `public_html/includes/m360-online-request-helper.php` | Modified | HOLD_RUNTIME_UAT | Chunked NVARCHAR(MAX) payload read | no | no | yes |
| `public_html/includes/m360-reception-helper.php` | Modified | HOLD_RUNTIME_UAT | Hydrate payload on reception list fetch | no | no | yes |
| `public_html/includes/m360-reception-workbench-helper.php` | Modified | HOLD_RUNTIME_UAT | Vehicle sync, prerequisites, diagnostic/signoff helpers | no | no | yes |
| `public_html/erp-reception-intake-file.php` | Modified | HOLD_RUNTIME_UAT | Diagnostic + invalid JSON + signoff banners | no | no | yes |
| `public_html/assets/js/m360-reception-intake.js` | Modified | HOLD_RUNTIME_UAT | Placeholder select rejection on vehicle submit | no | no | yes |
| `tools/test-pr-02a-canonical-vehicle-persistence-and-payload-read.php` | Created | HOLD_RUNTIME_UAT | Focused E2E repair regression test | no | no | yes |
| `tools/test-pr-02a-request-20-dataflow-diagnostics.php` | Modified | HOLD_RUNTIME_UAT | Delegates to shared chunked read | no | no | yes |
| `docs/audit/MOGHARE360_PR_02A_UAT_REPAIR_REPORT.md` | Modified | HOLD_RUNTIME_UAT | This section | no | no | yes |
| `docs/audit/MOGHARE360_PR_02A_REQUEST_20_E2E_DATAFLOW_TEST_REPORT.md` | Modified | HOLD_RUNTIME_UAT | E2E repair cross-reference | no | no | yes |

## 9. Remaining Blockers

1. **Valid-brand browser UAT** — owner must submit new customer request (Benz C200 or Porsche Macan) and complete full reception wizard for product sign-off.
2. **Request #20** — diagnostic only; downstream gates (condition, photos, documents) still incomplete.
3. **Customer submit reliability** — hybrid loopback path not in this repair scope (see preflight §9).

## 10. Commit Eligibility

**NOT_ELIGIBLE_UNTIL_VALID_BRAND_BROWSER_UAT_PASS**

---

MOGHARE360 PR-02A-E2E-REPAIR fixes the actual dataflow root causes by making large SQL Server payload reads safe, persisting canonical vehicle fields required by the reception wizard, keeping Toyota/test requests diagnostic-only and outside current product sign-off, and preserving OTP, Auth/Login, DB schema, JobCard, C-2D, contract text, and private config untouched.

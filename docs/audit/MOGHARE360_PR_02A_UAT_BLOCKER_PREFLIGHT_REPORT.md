# MOGHARE360 PR-02A — UAT Blocker Preflight Report

**Mission ID:** PR-02A-UAT-BLOCKER-PREFLIGHT  
**Date:** 2026-07-06  
**Type:** Browser UAT failure discovery only — no runtime changes  
**Commit Eligibility:** `NOT_ELIGIBLE_UAT_PREFLIGHT_ONLY`

---

## 1. Executive Summary

PR-02A runtime remains **unsigned**. Docs/audit batch is committed; ~101 held runtime files remain uncommitted. Static code review, read-only DB diagnostics, SHA256 XAMPP↔repo comparison, and automated calendar tests were run. **No PHP/JS/CSS files were modified.**

| UAT failure | Primary root cause (discovery) | Severity |
|-------------|-------------------------------|----------|
| Customer final submit resets to step 1 | Full-page POST reload + `initOtpFirstFlow()` always collapses wizard; no post-success state; `mobile_verified` never hydrated from session | **Blocker** |
| Reception brand dropdown empty | Brand/model options are JS-only; binding is silent-fail if `m360BindVehicleSelector` unavailable or `#m360_rw_vehicle_brand` absent at deferred script run; no server-side brand `<option>` fallback | **Blocker** |
| Calendar Fri/holiday not visibly disabled | Server/helper logic **present and PASS** in CLI tests; disabled CSS in `mirror.css` only (not `moghare360-v1-luxury-ui.css`); likely UAT visual/contrast/cache gap | **High** |
| Staff/reception sends OTP to customer | Explicit reception OTP wizard with `send_customer_otp` action + `m360_otp_send()` in workbench helper | **Security blocker** |
| Unverified requests visible/open in reception | `m360_reception_list_requests()` has **no** `otp_verified = 1` filter; intake opens any `online_request_id` without verification gate | **Security blocker** |

**STOP / GO:** **STOP** implementation in this phase. **GO** only to a scoped **PR-02A-UAT-REPAIR** pass after owner decisions (§11).

---

## 2. Owner Browser UAT Failures

1. Customer online final submit returns to the beginning as if nothing was registered.
2. Reception dropdowns are empty; brand select only shows placeholder.
3. Calendar holidays/Fridays must be visually disabled and not clickable.
4. Staff/reception online request access must never send OTP to customer.
5. Online requests must not be visible to reception unless `otp_verified = 1`.
6. Multiple active/duplicate files likely exist in repo tree.

---

## 3. Active Runtime Variant Inventory

### A) Discovery labels

| Label | Value |
|-------|-------|
| `CUSTOMER_ACTIVE_PAGE` | `public_html/customer-request.php` |
| `CUSTOMER_ACTIVE_JS` | `assets/js/iran-provinces-cities.js`, `assets/js/vehicle-brand-classes.js`, `assets/js/customer-form.js` (cache-busted via `filemtime`) |
| `CUSTOMER_ACTIVE_CSS` | `assets/css/mirror.css`, `assets/css/moghare360-v1-luxury-ui.css` (via `mirror_render_head()`) |
| `RECEPTION_ACTIVE_PAGE` | `public_html/erp-reception-intake-file.php` |
| `RECEPTION_ACTIVE_JS` | `assets/js/vehicle-brand-classes.js` (defer), `assets/js/m360-reception-intake.js` (defer) |
| `RECEPTION_ACTIVE_CSS` | `assets/css/moghare360-v1-luxury-ui.css`, `assets/css/mirror.css` |
| `ONLINE_REQUEST_LIST_ACTIVE_PAGE` | `public_html/erp-reception-online-requests.php` (linked from workbench `erp-reception-workbench.php`) |
| `DUPLICATE_VARIANTS_COUNT` | **22** non-canonical copies under `release/`, `dist/`, `docs/contract-flow-reference/` (see §3.1) |
| `XAMPP_REPO_MATCH` | **yes** for PR-02A key files (SHA256 match on 2026-07-06) |

### 3.1 Canonical vs duplicate paths

**Canonical runtime (localhost:8080/moghare360):** only `public_html/` (+ `includes/` helpers required by those pages).

| Canonical file | Duplicate/stale variants (not served by XAMPP docroot) |
|----------------|--------------------------------------------------------|
| `public_html/customer-request.php` | 6 under `release/*`, 1 under `dist/moghare360-v1-local-demo-rc/` |
| `public_html/assets/js/customer-form.js` | 6 release/dist copies |
| `public_html/assets/js/vehicle-brand-classes.js` | 6 release/dist copies |
| `public_html/erp-reception-online-requests.php` | 1 under `dist/moghare360-v1-local-demo-rc/` |
| `public_html/includes/m360-calendar-1405-helper.php` | 0 duplicates (single canonical copy) |
| `public_html/customer-request-status.php` | multiple release copies (separate status page; not PR-02A intake) |

**XAMPP SHA256 match (repo ↔ `C:\xampp\htdocs\moghare360`):**

- `customer-request.php` — MATCH
- `assets/js/vehicle-brand-classes.js` — MATCH
- `assets/js/m360-reception-intake.js` — MATCH
- `assets/css/mirror.css` — MATCH
- `includes/m360-calendar-1405-helper.php` — MATCH
- `erp-reception-intake-file.php` — MATCH

**Local API target:** `mirror-config.php` → `MASTER_SERVER_BASE_URL = http://localhost:8080/moghare360` (customer submit POST forwards to local `api/customer/request.php`).

**HTTP smoke note:** Unauthenticated `curl` to reception intake timed out (staff session required). Discovery relied on source + CLI diagnostics.

---

## 4. Customer Final Submit Reset Investigation

### B) Discovery labels

| Label | Value |
|-------|-------|
| `CUSTOMER_FINAL_SUBMIT_HANDLER` | `customer-request.php` POST block (lines 123–258) + `customer-form.js` `form.submit` guard |
| `CUSTOMER_FINAL_SUBMIT_API` | `mirror_api_customer_request()` → `POST /api/customer/request` → `public_html/api/customer/request.php` → `m360_online_req_insert()` |
| `SUCCESS_STATE_EXISTS` | **partial** — top alert only (`ثبت موفق…`); no dedicated success step |
| `REQUEST_ID_SHOWN_AFTER_SUCCESS` | **no** — API returns `online_request_id` in JSON; UI never renders it |
| `FAILURE_ERROR_VISIBLE` | **yes** — `m360-alert-error` when `$result['ok'] === false` |
| `STEP_RESET_CAUSE` | Full-page POST reload; `initOtpFirstFlow()` on `DOMContentLoaded` always calls `hideFormSections()` and shows step 1; `#mobile_verified` hardcoded `0` in HTML (not restored from `m360_otp_is_verified()` session); no PRG/redirect-after-POST |
| `FIX_RECOMMENDATION` | Post-success PRG or client state restore; hydrate `mobile_verified` from PHP session on POST back; show `online_request_id` + success panel; optional form disable after success |

### 4.1 Flow trace

1. **Client:** `#m360_submit_btn` submits `form.m360-customer-form` via **synchronous POST** to `customer-request.php`.
2. **Client guard:** `customer-form.js` blocks submit if `#mobile_verified !== '1'` or `visit_date` empty.
3. **Server:** Validates OTP session (`m360_otp_is_verified($input['mobile'])`), visit date (`m360_rw_calendar_validate_visit_date`), plate, profile fields.
4. **API:** Builds payload with `otp_verified_token`; calls `mirror_api_customer_request($payload)`.
5. **Insert:** `api/customer/request.php` inserts row, sets `otp_verified => 1` in payload, returns `online_request_id` in JSON body.
6. **Response render:** Same page re-rendered. Success/failure alert at top; **form always starts at OTP step 1** because:
   - HTML: `#m360_step_mobile` has `m360-step-card--active`; later sections `m360-step--hidden`
   - HTML: `#mobile_verified` value always `0` on render (not tied to session)
   - JS: `initOtpFirstFlow()` resets wizard on every load

### 4.2 Why owner sees “nothing registered”

Even when DB insert succeeds, the UX **looks like a fresh visit**: step 1 mobile entry, disabled submit, hidden profile/vehicle/request sections. The success banner is easy to miss above the collapsed wizard. No tracking/reference number is shown.

---

## 5. Reception Vehicle Dropdown Empty Investigation

### C) Discovery labels

| Label | Value |
|-------|-------|
| `VEHICLE_BRAND_JS_LOADED` | **expected yes** — `<script defer src="assets/js/vehicle-brand-classes.js">` in intake `<head>` |
| `VEHICLE_BRAND_OBJECT_NAME` | `window.M360_VEHICLE_BRANDS` |
| `APPROVED_BRANDS_FOUND` | **yes in repo/XAMPP** — بنز، ب ام و، پورشه، ولوو، فولکس واگن، سایر |
| `PORSCHE_MACAN_FOUND` | **yes** — `پورشه: […, "Macan", …]` |
| `RECEPTION_SELECT_IDS` | `#m360_rw_vehicle_brand`, `#m360_rw_vehicle_class`, `#m360_rw_vehicle_year` |
| `JS_ID_MISMATCH` | **no** — PHP `m360_rw_intake_render_vehicle_selector()` IDs match JS `m360-reception-intake.js` |
| `XAMPP_JS_MATCH` | **yes** (SHA256) |
| `DROPDOWN_EMPTY_ROOT_CAUSE` | Brand/class `<select>` ships with **placeholder only**; options injected only by `m360BindVehicleSelector()` at page load. Binding is **silent** if `window.m360BindVehicleSelector` missing or `#m360_rw_vehicle_brand` null. Single-step wizard renders only active step — binding fails if script runs when vehicle DOM absent. No server-rendered brand `<option>` fallback. |
| `FIX_RECOMMENDATION` | Server-render approved brand options in PHP **or** move binding to `DOMContentLoaded` / step-aware re-init; fail-loud console error if `M360_VEHICLE_BRANDS` missing; verify vehicle step active on UAT URL |

### 5.1 Request #18 diagnostic (read-only CLI)

```
online_request_id: 18
otp_verified_row: 1
resolved_active_step: vehicle
vehicle step complete: no → editable form with JS-populated brand select expected
```

### 5.2 Implementation notes

- `m360_rw_intake_render_vehicle_selector()` outputs empty brand/class selects + server-populated year options (21 years).
- Inline `window.m360RwVehicleInit={brand,model}` is emitted in **body** during vehicle step render.
- `m360-reception-intake.js` (deferred, head) calls `m360BindVehicleSelector()` once at script execution — **not** on `DOMContentLoaded`.
- Plate digit/letter selects are **server-rendered** and should not be empty; if owner meant those, root cause differs.

---

## 6. Jalali Calendar Disabled-Day Investigation

### D) Discovery labels

| Label | Value |
|-------|-------|
| `CALENDAR_RENDERER` | `m360_rw_calendar_render_day_button()` in `includes/m360-calendar-1405-helper.php`; used by `customer-request.php` (`#m360_server_calendar`) and `m360_rw_intake_render_visit_calendar()` (`#m360_rw_server_calendar`) |
| `CALENDAR_WINDOW_MODE` | `next_30_calendar_days` via `m360_rw_calendar_next_30_day_window()` |
| `FRIDAY_DISABLED` | **yes** — `is_selectable=false`, `disable_reason=جمعه` |
| `OFFICIAL_HOLIDAYS_DISABLED` | **yes** — `hol === تعطیل` from locked 1405 embed; marker `34` ignored |
| `DISABLED_VISUAL_STYLE_EXISTS` | **yes** — `.m360-calendar-day--disabled` / `:disabled` in `mirror.css` |
| `DISABLED_CLICK_BLOCK_EXISTS` | **yes** — `disabled` + `data-selectable="0"` on buttons; JS skips disabled in `customer-form.js` and `m360-reception-intake.js` |
| `SERVER_REJECTS_DISABLED_DATE` | **yes** — `m360_rw_calendar_validate_visit_date()` |
| `CALENDAR_ROOT_CAUSE` | Logic/CSS present and **CLI test PASS** (`tools/test-pr-02a-jalali-working-calendar.php`). UAT gap likely **visual contrast** on dark luxury theme, **browser cache**, or owner expecting working-days-only grid (old behavior) vs all 30 days with some disabled. Disabled styles live in `mirror.css` only — not duplicated in `moghare360-v1-luxury-ui.css`. |
| `FIX_RECOMMENDATION` | Browser hard-refresh UAT; strengthen disabled styling (`pointer-events:none`, higher contrast); add reception/customer UAT screenshot checklist; confirm 30-day grid shows disabled Fri/holiday cells |

### 6.1 Automated verification

```
PR-02A jalali calendar window: PASS (12 checks)
Includes: disabled render class, server rejects Friday, marker 34 ignored, mixed selectable/disabled window
```

---

## 7. Reception OTP Security Gate Investigation

### E) Discovery labels

| Label | Value |
|-------|-------|
| `ONLINE_REQUEST_LIST_PAGE` | `public_html/erp-reception-online-requests.php` |
| `OTP_VERIFIED_FILTER_EXISTS` | **no** — SQL `WHERE 1=1` + optional `request_status` only |
| `STAFF_SEND_OTP_TRIGGER_EXISTS` | **yes** — reception intake OTP step form `action_type=send_customer_otp`, button «ارسال OTP به مشتری» → `m360_rw_intake_process_send_otp()` → `m360_otp_send($mobile)` |
| `AUTO_SEND_OTP_ON_STAFF_PAGE` | **no** auto-send on list page load; **yes** staff-triggered send on intake OTP step |
| `UNVERIFIED_REQUEST_VISIBLE_TO_RECEPTION` | **yes** — all rows returned regardless of `otp_verified` |
| `UNVERIFIED_REQUEST_OPENABLE_IN_INTAKE` | **yes** — `erp-reception-intake-file.php?online_request_id=N` loads without verification gate; unresolved requests land on `otp` wizard step |
| `SECURITY_ROOT_CAUSE` | Reception path treats OTP as staff-operable (`send_customer_otp` / `verify_customer_otp`); list query omits `otp_verified = 1`; owner rule requires customer-only OTP and reception visibility gated on verification |
| `FIX_RECOMMENDATION` | Remove/disable staff `send_customer_otp` path; filter `erp-reception-online-requests.php` to `otp_verified = 1`; block intake deep-link or list action for unverified rows; customer OTP remains only on `customer-request.php` + `api/customer/send-otp.php` |

### 7.1 Request #18 / #20 OTP status (read-only DB diagnostic)

| Request | `otp_verified_row` | Visible in list today | Intake openable |
|---------|-------------------|----------------------|-----------------|
| #18 | **1** | yes (no filter) | yes |
| #20 | **1** | yes (no filter) | yes |

Both test requests are verified in DB; the **policy gap** still allows unverified rows to appear and staff OTP send regardless.

### 7.2 Owner rule vs current code

| Owner rule | Current behavior |
|------------|------------------|
| OTP only on customer-facing flow | Customer: `api/customer/send-otp.php`. Reception: **also** `send_customer_otp` staff action |
| Reception must not send OTP | `m360_rw_intake_render_otp_wizard_block()` renders send button |
| List only `otp_verified = 1` | `m360_reception_list_requests()` — no OTP column/filter in SQL |

---

## 8. Root Cause Summary

| # | Symptom | Root cause | Layer |
|---|---------|------------|-------|
| 1 | Customer submit “reset” | POST-redirect-same-page + JS wizard re-init; no success persistence / request ID | UX + PHP render |
| 2 | Empty reception brand dropdown | JS-only population; silent bind failure; no PHP fallback options | JS + PHP render |
| 3 | Calendar disabled days UAT fail | Code/tests pass; likely visual UAT or cache; styles only in `mirror.css` | CSS/UX |
| 4 | Staff sends OTP | Explicit `send_customer_otp` in reception workbench | Security / policy |
| 5 | Unverified in reception list | Missing `otp_verified` filter in list SQL + no intake gate | Security / policy |
| 6 | Duplicate files | 22 stale copies in `release/` + `dist/` (not XAMPP active) | Repo hygiene |

---

## 9. Files Proposed For Repair (PR-02A-UAT-REPAIR scope)

| File | Repair intent |
|------|---------------|
| `public_html/customer-request.php` | Post-success state, show `online_request_id`, session-hydrate verified mobile |
| `public_html/assets/js/customer-form.js` | Restore verified wizard after POST success; success-step UX |
| `public_html/erp-reception-intake-file.php` | Remove staff OTP send UI (or gate per owner decision) |
| `public_html/erp-reception-online-requests.php` | Filter `otp_verified = 1`; hide actions for unverified |
| `public_html/includes/m360-reception-helper.php` | Add OTP filter to `m360_reception_list_requests()` |
| `public_html/includes/m360-reception-workbench-helper.php` | Disable `send_customer_otp`; optional intake verification gate; server-render brand options OR document JS bind fix |
| `public_html/assets/js/m360-reception-intake.js` | DOMContentLoaded / step-aware vehicle bind; fail-loud if brands missing |
| `public_html/assets/css/mirror.css` | Stronger disabled-day contrast (if owner confirms visual gap) |
| `tools/test-pr-02a-*.php` | Add/adjust tests for new OTP gate + submit UX (after repair) |

---

## 10. Files Forbidden To Touch (unless separate approved mission)

| Area | Paths / reason |
|------|----------------|
| OTP helper/config/API core | `m360-otp-helper.php`, `m360-otp-config-loader.php`, `private/m360-otp-config.php`, `api/customer/send-otp.php` — only touch under dedicated OTP mission |
| Auth / Login | `staff-login.php`, `staff-auth.php`, `access-control.php` |
| DB schema | migrations / DDL |
| JobCard / C-2D | jobcard conversion paths |
| Private config | `private/*` secrets |
| Release/dist duplicates | `release/*`, `dist/*` — not active runtime; do not edit for UAT repair |
| Calendar 1405 source | `docs/source/calendar/iran_calendar_1405_source.xlsx` |

---

## 11. Owner Decisions Required

1. **Customer success UX:** Show `online_request_id` + lock form after success, or redirect to `customer-request-status.php`?
2. **Reception OTP:** Fully **remove** staff send/verify OTP wizard, or repurpose as read-only verification display only?
3. **Unverified requests:** Hide from list entirely, or show greyed with no intake link?
4. **Brand dropdown fix:** Prefer **server-rendered** brand options (PHP) or **JS re-bind** fix only?
5. **Calendar UAT:** Confirm expected UI = 30 cells with Fri/holiday visibly greyed (not hidden)?
6. **Repair phase boundary:** PR-02A-UAT-REPAIR only, or include P11.9 OTP wizard cleanup in same pass?

---

## 12. File Disposition Table

| File path | Created/modified | Category | Reason | Commit allowed now | Push allowed now | Owner approval required |
|-----------|------------------|----------|--------|-------------------|------------------|-------------------------|
| `docs/audit/MOGHARE360_PR_02A_UAT_BLOCKER_PREFLIGHT_REPORT.md` | Created | COMMIT_NOW_DOCS | UAT blocker discovery/governance report only; no runtime coupling | yes | yes | no |

**No other files created or modified in this task.**

---

## 13. Recommended PR-02A-UAT-REPAIR Scope

**In scope (recommended):**

1. Customer post-submit success state + `online_request_id` display
2. Reception brand dropdown reliability (PHP fallback or robust JS bind)
3. Calendar disabled-day UAT styling confirmation
4. Reception OTP policy enforcement (no staff send; list `otp_verified = 1`; intake gate)
5. PR-02A test updates for above

**Out of scope:**

- OTP provider/ippanel rewiring
- DB schema changes
- JobCard / C-2D
- Cleaning `release/` / `dist/` duplicates (separate hygiene task)

**Pre-repair checklist:**

- [ ] Owner answers §11 decisions
- [ ] Browser hard-refresh (Ctrl+F5) on customer + reception UAT
- [ ] Confirm XAMPP docroot = `public_html` canonical only

---

## 14. STOP / GO Decision

| Decision | Status |
|----------|--------|
| **STOP** — this preflight phase | **ACTIVE** — no implementation, no commit |
| **GO** — PR-02A-UAT-REPAIR | **Conditional** — after owner decisions §11 |

**Commit Eligibility:** `NOT_ELIGIBLE_UAT_PREFLIGHT_ONLY`

---

MOGHARE360 PR-02A-UAT-BLOCKER-PREFLIGHT identifies active runtime files and root causes behind customer submit reset, empty reception dropdowns, selectable disabled days, and forbidden staff-triggered OTP behavior, classifies every file it creates or modifies, and stops before implementation or commit.

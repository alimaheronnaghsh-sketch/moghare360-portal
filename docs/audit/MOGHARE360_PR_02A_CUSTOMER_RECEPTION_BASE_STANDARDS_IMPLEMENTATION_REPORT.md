# MOGHARE360 PR-02A — Customer / Reception Base Data Standards Implementation

**Mission ID:** PR-02A  
**Date:** 2026-07-06  
**Commit Eligibility:** `NOT_ELIGIBLE_SCOPE_EXCEPTION_LOCK_ONLY`

---

## 1. Canonical Docs Read

| # | Document | Read |
|---|----------|------|
| 1 | `docs/00_CANONICAL/MOGHARE360_MASTER_PRODUCT_BLUEPRINT.md` | ✅ |
| 2 | `docs/00_CANONICAL/MOGHARE360_DATABASE_GAP_MATRIX.md` | ✅ |
| 3 | `docs/00_CANONICAL/MOGHARE360_RUNTIME_CLEANUP_PLAN.md` | ✅ |
| 4 | `docs/00_CANONICAL/MOGHARE360_EXECUTION_ROADMAP.md` | ✅ |
| 5 | `docs/00_CANONICAL/MOGHARE360_CURSOR_EXECUTION_RULES.md` | ✅ |
| 6 | `docs/audit/MOGHARE360_PR_00_PROGRAM_RESET_CONTROL_PACK_REPORT.md` | ✅ |
| 7 | `docs/audit/MOGHARE360_PR_02_PREFLIGHT_CUSTOMER_RECEPTION_STANDARDS_DISCOVERY_REPORT.md` | ✅ |
| 8 | `docs/audit/MOGHARE360_PR_02_CALENDAR_1405_XLSX_SOURCE_LOCK_REPORT.md` | ✅ |

---

## 2. Owner Approval Scope

PR-02A implemented **reception-only** alignment to approved customer-site standards:

- Iranian plate (select widget, customer pattern)
- Luxury brand / model / year controlled selectors
- Jalali **next 30 calendar days** window (1405 source embed; working days only selectable)
- Hall Manager send gate (no technician/assistant assignment)
- Top-level سایر → manager exception; per-brand model سایر → `MODEL_LIST_GAP`

**Not in scope (preserved):** OTP, Auth/Login, private config, DB schema, contract text, JobCard/C-2D, manager approval UI, Hall Manager technical assignment UI.

---

## 3. Files Modified

| File | Change |
|------|--------|
| `public_html/includes/m360-reception-workbench-helper.php` | Calendar embed, vehicle validation, plate widget/save, hall manager action, wizard order |
| `public_html/erp-reception-intake-file.php` | Vehicle/referral UI, mirror.css + vehicle-brand-classes.js |
| `public_html/assets/js/m360-reception-intake.js` | Customer-pattern plate, vehicle bind, working-day calendar |
| `public_html/assets/js/vehicle-brand-classes.js` | Approved brands, سایر rules, MODEL_LIST_GAP helpers |
| `public_html/assets/css/moghare360-v1-luxury-ui.css` | Reception vehicle/calendar/other-panel styles |
| `public_html/includes/m360-calendar-1405-helper.php` | **Scope exception** — shared 30-day Jalali window + holiday rules (see §9.1) |
| `public_html/customer-request.php` | Customer calendar alignment (owner correction) |
| `public_html/assets/js/customer-form.js` | Disabled-day click guard |
| `public_html/assets/css/mirror.css` | Disabled-day visual styles |
| `tools/test-pr-02a-*.php` (6 files) | Automated PR-02A verification |

**XAMPP copy completed** to `C:\xampp\htdocs\moghare360\` (public_html files only; `private/m360-otp-config.php` not touched).

---

## 4. Files Not Modified

- OTP helper/config/API, `staff-login.php`, `staff-auth.php`, `access-control.php`
- `private/m360-otp-config.php`
- DB schema / migrations
- JobCard / C-2D conversion paths
- Contract legal text

---

## 5. Plate Alignment Result

| Label | Value |
|-------|-------|
| `PLATE_STANDARD_SOURCE` | customer-request.php + customer-form.js + mirror.css |
| `PLATE_REIMPLEMENTED` | no |
| `PLATE_ALIGNED_TO_CUSTOMER_STANDARD` | yes |
| `RECEPTION_FREE_TEXT_PLATE_REMOVED` | yes |

Reception `m360_rw_intake_render_plate_widget()` now uses digit/letter **selects** and hidden `plate_region_2_digits` / `plate_display` like the customer flow. `m360_rw_intake_build_plate_from_post()` rejects uncontrolled free-text `plate` as normal workflow.

---

## 6. Vehicle Selector / Brand Model Subclass Result

| Label | Value |
|-------|-------|
| `APPROVED_BRANDS_CURRENT` | Benz, BMW, Porsche, Volvo, Volkswagen, Other (بنز، ب ام و، پورشه، ولوو، فولکس واگن، سایر) |
| `MODEL_SUBCLASS_SOURCE` | vehicle-brand-classes.js |
| `BRAND_DEPENDENT_MODEL_ACTIVATION` | yes |
| `RECEPTION_FREE_TEXT_BRAND_REMOVED` | yes |
| `TOP_LEVEL_OTHER_MANAGER_EXCEPTION` | yes |
| `PER_BRAND_OTHER_MODEL_LIST_GAP` | yes |

Reception vehicle step uses controlled `vehicle_brand` / `vehicle_class` / `vehicle_year_pair` selects with server-side `m360_rw_intake_validate_vehicle_selection()`.

---

## 7. Top-Level Other Manager Exception Result

- Top-level **سایر** has **no** model list (`"سایر": []` in JS).
- UI warns: خارج از محدوده استاندارد — نیازمند بررسی / تأیید مدیر.
- `brand_other_explanation` required; payload `brand_status = MANAGER_EXCEPTION_REVIEW`.

---

## 8. Per-Brand Other Model Gap Result

- Per-brand model **سایر** remains in each approved brand list.
- `model_other_explanation` required when selected.
- Payload `model_status = MODEL_LIST_GAP`; brand scope preserved.

---

## 9. Jalali 30-Day Calendar Window Result (Owner correction)

| Label | Value |
|-------|-------|
| `CALENDAR_SOURCE` | docs/source/calendar/iran_calendar_1405_source.xlsx |
| `CALENDAR_TYPE` | jalali |
| `CALENDAR_WINDOW_MODE` | next_30_calendar_days |
| `SELECTABLE_DAYS_MODE` | working_days_only |
| `FRIDAY_DISABLED` | yes |
| `IRAN_OFFICIAL_HOLIDAYS_DISABLED` | yes |
| `OFFICIAL_HOLIDAY_MARKER` | تعطیل |
| `NORMAL_NON_HOLIDAY_MARKER_IGNORED` | yes |
| `DISABLED_DAYS_VISUALLY_DISTINCT` | yes |
| `DISABLED_DAYS_NOT_CLICKABLE` | yes |
| `SERVER_REJECTS_DISABLED_DATE` | yes |
| `RECEPTION_FREE_TEXT_DATE_REMOVED` | yes |
| `RECEPTION_CALENDAR_ALIGNED` | yes |
| `CUSTOMER_CALENDAR_ALIGNED` | yes |

**Policy:** Customer/reception show the next **30 calendar days** (Jalali labels). Within that window, only working days are selectable; Fridays and official `تعطیل` holidays render disabled (distinct style, reason label, not clickable). Server-side `m360_rw_calendar_validate_visit_date()` rejects disabled or out-of-window dates. Shared logic: `includes/m360-calendar-1405-helper.php`.

### 9.1 Calendar Helper Scope Exception (PR-02A-CALENDAR-HELPER-SCOPE-EXCEPTION-LOCK)

| Label | Value |
|-------|-------|
| `CALENDAR_HELPER_SCOPE_EXCEPTION` | yes |
| `CALENDAR_HELPER_APPROVAL_REQUIRED` | owner |
| `CALENDAR_HELPER_RUNTIME_SCOPE` | shared_customer_reception_calendar_only |

**Runtime path:** `public_html/includes/m360-calendar-1405-helper.php`

**Why created:** Owner correction required customer and reception to share identical next-30-calendar-days Jalali logic. The helper was extracted during the calendar correction pass so both surfaces use one implementation. It was **not** listed in the original PR-02A allowed runtime files and must be registered before PR-02A sign-off or commit.

**Why shared helper is safer than duplication:** A single embed + validation path prevents customer/reception drift (different holiday markers, window length, Friday rules, or server rejection). One locked 1405 source, one `m360_rw_calendar_validate_visit_date()` gate, one `m360_rw_calendar_render_day_button()` renderer.

**Confirmed in-scope (helper only):**

- Next 30 calendar days window (`m360_rw_calendar_next_30_day_window`)
- Jalali date rendering and validation
- Friday disabled (`fri` flag / weekday جمعه)
- Iran official holiday disabled from locked 1405 source (`تعطیل` marker; `34` never treated as holiday)
- Disabled day styling/rendering (`m360_rw_calendar_render_day_button`; CSS in `mirror.css`)
- Server-side validation (`m360_rw_calendar_validate_visit_date`)

**Confirmed out-of-scope (helper does not handle):**

- OTP, Auth/Login, DB schema, JobCard, C-2D, contract text, private config

**Consumers:** `customer-request.php` and `m360-reception-workbench-helper.php` (reception intake) both `require_once` the helper.

**Dependencies:** No external API. No invented holiday data. Locked source remains `docs/source/calendar/iran_calendar_1405_source.xlsx` (365 rows embedded gz+base64 in helper).

**Scope note:** `docs/audit/MOGHARE360_PR_02A_SCOPE_EXCEPTION_CALENDAR_HELPER.md`

---

## 10. Hall Manager Gate Result

| Label | Value |
|-------|-------|
| `HALL_MANAGER_GATE_READY` | yes |
| `RECEPTION_DIRECT_TECH_ASSIGNMENT` | no |
| `HALL_ASSIGNMENT_IMPLEMENTED` | no |
| `SQL_OR_WORKFLOW_PROPOSAL_NEEDED_LATER` | no |

- Wizard step 8: **ارسال به مسئول سالن** (after signature lock).
- Action `send_to_hall_manager`; UI button **ارسال پرونده به مسئول سالن**.
- Status **آماده بررسی مسئول سالن** stored in `reception_intake.hall_manager`.
- Referral team picker removed; no technician/assistant fields.

---

## 11. Contract / OTP / Auth / DB / JobCard Preservation

| Label | Value |
|-------|-------|
| `CONTRACT_TEXT_UNTOUCHED` | yes |
| `OTP_UNTOUCHED` | yes |
| `AUTH_UNTOUCHED` | yes |
| `DB_SCHEMA_UNTOUCHED` | yes |
| `JOBCARD_C2D_UNTOUCHED` | yes |
| `PRIVATE_CONFIG_UNTOUCHED` | yes |

---

## 12. Tests Passed

All six PR-02A tests **PASS** via `C:\xampp\php\php.exe`:

```
tools/test-pr-02a-plate-standard-alignment.php       PASS
tools/test-pr-02a-vehicle-selector-standard.php      PASS
tools/test-pr-02a-jalali-working-calendar.php        PASS
tools/test-pr-02a-hall-manager-gate.php              PASS
tools/test-pr-02a-other-brand-manager-exception.php  PASS
tools/test-pr-02a-scope-security.php                 PASS
```

---

## 13. Browser UAT Result

| URL | HTTP | Automated smoke |
|-----|------|-----------------|
| `http://localhost:8080/moghare360/customer-request.php` | 200 | Plate widget present; customer-facing (no staff assignment) |
| `http://localhost:8080/moghare360/erp-reception-intake-file.php?online_request_id=18&active_step=vehicle` | 200 | `plate_first_digit_1`, `m360_rw_vehicle_brand`, `m360_rw_server_calendar`, `mirror.css` present |

**Manual UAT still required (owner hard refresh Ctrl+F5):**

1. Complete reception wizard through signature lock on request #18 (or test intake).
2. Confirm referral step shows **ارسال پرونده به مسئول سالن** after lock.
3. Confirm Porsche → Macan dependency and top-level سایر explanation panels.
4. Confirm Friday/holiday days render disabled (not selectable) in customer and reception 30-day grids.

**Browser UAT status:** `PARTIAL_AUTOMATED_PASS — MANUAL_LOCK_AND_SEND_STEP_PENDING`

---

## 14. Remaining Blockers

1. **Full browser UAT** on locked intake + hall-manager send (request #18 not locked in smoke test).
2. **Calendar helper scope exception** — owner approval of `m360-calendar-1405-helper.php` as controlled runtime (see §9.1; `MOGHARE360_PR_02A_SCOPE_EXCEPTION_CALENDAR_HELPER.md`).
3. **Hall Manager technical assignment UI** — future phase (explicitly forbidden in PR-02A).

---

## 15. Commit Eligibility

**NOT_ELIGIBLE_SCOPE_EXCEPTION_LOCK_ONLY**

This documentation pass registers the calendar helper scope exception only. No runtime, OTP, Auth, DB, JobCard, or private-config changes were made.

**Prior blockers still apply after owner approves scope exception:**

- Manual browser UAT (§13 items 1–4)
- Owner sign-off on `CALENDAR_HELPER_SCOPE_EXCEPTION` (§9.1)

Until both are satisfied, PR-02A remains not eligible for commit.

---

MOGHARE360 PR-02A aligns reception base-data standards with the approved customer-site standards for Iranian plate, luxury-brand dependent vehicle selection, and Jalali next-30-calendar-days visit-window behavior (working days only selectable), preserves customer/reception responsibility separation, routes completed intake only to the Hall Manager gate, enforces top-level Other as a manager-exception path, treats per-brand Other as a model-list gap, centralizes calendar logic in the registered scope-exception helper `m360-calendar-1405-helper.php`, and keeps OTP, Auth/Login, DB schema, contract text, JobCard, C-2D, and private config untouched.

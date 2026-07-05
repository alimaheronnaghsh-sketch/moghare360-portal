# MOGHARE360 PR-02A — Calendar Helper Scope Exception Lock

**Mission ID:** PR-02A-CALENDAR-HELPER-SCOPE-EXCEPTION-LOCK  
**Date:** 2026-07-06  
**Type:** Documentation + scope security only (no runtime changes in this pass)  
**Commit Eligibility:** `NOT_ELIGIBLE_SCOPE_EXCEPTION_LOCK_ONLY`

---

## 1. Scope Exception Summary

| Label | Value |
|-------|-------|
| `CALENDAR_HELPER_SCOPE_EXCEPTION` | yes |
| `CALENDAR_HELPER_APPROVAL_REQUIRED` | owner |
| `CALENDAR_HELPER_RUNTIME_SCOPE` | shared_customer_reception_calendar_only |
| `OTP_UNTOUCHED` | yes |
| `AUTH_UNTOUCHED` | yes |
| `DB_SCHEMA_UNTOUCHED` | yes |
| `JOBCARD_C2D_UNTOUCHED` | yes |
| `PRIVATE_CONFIG_UNTOUCHED` | yes |

---

## 2. New Runtime File (Controlled Exception)

| Field | Value |
|-------|-------|
| **Path** | `public_html/includes/m360-calendar-1405-helper.php` |
| **Status** | Introduced during PR-02A calendar correction; **not** in original PR-02A allowed runtime list |
| **Registration** | Required before PR-02A sign-off or commit |

---

## 3. Why the Helper Was Created

During owner correction of the visit-date rule (from “30 working days” to **next 30 calendar days** with working days only selectable), calendar logic existed inside `m360-reception-workbench-helper.php` and needed to be shared with `customer-request.php`.

Extracting `m360-calendar-1405-helper.php` centralizes:

- 1405 source embed (locked xlsx → gz+base64 JSON rows)
- 30-day window construction
- Friday / official-holiday disable rules
- Day-button HTML rendering
- Server-side visit-date validation

Without this file, customer and reception would duplicate embed data and rules, increasing risk of inconsistent behavior.

---

## 4. Why a Shared Helper Is Safer Than Duplicated Calendar Logic

| Risk with duplication | Shared helper mitigation |
|----------------------|---------------------------|
| Customer/reception show different day counts or Jalali labels | Single `m360_rw_calendar_next_30_day_window()` |
| Holiday marker `تعطیل` applied on one surface only | Single `m360_rw_calendar_day_is_official_holiday()` |
| Friday disable inconsistent | Single `fri` / weekday rule in one module |
| Server accepts disabled dates on one path only | Single `m360_rw_calendar_validate_visit_date()` |
| Embed updated in one file, stale in another | One embed constant `m360_rw_calendar_1405_embed_b64()` |

---

## 5. Confirmed In-Scope (Helper Handles Only)

The helper is limited to **shared customer + reception visit-date calendar** behavior:

1. **Next 30 calendar days** — `m360_rw_calendar_next_30_day_window()` iterates 30 consecutive Gregorian days from today (Asia/Tehran).
2. **Jalali date rendering / validation** — Jalali labels from locked 1405 rows; `m360_rw_calendar_validate_visit_date()` enforces window + selectability.
3. **Friday disabled** — `fri` flag and جمعه weekday; `is_selectable = false`, `disable_reason = جمعه`.
4. **Iran official holiday disabled** — Only `hol === تعطیل` from locked 1405 source; marker `34` explicitly **not** treated as holiday.
5. **Disabled day styling / rendering** — `m360_rw_calendar_render_day_button()` emits disabled attributes/classes; UI CSS in `mirror.css` (`.m360-calendar-day--disabled`).
6. **Server-side validation** — Rejects empty, out-of-window, Friday, and official-holiday dates before save.

**Locked source (unchanged):** `docs/source/calendar/iran_calendar_1405_source.xlsx`

---

## 6. Confirmed Out-of-Scope (Helper Does Not Handle)

| Area | Status |
|------|--------|
| OTP | Does not read/write OTP config, APIs, or verification |
| Auth / Login | No session, staff, or access-control logic |
| DB schema | No migrations, tables, or SQL DDL |
| JobCard | No job-card or workshop card conversion |
| C-2D | No C-2D pipeline or drawing logic |
| Contract text | No legal/contract copy |
| Private config | Does not include or require `private/m360-otp-config.php` |

---

## 7. Consumers (Both Surfaces Use the Helper)

| Consumer | Integration |
|----------|-------------|
| **Customer** | `public_html/customer-request.php` → `require_once …/m360-calendar-1405-helper.php` |
| **Reception** | `public_html/includes/m360-reception-workbench-helper.php` → `require_once …/m360-calendar-1405-helper.php` |

Supporting UI (not part of this scope exception file, but aligned):

- `public_html/assets/js/customer-form.js` — disabled-day click guard
- `public_html/assets/js/m360-reception-intake.js` — disabled-day click guard
- `public_html/assets/css/mirror.css` — disabled-day visual styles

---

## 8. Data & Dependency Guarantees

| Guarantee | Confirmation |
|-----------|--------------|
| No external API dependency | Embed is static gz+base64 inside helper; no HTTP fetch |
| No invented holiday data | Rows derived only from locked xlsx source |
| Locked source path | `docs/source/calendar/iran_calendar_1405_source.xlsx` |
| Official holiday marker | `تعطیل` only (`M360_RW_CALENDAR_OFFICIAL_HOLIDAY_MARKER`) |
| Normal non-holiday marker ignored | `34` returns `false` in `m360_rw_calendar_day_is_official_holiday()` |

---

## 9. Public API Surface (Helper Functions)

| Function | Purpose |
|----------|---------|
| `m360_rw_calendar_1405_embed_b64()` | Locked embed payload |
| `m360_rw_calendar_1405_rows()` | Decode/cache 365 source rows |
| `m360_rw_calendar_day_is_official_holiday()` | `تعطیل` check; `34` excluded |
| `m360_rw_calendar_day_is_working()` | Working-day predicate |
| `m360_rw_calendar_rows_by_gregorian_index()` | Gregorian → row lookup |
| `m360_rw_calendar_next_30_day_window()` | 30-day window with selectability |
| `m360_rw_calendar_validate_visit_date()` | Server-side rejection |
| `m360_rw_calendar_render_day_button()` | Day button HTML |
| `m360_rw_calendar_working_days_window()` | Legacy-compatible working-day list (delegates to 30-day window) |

---

## 10. Owner Approval Required

Before PR-02A commit eligibility can advance:

1. Owner acknowledges `CALENDAR_HELPER_SCOPE_EXCEPTION = yes`
2. Owner approves runtime scope `shared_customer_reception_calendar_only`
3. Manual browser UAT per main PR-02A report §13 remains pending

**This document does not implement behavior, modify calendar logic, or authorize commit.**

---

## 11. Related Documents

- `docs/audit/MOGHARE360_PR_02A_CUSTOMER_RECEPTION_BASE_STANDARDS_IMPLEMENTATION_REPORT.md` (§9.1)
- `docs/audit/MOGHARE360_PR_02_CALENDAR_1405_XLSX_SOURCE_LOCK_REPORT.md`
- `docs/source/calendar/iran_calendar_1405_source.xlsx`

---

**End of scope exception lock — documentation only.**

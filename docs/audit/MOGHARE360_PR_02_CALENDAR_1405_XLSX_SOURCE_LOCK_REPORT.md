# MOGHARE360 PR-02-CALENDAR-1405-XLSX-SOURCE-LOCK Report

**Mission ID:** PR-02-CALENDAR-1405-XLSX-SOURCE-LOCK  
**Type:** Controlled source data registration only — **no runtime implementation**  
**Date:** 2026-07-06 (filename standardized → `iran_calendar_1405_source.xlsx`)  
**Commit Eligibility:** `NOT_ELIGIBLE_SOURCE_FILENAME_STANDARDIZATION_ONLY` (was `NOT_ELIGIBLE_SOURCE_LOCK_ONLY`)  
**Governance context:** Resolves preflight flag **IRAN_OFFICIAL_HOLIDAY_SOURCE_MISSING** at source-data level only (Blueprint §3.5). Runtime calendar logic is **not** implemented in this phase.

---

## 1. Purpose

Register the owner-provided Jalali calendar workbook for solar year **1405** as the controlled MOGHARE360 source artifact before PR-02A visit-date alignment. Verify structure (dates, weekday, official holidays, Fridays). **No** PHP/JS/CSS/SQL/runtime JSON generation in this phase.

---

## 2. Source File Registration

| Field | Value |
|-------|-------|
| **Controlled path** | `docs/source/calendar/iran_calendar_1405_source.xlsx` |
| **Filename (canonical)** | `iran_calendar_1405_source.xlsx` |
| **Former path (retired)** | `docs/source/calendar/تقویم 1405.xlsx` — renamed; **no duplicate** retained |
| **File size** | 453,404 bytes |
| **SHA256** | `3bad3de9274f1831abb9e6d689f81f02ca13382f5acf35acdd670fd665b349a2` |
| **Action taken** | Owner file registered; renamed to ASCII-safe canonical name `iran_calendar_1405_source.xlsx` (PR-02-CALENDAR-SOURCE-FILENAME-STANDARDIZATION) |
| **Runtime mirror** | **Not copied** — source docs only |

---

## 3. Required Report Labels

| Label | Value |
|-------|-------|
| **SOURCE_FILE_REGISTERED** | **yes** |
| **SOURCE_FORMAT** | **xlsx** |
| **JALALI_YEAR** | **1405** |
| **SHEET_NAME** | **مدل ۱** |
| **DATE_ROWS** | **365** |
| **OFFICIAL_HOLIDAY_ROWS** | **26** |
| **FRIDAY_ROWS** | **52** |
| **HAS_GREGORIAN_DATE** | **yes** (column `میلادی`) |
| **HAS_JALALI_DATE** | **yes** (column `تاریخ`, format `1405-MM-DD`) |
| **HAS_WEEKDAY** | **yes** (column `روز`) |
| **HAS_OFFICIAL_HOLIDAY_FLAG** | **yes** (column `تعطیل رسمی`) |
| **OFFICIAL_HOLIDAY_MARKER** | **تعطیل** |
| **NORMAL_NON_HOLIDAY_MARKER** | **34** (owner rule — see §5.3) |
| **FRIDAY_MUST_BE_LOGIC_DISABLED** | **yes** |
| **PERSIAN_TEXT_ENCODING_QUALITY** | **clean** (UTF-8 Persian readable in workbook analysis) |
| **RUNTIME_IMPLEMENTATION_DONE** | **no** |

---

## 4. Workbook Structure Verification

### 4.1 Sheet

| Check | Result |
|-------|--------|
| Workbook exists | ✅ |
| Sheet name | **`مدل ۱`** (only sheet in workbook) |
| Header row | **Row 2** |

### 4.2 Headers (row 2) — expected vs found

| Expected | Found | Match |
|----------|-------|-------|
| قمری | قمری | ✅ |
| میلادی | ` میلادی` (leading space) | ⚠️ minor — trim on consume |
| ماه | ماه | ✅ |
| تاریخ | تاریخ | ✅ |
| روز | روز | ✅ |
| تعطیل رسمی | تعطیل رسمی | ✅ |
| شرح تعطیلی | شرح تعطیلی  | ✅ (trailing space) |
| شرح 1–5 | شرح 1 … شرح 5 | ✅ |

### 4.3 Data row range

| Metric | Value |
|--------|-------|
| First data row | **3** |
| Last data row | **367** |
| `max_row` (sheet) | 367 |
| **Jalali date rows** | **365** ✅ |

### 4.4 Sample boundary rows

| Boundary | قمری | میلادی (Gregorian) | ماه | تاریخ (Jalali) | روز | تعطیل رسمی |
|----------|------|-------------------|-----|----------------|-----|------------|
| First (1405-01-01) | 1 شوال 1447 | 2026-03-21 | فروردین | 1405-01-01 | شنبه | **تعطیل** (عید فطر ، عید نوروز) |
| Last (1405-12-29) | 11 شوال 1447 | 2027-03-20 | اسفند | 1405-12-29 | شنبه | **تعطیل** (روز ملی شدن صنعت نفت ایران) |

---

## 5. Holiday, Friday, and Marker Rules

### 5.1 Official holidays (`تعطیل رسمی` = `تعطیل`)

| Metric | Count |
|--------|------:|
| Rows marked **تعطیل** | **26** |
| Rows not marked تعطیل | **339** |
| **Total** | **365** |

Non-holiday rows use **empty** value in `تعطیل رسمی` (not the string `تعطیل`).

### 5.2 Fridays (`روز` = `جمعه`)

| Weekday (sample distribution) | Rows |
|-------------------------------|-----:|
| شنبه | 53 |
| 1 شنبه … 5 شنبه | 52 each |
| **جمعه** | **52** |

**FRIDAY_MUST_BE_LOGIC_DISABLED = yes:** Fridays must be disabled in visit calendar by **weekday logic** (`روز` = `جمعه`), independent of `تعطیل رسمی` (a Friday may or may not also be an official holiday).

### 5.3 Normal non-holiday marker `34` (owner rule)

| Check | Result |
|-------|--------|
| Rows with `تعطیل رسمی` = `34` | **0** |
| Rows with `تعطیل رسمی` = numeric/string `34` anywhere in flag column | **0** |

**Finding:** This workbook uses **empty** cells for non-official-holiday days, not the literal value `34`.

**Owner rule locked for PR-02A consumers:** Value **`34` must not be treated as an official holiday** if encountered in downstream data or legacy imports. Only explicit marker **`تعطیل`** (and owner-approved future markers) may disable a day as official holiday.

---

## 6. Limitations and Consumption Notes (not implementation)

1. **Source only** — no normalized JSON, no PHP/JS calendar module, no customer/reception page changes in this phase.
2. **Single year** — covers Jalali **1405** only (`1405-01-01` … `1405-12-29`).
3. **Header whitespace** — trim `میلادی` and `شرح تعطیلی` column names when parsing.
4. **Working-day calendar** — Blueprint §3.5 requires **30 working days** selection UX; this file supplies full-year truth. PR-02A must compute working days from: exclude Fridays + exclude `تعطیل` official holidays + forward window.
5. **No invented holidays** — runtime must consume this file (or future owner-approved derivatives), not hard-coded holiday lists.
6. **Hijri column (`قمری`)** — present for reference; visit-date governance targets Jalali + Gregorian + weekday + official flag.

---

## 7. Files Touched in This Phase

| File | Action |
|------|--------|
| `docs/source/calendar/iran_calendar_1405_source.xlsx` | Renamed from `تقویم 1405.xlsx`; sole calendar source file |
| `docs/audit/MOGHARE360_PR_02_CALENDAR_1405_XLSX_SOURCE_LOCK_REPORT.md` | **Created** (this report) |

**Forbidden areas confirmed untouched:** `public_html/`, PHP, JS, CSS, SQL, private config, OTP, Auth/Login, reception runtime, JobCard, DB schema.

---

## 8. STOP / GO for PR-02A Calendar Work

| Gate | Status |
|------|--------|
| Official holiday **source** registered | ✅ |
| Structure verified (365 rows, columns, markers) | ✅ |
| Runtime calendar implementation | ❌ **STOP** — not in this phase |
| PR-02A may consume this source | ⏳ After owner GO on PR-02-GOVERNANCE-LOCK + PR-02A scope |

---

## 9. Preflight Cross-Reference

Updates preflight report flag:

- **IRAN_OFFICIAL_HOLIDAY_SOURCE_MISSING** → **resolved at source-lock level** for year 1405 via `docs/source/calendar/iran_calendar_1405_source.xlsx`
- Runtime wiring still **pending** PR-02A

---

MOGHARE360 PR-02-CALENDAR-1405-XLSX-SOURCE-LOCK registers the owner-provided 1405 Jalali calendar Excel file as a controlled source artifact, verifies its date, weekday, Friday, and official holiday structure, and keeps runtime PHP, JS, CSS, SQL, OTP, Auth/Login, reception, JobCard, private config, and DB schema untouched.

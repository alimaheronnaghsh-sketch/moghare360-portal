# MOGHARE360 P11.9-C-2B — UAT Rejection Report

**Phase:** P11.9-C-2B-UAT-REJECTION  
**Mode:** Report only — no code, SQL, Auth, or workflow changes  
**Date:** 2026-07-04  
**Product:** MOGHARE360 V1 RC  
**Subject:** Reception Workbench (C-2B + FIX-A) — technical, UX, and process failure review

---

## 1. Executive Summary

**UAT verdict: REJECTED.**

The owner correctly rejected P11.9-C-2B for UAT. The implementation cannot be accepted for production or for progression to P11.9-C-2C.

Three failure classes overlap:

1. **Technical / runtime:** A PHP fatal error makes `erp-reception-intake-file.php` unusable in the browser for real request IDs (including 18 and 20). Automated tests reported PASS while the primary intake shell never executed successfully at runtime.
2. **Process / workflow:** The owner-approved temporary vs full reception model is only partially reflected in labels and read-only gate text. Critical behaviors (service classification entry, expert review routing, intake-first action flow) are missing or contradicted elsewhere.
3. **UI / UX:** Reception remains visually split between green luxury workbench pages and legacy blue/white table admin pages. The intake shell is an overloaded all-in-one panel. Raw online request detail still exposes full decision actions before intake completion.

**Final decision:** P11.9-C-2B is rejected for UAT and must be reworked (**P11.9-C-2B-REWORK-A**) before P11.9-C-2C.

---

## 2. Runtime Fatal Failure

### 2.1 Error observed (owner / browser)

```
m360_rw_build_gate(): Argument #9 ($jobcard) must be of type ?array, null given
— OR —
8 arguments passed, 9 expected
```

(PHP 8+ surfaces this as `ArgumentCountError` when the ninth parameter is omitted entirely.)

### 2.2 Exact function signature

**File:** `public_html/includes/m360-reception-workbench-helper.php`

```php
function m360_rw_build_gate(
    array $request,
    array $payload,
    ?array $customer,
    ?array $vehicle,
    ?array $intake,
    array $contracts,
    array $vehiclePhotos,
    array $jobcardMedia,
    ?array $jobcard
): array
```

**Nine parameters required.** The ninth is `?array $jobcard` (nullable type, but the argument position must still be supplied).

### 2.3 Exact call sites

| Location | Line | Arguments passed | Correct? |
|----------|------|------------------|----------|
| `m360_rw_build_intake_file()` — **empty gate bootstrap** | ~558 | **8** — `([], [], [], [], [], [], [], [])` | **NO — FATAL** |
| `m360_rw_build_intake_file()` — successful path | ~614 | **9** — includes `$jobcard` | Yes |
| `tools/test-p11-9-c-2b-intake-completion-shell.php` | ~50, 62, 69 | 9 (via `null` last arg) | Yes |
| `tools/test-p11-9-c-2b-fix-a-process-correction.php` | ~49, 58 | 9 (via `null` last arg) | Yes |

### 2.4 Execution path that breaks the browser

**File:** `public_html/erp-reception-intake-file.php`

```php
$file = ($conn !== false && $onlineRequestId > 0)
    ? m360_rw_build_intake_file($conn, $onlineRequestId)
    : m360_rw_build_intake_file(false, 0);
```

Both branches call `m360_rw_build_intake_file()`, which **always** executes line 558 first:

```php
$emptyGate = m360_rw_build_gate([], [], [], [], [], [], [], []); // 8 args — fatal
```

Therefore **every** HTTP request to:

- `erp-reception-intake-file.php?online_request_id=18`
- `erp-reception-intake-file.php?online_request_id=20`

fatals before any HTML is rendered, regardless of DB row content.

### 2.5 Root cause

When FIX-A expanded `m360_rw_build_gate()` with a ninth parameter (`$jobcard`), the empty-gate call inside `m360_rw_build_intake_file()` was not updated from 8 to 9 arguments. This is a regression introduced during gate enhancement, not an environment-only issue.

---

## 3. Why Previous Tests Missed It

### 3.1 What tests actually did

| Test suite | What it verified | What it did **not** do |
|------------|------------------|------------------------|
| `test-p11-9-c-2b-intake-completion-shell.php` | File exists; Persian strings in source; calls `m360_rw_build_gate()` **directly** with 9 args | Never calls `m360_rw_build_intake_file()`; never HTTP-loads intake page |
| `test-p11-9-c-2b-fix-a-process-correction.php` | Same — direct gate calls + string search | Same gap |
| `test-p11-9-c-2b-reception-workbench.php` | Workbench file strings + helper decode unit checks | No intake page bootstrap |
| `test-p11-9-c-2b-scope-security.php` | File existence, no recent SQL/auth touch | No runtime |
| `test-v1-production-signoff.php` | Unrelated signoff pages/tables | No reception intake routes |

### 3.2 Why PASS was reported falsely from a UAT perspective

1. **Static string tests ≠ runtime tests.** Passing tests only proved labels exist in source files.
2. **Unit tests bypassed the broken call site.** Tests invoke `m360_rw_build_gate(..., null)` with 9 arguments, skipping `m360_rw_build_intake_file()` line 558 entirely.
3. **No HTTP smoke test** against `erp-reception-intake-file.php?online_request_id=1` (or 18/20).
4. **No PHP bootstrap test** that `require`s intake-file.php or calls `m360_rw_build_intake_file($conn, $id)` with a mock connection.
5. **Browser validation was documented as “operator steps”** in `MOGHARE360_P11_9_C_2B_RECEPTION_WORKBENCH_REPORT.md` §9 but **was not executed by Cursor** before claiming implementation complete — and would have failed immediately on intake URLs due to the fatal.

### 3.3 Exact test that must be added (Rework-A)

**Required:** `tools/test-p11-9-c-2b-rework-runtime-smoke.php` (name indicative) that:

1. Bootstraps `m360-reception-workbench-helper.php`.
2. Calls `m360_rw_build_intake_file(false, 0)` and asserts no exception.
3. Calls `m360_rw_build_gate([], [], null, null, null, [], [], [], null)` — explicit 9-arg empty gate.
4. Optionally HTTP GET `erp-reception-intake-file.php?online_request_id=18` via CLI/curl against local XAMPP and asserts HTTP 200 and absence of `ArgumentCountError` / fatal strings in body.

**Gate criterion for Rework-A:** Intake shell must render for IDs 18 and 20 without PHP fatal.

### 3.4 Repo vs XAMPP divergence

- If XAMPP mirrors the same repo copy, **both fail identically** — the bug is in committed source at line 558.
- Divergence is possible if an operator copied an older workbench file but not the helper, or vice versa; however the **current repo** contains the 8-argument call, so a fresh copy reproduces the fatal.
- **Browser validation was falsely assumed complete** when only CLI static tests ran.

---

## 4. Owner Process Compliance Review

### 4.1 First page — only پذیرش + پروفایل پرسنلی

| Owner requirement | Current implementation | Verdict |
|-------------------|------------------------|---------|
| Landing shows only two paths | `erp-reception-workbench.php` landing grid with two cards | **Partial PASS** |
| No extra operational noise on landing | Footer still links to online list / jobcards from sub-pages; landing itself is clean | **Partial** |
| Staff home also promotes workbench | Additional entry via `erp-staff-home.php` hub card | Acceptable extra entry |

### 4.2 Personnel profile path

| Owner item | Current | Verdict |
|------------|---------|---------|
| پروفایل پرسنلی من | Card in `?section=profile` | **PASS** |
| مرخصی / اضافه‌کاری | Single card → `erp-hr-dashboard.php` | **Partial** (combined, not separate) |
| مدارک پرسنلی | Card → `erp-hr-training-discipline.php` | **PASS** |
| فیش حقوقی | Card → `erp-payroll-preview.php` | **PASS** |
| HR placeholders when missing | Shows P15 placeholder text | **Partial** |

Profile hub structure is **closest to owner spec** among reception deliverables.

### 4.3 Reception path — four stages

| Owner stage | Current | Verdict |
|-------------|---------|---------|
| پذیرش موقت | Hub card links to `online-requests?status=UNDER_REVIEW` — not a dedicated temporary queue | **FAIL** |
| پذیرش | Hub card with 3 sub-links (walk-in, online, jobcards) | **Partial PASS** |
| کنترل کیفی | Placeholder + link attempt to `erp-qc-board.php` | **Partial** (not reception-scoped) |
| ترخیص | Placeholder only | **Partial** (expected backlog) |

**Missing:** Dedicated **پذیرش موقت** work queue filtered by gate status (`temporary_reception`, `complete_unclear_fault`), not generic UNDER_REVIEW filter.

### 4.4 Full reception subroutes

| Subroute | Current | Verdict |
|----------|---------|---------|
| پذیرش حضوری | `?section=walkin` — placeholder text only | **Partial** |
| پذیرش آنلاین / تکمیل پرونده | Links to `erp-reception-online-requests.php` | **Partial** — intake shell fatals |
| پیگیری پرونده‌های در جریان | Links to `erp-reception-jobcards.php` | **Partial PASS** |

### 4.5 Temporary reception behavior

| Owner rule | Current | Verdict |
|------------|---------|---------|
| Intake completion work allowed | Intended in intake shell — **page fatals** | **FAIL** |
| Only fault/service path unclear | Gate logic distinguishes `complete_unclear_fault` / `temporary_reception` in helper — **never shown in browser** | **FAIL (runtime)** |
| Rejection allowed | Intake shell forms for reject (FIX-A) — **unreachable due to fatal**; detail page still allows reject without intake | **Partial / contradicted** |
| Request more information | Intake `under_review` form — unreachable; detail allows without gate | **Partial** |
| Initial diagnostic / expert review | Placeholder text only — no route | **FAIL** |
| Convert blocked until path clear | Gate sets `can_show_convert` only on `ready_convert` — **unreachable in browser** | **FAIL (runtime)** |

### 4.6 Service classification (reception-filled)

| Owner rule | Current | Verdict |
|------------|---------|---------|
| Filled by reception staff, not customer | Label says "ثبت توسط پذیرشگر"; read-only taxonomy display | **Partial** |
| کارشناسی و عیب‌یابی + 6 multi-select subs | Taxonomy listed in HTML; **no multi-select UI** | **FAIL** |
| سرویس‌های دوره‌ای | Listed in taxonomy only | **FAIL** (no entry) |
| کارشناسی خرید و فروش | Listed in taxonomy only | **FAIL** (no entry) |
| Management reporting purpose | Not operational — no persisted classification | **FAIL** |
| Write placeholder if no route | Placeholder string present | **Partial PASS** |
| Customer must not select | Customer form has `request_type` (different field) — mismatch warning only | **Partial** |

**Note:** `customer-request.php` exposes `request_type` to customers — reported as mismatch, not corrected (correct per phase scope, but owner process expects separation).

---

## 5. UI/UX Quality Review

### 5.1 Page-by-page assessment

| Page | Professional? | Shell consistency | Issues |
|------|---------------|---------------------|--------|
| `erp-reception-workbench.php` (landing) | Moderate | Green luxury CSS | Acceptable two-card landing; English "hub" in subtitle area |
| `erp-reception-workbench.php?section=reception` | Moderate | Green luxury | Crowded 4-card hub + KPIs; QC/delivery placeholders feel unfinished |
| `erp-reception-workbench.php?section=profile` | Moderate | Green luxury | Acceptable |
| `erp-reception-intake-file.php` | **Poor (unusable)** | Green luxury CSS intended | **Fatal**; if fixed: extremely long all-in-one scroll — gate, customer, vehicle, payload, taxonomy, checklist, actions |
| `erp-reception-online-requests.php` | **Poor** | **Legacy soft-run (blue/white table)** | Still table-first admin; not workbench sub-flow |
| `erp-reception-online-request-detail.php` | **Poor** | **Legacy soft-run** | Summary grid + 4 action buttons; contradicts intake-first model |
| `erp-staff-home.php` | Moderate | Staff home CSS | Extra reception hub card — OK as entry |

### 5.2 Specific UX failures

1. **Visually inconsistent:** Workbench green dark luxury vs P1 list/detail light blue-gray tables — same role, two eras of UI.
2. **Too crowded:** Intake shell stacks 10+ sections on one page instead of step flow (مشتری → خودرو → دسته‌بندی → مستندات → gate → actions).
3. **Actions too early:** Detail page shows accept + convert + reject immediately; list offers "مشاهده" alongside "تکمیل پرونده" with equal weight.
4. **Old admin aesthetic:** `erp-reception-online-requests.php` and `detail.php` unchanged visually from P1 soft-run era.
5. **Confusing labels:** "Hub فرآیند پذیرش" (mixed Persian/English); "JobCard" Latin in Persian UI; `ready_full_reception` not exposed as clear staff-facing state on working pages.
6. **False completeness:** Green workbench implies production-ready reception; clicking through to intake or list breaks experience (fatal or legacy UI).

### 5.3 Owner UX expectations not met

- **Page-by-page flow** instead of dashboard dump — **not implemented**
- **MOGHAREH360 green shell on all reception paths** — **partial** (P1 pages excluded)
- **Receptionist-directed journey** starting at تکمیل پرونده — **not enforced**

---

## 6. Action Placement Review

### 6.1 Current exposure map

| Action | `online-request-detail.php` | `intake-file.php` | Gate expected |
|--------|----------------------------|-------------------|---------------|
| رد درخواست | **Always when canAct** | Temp section (FIX-A) — **fatals** | Temp reception OK |
| علامت‌گذاری در حال بررسی | **Always when canAct** | Temp section — **fatals** | Temp OK |
| پذیرش درخواست (accept) | **Always when canAct** | **Not on intake shell** | Should be after full reception gate |
| تبدیل به کارت کار | **Always when canAct** | Only if `can_show_convert` — **fatals** | After service path clear only |

### 6.2 Findings

1. **Shown too early:** Accept, convert, reject, under_review on **raw detail page** without intake gate or service classification check.
2. **Should move behind intake gate:** Accept (final reception), convert — primary actions should live on intake shell after gate state is visible.
3. **Should stay in temporary reception:** Reject, request more info (under_review), hold/expert referral placeholders.
4. **Should disable until service classification done:** Convert — logic exists in helper but detail page bypasses it entirely; backend `m360_reception_convert_to_jobcard()` still allows convert on OTP/customer/plate only.

### 6.3 Process contradiction

Owner: *"Raw online request detail should not be the main decision point."*

Current: Detail page remains the **only fully working** action surface; intake shell (intended primary) **does not render**.

---

## 7. Service Classification Review

### 7.1 What exists

- Helper taxonomy `m360_rw_service_classification_taxonomy()` with correct Persian labels.
- Read-only display block on intake shell (when page works).
- Gate checks `reception_service_*` payload keys (read-only).
- Placeholder: «ثبت دسته‌بندی خدمات در فاز تکمیل عملیات پذیرش فعال می‌شود.»

### 7.2 What is missing (UAT blockers)

| Requirement | Status |
|-------------|--------|
| Reception staff multi-select under کارشناسی و عیب‌یابی | **Missing UI** |
| Select سرویس‌های دوره‌ای | **Missing UI** |
| Select کارشناسی خرید و فروش | **Missing UI** |
| Persist classification (payload write without new table) | **Not in C-2B scope — but UAT needs at least stub write or clear C-2C dependency** |
| Block convert until classification saved | Helper logic only; detail bypass + backend convert ignores classification |
| Reporting / analytics readiness | **Not achievable** — no data captured |

### 7.3 Customer vs reception field collision

- Customer online form: `request_type` select (`customer-request.php`).
- Reception internal classification: separate keys not written.
- Warning shown on intake — **insufficient** for owner process separation.

---

## 8. Gap Matrix

| Requirement | Current implementation | Pass / Fail / Partial | Evidence | Business impact | Required correction | Fix priority |
|-------------|-------------------------|----------------------|----------|-----------------|---------------------|--------------|
| First page only پذیرش / پروفایل پرسنلی | Two-card landing | **Partial** | `erp-reception-workbench.php` L54–66 | Minor confusion | Keep landing; remove noise from default path | P2 |
| Personnel hub separation | `?section=profile` with 4 HR cards | **Partial** | workbench L68–84 | HR shortcuts fragmented | Match exact 4 labels/routes | P2 |
| Reception hub four stages | Hub at `?section=reception` | **Partial** | helper `m360_rw_reception_process_hub_cards()` | Staff cannot find temp queue | Dedicated temp reception list by gate | P1 |
| Temporary reception logic | Gate states in helper | **Fail** | Line 558 fatal; no temp queue UI | Core process broken | Fix fatal + temp workbench | **P0** |
| Full reception subroutes | Sub-links under پذیرش card | **Partial** | hub subs | Walk-in still placeholder | Wire flow pages | P1 |
| Service classification | Read-only taxonomy | **Fail** | intake shell section | No management reporting | Multi-select + payload write (no new table) | **P0** |
| Multi-select diagnostic categories | Display list only | **Fail** | taxonomy HTML | Cannot classify faults | Interactive UI (Rework-A or C-2C) | **P0** |
| Intake file gate | Helper logic | **Fail** | fatal at build_intake_file | No gate visible | Fix 9-arg call + render | **P0** |
| Reject allowed in temp reception | Detail always; intake unreachable | **Partial** | detail L141–146 | Wrong entry point | Intake-first; reject on temp shell | P1 |
| Convert blocked until fault clear | Helper `can_show_convert`; detail ignores | **Fail** | detail shows convert always | Premature JobCards | Hide convert on detail; enforce gate + backend | **P0** |
| Raw detail action placement | All 4 actions exposed | **Fail** | detail L119–147 | Process bypass | Demote detail to read-only + link to intake | **P0** |
| Visual quality | Split green vs legacy | **Fail** | CSS paths differ | Unprofessional UAT | Unify P1 list/detail under luxury shell | P1 |
| Runtime fatal coverage | 8-arg call line 558 | **Fail** | helper L558 | **Production blocker** | Pass `null` as 9th arg | **P0** |
| Browser validation coverage | Not run; tests static | **Fail** | C-2B report §9 | False PASS claims | Mandatory HTTP smoke tests | **P0** |

---

## 9. Required Rework Plan

### P11.9-C-2B-REWORK-A — Reception Workbench Process & UX Rebuild

**Prerequisite:** Do **not** start P11.9-C-2C until Rework-A passes owner UAT checklist.

#### 1. Exact scope

- Fix runtime fatal and add runtime smoke tests (mandatory gate).
- Restructure reception navigation: landing → profile hub | reception hub → temp/full flows.
- Make **intake file** the primary operational surface; demote raw detail to read-only + deep link.
- Implement service classification **UI** (multi-select) with payload persistence using existing `request_payload_json` or safe reception-only JSON merge — **no new DB tables**.
- Align action placement with temporary vs full reception model.
- Apply green luxury shell to P1 online list + detail (visual parity minimum).
- Split intake shell into stepped sections or tabs (reduce all-in-one crowding).

#### 2. Allowed files (indicative)

- `public_html/includes/m360-reception-workbench-helper.php`
- `public_html/erp-reception-workbench.php`
- `public_html/erp-reception-intake-file.php`
- `public_html/erp-reception-online-requests.php` (link/navigation/shell only)
- `public_html/erp-reception-online-request-detail.php` (action demotion / redirect)
- `public_html/assets/css/moghare360-v1-luxury-ui.css`
- `tools/test-p11-9-c-2b-rework-*` (new runtime + browser smoke)
- `docs/audit/` (rework report only)

#### 3. Forbidden files

- Auth/login stack, `staff-auth.php`, `access-control.php`
- Permissions, roles, departments, positions
- SQL migrations / schema changes
- OTP architecture changes
- Workflow handler core rewrites (except safe gating messages)
- Private files, secrets, P12 scope

#### 4. Pages to keep

- `erp-reception-workbench.php` (restructure, do not discard)
- `erp-reception-intake-file.php` (rebuild UX, fix fatal)
- `erp-reception-online-requests.php` (reskin + navigation)
- Existing P2/P1.5 routes as linked targets (jobcards, contracts)

#### 5. Pages to replace / restructure

- Intake shell layout → stepped flow
- Reception hub → explicit temp reception queue page or filtered view
- Online request detail → read-only summary; actions moved to intake with gate

#### 6. Runtime tests (required)

- `m360_rw_build_intake_file()` smoke (0 and valid ID)
- `m360_rw_build_gate()` 9-argument empty call
- HTTP 200 on intake URLs for IDs 18, 20 (logged-in staff session or CLI bootstrap)
- Gate state assertions for sample payloads (temp vs ready_convert)

#### 7. Browser validation URLs

1. `http://localhost:8080/moghare360/erp-staff-home.php` (demo.reception)
2. `http://localhost:8080/moghare360/erp-reception-workbench.php`
3. `http://localhost:8080/moghare360/erp-reception-workbench.php?section=reception`
4. `http://localhost:8080/moghare360/erp-reception-workbench.php?section=profile`
5. `http://localhost:8080/moghare360/erp-reception-online-requests.php`
6. `http://localhost:8080/moghare360/erp-reception-intake-file.php?online_request_id=18`
7. `http://localhost:8080/moghare360/erp-reception-intake-file.php?online_request_id=20`
8. `http://localhost:8080/moghare360/erp-reception-online-request-detail.php?request_id=18`

#### 8. Owner acceptance checklist

- [ ] Intake file loads without PHP fatal for IDs 18 and 20
- [ ] Landing shows only پذیرش + پروفایل پرسنلی
- [ ] Reception hub shows four stages with clear temp vs full paths
- [ ] Temporary reception allows reject / request info; blocks convert
- [ ] Service classification multi-select works (reception-only)
- [ ] Convert only when fault/service path clear
- [ ] Raw detail no longer primary decision page
- [ ] Visual consistency across reception pages (green shell)
- [ ] Runtime smoke tests in CI/local signoff bundle

#### 9. No DB change rule

Rework-A must persist classification via existing payload columns or documented safe update path — **no new tables**.

#### 10. No C-2C until Rework-A passes

P11.9-C-2C (write actions) remains blocked until UAT signoff on Rework-A.

---

## 10. Stop / Continue Decision

| Decision | **STOP C-2B / STOP C-2C — REWORK REQUIRED** |
|----------|-----------------------------------------------|
| Rationale | Runtime fatal makes core deliverable non-functional; process and UX do not meet owner-approved model; test strategy gave false confidence |
| Next phase | **P11.9-C-2B-REWORK-A** only |
| Blocked | P11.9-C-2C until Rework-A owner UAT pass |

**P11.9-C-2B is rejected for UAT and must be reworked before P11.9-C-2C.**

---

P11.9-C-2B-UAT-REJECTION documents the technical runtime failure, UX failure, process mismatch, action placement gaps, and missing owner-process compliance of the current reception workbench implementation before any further development, without changing code, SQL, Auth/Login, permissions, roles, workflow, users, JobCards, OTP, private files, secrets, or P12 scope.

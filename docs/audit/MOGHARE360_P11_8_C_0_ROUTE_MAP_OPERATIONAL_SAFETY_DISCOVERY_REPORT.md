# MOGHARE360 P11.8-C-0 — Route Map Operational Safety Discovery Report

**Phase:** P11.8-C-0  
**Mode:** REPORT ONLY — no code, SQL, Auth, permissions, roles, or registry changes  
**Date:** 2026-06-26  
**Scope:** `erp-route-map.php`, `m360-navigation-registry.php`, P11.8 Staff Home / operational shell safety context

---

## 1. Executive Summary

The Route Map (`erp-route-map.php`) is a **P10 release-hardening technical catalog**. It lists **54 registry routes** in one flat table and renders **every URL as a clickable `<a href>`** regardless of method, API flag, or customer flag. The **File OK** badge only means `file_exists()` on disk — not operational readiness.

After P11.8 Staff Home route-safety work (guided `info`, non-clickable `note`, `runtime_hold` for part-use/payment), **Route Map remains the largest unsafe navigation surface**: staff and managers can reach it from Product Home, RC nav, and the new operational shell link **نقشه مسیرها**.

**Verdict:** Route Map is **not operationally safe** for daily staff/manager use without UI classification changes. The navigation registry **already contains most metadata needed** (`expected_method`, `is_api`, `is_customer_entry`, `is_staff_entry`, `access_type`, `notes`) but **Route Map ignores it for link behavior**.

**Recommendation:** **Upgrade** existing Route Map + registry consumption (P11.8-C) — do **not** build a parallel route system. Reuse P11.8 Staff Home rules (`m360_staff_home_is_action_endpoint`, guided detail list, `M360_STAFF_HOME_RUNTIME_NOT_READY`).

---

## 2. Owner Requirement Interpretation

The owner observes that mixing GET boards, POST handlers, APIs, and customer token pages in one clickable table causes:

- Direct clicks on action endpoints (confusing redirects or raw errors)
- API routes treated like normal pages
- Customer flows mixed with staff workbench routes
- **File OK** mistaken for “safe to operate”

P11.8-C-0 must determine whether this is fixable by **reusing existing P11.8 route-safety patterns** before any registry or permission expansion.

---

## 3. Existing Route Map / Registry Discovery

### 3.1 Standing rule applied

| Question | Answer |
|----------|--------|
| Similar capability exists? | **Yes — partial**, fragmented |
| Where? | Staff Home helper (P11.8-A/B), registry flags, route audit helper |
| Complete? | **Staff Home: yes for workbench cards. Route Map: no.** |
| Upgrade vs build? | **Upgrade Route Map rendering + shared classifier helper** |
| Minimal build if none? | Single `m360_nav_operational_class()` reused by Route Map + optional Link Audit |

### 3.2 Route Map implementation (`erp-route-map.php`)

| Aspect | Current behavior |
|--------|------------------|
| Data source | `m360_route_audit_summary()` → `m360_nav_registry()` + `file_exists` |
| URL column | **Always clickable** `<a href="url">url</a>` (raw filename visible) |
| Method column | Display only — **does not disable links** |
| API / Customer / Staff columns | Yes/No text — **does not affect clickability** |
| File column | OK / MISSING from disk only |
| Access guard | `m360_release_hardening_require_staff()` — any logged-in staff |
| Operational shell | Linked from all P11.8-B boards/details as **نقشه مسیرها** |

### 3.3 Navigation registry (`m360-navigation-registry.php`)

54 routes P1–P10 with fields:

- `expected_method` (GET/POST)
- `is_api`, `is_customer_entry`, `is_staff_entry`, `is_owner_entry`, `is_demo_entry`
- `access_type` (public, staff, customer)
- `notes` (sometimes documents POST-only, e.g. soft-run checklist)

**Not in registry (but on Staff Home):** `erp-jobcard-part-use.php`, `erp-payment-tracking.php`, `erp-stock-board.php`, many bridge targets — Route Map is **not exhaustive** of Staff Home but still unsafe for listed routes.

### 3.4 P11.8 Staff Home safety (reference — complete for workbench)

| Mechanism | File | Status |
|-----------|------|--------|
| Action endpoint blocklist | `m360_staff_home_is_action_endpoint()` | **Partial** — misses contract generate/send |
| Guided detail | `card_type = info` | **Complete** on workbench |
| Internal action cards | `card_type = note` | **Complete** |
| Runtime not ready | `runtime_hold` + `M360_STAFF_HOME_RUNTIME_NOT_READY` | **Complete** (part-use, payment) |
| Reference bridge | `ref` / `ref_coord` / `diag` | **Complete** |

**Disconnected:** Route Map does **not** call any Staff Home safety helpers.

### 3.5 Operational shell (`m360-operational-shell-helper.php`)

Exposes Route Map to **all operational roles** via secondary nav link. Increases exposure of unsafe catalog during One-Day Run.

---

## 4. Route Classification Matrix

**Legend — Current route map behavior:** all rows use clickable URL link unless file missing (link still shown).

### 4.1 Operational clickable (GET board/list/safe hub) — 22 routes

| Route | Method | Current map | Proposed class | Clickable? | Risk | Action |
|-------|--------|-------------|----------------|------------|------|--------|
| `erp-reception-online-requests.php` | GET | Clickable | Operational | Yes | Low | Keep link |
| `erp-intake-contracts.php` | GET | Clickable | Operational | Yes | Low | Keep link |
| `erp-reception-jobcards.php` | GET | Clickable | Operational | Yes | Low | Keep link |
| `erp-technical-board.php` | GET | Clickable | Operational | Yes | Low | Keep link |
| `erp-estimate-board.php` | GET | Clickable | Operational | Yes | Low | Keep link |
| `erp-work-execution-board.php` | GET | Clickable | Operational | Yes | Low | Keep link |
| `erp-qc-board.php` | GET | Clickable | Operational | Yes | Low | Keep link |
| `erp-final-invoice-board.php` | GET | Clickable | Operational | Yes | Low | Keep link |
| `erp-product-home.php` | GET | Clickable | Operational | Yes | Low | Keep link |
| `contract-template-intake.php` | GET | Clickable | Operational | Yes | Med | Keep; label as staff tool |
| `customer-request.php` | GET | Clickable | Customer/Public | Caution | Med | Separate section or external link label |
| `erp-management-dashboard.php` | GET | Clickable | Diagnostic/Owner | Yes* | Low | *Manager reference |
| `erp-owner-control-center.php` | GET | Clickable | Diagnostic/Owner | Yes* | Low | *Owner/manager |
| `erp-operational-kpi.php` | GET | Clickable | Diagnostic/Owner | Yes* | Low | *Manager |
| `erp-bottleneck-monitor.php` | GET | Clickable | Diagnostic/Owner | Yes* | Low | *Manager |
| `erp-financial-control-summary.php` | GET | Clickable | Diagnostic/Owner | Yes* | Low | *Manager |
| `erp-soft-run-control-center.php` | GET | Clickable | Demo/Release | Yes* | Med | Demo section only |
| `erp-end-to-end-demo-scenario.php` | GET | Clickable | Demo/Release | Yes* | Med | Demo section |
| `erp-demo-flow-map.php` | GET | Clickable | Demo/Release | Yes* | Med | Demo section |
| `erp-demo-readiness-report.php` | GET | Clickable | Demo/Release | Yes* | Med | Demo section |
| `erp-demo-package-rc.php` | GET | Clickable | Demo/Release | Yes* | Med | RC section |
| `erp-release-readiness.php` | GET | Clickable | Demo/Release | Yes* | Med | Owner RC |

*Acceptable for owner/admin; confusing for line staff if shown in same table without filter.

### 4.2 Guided only (detail / record context) — 10 routes

| Route | Method | Current map | Proposed class | Clickable? | Risk | Action |
|-------|--------|-------------|----------------|------------|------|--------|
| `erp-reception-online-request-detail.php` | GET | Clickable bare URL | Guided | **No** (or board-first) | **High** — needs request ID | Text + «از فهرست» |
| `erp-intake-contract-detail.php` | GET | Clickable | Guided | **No** | **High** — needs contract_id | Same |
| `erp-reception-jobcard-detail.php` | GET | Clickable | Guided | **No** | **High** — needs jobcard_id | Same |
| `erp-technical-jobcard-detail.php` | GET | Clickable | Guided | **No** | **High** | Same |
| `erp-estimate-detail.php` | GET | Clickable | Guided | **No** | **High** | Same |
| `erp-work-execution-detail.php` | GET | Clickable | Guided | **No** | **High** | Same |
| `erp-qc-detail.php` | GET | Clickable | Guided | **No** | **High** | Same |
| `erp-final-invoice-detail.php` | GET | Clickable | Guided | **No** | **High** | Same |
| `erp-settlement-detail.php` | GET | Clickable | Guided | **No** | **High** | Same |
| `erp-jobcard-timeline.php` | GET | Clickable | Guided | **No** | **High** — needs jobcard_id | Same as P11.8-A-FIX-A |

### 4.3 Internal action endpoint (POST) — 13 routes

| Route | Method | Current map | Proposed class | Clickable? | Risk | Action |
|-------|--------|-------------|----------------|------------|------|--------|
| `erp-reception-online-request-accept.php` | POST | Clickable | Internal action | **No** | **High** | Badge: عملیات داخلی |
| `erp-intake-contract-generate.php` | POST | Clickable | Internal action | **No** | **High** | Same |
| `erp-intake-contract-send.php` | POST | Clickable | Internal action | **No** | **High** | Same |
| `erp-reception-jobcard-action.php` | POST | Clickable | Internal action | **No** | **High** | Same |
| `erp-technical-jobcard-action.php` | POST | Clickable | Internal action | **No** | **High** | Same |
| `erp-estimate-action.php` | POST | Clickable | Internal action | **No** | **High** | Same |
| `erp-work-execution-action.php` | POST | Clickable | Internal action | **No** | **High** | Same |
| `erp-qc-action.php` | POST | Clickable | Internal action | **No** | **High** | Same |
| `erp-final-invoice-action.php` | POST | Clickable | Internal action | **No** | **High** | Same |
| `erp-settlement-action.php` | POST | Clickable | Internal action | **No** | **High** | Same |
| `erp-soft-run-checklist.php` | POST† | Clickable | Mixed† | Caution | Med | †Page loads on GET; registry method misleading |

**GET click behavior today:** Most P2–P7 `*-action.php` **redirect to board** (not ideal UX). Legacy/mission pages may emit **`ERP security validation failed.`** when auth/CSRF guards run on wrong method — owner observation is valid for those paths.

### 4.4 API route — 12 routes

| Route | Method | Current map | Proposed class | Clickable? | Risk | Action |
|-------|--------|-------------|----------------|------------|------|--------|
| `api/customer/request.php` | POST | Clickable | API | **No** | **High** | Label: API / سیستم |
| `api/customer/contract-send-otp.php` | POST | Clickable | API | **No** | **High** | Same |
| `api/customer/contract-sign.php` | POST | Clickable | API | **No** | **High** | Same |
| `api/customer/estimate-send-otp.php` | POST | Clickable | API | **No** | **High** | Same |
| `api/customer/estimate-approve.php` | POST | Clickable | API | **No** | **High** | Same |
| `api/customer/delivery-send-otp.php` | POST | Clickable | API | **No** | **High** | Same |
| `api/customer/delivery-confirm.php` | POST | Clickable | API | **No** | **High** | Same |
| `api/management/kpi-summary.php` | GET | Clickable | API | **No** | **High** — raw JSON in browser | Same |
| `api/management/bottleneck-summary.php` | GET | Clickable | API | **No** | **High** | Same |
| `api/management/jobcard-timeline.php` | GET | Clickable | API | **No** | **High** | Same |
| `api/soft-run/demo-scenario-status.php` | GET | Clickable | API | **No** | Med | Same |
| `api/soft-run/readiness-summary.php` | GET | Clickable | API | **No** | Med | Same |

### 4.5 Customer route — 7 routes

| Route | Method | Current map | Proposed class | Clickable? | Risk | Action |
|-------|--------|-------------|----------------|------------|------|--------|
| `customer-intake-contract.php` | GET | Clickable | Customer | **No** for staff nav | **High** — token/session | Separate customer section |
| `customer-intake-contract-sign.php` | GET | Clickable | Customer | **No** | **High** | Same |
| `customer-estimate-approval.php` | GET | Clickable | Customer | **No** | **High** | Same |
| `customer-estimate-approval-sign.php` | GET | Clickable | Customer | **No** | **High** | Same |
| `customer-delivery-review.php` | GET | Clickable | Customer | **No** | **High** | Same |
| `customer-delivery-sign.php` | GET | Clickable | Customer | **No** | **High** | Same |
| `customer-request.php` | GET | Clickable | Customer/Public | Optional | Med | Public form — not staff daily route |

### 4.6 Runtime-not-ready (Staff Home only — not in registry)

| Route | In route map? | Staff Home P11.8-B-A |
|-------|---------------|----------------------|
| `erp-jobcard-part-use.php` | **No** | `runtime_hold` — not clickable |
| `erp-payment-tracking.php` | **No** | `runtime_hold` — not clickable |

**Gap:** Staff Home demotes these; Route Map cannot warn about them until registry includes them with `operational_ready` metadata.

### 4.7 Diagnostic / release / meta — 2 routes

| Route | Method | Current map | Proposed class | Clickable? | Risk | Action |
|-------|--------|-------------|----------------|------------|------|--------|
| `erp-route-map.php` | GET | Clickable | Diagnostic/Release | Yes (meta) | Med — self-reference | Technical view OK |
| `erp-link-audit.php` | GET | Clickable | Diagnostic/Release | Yes | Low | Owner/RC only |

---

## 5. High-Risk Route Findings

| Route | Why risky | Current display | Recommended display | Code change? | Workflow change? |
|-------|-----------|-----------------|---------------------|--------------|------------------|
| All `*-action.php` (10) | POST handler; not a page | Clickable URL + File OK | **عملیات داخلی — ورود مستقیم مجاز نیست** | Yes (Route Map UI) | No |
| `erp-reception-online-request-accept.php` | POST accept handler | Clickable | Internal action badge | Yes | No |
| `erp-intake-contract-generate/send.php` | POST contract ops | Clickable | Internal action badge | Yes | No |
| All `api/*` (12) | JSON/API contract | Clickable + Staff=Yes on some | **API — فقط برای سیستم** | Yes | No |
| All `customer-*` (6) | OTP/token customer flow | Clickable mixed with staff | **مسیر مشتری — نه میز کار** | Yes | No |
| All `*-detail.php` (9) | Requires record ID | Clickable bare URL | **راهنمای مسیر — از تابلو** | Yes | No |
| `erp-jobcard-timeline.php` | Requires jobcard_id | Clickable | Guided | Yes | No |
| `api/management/kpi-summary.php` | GET but returns JSON | Clickable; looks like page | API non-clickable | Yes | No |
| Routes with File OK only | Disk exists ≠ product-ready | Green OK badge | Add **operational_ready** class | Yes (metadata) | No |

---

## 6. Registry Metadata Gaps

| Missing metadata | Why needed | Existing equivalent? | Recommended field/logic | Scope |
|------------------|------------|----------------------|-------------------------|-------|
| `operational_class` enum | Drive Route Map link/badge | Partially in Staff Home `card_type` | `operational \| guided \| action \| api \| customer \| demo \| runtime_hold` | P11.8-C UI |
| `operational_clickable` bool | Explicit link policy | Implied by Staff Home clickability | Derived from class + runtime_ready | P11.8-C UI |
| `requires_record_param` | Detail pages | P11.8-A discovery list | e.g. `jobcard_id`, `contract_id` | Optional notes |
| `runtime_ready` | part-use, payment | `M360_STAFF_HOME_RUNTIME_NOT_READY` | Same map in shared helper | Extend registry or shared PHP const |
| Routes outside registry | Stock, part-use, payment | Staff Home only | Add to registry **or** exclude from operational view | Backlog if full catalog needed |
| Unified action classifier | Contract POST missing in Staff Home | `m360_staff_home_is_action_endpoint()` | `m360_nav_is_action_endpoint($url)` shared | P11.8-C refactor |
| Display title vs URL | Raw filename in link text | `title_fa` exists | Show title only; hide raw URL in operational mode | P11.8-C UI |
| Dual view mode | Owner wants technical + operational | None | `?view=technical\|operational` toggle | P11.8-C |

**No DB column required** — PHP registry array extensions or derived functions suffice.

---

## 7. Operational Safety Risks

1. **POST routes clickable** — 13 registry POST routes rendered as normal links; staff may think File OK means “open this page.”  
2. **API routes clickable** — 12 API endpoints open JSON/error responses in browser tab.  
3. **Action endpoints UX** — P2–P7 actions redirect on GET (confusing); legacy paths may show **`ERP security validation failed.`**  
4. **Detail pages without ID** — Empty/error state; wastes One-Day Run time.  
5. **Customer token pages in staff table** — Mixed with `Staff=Yes` rows; role confusion.  
6. **File OK ≠ operational ready** — No check for load errors (payment tracking), legacy guards (part-use), or empty boards.  
7. **Route map linked from operational shell** — P11.8-B exposes unsafe catalog to technicians/reception during daily work.  
8. **Manager One-Day Run risk** — Manager bridge + Route Map both list reference routes; Route Map lacks P11.8 safety classes.  
9. **Registry / Staff Home drift** — Action list in Staff Home omits contract generate/send; Route Map lists them as clickable POST.

---

## 8. Reuse / Upgrade / Build Decision

| Area | Existing base? | Status | Next action | Build new? |
|------|----------------|--------|-------------|------------|
| Route safety classification | Staff Home P11.8-A/B | **Partial** | Extract shared `m360_nav_operational_class()` | **No** — upgrade |
| Registry metadata | `m360_nav_route()` flags | **Partial** | Derive class from method + flags + URL patterns | **No** — upgrade |
| Route Map UI | `erp-route-map.php` | **Broken for ops** | Operational view: badges, non-clickable rows, grouped sections | **No** — upgrade |
| Link Audit | `erp-link-audit.php` | Technical OK | Keep technical; optional same classifier | **No** |
| Runtime-not-ready | `M360_STAFF_HOME_RUNTIME_NOT_READY` | **Staff Home only** | Share with Route Map when routes added | **No** |
| Dual technical/operational catalog | None | Missing | Toggle or separate tab | **Minimal UI** |
| New permission for Route Map | None | Not required | Optional hide RC nav from line roles — backlog | **No** (backlog) |
| DB / workflow | N/A | Forbidden | — | **No** |

---

## 9. Minimum Next Patch Recommendation

**P11.8-C (proposed) — UI-only Route Map operational safety**

1. Add shared helper (e.g. in `m360-route-audit-helper.php` or new `m360-nav-operational-safety-helper.php`):
   - Reuse `m360_staff_home_is_action_endpoint()` logic + registry `is_api`, `expected_method`, detail URL patterns, customer flags.
   - Map to Persian badges matching Staff Home: **مسیر فعال**, **راهنمای مسیر**, **عملیات داخلی**, **API**, **مسیر مشتری**, **نیازمند بررسی عملیاتی**.

2. Update **`erp-route-map.php` only** (and optionally hide operational shell link for line staff — backlog):
   - **Operational view (default):** clickable only for operational GET boards/hubs; others show badge + `title_fa` without `<a href>`.
   - **Technical view (`?view=technical`):** current full table for owner/RC.
   - Replace raw URL link text with `title_fa`; show method/API/customer as badges.

3. **Do not change** `m360-navigation-registry.php` array in first patch if classifier can derive from existing fields (owner forbade registry change in this phase — **report recommends C phase may extend registry** with owner approval).

4. Add tests mirroring P11.8-A route-safety rules for Route Map HTML output.

**Needs DB?** No. **Needs permission?** No. **Needs workflow change?** No.

**Backlog only:** Restrict Route Map to owner/admin role; add part-use/payment to registry with runtime_hold; fix destination pages; Link Audit operational mode.

---

## 10. Final Persian Answers

**1. آیا Route Map فعلی برای کاربر عملیاتی امن است؟**  
**خیر.** همه URLها تقریباً clickable هستند و File OK فقط وجود فایل را نشان می‌دهد، نه آمادگی عملیاتی.

**2. آیا POST/action endpointها باید clickable باشند؟**  
**خیر.** باید «عملیات داخلی / ورود مستقیم مجاز نیست» نمایش داده شوند — همان قاعده P11.8-A برای Staff Home.

**3. آیا API routeها باید مثل لینک معمولی نمایش داده شوند؟**  
**خیر.** باید «API / فقط برای سیستم» باشند و لینک مستقیم نداشته باشند.

**4. آیا File OK یعنی مسیر برای عملیات آماده است؟**  
**خیر.** فقط `file_exists` است. مثال: `erp-payment-tracking.php` ممکن است File OK باشد ولی بارگذاری عملیاتی نشود؛ part-use هنوز محصولی نشده.

**5. آیا باید Route Map به دو حالت «فنی» و «عملیاتی» تقسیم شود؟**  
**بله — توصیه می‌شود.** حالت فنی برای RC/مالک؛ حالت عملیاتی برای Staff Home و One-Day Run با قواعد P11.8.

**6. کمترین Patch بعدی چیست؟**  
**P11.8-C:** helper مشترک طبقه‌بندی + Route Map با لینک فقط برای board/list امن + badge برای guided/action/api/customer — بدون تغییر DB/permission/workflow.

**7. آیا این کار نیاز به DB یا Permission جدید دارد؟**  
**خیر** برای Patch پیشنهادی UI-only.

**8. چه چیزهایی باید فقط backlog بماند؟**  
- اصلاح صفحات part-use و payment-tracking  
- محدودسازی Route Map با permission نقش  
- افزودن همه مسیرهای Staff Home به registry  
- تغییر handlerهای POST  
- P12 / impersonation / HR  

---

P11.8-C-0 discovers whether the Route Map can be safely upgraded from a technical route catalog into an operationally safe navigation reference by reusing existing route metadata and P11.8 route-safety rules before any code changes.

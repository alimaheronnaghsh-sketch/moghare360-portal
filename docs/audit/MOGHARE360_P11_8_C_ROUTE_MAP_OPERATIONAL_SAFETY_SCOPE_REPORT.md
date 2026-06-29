# MOGHARE360 P11.8-C — Route Map Operational Safety Scope Report

**Phase:** P11.8-C  
**Gate:** PASS — UI-only Route Map upgrade with shared operational classifier  
**Date:** 2026-06-26  
**Based on:** `MOGHARE360_P11_8_C_0_ROUTE_MAP_OPERATIONAL_SAFETY_DISCOVERY_REPORT.md`

---

## 1. P11.8-C-0 findings implemented

| Finding | Implementation |
|---------|----------------|
| All 54 routes rendered as clickable links | **Fixed** — operational view uses class-based link rules |
| File OK implies operational readiness | **Fixed** — renamed to **فایل موجود / فایل ناموجود** |
| POST/action endpoints mixed with boards | **Fixed** — class **عملیات داخلی**, non-clickable in operational view |
| API routes as normal links | **Fixed** — class **API سیستم**, non-clickable |
| Customer token routes mixed with staff | **Fixed** — class **مسیر مشتری**, separated in table grouping |
| Detail pages without required ID | **Fixed** — class **راهنمای مسیر**, non-clickable |
| Route Map reachable from shell/Product Home | **Preserved** — page upgraded, links still work |
| Runtime-not-ready (part-use, payment) | **Applied** when URL matches known list (Staff Home parity) |
| Staff Home safety drift | **Aligned** — shared action-endpoint patterns |

---

## 2. Registry metadata reused (no semantic change)

| Field | Use |
|-------|-----|
| `expected_method` | POST → action class |
| `is_api` | API class |
| `is_customer_entry` | Customer class |
| `is_staff_entry` | Operational eligibility |
| `is_owner_entry` / `is_demo_entry` | Diagnostic class |
| `category`, `phase_code`, `title_fa` | Display columns |
| `url` | Classification + link target |
| `file_exists` (audit helper) | **فایل موجود** only |

Registry array in `m360-navigation-registry.php` is **not modified**.

---

## 3. Clickable route classes (operational view)

- **Operational clickable** — GET board/list/hub/intake staff pages  
- **Diagnostic / release / demo** — GET safe pages (P8 dashboards, product home, soft-run centers, route map meta)

---

## 4. Non-clickable route classes (operational view)

- **Guided only** — `*-detail.php`, timeline  
- **Internal action** — POST routes, `*-action.php`, accept/generate/send handlers  
- **API** — all `api/*`  
- **Customer** — customer token/public flows (including `customer-request.php`)  
- **Runtime-not-ready** — `erp-jobcard-part-use.php`, `erp-payment-tracking.php` when listed  

---

## 5. Operational and technical views

| View | Query | Default |
|------|-------|---------|
| **نمای عملیاتی** | `?view=operational` or default | Yes |
| **نمای فنی** | `?view=technical` | No |

Both views show all registry routes with class badges. Operational view restricts active navigation links. Technical view shows path as code/text for unsafe routes — not “click to use” styling.

---

## 6. File OK reframed as File Exists

**File OK** was misread as “ready to operate.” New labels:

- **فایل موجود** — `file_exists()` true  
- **فایل ناموجود** — missing on disk  

Operational safety is a **separate column** (قابل ورود / راهنمای مسیر / …), never inferred from file existence alone.

---

## 7. Why no DB / permission / Auth / workflow / API change

Classification is **derived at render time** from existing registry fields and URL patterns. Route Map HTML/CSS only. No handler, API, or token logic touched.

---

## 8. Risks remaining backlog

| Item | Status |
|------|--------|
| Fix `erp-jobcard-part-use.php` destination | Backlog |
| Fix `erp-payment-tracking.php` load error | Backlog |
| Add part-use/payment to registry | Backlog (optional) |
| Restrict Route Map to owner role only | Backlog |
| Hide **نقشه مسیرها** from line-staff shell | Backlog |
| Unified `m360_nav_is_action_endpoint()` in staff home helper refactor | Backlog |

**Gate result:** Proceed with helper + Route Map UI + tests.

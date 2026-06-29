# MOGHARE360 P11.8-C — Route Map Operational Safety Report

**Phase:** P11.8-C  
**Status:** COMPLETE  
**Date:** 2026-06-26  
**Scope gate:** `MOGHARE360_P11_8_C_ROUTE_MAP_OPERATIONAL_SAFETY_SCOPE_REPORT.md`  
**Discovery basis:** `MOGHARE360_P11_8_C_0_ROUTE_MAP_OPERATIONAL_SAFETY_DISCOVERY_REPORT.md`

---

## 1. Scope Gate Result

**PASS** — All planned changes are UI-only (Route Map page, classifier helper, CSS, docs, tests). No registry semantics, Auth/Login, permissions, roles, database schema, SQL migrations, workflow logic, action handlers, API behavior, customer token logic, or P12 scope was touched.

---

## 2. P11.8-C-0 Findings Implemented

| Finding | Result |
|---------|--------|
| All registry routes rendered as normal clickable links | Fixed — only 23 of 63 routes are active links in operational view |
| File OK implied operational readiness | Fixed — **فایل موجود / فایل ناموجود** separate from safety class |
| POST/action endpoints mixed with boards | Fixed — **عملیات داخلی**, non-clickable |
| API routes as normal navigation | Fixed — **API سیستم**, path shown as code |
| Customer routes mixed with staff workbench | Fixed — **مسیر مشتری**, separated; not ops-clickable |
| Detail pages without required ID | Fixed — **راهنمای مسیر**, guided-only |
| Runtime-not-ready routes | Fixed — **نیازمند بررسی عملیاتی** for part-use and payment-tracking |
| Route Map reachable from shell / Product Home | Preserved |
| Staff Home safety alignment | Shared action-endpoint and runtime-hold patterns |

---

## 3. Files Changed

| File | Change |
|------|--------|
| `public_html/erp-route-map.php` | Operational + technical views, safety columns, conditional links |
| `public_html/includes/m360-route-operational-safety-helper.php` | **New** — classifier, badges, link rules |
| `public_html/assets/css/m360-route-map-safety.css` | **New** — RTL badges, disabled paths, view tabs |
| `docs/audit/MOGHARE360_P11_8_C_ROUTE_MAP_OPERATIONAL_SAFETY_SCOPE_REPORT.md` | Scope gate |
| `docs/audit/MOGHARE360_P11_8_C_ROUTE_MAP_OPERATIONAL_SAFETY_REPORT.md` | This report |
| `tools/test-p11-8-c-route-map-operational-safety.php` | **New** |
| `tools/test-p11-8-c-route-classifier.php` | **New** |
| `tools/test-p11-8-c-scope-security.php` | **New** |

**Not modified:** `m360-navigation-registry.php`, Auth/Login, SQL, permissions, workflow handlers, APIs.

---

## 4. Route Classes Added

| Class | Badge (FA) | Count (registry) |
|-------|------------|------------------|
| `operational` | قابل ورود | 9 |
| `guided` | راهنمای مسیر | 10 |
| `action` | عملیات داخلی | 11 |
| `api` | API سیستم | 12 |
| `customer` | مسیر مشتری | 7 |
| `diagnostic` | تشخیصی / مدیریتی | 14 |
| `runtime_hold` | نیازمند بررسی عملیاتی | 0 in registry audit rows* |

\* `erp-jobcard-part-use.php` and `erp-payment-tracking.php` are on the runtime-hold list when present in audit rows; they may not appear in the main registry audit set but are classified when encountered (Staff Home parity).

---

## 5. Operational View (نمای عملیاتی)

- **Default** (`?view=operational` or no query)
- Active links only for **قابل ورود** and safe **تشخیصی / مدیریتی** GET routes with existing files
- All other classes show Persian badge, reason, and **غیرفعال** link behavior
- Summary cards: total routes, operational clickable count, protected (non-board) routes

---

## 6. Technical View (نمای فنی)

- `?view=technical`
- Full inventory with class badges preserved
- Unsafe routes show path as `<code>` — not styled as “click to use”
- Customer GET routes may show **فقط فنی** link where file exists
- File column uses **فایل موجود** — never “OK” or “آماده عملیات”

---

## 7. Clickable vs Non-clickable Rules

| Class | Operational view | Technical view |
|-------|------------------|----------------|
| Operational | Active link if file exists | Active link |
| Diagnostic | Active link if file exists | Active link |
| Guided | Disabled (code path) | Disabled (code path) |
| Action | Disabled | Path as code only |
| API | Disabled | Path as code only |
| Customer | Disabled | Optional link (**فقط فنی**) |
| Runtime hold | Disabled | Disabled |

---

## 8. High-Risk Routes Reclassified

| Route | Previous | Now |
|-------|----------|-----|
| `*-action.php`, accept/generate/send | Clickable link | عملیات داخلی |
| `api/*` | Clickable link | API سیستم |
| `*-detail.php`, timeline | Clickable link | راهنمای مسیر |
| `customer-*` | Mixed with staff | مسیر مشتری |
| `erp-soft-run-checklist.php` (POST) | Clickable | عملیات داخلی |
| P8/P9/P10 diagnostic pages | Same as boards | تشخیصی / مدیریتی (safe GET links) |
| `erp-jobcard-part-use.php`, `erp-payment-tracking.php` | File OK → link | نیازمند بررسی عملیاتی |

---

## 9. Tests Passed

| Test | Result |
|------|--------|
| `php -l erp-route-map.php` | PASS |
| `php -l m360-route-operational-safety-helper.php` | PASS |
| `test-p11-8-c-route-classifier.php` | 197 / 197 |
| `test-p11-8-c-route-map-operational-safety.php` | 107 / 107 |
| `test-p11-8-c-scope-security.php` | 9 / 9 |
| `test-p11-8-b-a-operational-shell.php` | 27 / 27 |
| `test-p11-8-b-a-runtime-route-readiness.php` | 20 / 20 |
| `test-p11-8-a-fix-a-route-safety-ui.php` | 95 / 95 |
| `test-v1-production-signoff.php` | 23 / 23 |

---

## 10. Browser Validation

**URL:** `http://localhost:8080/moghare360/erp-route-map.php`

| Check | Result |
|-------|--------|
| HTTP 200 | PASS |
| Default operational view | PASS |
| Technical view tab | PASS |
| No raw “File OK” | PASS |
| **فایل موجود** label | PASS |
| No PHP warnings/notices | PASS |
| No Persian mojibake | PASS |

Files copied to `C:\xampp\htdocs\moghare360\`. Route Map links from Staff Home, Product Home, and operational shell remain unchanged (same target URL).

---

## 11. Security Confirmation

- No Auth/Login change
- No password/session change
- No permission/role seed change
- No DB schema change
- No SQL migration
- No workflow change
- No action handler change
- No API behavior change
- No customer token behavior change
- No impersonation
- No manager override engine
- No HR self-service
- No P12 scope
- No secrets committed

---

## 12. Remaining Gaps (Backlog)

| Item | Reason deferred |
|------|-----------------|
| Automated browser runtime probe per route | Would require HTTP harness / auth context — out of P11.8-C UI scope |
| Dynamic runtime-not-ready from live HTTP | Needs runtime probe service — backlog |
| Registry routes not in audit set (part-use, payment if absent) | No registry change allowed — hold list is static helper |
| Permission-aware link visibility | Would change permission model |
| Customer route sandbox preview | Would touch customer token flow |

---

## 13. Recommended Next Step

**P11.8-D or One-Day Run dry run:** Use operational view as the staff/manager navigation reference during soft run; extend runtime-not-ready list only when new P11.8-B browser validation reports confirm additional routes — still UI-only, no registry semantics change.

---

P11.8-C upgrades Route Map into an operationally safe route reference by classifying clickable, guided, action, API, customer, diagnostic and runtime-not-ready routes without changing Auth/Login, permissions, roles, database schema, SQL, workflow logic, action handlers, API behavior, customer token behavior, impersonation, manager override, HR self-service, or P12 scope.

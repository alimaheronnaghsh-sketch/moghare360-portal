# MOGHARE360 P11.7.1-A — Staff Home UX Polish Scope Gate

**Phase:** P11.7.1-A  
**Date:** 2026-06-26  
**Reference:** `docs/audit/MOGHARE360_P11_7_1_EXISTING_FEATURE_DISCOVERY_REPORT.md`

---

## 1. Included Findings from P11.7.1-0

| Finding | P11.7.1-A action |
|---------|------------------|
| Staff Home exposes raw PHP filenames on workbench cards | **Fix** — hide from user-facing body; `data-route` only |
| Raw `role_code` shown in identity card and card meta | **Fix** — Persian role labels |
| English KPI labels (`user_id`, `role_code`, `Permission`) | **Fix** — Persian labels |
| Permission preview on non-admin workbench (broken/confusing) | **Fix** — OWNER/SYSTEM_ADMIN only |
| Detail/action pages shown with filename disabled buttons | **Fix** — guided Persian notes, no filename buttons |
| HR self-service missing | **Backlog cards only** — no pages |
| Manager reference access fragmented | **Backlog card** for OWNER/SYSTEM_ADMIN — no engine |
| SERVICE_MANAGER workbench needs clearer Persian UX | **Fix** — labels, paths, hide filenames |

---

## 2. Excluded Findings and Why

| Finding | Excluded | Reason |
|---------|----------|--------|
| HR self-service module (profile, leave, password, documents) | **Not built** | P15 scope; requires Auth/permission design |
| Manager override / reference access engine | **Not built** | Requires audit model + permission design; impersonation forbidden |
| Impersonation / act-on-behalf | **Not built** | Security risk; no safe mechanism exists |
| Connect Phase 7 HR admin to Staff Home | **Not in this patch** | Navigation expansion beyond UX polish |
| Product home operational bridge for SERVICE_MANAGER | **Optional backlog only** | Would change workbench route matrix scope |
| DB schema for leave/overtime/documents | **Not changed** | Forbidden |
| Permission / role seed changes | **Not changed** | Forbidden |
| P12 scope | **Not touched** | Forbidden |

---

## 3. Why HR Self-Service Is Not Built Now

Phase 7 explicitly scoped HR as **internal admin only**. P11.7 scope gate assigned employee self-service to **P15**. Building it now would require new pages, Auth flows, permission scoping, and schema — all forbidden in P11.7.1-A. Backlog cards document the gap without pretending functionality exists.

---

## 4. Why Manager Override Is Not Built Now

Discovery confirmed no safe impersonation mechanism. Workflow overrides exist per-module but are not role-scoped reference access. Expanding manager override without audit design risks bypassing workflow gates. P11.7.1-A adds a **backlog card** documenting the need for a future controlled phase with audit — not silent acting as another user.

---

## 5. UX Issues Safe to Fix Now

- Persian role label map and identity card labels
- Hide PHP filenames from visible card body / disabled buttons
- Usage-path guidance text (تابلو / فهرست / پرونده)
- Remove permission preview from non-admin roles
- Improve Persian descriptions (remove visible `POST`, raw English)
- HR / manager reference **backlog cards** (disabled, no links)
- CSS for dev-only metadata (hidden route hints)

---

## 6. Raw Technical Strings Hidden from Normal Users

| Before (visible) | After |
|------------------|-------|
| `erp-technical-jobcard-detail.php` on card meta/button | Hidden — `data-route` attribute only |
| `نقش: SERVICE_MANAGER` on cards | `مسیر استفاده: …` (Persian guidance) |
| `role_code` KPI label + raw value | Label `نقش` + Persian role name |
| `user_id` label | `شناسه کاربری` |
| `تعداد Permission مؤثر` | `سطح دسترسی` |
| Disabled button text = filename | `راهنمای مسیر` or removed |

Raw `role_code` remains in session/DB unchanged. Developer filename available via HTML `data-route` on card element (not rendered as text).

---

## 7. Files to Modify

| File | Change |
|------|--------|
| `public_html/includes/m360-staff-home-helper.php` | Role labels, render polish, backlog cards, remove non-admin permission preview |
| `public_html/erp-staff-home.php` | Persian identity labels and role display |
| `public_html/assets/css/m360-staff-home.css` | Usage path / dev metadata styling |
| `docs/audit/MOGHARE360_P11_7_1_STAFF_HOME_UX_POLISH_SCOPE_REPORT.md` | This document |
| `docs/audit/MOGHARE360_P11_7_1_STAFF_HOME_UX_POLISH_REPORT.md` | Post-implementation report |
| `tools/test-p11-7-1-staff-home-ux-polish.php` | UX regression tests |
| `tools/test-p11-7-1-no-new-scope.php` | Scope security tests |

**Not modified:** Auth/Login, SQL, permissions, roles, P1–P7 handlers, OTP, bridge, P12.

---

## 8. Tests to Validate

```
php -l public_html/erp-staff-home.php
php -l public_html/includes/m360-staff-home-helper.php
php tools/test-p11-7-1-staff-home-ux-polish.php
php tools/test-p11-7-1-no-new-scope.php
php tools/test-p11-7-role-workbench-matrix.php
php tools/test-p11-7-broken-link-fixes.php
php tools/test-p11-7-staff-home-persian-encoding.php
php tools/test-p11-7-scope-security.php
php tools/test-p11-4-4-staff-home-authorization.php
php tools/test-v1-production-signoff.php
```

---

## Stop Condition Confirmation

No item in this scope requires DB schema, permission seed, role seed, Auth/Login, workflow state, HR self-service implementation, manager override engine, impersonation, or P12 scope. **Proceed with P11.7.1-A implementation.**

# MOGHARE360 P11.8-A — Manager Reference Navigation Bridge Scope Gate

**Phase:** P11.8-A  
**Date:** 2026-06-26  
**Reference:** `docs/audit/MOGHARE360_P11_8_0_MANAGER_REFERENCE_NAVIGATION_DISCOVERY_REPORT.md`

---

## 1. P11.8-0 Findings Implemented

| Finding | P11.8-A action |
|---------|----------------|
| Fragmented manager reference | **Implement** Staff Home group «مرجع عملیاتی One-Day Run» (OWNER/SYSTEM_ADMIN) |
| Product home omits some One-Day Run boards | **Include** online requests, contracts, payment tracking, parts, delivery in bridge |
| SERVICE_MANAGER lacks cross-unit reference | **Implement** group «مرجع هماهنگی سالن» |
| Route registry reusable | **Reuse** existing board/list URLs only |
| Permission preview admin diagnostic | **Include** in admin bridge diagnostic subsection |
| P8 read-only oversight | **Include** management dashboard + owner control in bridge |
| Timeline for audit diagnosis | **Info/guided** card only (JobCard ID required) |
| Impersonation absent | **Confirm** — backlog card only |

---

## 2. Findings Remaining Backlog

| Item | Reason |
|------|--------|
| Impersonation / act-as-staff | Forbidden — disabled backlog card |
| Manager override engine | Requires security design — backlog card |
| Permission expansion | Forbidden — backlog card |
| HR self-service | P15 — existing backlog cards |
| Role-based page guards | Post-V1 hardening |
| Unified manager audit event taxonomy | Optional future |
| `erp-purchase-request-list.php` | File missing — backlog in bridge |
| Settlement detail direct link | Requires record ID — info/guided only |

---

## 3. Existing Routes Reused

All targets are existing `public_html/*.php` board/list pages from P1–P7 and P8/P10 navigation — no new pages.

---

## 4. Board/List Safe Routes (Clickable)

| Route | Role bridge |
|-------|-------------|
| `erp-reception-online-requests.php` | Admin |
| `erp-reception-jobcards.php` | Admin, SM coordination |
| `erp-intake-contracts.php` | Admin |
| `erp-technical-board.php` | Admin (ref) |
| `erp-work-execution-board.php` | Admin (ref) |
| `erp-stock-board.php` | Admin |
| `erp-part-reserve.php` | Admin, SM coordination |
| `erp-jobcard-part-use.php` | Admin, SM coordination |
| `erp-purchase-request-create.php` | Admin |
| `erp-payment-tracking.php` | Admin, SM coordination |
| `erp-estimate-board.php` | Admin, SM coordination |
| `erp-final-invoice-board.php` | Admin, SM coordination |
| `erp-qc-board.php` | Admin |
| `erp-delivery-control.php` | Admin |
| `erp-jobcard-timeline.php` | **Not clickable** — info only |
| `erp-settlement-detail.php` | **Not clickable** — info only |
| `erp-access-permission-preview.php` | Admin diagnostic |
| `erp-route-map.php` | Admin diagnostic |
| `erp-owner-control-center.php` | Admin diagnostic |
| `erp-management-dashboard.php` | Admin diagnostic |

---

## 5. Unsafe Routes (Not Directly Linked)

| Route | Treatment |
|-------|-----------|
| All `*-action.php` POST endpoints | Note cards only (existing) — never in bridge |
| All `*-detail.php` requiring IDs | Info/guided only |
| `erp-access-management.php` | Admin «کار امروز» only — not in SM bridge |
| Customer/token pages | Excluded from bridge |
| Missing files | Backlog cards |

---

## 6. Why Impersonation Is Not Implemented

Owner policy and P11.8-0 security review: no safe impersonation mechanism exists. Manager must act with own session and `user_id` in existing audit tables. Impersonation would require Auth/session architecture change — **forbidden**.

---

## 7. Why No DB / Permission / Workflow / Auth Change

Bridge adds **navigation cards** to existing Staff Home helper using `m360_nav_file_exists()`. Page guards and workflow handlers unchanged. Session login unchanged. No new permissions required because existing session-level guards already allow navigation (discovery documented risk; no expansion in P11.8-A).

---

## 8. OWNER/SYSTEM_ADMIN vs SERVICE_MANAGER Bridge

| Aspect | OWNER / SYSTEM_ADMIN | SERVICE_MANAGER |
|--------|----------------------|-----------------|
| Group title | مرجع عملیاتی One-Day Run | مرجع هماهنگی سالن |
| Scope | Full P1–P7 reference + diagnostics | Cross-unit coordination reference only |
| Diagnostics | Permission preview, route map, P8 dashboards | **Excluded** |
| Access management | Separate «کار امروز» card | **Excluded** |
| Primary workbench | Admin tools + bridge | Technical/execution/QC unchanged |
| Finance links | Board links + settlement info | Reference-labeled boards only |

---

## 9. Stop Conditions

**Proceed.** No item in P11.8-A scope requires impersonation, act-as-staff, DB schema, permission/role seed, Auth/Login, workflow handler changes, direct action endpoints, or P12 scope.

**Stop if:** any implementation adds new permissions, impersonation, override engine, or clickable detail/action routes — **not planned**.

---

## Files to Modify

- `public_html/includes/m360-staff-home-helper.php`
- `public_html/erp-staff-home.php` (if needed — likely helper only)
- `public_html/assets/css/m360-staff-home.css`
- Tests and audit reports per P11.8-A spec

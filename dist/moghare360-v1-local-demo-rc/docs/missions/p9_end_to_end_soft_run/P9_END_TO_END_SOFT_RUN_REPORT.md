# P9 — End-to-End Soft Run / Demo Tracking

**MOGHARE360 V1** | Mission report

## 1. Purpose

P9 adds a non-destructive soft-run layer for owner demos and internal readiness checks. It tracks demo scenario progress across P1–P8 without mutating operational workflow tables (except optional checklist notes in `erp_soft_run_checklist`).

## 2. Schema discovery

| Item | Finding |
|------|---------|
| Soft run scenarios | `dbo.erp_soft_run_scenarios` — demo scenario metadata, readiness score |
| Soft run events | `dbo.erp_soft_run_events` — stage/event audit trail |
| Soft run checklist | `dbo.erp_soft_run_checklist` — operator checklist (only P9 write surface) |
| Demo marker | JobCards/customers prefixed `M360-DEMO` or scenario code `M360-DEMO-E2E-V1` |
| Operational tables | Read-only from P9 helpers; evidence via SELECT |

## 3. SQL migration

**File:** `database/migrations/P9_end_to_end_soft_run.sql`

Non-destructive `IF OBJECT_ID … CREATE TABLE` only. No `DROP`, `DELETE`, `TRUNCATE`, or operational `INSERT`/`UPDATE`.

## 4. Files added

**UI:** `erp-soft-run-control-center.php`, `erp-end-to-end-demo-scenario.php`, `erp-soft-run-checklist.php`, `erp-demo-flow-map.php`, `erp-demo-readiness-report.php`

**API (GET read-only):** `api/soft-run/readiness-summary.php`, `api/soft-run/demo-scenario-status.php`

**Helpers:** `m360-soft-run-helper.php`, `m360-demo-scenario-helper.php`, `m360-demo-readiness-helper.php`, `m360-e2e-validation-helper.php`

**Assets:** `assets/css/m360-soft-run.css`, `assets/js/m360-soft-run.js`, `assets/moghare360-ui/moghare360-soft-run-release.css`

**Tests:** 8 suites under `tools/test-p9-*.php`

**Docs:** `docs/demo/MOGHARE360_DEMO_SCENARIO_GUIDE.md`, `MOGHARE360_SOFT_RUN_OPERATOR_CHECKLIST.md`, `MOGHARE360_OWNER_DEMO_SCRIPT.md`

**Not changed:** auth core, `staff-login.php`, `owner-login.php`, `access-control.php`, `config.php`

## 5. Soft run control center

Persian RTL hub with readiness score, demo JobCard id, PASS/WARNING/BLOCKED counts, category matrix, and P1–P8 phase links.

## 6. End-to-end demo scenario

Maps 19 stages from online request through management dashboard. Each stage shows evidence table, gate result, audit result, and link to operational page.

## 7. E2E gate validation

`m360_e2e_validate_jobcard()` reads P1–P8 evidence using existing gate helpers (`assert_gates`) — no bypass, no mutation.

## 8. Readiness report

Score = PASS checklist items / total × 100. Recommendations: blocked, owner soft run, or internal demo ready.

## 9. P8 integration

Nav link to `erp-management-dashboard.php`. E2E `MANAGEMENT_DASHBOARD` stage checks P8 views and timeline.

## 10. Scope control

| Out of scope | Confirmed |
|--------------|-----------|
| Workflow mutation on erp_jobcards / estimates / invoices | None in P9 helpers |
| Payment gateway / bank / tax | None |
| Accounting voucher / ledger | None |
| Purchase / inventory write | None |
| Gate bypass | None |
| staff-login rewrite | None |

## 11. Tests passed

| Suite | Result |
|-------|--------|
| `test-p9-soft-run-schema.php` | 12/12 |
| `test-p9-soft-run-ui.php` | 34/34 |
| `test-p9-demo-scenario-flow.php` | 18/18 |
| `test-p9-e2e-gate-validation.php` | 20/20 |
| `test-p9-demo-data-safety.php` | 16/16 |
| `test-p9-management-dashboard-integration.php` | 18/18 |
| `test-p9-readiness-report.php` | 18/18 |
| `test-p9-scope-security.php` | 19/19 |

**Total P9 assertions:** 155/155 PASS

PHP `-l` passed on all P9 UI, API, and helper files.

## 12. Security

Staff session required (`m360_soft_run_require_staff`). Checklist POST uses CSRF. No credentials in repo UI. Migration and E2E layer are read-only toward operational data.

---

**MOGHARE360 P9 enables end-to-end soft-run visibility, demo scenario tracking, readiness scoring, and P8 management integration without operational workflow mutation, accounting, payment gateway, or security-bypass scope.**

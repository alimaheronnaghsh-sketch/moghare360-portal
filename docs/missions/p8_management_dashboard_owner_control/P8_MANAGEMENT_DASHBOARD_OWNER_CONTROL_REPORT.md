# P8 — Management Dashboard / Operational KPI / Owner Control

**MOGHARE360 V1** | Mission report

## 1. Schema discovery

| Item | Finding |
|------|---------|
| JobCard | `dbo.erp_jobcards` — PK `jobcard_id` |
| Status columns | `jobcard_status`, `technical_status`, `estimate_status`, `work_execution_status`, `qc_status`, `delivery_readiness_status`, `final_invoice_status`, `settlement_status`, `customer_delivery_status` (P2–P7) |
| Closed detection | `jobcard_status = CLOSED` and/or `jobcard_closed_at` |
| Delivery ready | `qc_status = DELIVERY_READY`, `delivery_readiness_status = READY`, or `jobcard_status = DELIVERY_READY` |
| Unpaid / settlement pending | `settlement_status` in PAYMENT_PENDING/PARTIAL + `remaining_amount` > 0; finalized invoice without SETTLED/MANAGER_RELEASE_APPROVED |
| Overdue | `age_hours` from `last_activity_at` / `updated_at` vs now; flags 24/48/72h for open JobCards |
| Rework | `qc_status` in QC_FAILED, REWORK_REQUIRED |
| Views | **Created** P8: `vw_m360_owner_jobcard_pipeline`, `vw_m360_owner_financial_control`, `vw_m360_owner_qc_control` |
| Timeline history | Aggregated from P1–P7 tables; missing tables skipped safely |

## 2. SQL migration

**Needed:** Yes — `database/migrations/P8_management_dashboard_owner_control.sql`

Non-destructive views + index only. PHP helpers fall back to direct `erp_jobcards` queries if views absent.

## 3. Files added

**UI:** `erp-management-dashboard.php`, `erp-owner-control-center.php`, `erp-operational-kpi.php`, `erp-jobcard-timeline.php`, `erp-bottleneck-monitor.php`, `erp-financial-control-summary.php`

**API (GET read-only):** `api/management/kpi-summary.php`, `bottleneck-summary.php`, `jobcard-timeline.php`

**Helpers:** `m360-management-kpi-helper.php`, `m360-owner-control-helper.php`, `m360-bottleneck-helper.php`, `m360-jobcard-timeline-helper.php`, `m360-financial-control-helper.php`

**Assets:** `assets/css/m360-management-dashboard.css`, `assets/js/m360-management-dashboard.js`

**Tests:** 8 suites under `tools/test-p8-*.php`

**Not changed:** auth core, `staff-login.php`, `owner-login.php`, `access-control.php`, `config.php`

## 4. Management dashboard

KPI cards with period filter (today / 7d / 30d / all). High-risk JobCard table. Links to owner control, bottleneck, timeline, financial summary. Persian RTL.

## 5. Owner control center

Read-only sections: high risk, overdue, unpaid, delivery-ready-unpaid, QC failed, manager release, status conflict, inactive. No approve/override/release/payment actions.

## 6. Operational KPI

Table with today / 7d / 30d values, Persian hints, OK/WARNING/CRITICAL (72h overdue, unpaid delivery-ready, released with balance, QC failed, status conflict).

## 7. JobCard timeline

`jobcard_id` input; merges history from online request, contract, jobcard, service, estimate, work, QC, delivery events. Missing tables do not fatal.

## 8. Bottleneck monitor

Stage counts, avg age, oldest JobCard per stage, critical stages, stuck list.

## 9. Financial control summary

Final invoice total, paid, remaining, settlement buckets, delivery-ready unpaid, released with balance, variance cases — read from P7 data only.

## 10. Read-only APIs

GET-only JSON; staff session required; no state mutation; no raw SQL/credential leakage.

## 11. Scope control

| Out of scope | Confirmed |
|--------------|-----------|
| Workflow mutation | None in P8 |
| approve/override/release/close/payment | None |
| Accounting voucher / ledger | None |
| Payment gateway / bank / tax | None |
| Purchase / inventory write | None |
| P1–P7 gate bypass | None |

## 12. Tests passed

| Suite | Result |
|-------|--------|
| `test-p8-management-schema.php` | 12/12 |
| `test-p8-management-dashboard.php` | 30/30 |
| `test-p8-kpi-calculation.php` | 15/15 |
| `test-p8-bottleneck-monitor.php` | 12/12 |
| `test-p8-jobcard-timeline.php` | 13/13 |
| `test-p8-financial-control-summary.php` | 21/21 |
| `test-p8-readonly-scope-control.php` | 18/18 |
| `test-p8-history-security.php` | 12/12 |

**Total P8 assertions:** 133/133 PASS

PHP `-l` passed on all P8 UI, API, and helper files.

`test-v1-production-signoff.php` — 23/23 PASS.

## 13. Security

No credentials, non-destructive SQL, auth unchanged, read-only management layer.

---

**MOGHARE360 P8 enables read-only management visibility, operational KPI control, bottleneck monitoring, JobCard timeline tracking, and owner financial control after the full P1–P7 workflow without adding workflow mutation, accounting, payment gateway, bank, tax, purchase, inventory write, or security-bypass scope.**

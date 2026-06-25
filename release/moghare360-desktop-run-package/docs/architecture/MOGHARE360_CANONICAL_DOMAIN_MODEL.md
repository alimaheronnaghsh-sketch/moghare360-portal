# MOGHARE360 — Canonical Domain Model

**Database:** MOGHARE360_ERP  
**Tables:** 96  
**Canonical domains:** 12  
**Status:** Planning baseline — Documentation only

---

## Purpose

This document defines the **canonical domain model** for MOGHARE360 ERP — the authoritative planning baseline for module contracts, validation, workflow, and future implementation in `app/modules/`.

Derived from Phase 02–05 database documentation and Phase 05 domain ownership map.

---

## Classification Authority Rule

- **Business function overrides table-name substring classification.**
- **Table-name heuristics are risk discovery only, not final ownership authority.**

Examples: `core_departments` → Identity (not Part); `erp_hr_employment_contracts` → HR (not Customer Contract).

---

## Canonical Flow (All Domains)

**UI → Validation Engine → Workflow Engine → Database → Audit Log**

No domain may bypass this flow for controlled writes.

---

## Domain 1 — Identity / Access / Security

| Attribute | Definition |
|-----------|------------|
| **Domain purpose** | Staff authentication context, RBAC, org structure, access change governance |
| **Data ownership** | Users, roles, permissions, departments, positions, staff profiles, access requests |
| **Write ownership** | Identity module / admin access service only |
| **Read-only consumers** | All modules (session, role, permission lookup) |
| **Validation responsibility** | Valid user refs, role keys, permission keys, access request completeness |
| **Workflow responsibility** | Access request: DRAFT → SUBMITTED → APPROVED → APPLIED |
| **Audit responsibility** | `core_audit_logs`, `core_access_change_history` |
| **Integration notes** | Maps to `app/security/`, `app/modules/` admin; **do not modify** production login files |
| **Forbidden direct writes** | Other domains writing to `core_users`, `core_roles`, `core_permissions` |

**Owned tables (15):** `core_access_*`, `core_departments`, `core_permissions`, `core_positions`, `core_role_permissions`, `core_roles`, `core_staff_profiles`, `core_user_roles`, `core_users`

**Readiness:** FOUNDATION_REFERENCE (311 rows seeded)

---

## Domain 2 — Audit / History

| Attribute | Definition |
|-----------|------------|
| **Domain purpose** | Immutable append-oriented change logs across ERP |
| **Data ownership** | All `*_history`, `core_audit_logs`, rule/commercial/report histories |
| **Write ownership** | Audit service only — triggered post Workflow Engine approval |
| **Read-only consumers** | Admin, reporting, compliance dashboards |
| **Validation responsibility** | Actor, action, entity ref, state transition payload |
| **Workflow responsibility** | None on history rows (append-only after parent workflow) |
| **Audit responsibility** | Self — history tables are audit artifacts |
| **Integration notes** | Cross-cutting; no business logic in history tables |
| **Forbidden direct writes** | UI or modules inserting history without workflow-approved parent write |

**Owned tables (20):** All `*_history` tables per Phase 05 ownership map

**Readiness:** SEED_OR_PROTOTYPE (42 rows, 9 empty)

---

## Domain 3 — Customer

| Attribute | Definition |
|-----------|------------|
| **Domain purpose** | Customer master, intake, phones, service contracts, satisfaction, scoring |
| **Data ownership** | `erp_customers`, intakes, phones, bindings/relations, service contracts |
| **Write ownership** | Customer module service |
| **Read-only consumers** | JobCard, CRM, Finance Preview, Reporting |
| **Validation responsibility** | National ID, mobile `09XXXXXXXXX`, Persian-only name |
| **Workflow responsibility** | Intake/contract state machine |
| **Audit responsibility** | `erp_customer_core_history`; binding changes via `erp_customer_vehicle_change_history` |
| **Integration notes** | Distinct from Vehicle master and HR employment contracts |
| **Forbidden direct writes** | JobCard, Inventory, Finance writing customer master |

**Owned tables (9):** Customer domain tables per ownership map

**Readiness:** SEED_OR_PROTOTYPE (3 rows, 6 empty)

---

## Domain 4 — Vehicle

| Attribute | Definition |
|-----------|------------|
| **Domain purpose** | Vehicle master, plate/VIN, photo records |
| **Data ownership** | `erp_vehicles`, `erp_vehicle_photo_records` |
| **Write ownership** | Vehicle module service |
| **Read-only consumers** | Customer (binding), JobCard, Operation |
| **Validation responsibility** | Iran plate, VIN ISO 3779, engine/chassis, brand/model/class |
| **Workflow responsibility** | Vehicle registration/bind approval |
| **Audit responsibility** | Vehicle change via customer_vehicle history when binding changes |
| **Integration notes** | **Camera direct only** for photos; **No upload bypass** |
| **Forbidden direct writes** | Customer module writing vehicle master without vehicle service |

**Owned tables (2):** `erp_vehicles`, `erp_vehicle_photo_records`

**Readiness:** SEED_OR_PROTOTYPE (1 row, 1 empty)

---

## Domain 5 — JobCard

| Attribute | Definition |
|-----------|------------|
| **Domain purpose** | Workshop job unit — cost header/lines, part usage on job |
| **Data ownership** | `erp_jobcards`, `erp_jobcard_part_usage`, `erp_jobcard_cost_*` |
| **Write ownership** | JobCard module service |
| **Read-only consumers** | Operation, Inventory, Finance Preview, Reporting |
| **Validation responsibility** | Valid customer/vehicle/contract refs, jobcard number |
| **Workflow responsibility** | DRAFT → … → CLOSED on jobcard entity |
| **Audit responsibility** | `erp_jobcard_change_history`, `erp_jobcard_part_usage_history` |
| **Integration notes** | Central hub for workshop execution; controls part usage when job active |
| **Forbidden direct writes** | Operation or Inventory writing `erp_jobcards` directly |

**Owned tables (4)**

**Readiness:** SEED_OR_PROTOTYPE (2 rows, 2 empty)

---

## Domain 6 — Operation / Service / QC / Delivery

| Attribute | Definition |
|-----------|------------|
| **Domain purpose** | Service execution, QC, delivery control, service approvals |
| **Data ownership** | `erp_service_operations`, `erp_operation_*`, `erp_qc_*`, `erp_delivery_*`, `erp_service_approval_requests` |
| **Write ownership** | Operation module service |
| **Read-only consumers** | JobCard, Reporting, CRM (post-delivery) |
| **Validation responsibility** | Service steps, QC pass/fail, delivery checklist |
| **Workflow responsibility** | Operation lifecycle tied to JobCard workflow |
| **Audit responsibility** | `erp_operation_history`, `erp_qc_check_history`, `erp_delivery_control_history`, `erp_service_operation_change_history` |
| **Integration notes** | Tablet/mobile mechanic and QC interfaces (future `app/frontend/`) |
| **Forbidden direct writes** | Inventory posting stock without workflow-approved usage |

**Owned tables (8)**

**Readiness:** STRUCTURAL_EMPTY / SEED (3 rows, 5 empty)

---

## Domain 7 — Inventory / Parts / Purchase

| Attribute | Definition |
|-----------|------------|
| **Domain purpose** | Parts catalog, stock, suppliers, reservations, purchase requests |
| **Data ownership** | `erp_parts`, `erp_stock_*`, `erp_inventory_*`, `erp_suppliers`, `erp_purchase_requests`, `erp_part_reservations` |
| **Write ownership** | Inventory module service |
| **Read-only consumers** | JobCard, Finance Preview (costing), Reporting |
| **Validation responsibility** | Stock rules, reservation conflicts, supplier refs |
| **Workflow responsibility** | Purchase request and reservation approval |
| **Audit responsibility** | `erp_inventory_purchase_history`, `erp_purchase_request_history` |
| **Integration notes** | Resolve `erp_stock_*` vs `erp_inventory_*` overlap before SQL (Phase 05) |
| **Forbidden direct writes** | JobCard decrementing stock without inventory service |

**Owned tables (11)**

**Readiness:** SEED_OR_PROTOTYPE (6 rows, 6 empty)

---

## Domain 8 — Finance Preview / Payment

| Attribute | Definition |
|-----------|------------|
| **Domain purpose** | Payment preview, invoice preview, labour rates, margins — **not official accounting** |
| **Data ownership** | `erp_payments`, `erp_payment_records`, `erp_invoice_previews`, `erp_finance_*`, `erp_financial_summary_snapshots` |
| **Write ownership** | Finance Preview module service |
| **Read-only consumers** | Reporting, JobCard (cost display) |
| **Validation responsibility** | Preview amounts, status enums; no tax invoice rules |
| **Workflow responsibility** | Payment preview approval; no statutory ledger posting |
| **Audit responsibility** | `erp_finance_history`, `erp_payment_history` |
| **Integration notes** | **No official accounting activation**; **No payment gateway** |
| **Forbidden direct writes** | Treating preview tables as general ledger |

**Owned tables (7)**

**Readiness:** PREVIEW_ONLY (4 rows, 3 empty)

---

## Domain 9 — CRM / Customer Experience

| Attribute | Definition |
|-----------|------------|
| **Domain purpose** | Follow-ups, upsell after service — not customer master |
| **Data ownership** | `erp_crm_followup_*`, `erp_upsell_opportunities` |
| **Write ownership** | CRM module service |
| **Read-only consumers** | Reporting, Customer (read customer ref) |
| **Validation responsibility** | Valid customer/jobcard refs, schedule dates |
| **Workflow responsibility** | Follow-up schedule lifecycle |
| **Audit responsibility** | `erp_crm_history` |
| **Integration notes** | Post-delivery engagement |
| **Forbidden direct writes** | CRM modifying `erp_customers` master |

**Owned tables (3)**

**Readiness:** STRUCTURAL_EMPTY (0 rows)

---

## Domain 10 — HR

| Attribute | Definition |
|-----------|------------|
| **Domain purpose** | Internal HR — employees, attendance, training, disciplinary, employment contracts |
| **Data ownership** | All `erp_hr_*` except history → `erp_hr_history` |
| **Write ownership** | HR module service |
| **Read-only consumers** | Identity (staff link), Reporting |
| **Validation responsibility** | Employee refs, contract dates |
| **Workflow responsibility** | HR record approval where required |
| **Audit responsibility** | `erp_hr_history` |
| **Integration notes** | `erp_hr_employment_contracts` is HR — not customer service contract |
| **Forbidden direct writes** | HR writing customer or jobcard tables |

**Owned tables (6)**

**Readiness:** STRUCTURAL_EMPTY (0 rows)

---

## Domain 11 — Reporting / Soft Run / Commercial

| Attribute | Definition |
|-----------|------------|
| **Domain purpose** | KPI snapshots, soft-run pilots, commercial/demo readiness — **not production SaaS** |
| **Data ownership** | `erp_business_kpi_*`, `erp_commercial_*`, `erp_soft_run_*`, `erp_license_preview_models` |
| **Write ownership** | Reporting / pilot services only |
| **Read-only consumers** | Admin dashboards, release readiness |
| **Validation responsibility** | Pilot scenario refs, readiness check definitions |
| **Workflow responsibility** | Soft-run pilot lifecycle (pilot scope only) |
| **Audit responsibility** | `erp_management_report_history`, `erp_commercial_release_history`, `erp_soft_run_pilot_history` |
| **Integration notes** | **No production SaaS activation** |
| **Forbidden direct writes** | Commercial tables driving live billing |

**Owned tables (9)**

**Readiness:** SOFT_RUN_READY (28 rows) / PREVIEW_ONLY

---

## Domain 12 — Rule / Workflow Decision

| Attribute | Definition |
|-----------|------------|
| **Domain purpose** | Business rule definitions and evaluation decisions |
| **Data ownership** | `erp_rule_definitions`, `erp_rule_decisions` |
| **Write ownership** | Workflow / rule engine service |
| **Read-only consumers** | Operation (approvals), Inventory (rule requests) |
| **Validation responsibility** | Rule syntax, entity context |
| **Workflow responsibility** | Rule evaluation gates service approvals |
| **Audit responsibility** | `erp_rule_audit_history` |
| **Integration notes** | Complements global Workflow Engine states |
| **Forbidden direct writes** | Modules bypassing rule engine for approval decisions |

**Owned tables (2)**

**Readiness:** SEED_OR_PROTOTYPE (6 rows, 1 empty)

---

## Domain Interaction Summary

```
Identity ──read──► All modules
Customer ──► Vehicle ──► JobCard ──► Operation ──► Inventory
                              └──► Finance Preview ──► CRM
HR, Rule, Reporting: supporting / parallel
Audit / History: append after all controlled writes
```

---

## Product Boundary

- Canonical domain model — planning baseline only
- **NOT_PRODUCTION_READY** for most business operation domains
- No official accounting, payment gateway, public portal, or SaaS activation

---

**END OF CANONICAL DOMAIN MODEL**

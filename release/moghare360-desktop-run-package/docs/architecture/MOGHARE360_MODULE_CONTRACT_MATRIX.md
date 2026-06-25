# MOGHARE360 — Module Contract Matrix

**Database:** MOGHARE360_ERP  
**Status:** Module contract planning — Documentation only  
**Maps to:** `app/modules/*` scaffold (inactive)

---

## Purpose

The **module contract matrix** binds each canonical domain to owned tables, gates, dependencies, and readiness. Future implementation must conform to these contracts.

---

## Readiness Levels

| Level | Meaning |
|-------|---------|
| **FOUNDATION_REFERENCE** | Seeded reference data; usable for RBAC/org |
| **SEED_OR_PROTOTYPE** | Demo/pilot rows; structure proven lightly |
| **STRUCTURAL_EMPTY** | Tables exist; 0 operational rows |
| **SOFT_RUN_READY** | Pilot/readiness infrastructure populated |
| **PREVIEW_ONLY** | Preview scope — not production accounting/billing |
| **NOT_PRODUCTION_READY** | Cannot claim production operation |

> **Most business operation domains are structurally present but still seed/demo/empty. Production readiness cannot be claimed from structure alone.**

---

## Contract Matrix

| Module / Domain | Owned Tables (count) | Allowed Inputs | Validation Gate | Workflow Gate | DB Write Owner | Audit Owner | Cross-Domain Dependencies | Forbidden Actions | Current Readiness |
|-----------------|------------------------|----------------|---------------|---------------|----------------|-------------|---------------------------|-------------------|-------------------|
| **Identity / Access / Security** | 15 `core_*` | Staff credentials (existing auth), role/permission keys, org data | Identity validation | Access request workflow | Identity service | Audit / History | None (foundation) | Rewrite auth files; direct UI DB write | **FOUNDATION_REFERENCE** |
| **Audit / History** | 20 `*_history`, `core_audit_logs` | Post-workflow audit payloads | Audit payload schema | Parent entity workflow complete | Audit service | Self | All domains (append) | Direct UI insert; skip workflow | **SEED_OR_PROTOTYPE** |
| **Customer** | 9 `erp_customer*` | National ID, mobile, name, intake, contract | National ID, mobile, Persian name | Intake/contract states | Customer module | Audit / History | Vehicle (bind ref) | Other modules writing customer master | **SEED_OR_PROTOTYPE** |
| **Vehicle** | 2 `erp_vehicle*` | Plate, VIN, engine, chassis, brand/model/class, camera capture | Plate, VIN, engine/chassis | Registration/bind | Vehicle module | Audit / History | Customer (bind) | Upload bypass for photos | **SEED_OR_PROTOTYPE** |
| **JobCard** | 4 `erp_jobcard*` | Customer, vehicle, contract refs, operations | Jobcard refs, cost rules | DRAFT→CLOSED | JobCard module | Audit / History | Customer, Vehicle, Operation, Inventory | Direct operation/inventory write to jobcard | **SEED_OR_PROTOTYPE** |
| **Operation / Service / QC / Delivery** | 8 `erp_operation*`, `erp_qc*`, `erp_delivery*`, `erp_service_*` | JobCard ref, service steps, QC, delivery | Step/QC validation | Tied to JobCard workflow | Operation module | Audit / History | JobCard, Vehicle | Inventory stock post without gate | **STRUCTURAL_EMPTY** / seed |
| **Inventory / Parts / Purchase** | 11 `erp_parts`, `erp_stock*`, `erp_inventory*`, `erp_suppliers`, `erp_purchase*` | Part refs, qty, supplier, PR | Stock/reservation rules | PR/reservation approval | Inventory module | Audit / History | JobCard, Supplier | Blind stock adjust from UI | **SEED_OR_PROTOTYPE** |
| **Finance Preview / Payment** | 7 `erp_finance*`, `erp_payment*`, `erp_invoice_previews` | JobCard cost, preview payment | Preview amount/status | Preview approval | Finance Preview module | Audit / History | JobCard | Official accounting; payment gateway | **PREVIEW_ONLY** |
| **CRM / Customer Experience** | 3 `erp_crm*`, `erp_upsell*` | Customer/jobcard ref, follow-up | Schedule/record validation | Follow-up lifecycle | CRM module | Audit / History | Customer, JobCard | Modify customer master | **STRUCTURAL_EMPTY** |
| **HR** | 6 `erp_hr_*` | Employee, contract, attendance | HR field validation | HR approval where required | HR module | Audit / History | Identity (staff link) | Confuse with customer contracts | **STRUCTURAL_EMPTY** |
| **Reporting / Soft Run / Commercial** | 9 `erp_*kpi*`, `erp_commercial*`, `erp_soft_run*`, `erp_license*` | Pilot scenarios, readiness | Pilot/readiness schema | Pilot lifecycle | Reporting module | Audit / History | Read all domains | Production SaaS billing | **SOFT_RUN_READY** / PREVIEW |
| **Rule / Workflow Decision** | 2 `erp_rule_*` | Rule defs, entity context | Rule syntax | Evaluation gate | Rule/Workflow service | Audit / History | Operation, Inventory | Bypass approval workflow | **SEED_OR_PROTOTYPE** |

---

## Module-to-Scaffold Mapping

| `app/modules/` | Canonical domain |
|----------------|------------------|
| `customer/` | Customer |
| `vehicle/` | Vehicle |
| `contract/` | Customer (service contracts) |
| `jobcard/` | JobCard |
| `inventory/` | Inventory / Parts / Purchase |
| `crm/` | CRM / Customer Experience |
| `finance_preview/` | Finance Preview / Payment |
| `hr/` | HR |
| `reporting/` | Reporting / Soft Run / Commercial |
| `audit/` | Audit / History (read/query) |

Cross-cutting: `app/security/`, `app/validation/`, `app/workflow/`, `app/api/`

---

## Contract Enforcement (Future)

1. Module service is sole write owner for owned tables
2. All writes pass Validation + Workflow gates
3. Audit append mandatory
4. Cross-domain via declared dependencies only

---

## Product Boundary

- Module contract matrix — planning only
- **NOT_PRODUCTION_READY** overall for workshop operations at volume

---

**END OF MODULE CONTRACT MATRIX**

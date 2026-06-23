# MOGHARE360 — Domain Validation Responsibility Matrix

**Database:** MOGHARE360_ERP  
**Status:** Locked planning baseline — Documentation only

---

## Classification Principles

- **Business function overrides table-name heuristic** — not substring ownership
- **No module may validate and write outside its owned domain without workflow approval**
- **Cross-domain validation must be explicit** — documented in module contract
- **Empty table does not mean useless** — validation rules apply when writes occur
- **Isolated table does not mean wrong** — leaf tables still require gates

---

## Responsibility Matrix

| Canonical Domain | Primary Validation Owner | Write Owner | Read Consumers | Required Validation Gate | Required Workflow Gate | Required Audit Event | Forbidden Bypass | Current Readiness |
|------------------|--------------------------|-------------|----------------|--------------------------|------------------------|----------------------|------------------|-------------------|
| Identity / Access / Security | Identity module | Identity service | All modules | User/role/permission format | Access request workflow | `core_audit_logs`, access history | Direct UI→`core_users` | FOUNDATION_REFERENCE |
| Audit / History | Audit service | Audit service | Admin, Reporting | Audit payload schema | Parent write complete | Self (append) | Skip audit on controlled write | SEED_OR_PROTOTYPE |
| Customer | Customer module | Customer service | JobCard, CRM, Finance | National ID, mobile, Persian name | Intake/contract states | `erp_customer_core_history` | Other modules writing customer master | SEED_OR_PROTOTYPE |
| Vehicle | Vehicle module | Vehicle service | Customer, JobCard, Operation | Plate, VIN, engine/chassis, media | Registration/bind | Vehicle/binding history | Upload bypass | SEED_OR_PROTOTYPE |
| JobCard | JobCard module | JobCard service | Operation, Inventory, Finance | Refs, jobcard number | DRAFT→CLOSED | Jobcard history | Operation writing jobcard master | SEED_OR_PROTOTYPE |
| Operation / Service / QC / Delivery | Operation module | Operation service | JobCard, CRM | Steps, QC, delivery | Tied to JobCard | Operation/QC/delivery history | QC skip | STRUCTURAL_EMPTY |
| Inventory / Parts / Purchase | Inventory module | Inventory service | JobCard, Finance | Stock, reservation, PR | PR/reservation approval | Inventory/purchase history | Stock adjust from UI | SEED_OR_PROTOTYPE |
| Finance Preview / Payment | Finance Preview module | Finance service | Reporting, JobCard | Preview amount/status | Preview approval | Finance/payment history | Official accounting path | PREVIEW_ONLY |
| CRM / Customer Experience | CRM module | CRM service | Reporting | Customer/jobcard ref | Follow-up lifecycle | `erp_crm_history` | CRM editing customer master | STRUCTURAL_EMPTY |
| HR | HR module | HR service | Identity, Reporting | Employee, contract dates | HR approval | `erp_hr_history` | HR vs customer contract confusion | STRUCTURAL_EMPTY |
| Reporting / Soft Run / Commercial | Reporting module | Reporting service | Admin | Pilot/readiness schema | Pilot lifecycle | Report/pilot history | Production SaaS billing | SOFT_RUN_READY |
| Rule / Workflow Decision | Rule/Workflow module | Rule service | Operation, Inventory | Rule syntax, context | Evaluation gate | `erp_rule_audit_history` | Approval bypass | SEED_OR_PROTOTYPE |

---

## Cross-Domain Validation (Explicit)

| Interaction | Validating module | Owning module | Gate |
|-------------|-------------------|---------------|------|
| Customer bind vehicle | Customer + Vehicle | Customer (bind), Vehicle (master) | Joint validation + workflow |
| JobCard create | JobCard | JobCard | Validates customer/vehicle refs |
| Part usage on job | Inventory | Inventory | Validates jobcard state + reservation |
| Payment preview | Finance Preview | Finance Preview | Validates jobcard CLOSED/APPLIED policy |
| Service approval | Rule + Operation | Operation | Rule decision before APPROVED |

---

## Flow Enforcement

**UI → Validation Engine → Workflow Engine → Database → Audit Log**

No domain may skip Validation Engine for its writes or cross-domain effects.

---

## Product Boundary

- No permission model modification
- No runtime implementation

---

**END OF DOMAIN VALIDATION RESPONSIBILITY MATRIX**

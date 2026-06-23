# MOGHARE360 — Read-Only Page Specifications

**Status:** All pages **PLANNED_NOT_IMPLEMENTED** — Phase 09 planning only  
**Local base:** http://localhost:8080/moghare360/

---

## Global Page Rules (All 8)

| Rule | Requirement |
|------|-------------|
| Read-only | No INSERT/UPDATE/DELETE |
| No forms that submit data | Display only |
| Required guard | Session + permission (see guard plan) |
| Flow banner | UI → Validation Engine → Workflow Engine → Database → Audit Log |
| Implementation status | **PLANNED_NOT_IMPLEMENTED** |

---

## 1. erp-readonly-architecture-overview.php

| Attribute | Specification |
|-----------|---------------|
| **Page purpose** | Admin structure overview: 12 canonical domains, architecture flow, product boundaries |
| **Primary source** | `MOGHARE360_CANONICAL_DOMAIN_MODEL.md` |
| **Secondary sources** | Master execution prompt, `MOGHARE360_MODULE_BOUNDARY_RULES.md` |
| **Display sections** | Domain grid (12 cards); flow diagram; boundary warnings; media rules |
| **Required guard** | Session + platform owner or `report.read` |
| **Read-only rule** | Static/hybrid render only |
| **Forbidden actions** | Write buttons; portal links; config exposure |
| **Test requirement** | No POST; auth deny without session |
| **Expected local route** | http://localhost:8080/moghare360/erp-readonly-architecture-overview.php |
| **Implementation status** | **PLANNED_NOT_IMPLEMENTED** |

---

## 2. erp-readonly-domain-map.php

| Attribute | Specification |
|-----------|---------------|
| **Page purpose** | Table→domain ownership map with ambiguous flags |
| **Primary source** | `MOGHARE360_DOMAIN_OWNERSHIP_MAP.md` |
| **Secondary sources** | `MOGHARE360_AMBIGUOUS_TABLE_OWNERSHIP_REVIEW.md`, domain table map |
| **Display sections** | 96-table matrix; 12 domain groups; heuristic warnings |
| **Required guard** | Session + admin read |
| **Read-only rule** | No ownership edit |
| **Forbidden actions** | DROP hints; reassignment UI |
| **Test requirement** | 96 tables; `core_departments` / `erp_hr_employment_contracts` notes |
| **Expected local route** | http://localhost:8080/moghare360/erp-readonly-domain-map.php |
| **Implementation status** | **PLANNED_NOT_IMPLEMENTED** |

---

## 3. erp-readonly-validation-matrix.php

| Attribute | Specification |
|-----------|---------------|
| **Page purpose** | Per-domain validation rules (National ID, mobile, VIN, plate, media) |
| **Primary source** | `MOGHARE360_VALIDATION_RULE_MATRIX.md` |
| **Secondary sources** | Domain validation responsibility matrix, error policy |
| **Display sections** | 12 validation groups; explicit rule checklist; media rules |
| **Required guard** | Session + permission |
| **Read-only rule** | No rule edit |
| **Forbidden actions** | Validation bypass toggle |
| **Test requirement** | National ID, mobile, VIN, plate visible; **Camera direct only**; **No upload bypass** |
| **Expected local route** | http://localhost:8080/moghare360/erp-readonly-validation-matrix.php |
| **Implementation status** | **PLANNED_NOT_IMPLEMENTED** |

---

## 4. erp-readonly-workflow-contract.php

| Attribute | Specification |
|-----------|---------------|
| **Page purpose** | Allowed and forbidden workflow transitions |
| **Primary source** | `MOGHARE360_WORKFLOW_STATE_TRANSITION_CONTRACT.md` |
| **Secondary sources** | Workflow simulation backlog |
| **Display sections** | Allowed matrix; forbidden list; state definitions |
| **Required guard** | Session + permission |
| **Read-only rule** | No transition buttons |
| **Forbidden actions** | Approve/apply/close buttons |
| **Test requirement** | DRAFT→SUBMITTED, APPROVED→APPLIED, APPLIED→CLOSED; DRAFT→APPLIED forbidden |
| **Expected local route** | http://localhost:8080/moghare360/erp-readonly-workflow-contract.php |
| **Implementation status** | **PLANNED_NOT_IMPLEMENTED** |

---

## 5. erp-readonly-permission-gates.php

| Attribute | Specification |
|-----------|---------------|
| **Page purpose** | Permission workflow gate matrix (conceptual) |
| **Primary source** | `MOGHARE360_PERMISSION_WORKFLOW_GATE_MATRIX.md` |
| **Secondary sources** | Optional read `core_permissions` (SELECT) |
| **Display sections** | Submit/review/approve/apply/close/cancel gates |
| **Required guard** | Session + permission |
| **Read-only rule** | No permission model change |
| **Forbidden actions** | New role/permission creation |
| **Test requirement** | Gate table complete; no auth file modification |
| **Expected local route** | http://localhost:8080/moghare360/erp-readonly-permission-gates.php |
| **Implementation status** | **PLANNED_NOT_IMPLEMENTED** |

---

## 6. erp-readonly-audit-contract.php

| Attribute | Specification |
|-----------|---------------|
| **Page purpose** | Audit event fields and required actions |
| **Primary source** | `MOGHARE360_WORKFLOW_AUDIT_EVENT_CONTRACT.md` |
| **Secondary sources** | Audit preview backlog; optional `core_audit_logs` SELECT sample |
| **Display sections** | Field list; audit-required actions; preview event types |
| **Required guard** | Session + audit read |
| **Read-only rule** | No audit delete |
| **Forbidden actions** | Skip-audit toggle |
| **Test requirement** | actor_user_id, old_state, new_state fields listed |
| **Expected local route** | http://localhost:8080/moghare360/erp-readonly-audit-contract.php |
| **Implementation status** | **PLANNED_NOT_IMPLEMENTED** |

---

## 7. erp-readonly-module-readiness.php

| Attribute | Specification |
|-----------|---------------|
| **Page purpose** | Module readiness levels per domain |
| **Primary source** | `MOGHARE360_MODULE_CONTRACT_MATRIX.md` |
| **Secondary sources** | Domain ownership summary, module sequence |
| **Display sections** | Readiness badges; row counts; NOT_PRODUCTION_READY banner |
| **Required guard** | Session + permission |
| **Read-only rule** | No readiness override |
| **Forbidden actions** | "Production ready" claim; SaaS activation |
| **Test requirement** | CRM/HR STRUCTURAL_EMPTY shown |
| **Expected local route** | http://localhost:8080/moghare360/erp-readonly-module-readiness.php |
| **Implementation status** | **PLANNED_NOT_IMPLEMENTED** |

---

## 8. erp-readonly-database-risk-board.php

| Attribute | Specification |
|-----------|---------------|
| **Page purpose** | Database posture: PK/FK, empty tables, dual IDs, overlap |
| **Primary source** | Structure health + gap analysis summaries |
| **Secondary sources** | SQL change candidates, ID alignment plan |
| **Display sections** | Risk panels; metrics (46 empty, 10 dual IDs, 33 cross-domain FK); decision banners |
| **Required guard** | Session + admin |
| **Read-only rule** | No SQL execution UI |
| **Forbidden actions** | Schema change; seed buttons |
| **Test requirement** | Metrics match Phase 03–05 docs |
| **Expected local route** | http://localhost:8080/moghare360/erp-readonly-database-risk-board.php |
| **Implementation status** | **PLANNED_NOT_IMPLEMENTED** |

---

**END OF READ-ONLY PAGE SPECIFICATIONS**

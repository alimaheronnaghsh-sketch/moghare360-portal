# MOGHARE360 — Critical Forms v2 Lock Plan

**Status:** PLANNED_NOT_IMPLEMENTED (all forms)  
**SQL:** No SQL required  
**Phase:** PHASE 17 — lock plan only; **no existing form modification**

---

## Purpose

Define **Forms v2** field control, validator dependencies, workflow gates, and audit requirements for ten critical operational forms before go-live.

**Flow:** UI → Validation Engine → Workflow Engine → Database → Audit Log

---

## 1. Customer Intake Form

| Category | Fields |
|----------|--------|
| **Required fields** | Persian name, national ID, mobile, customer channel, customer class |
| **Controlled fields** | Channel dropdown, class dropdown |
| **Free text fields** | Notes, reception remarks |
| **Validator dependency** | Persian name, National ID, Mobile |
| **Workflow dependency** | Intake DRAFT → SUBMITTED → APPLIED |
| **Audit dependency** | `erp_customer_core_history` |
| **Status** | PLANNED_NOT_IMPLEMENTED |

---

## 2. Vehicle Registration Form

| Category | Fields |
|----------|--------|
| **Required fields** | Plate (segmented), brand, model, vehicle class; VIN or plate per policy |
| **Controlled fields** | Brand dropdown, model cascade, class dropdown, plate segments, letter selector |
| **Free text fields** | Vehicle notes, color note (if not dropdown) |
| **Validator dependency** | Iranian plate, VIN, engine/chassis (optional) |
| **Workflow dependency** | Registration before customer bind |
| **Audit dependency** | Vehicle/binding history tables |
| **Status** | PLANNED_NOT_IMPLEMENTED |

---

## 3. JobCard Create Form

| Category | Fields |
|----------|--------|
| **Required fields** | Customer ref, vehicle ref, jobcard type, service category |
| **Controlled fields** | Customer search/select, vehicle cascade, type dropdown, service category dropdown |
| **Free text fields** | Customer complaint, reception notes |
| **Validator dependency** | FK refs valid; dropdown enums; customer/vehicle validators on linked masters |
| **Workflow dependency** | DRAFT → SUBMITTED → APPROVED → APPLIED |
| **Audit dependency** | `erp_jobcard_change_history` |
| **Status** | PLANNED_NOT_IMPLEMENTED |

---

## 4. Service Operation Form

| Category | Fields |
|----------|--------|
| **Required fields** | JobCard ref, operation type, assigned technician, step status |
| **Controlled fields** | Operation type dropdown, step status enum, technician select |
| **Free text fields** | Technician notes, finding description |
| **Validator dependency** | JobCard state must be APPROVED/APPLIED |
| **Workflow dependency** | Operation tied to JobCard lifecycle |
| **Audit dependency** | `erp_operation_history` |
| **Status** | PLANNED_NOT_IMPLEMENTED |

---

## 5. Inventory / Part Reservation Form

| Category | Fields |
|----------|--------|
| **Required fields** | JobCard ref, part ref, quantity, location |
| **Controlled fields** | Part search/select, part category dropdown, location dropdown |
| **Free text fields** | Reservation note |
| **Validator dependency** | Stock availability; quantity > 0; part category valid |
| **Workflow dependency** | Reservation approval per inventory policy |
| **Audit dependency** | `erp_jobcard_part_usage_history`, inventory history |
| **Status** | PLANNED_NOT_IMPLEMENTED |

---

## 6. Purchase Request Form

| Category | Fields |
|----------|--------|
| **Required fields** | Part ref or description ref, quantity, supplier, PR reason |
| **Controlled fields** | Part category dropdown, supplier dropdown, PR status |
| **Free text fields** | Justification note |
| **Validator dependency** | Quantity, supplier ref, category |
| **Workflow dependency** | PR DRAFT → SUBMITTED → APPROVED |
| **Audit dependency** | Purchase history tables |
| **Status** | PLANNED_NOT_IMPLEMENTED |

---

## 7. QC Check Form

| Category | Fields |
|----------|--------|
| **Required fields** | JobCard ref, QC checklist items, pass/fail result |
| **Controlled fields** | QC result dropdown, checklist enums |
| **Free text fields** | QC remark |
| **Validator dependency** | Operation complete; media camera rules (Phase 18) |
| **Workflow dependency** | QC gate before delivery |
| **Audit dependency** | `erp_qc_check_history` |
| **Status** | PLANNED_NOT_IMPLEMENTED |

---

## 8. Delivery Control Form

| Category | Fields |
|----------|--------|
| **Required fields** | JobCard ref, delivery checklist, handover confirmation |
| **Controlled fields** | Checklist enums, delivery status dropdown |
| **Free text fields** | Handover notes |
| **Validator dependency** | QC passed; JobCard state eligible for delivery |
| **Workflow dependency** | Delivery → CLOSED path |
| **Audit dependency** | `erp_delivery_control_history` |
| **Status** | PLANNED_NOT_IMPLEMENTED |

---

## 9. CRM Follow-up Form

| Category | Fields |
|----------|--------|
| **Required fields** | Customer ref, jobcard ref (optional policy), follow-up type, next action date |
| **Controlled fields** | Follow-up type dropdown, status dropdown |
| **Free text fields** | Follow-up note, call summary |
| **Validator dependency** | Customer/jobcard refs; no CRM write to customer master |
| **Workflow dependency** | CRM follow-up lifecycle |
| **Audit dependency** | `erp_crm_history` |
| **Status** | PLANNED_NOT_IMPLEMENTED |

---

## 10. HR Employee Form

| Category | Fields |
|----------|--------|
| **Required fields** | Persian name, national ID, mobile, department, position, contract type |
| **Controlled fields** | Department dropdown, position dropdown, contract type dropdown |
| **Free text fields** | HR notes |
| **Validator dependency** | Persian name, National ID, Mobile |
| **Workflow dependency** | HR approval workflow |
| **Audit dependency** | `erp_hr_history` |
| **Status** | PLANNED_NOT_IMPLEMENTED |

---

## Cross-Form Rules (All Forms)

| Rule | Requirement |
|------|-------------|
| No direct UI→DB | All submits through Validation Engine |
| No validation bypass | Server-side re-validation mandatory |
| Permission gate | Per `MOGHARE360_PERMISSION_WORKFLOW_GATE_MATRIX.md` |
| Dropdown-first | Per dropdown/cascading select rules |
| Forms v2 | Replace legacy free-text sensitive fields — **no modification in Phase 17** |

---

## Implementation Status

**All forms: PLANNED_NOT_IMPLEMENTED**

---

**END OF CRITICAL FORMS V2 LOCK PLAN**

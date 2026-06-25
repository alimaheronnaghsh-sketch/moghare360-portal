# MOGHARE360 — Validation Rule Matrix

**Database:** MOGHARE360_ERP  
**Status:** Locked planning baseline — Documentation only  
**Flow:** UI → Validation Engine → Workflow Engine → Database → Audit Log

---

## Purpose

The **validation rule matrix** defines required validation per canonical domain before Workflow Engine and database write. Bypassing any rule is forbidden.

---

## Global Validation Rules (All Domains)

| Rule ID | Rule | Applies |
|---------|------|---------|
| G-01 | **Permission guard validation** | Every mutating action |
| G-02 | **State transition validation** | Workflow state changes |
| G-03 | **Required field validation** | All create/update |
| G-04 | **Status value validation** | Enum/dropdown fields |
| G-05 | Session + auth context present | All writes |
| G-06 | CSRF token valid (write routes) | POST mutations |
| G-07 | No direct UI→DB write | All domains |

---

## 1. Identity / Access / Security Validation

| Attribute | Rule |
|-----------|------|
| **Required fields** | `user_id`, role keys, permission keys, department/position refs where applicable |
| **Format rules** | Valid username format; permission key pattern; no empty role code |
| **Ownership rules** | Only Identity module writes `core_*` tables |
| **Permission rules** | `access.admin`, `access.request`, `access.approve` concepts |
| **Workflow-state rules** | Access request: DRAFT→SUBMITTED→UNDER_REVIEW→APPROVED→APPLIED |
| **Duplicate prevention** | Unique username; unique permission key; no duplicate role-permission pair |
| **Business risk if bypassed** | Unauthorized staff access; privilege escalation |
| **Audit requirement** | `core_audit_logs`, `core_access_change_history` on every access change |

---

## 2. Customer Validation

| Attribute | Rule |
|-----------|------|
| **Required fields** | Name, national ID or business ref, mobile, intake channel |
| **Format rules** | **National ID validation** (Iran 10-digit algorithm); **Mobile validation** (`09XXXXXXXXX`); Persian-only name |
| **Ownership rules** | Customer module owns `erp_customers`, intakes, phones, service contracts |
| **Permission rules** | `customer.create`, `customer.update`, `customer.submit` |
| **Workflow-state rules** | Intake/contract cannot skip to APPLIED from DRAFT |
| **Duplicate prevention** | Unique national ID; unique mobile per policy |
| **Business risk if bypassed** | Wrong customer identity; duplicate customers; compliance failure |
| **Audit requirement** | `erp_customer_core_history` on master change |

---

## 3. Vehicle Validation

| Attribute | Rule |
|-----------|------|
| **Required fields** | Plate or VIN, brand, model, class |
| **Format rules** | **Plate validation** (Iran standard); **VIN validation** (ISO 3779); engine/chassis format |
| **Ownership rules** | Vehicle module owns `erp_vehicles`, `erp_vehicle_photo_records` |
| **Permission rules** | `vehicle.create`, `vehicle.bind`, `vehicle.photo` |
| **Workflow-state rules** | Registration before bind to customer job path |
| **Duplicate prevention** | Unique plate; unique VIN |
| **Business risk if bypassed** | Wrong vehicle on job; insurance/warranty disputes |
| **Audit requirement** | Binding changes via `erp_customer_vehicle_change_history` |

### Media / Photo Validation (Vehicle)

| Rule | Requirement |
|------|-------------|
| **Media/photo validation** | Max 6 input images; metadata from camera capture |
| **Camera direct only** | No file-picker upload path |
| **No upload bypass** | Reject alternate ingest channels |

---

## 4. JobCard Validation

| Attribute | Rule |
|-----------|------|
| **Required fields** | Customer ref, vehicle ref, contract ref (where required), jobcard type |
| **Format rules** | Valid FK refs; jobcard number format |
| **Ownership rules** | JobCard module owns `erp_jobcards`, cost headers/lines, part usage |
| **Permission rules** | `jobcard.create`, `jobcard.submit`, `jobcard.approve` |
| **Workflow-state rules** | Full state machine; no DRAFT→APPLIED |
| **Duplicate prevention** | Unique jobcard number per period |
| **Business risk if bypassed** | Orphan jobs; uncosted work; wrong workshop queue |
| **Audit requirement** | `erp_jobcard_change_history`, `erp_jobcard_part_usage_history` |

---

## 5. Operation / Service / QC / Delivery Validation

| Attribute | Rule |
|-----------|------|
| **Required fields** | JobCard ref, operation type, service steps |
| **Format rules** | QC pass/fail enum; delivery checklist completeness |
| **Ownership rules** | Operation module owns service/QC/delivery tables |
| **Permission rules** | `operation.execute`, `qc.decide`, `delivery.control` |
| **Workflow-state rules** | Operations require APPROVED/APPLIED JobCard |
| **Duplicate prevention** | One active operation case per step policy |
| **Business risk if bypassed** | QC bypass; delivery without inspection |
| **Audit requirement** | `erp_operation_history`, `erp_qc_check_history`, `erp_delivery_control_history` |

### Media (QC / Delivery)

- **Camera direct only** · **No upload bypass** for QC photos

---

## 6. Inventory / Parts / Purchase Validation

| Attribute | Rule |
|-----------|------|
| **Required fields** | Part ref, quantity, location, supplier (PR) |
| **Format rules** | Positive quantity; valid stock location; supplier active |
| **Ownership rules** | Inventory module owns parts, stock, purchase tables |
| **Permission rules** | `inventory.reserve`, `inventory.move`, `purchase.request` |
| **Workflow-state rules** | Reservation requires approved JobCard; PR approval workflow |
| **Duplicate prevention** | No double-reserve same part for same job |
| **Business risk if bypassed** | Negative stock; wrong parts on job |
| **Audit requirement** | `erp_inventory_purchase_history`, `erp_purchase_request_history` |

---

## 7. Finance Preview / Payment Validation

| Attribute | Rule |
|-----------|------|
| **Required fields** | JobCard ref, preview amount, payment type (preview) |
| **Format rules** | Non-negative amounts; preview status enum only |
| **Ownership rules** | Finance Preview module owns payment/invoice preview tables |
| **Permission rules** | `finance.preview`, `payment.preview` — **not** official accounting |
| **Workflow-state rules** | Preview after QC/delivery gate; no statutory invoice |
| **Duplicate prevention** | No duplicate preview payment for same job state |
| **Business risk if bypassed** | Incorrect customer charges; mistaken as official ledger |
| **Audit requirement** | `erp_finance_history`, `erp_payment_history` |

> **No official accounting activation** · **No payment gateway**

---

## 8. CRM / Customer Experience Validation

| Attribute | Rule |
|-----------|------|
| **Required fields** | Customer ref, follow-up type, schedule date |
| **Format rules** | Valid customer/jobcard ref; future schedule date |
| **Ownership rules** | CRM module owns follow-up and upsell tables |
| **Permission rules** | `crm.followup`, `crm.upsell` |
| **Workflow-state rules** | Follow-up after CLOSED/APPLIED JobCard per policy |
| **Duplicate prevention** | No duplicate open follow-up same type |
| **Business risk if bypassed** | Customer contact errors; missed satisfaction loop |
| **Audit requirement** | `erp_crm_history` |

---

## 9. HR Validation

| Attribute | Rule |
|-----------|------|
| **Required fields** | Employee ref, record type, effective dates |
| **Format rules** | Valid date ranges; employment contract dates |
| **Ownership rules** | HR module owns `erp_hr_*` (not customer contracts) |
| **Permission rules** | `hr.employee`, `hr.attendance`, `hr.contract` |
| **Workflow-state rules** | HR record approval where configured |
| **Duplicate prevention** | Unique employee code |
| **Business risk if bypassed** | Wrong employment records |
| **Audit requirement** | `erp_hr_history` |

---

## 10. Reporting / Soft Run / Commercial Validation

| Attribute | Rule |
|-----------|------|
| **Required fields** | Pilot scenario ref, readiness check type |
| **Format rules** | Valid scenario/check enum; no production billing fields |
| **Ownership rules** | Reporting module owns KPI, soft-run, commercial tables |
| **Permission rules** | `report.read`, `pilot.run`, `commercial.readiness` |
| **Workflow-state rules** | Pilot lifecycle only — **no production SaaS** |
| **Duplicate prevention** | Idempotent readiness check runs |
| **Business risk if bypassed** | False production readiness claims |
| **Audit requirement** | `erp_management_report_history`, `erp_soft_run_pilot_history` |

---

## 11. Rule / Workflow Decision Validation

| Attribute | Rule |
|-----------|------|
| **Required fields** | Rule definition key, entity context, decision outcome |
| **Format rules** | Valid rule syntax; entity type enum |
| **Ownership rules** | Rule module owns definitions and decisions |
| **Permission rules** | `rule.evaluate`, `rule.admin` |
| **Workflow-state rules** | Decisions gate APPROVED transitions |
| **Duplicate prevention** | Versioned rule definitions |
| **Business risk if bypassed** | Approval bypass; policy violations |
| **Audit requirement** | `erp_rule_audit_history` |

---

## 12. Audit / History Validation

| Attribute | Rule |
|-----------|------|
| **Required fields** | Actor, action, entity ref, timestamp |
| **Format rules** | Immutable append; valid entity_type/id |
| **Ownership rules** | Audit service writes history; modules request append |
| **Permission rules** | System/service identity for append; read gated |
| **Workflow-state rules** | Append only after parent workflow approved write |
| **Duplicate prevention** | N/A (append-only) |
| **Business risk if bypassed** | No compliance trail; forensic gap |
| **Audit requirement** | Self — audit rows are the audit artifact |

---

## Explicit Rule Checklist (Cross-Domain)

| Rule | Documented in |
|------|---------------|
| **National ID validation** | Customer |
| **Mobile validation** | Customer |
| **VIN validation** | Vehicle |
| **Plate validation** | Vehicle |
| **Required field validation** | All groups (G-03) |
| **Status value validation** | All groups (G-04) |
| **State transition validation** | All groups (G-02) |
| **Permission guard validation** | All groups (G-01) |
| **Media/photo validation** | Vehicle, Operation |
| **Camera direct only** | Vehicle, Operation |
| **No upload bypass** | Vehicle, Operation |

---

## Product Boundary

- Validation rule matrix — planning only
- No SQL execution; **Do not create SQL yet**

---

**END OF VALIDATION RULE MATRIX**

# MOGHARE360 — Controlled Write Candidate Register

**Database:** MOGHARE360_ERP  
**Status:** NOT approved for implementation

---

## Register Policy

- **Controlled writes are NOT approved yet**
- Write implementation must wait until **read-only layer is signed off**
- Write implementation must wait until **validation/workflow/audit contract is implemented**
- Write implementation must wait until **approved SQL package exists** if schema change is required

---

## Candidate Write Groups

### Customer Intake Submit

| Attribute | Value |
|-----------|-------|
| **Domain owner** | Customer |
| **Required validation** | National ID, mobile, Persian name |
| **Required workflow state** | DRAFT → SUBMITTED |
| **Required permission gate** | Submit gate (`customer.submit`) |
| **Required audit event** | `erp_customer_core_history` |
| **SQL dependency** | None if schema sufficient |
| **Runtime dependency** | Validation Engine, Workflow Engine, Customer module service |
| **Current status** | **NOT_APPROVED_FOR_IMPLEMENTATION** |

---

### Vehicle Registration Submit

| Attribute | Value |
|-----------|-------|
| **Domain owner** | Vehicle |
| **Required validation** | Plate, VIN, engine/chassis; **Camera direct only**; **No upload bypass** |
| **Required workflow state** | DRAFT → SUBMITTED |
| **Required permission gate** | `vehicle.create` |
| **Required audit event** | Vehicle/binding history |
| **SQL dependency** | None expected |
| **Runtime dependency** | Vehicle module, media validation |
| **Current status** | **NOT_APPROVED_FOR_IMPLEMENTATION** |

---

### JobCard Draft Create

| Attribute | Value |
|-----------|-------|
| **Domain owner** | JobCard |
| **Required validation** | Customer/vehicle/contract refs |
| **Required workflow state** | Create in DRAFT |
| **Required permission gate** | `jobcard.create` |
| **Required audit event** | `erp_jobcard_change_history` |
| **SQL dependency** | None expected |
| **Runtime dependency** | JobCard module |
| **Current status** | **NOT_APPROVED_FOR_IMPLEMENTATION** |

---

### Service Operation Draft Create

| Attribute | Value |
|-----------|-------|
| **Domain owner** | Operation / Service / QC / Delivery |
| **Required validation** | JobCard ref, operation type |
| **Required workflow state** | DRAFT |
| **Required permission gate** | `operation.execute` |
| **Required audit event** | `erp_operation_history` |
| **SQL dependency** | None expected |
| **Runtime dependency** | Operation module |
| **Current status** | **NOT_APPROVED_FOR_IMPLEMENTATION** |

---

### Inventory Reservation Draft Create

| Attribute | Value |
|-----------|-------|
| **Domain owner** | Inventory / Parts / Purchase |
| **Required validation** | Part ref, qty, jobcard state |
| **Required workflow state** | DRAFT → SUBMITTED |
| **Required permission gate** | `inventory.reserve` |
| **Required audit event** | Reservation history |
| **SQL dependency** | Possible FK review (cross-domain) |
| **Runtime dependency** | Inventory module |
| **Current status** | **NOT_APPROVED_FOR_IMPLEMENTATION** |

---

### Purchase Request Draft Create

| Attribute | Value |
|-----------|-------|
| **Domain owner** | Inventory / Parts / Purchase |
| **Required validation** | Supplier, parts, qty |
| **Required workflow state** | DRAFT → SUBMITTED |
| **Required permission gate** | `purchase.request` |
| **Required audit event** | `erp_purchase_request_history` |
| **SQL dependency** | None expected |
| **Runtime dependency** | Inventory module |
| **Current status** | **NOT_APPROVED_FOR_IMPLEMENTATION** |

---

### QC Check Draft Create

| Attribute | Value |
|-----------|-------|
| **Domain owner** | Operation / Service / QC / Delivery |
| **Required validation** | JobCard APPLIED; QC checklist; camera media |
| **Required workflow state** | DRAFT |
| **Required permission gate** | `qc.decide` |
| **Required audit event** | `erp_qc_check_history` |
| **SQL dependency** | None expected |
| **Runtime dependency** | Operation module |
| **Current status** | **NOT_APPROVED_FOR_IMPLEMENTATION** |

---

### Delivery Control Draft Create

| Attribute | Value |
|-----------|-------|
| **Domain owner** | Operation / Service / QC / Delivery |
| **Required validation** | QC pass; delivery checklist |
| **Required workflow state** | DRAFT → SUBMITTED |
| **Required permission gate** | `delivery.control` |
| **Required audit event** | `erp_delivery_control_history` |
| **SQL dependency** | None expected |
| **Runtime dependency** | Operation module |
| **Current status** | **NOT_APPROVED_FOR_IMPLEMENTATION** |

---

### CRM Follow-Up Draft Create

| Attribute | Value |
|-----------|-------|
| **Domain owner** | CRM / Customer Experience |
| **Required validation** | Customer/jobcard ref |
| **Required workflow state** | DRAFT |
| **Required permission gate** | `crm.followup` |
| **Required audit event** | `erp_crm_history` |
| **SQL dependency** | None expected |
| **Runtime dependency** | CRM module |
| **Current status** | **NOT_APPROVED_FOR_IMPLEMENTATION** |

---

### HR Employee Draft Create

| Attribute | Value |
|-----------|-------|
| **Domain owner** | HR |
| **Required validation** | Employee fields, contract dates |
| **Required workflow state** | DRAFT |
| **Required permission gate** | `hr.employee` |
| **Required audit event** | `erp_hr_history` |
| **SQL dependency** | None expected |
| **Runtime dependency** | HR module |
| **Current status** | **NOT_APPROVED_FOR_IMPLEMENTATION** |

---

## Approval Gate Summary

```
Read-only layer signoff (Phase 09+)
  → Validation/Workflow/Audit engine implementation
  → Validation test backlog pass
  → ChatGPT approves specific write candidate
  → Implementation phase authorized
```

---

**END OF CONTROLLED WRITE CANDIDATE REGISTER**

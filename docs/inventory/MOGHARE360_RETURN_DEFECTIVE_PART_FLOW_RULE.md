# MOGHARE360 — Return / Defective Part Flow Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Overview

Defines **return part** and **defective part** flows — stock quarantine, supplier return, warranty claims — with JobCard and QC linkage.

---

## Return Part Flow

| Step | Rule |
|------|------|
| 1 | Identify part — unused, wrong part, excess issue |
| 2 | Create return request — JobCard ref if applicable |
| 3 | Manager approval |
| 4 | **Return to warehouse** | Restock if good condition — main/secondary |
| 5 | Stock effect | Increase available at destination |
| 6 | Audit | `part_returned_to_stock` |

---

## Defective Part Flow

| Step | Rule |
|------|------|
| 1 | Defect found — QC or technician |
| 2 | Move to **defective holding area** warehouse |
| 3 | Remove from available qty |
| 4 | **QC relation** | Link to QC fail / rework record |
| 5 | Decision | Scrap, **return to supplier**, warranty claim |
| 6 | Audit | `part_defective_quarantined` |

---

## Return to Supplier

| Rule | Detail |
|------|--------|
| RMA reference | Supplier return auth number |
| Stock effect | Decrease defective hold |
| Supplier credit preview | Adjust preview balance |
| Approval | `purchase.approve` or owner |

---

## Warranty / Claim Relation

| Rule | Detail |
|------|--------|
| Warranty flag | Part OEM warranty eligible |
| Claim status | DRAFT → SUBMITTED → RESOLVED |
| JobCard link | Original consumption JobCard |
| No financial post | Preview only until Phase 23 |

---

## JobCard Relation

| Scenario | Binding |
|----------|---------|
| Defective on active job | `jobcard_id` + `operation_id` |
| Return unused reservation | Release reservation + return stock |
| Customer delivery delay | Flag if waiting replacement part |

---

## Stock Effect Planning

| Flow | On-hand | Available | Reserved | Defect hold |
|------|---------|-----------|----------|-------------|
| Defect quarantine | − | − | − | + |
| Return to stock (good) | + | + | — | − |
| Return to supplier | − | — | — | − |
| Scrap | − | — | — | − |

---

## Approval Requirement

| Action | Approver |
|--------|----------|
| Restock returned part | Inventory manager |
| Scrap | Manager |
| Supplier return | Manager + purchase role |
| Warranty claim | Owner policy |

---

## Audit Requirement

| Event | Detail |
|-------|--------|
| `return_request_created` | type, part, qty |
| `defective_quarantined` | qc_ref |
| `supplier_return_shipped` | rma |
| `warranty_claim_opened` | jobcard_id |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF RETURN / DEFECTIVE PART FLOW RULE**

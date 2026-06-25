# MOGHARE360 — After-sales Warranty Tracking Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Warranty Tracking Purpose

Track parts and labor warranty periods after delivery — link claims to JobCard, complaints, and inventory return/defective flows without official accounting posting in Phase 22.

---

## Warranty Relation to JobCard

| Rule | Detail |
|------|--------|
| **Warranty relation to JobCard** | Originating CLOSED JobCard mandatory for job warranty |
| Warranty record | `jobcard_id`, `customer_id`, `vehicle_id` |
| Delivery date | Often warranty start for labor |
| Revisit JobCard | New JobCard for warranty work — link to original |

---

## Warranty Relation to Parts / Service

| Type | Rule |
|------|------|
| **Parts warranty** | Part consumption row — supplier/OEM term |
| **Labor warranty** | Service operation — workshop term days |
| Catalog | Warranty days dropdown per service/part category |
| Overlap | Shorter of part vs labor policy |

---

## Warranty Dates

| Field | Rule |
|-------|------|
| **Warranty start date** | Delivery close or part install timestamp |
| **Warranty end date** | Start + term days — or explicit date |
| Mileage cap | Optional max km — planning field |
| Status | ACTIVE / EXPIRED / VOID |

---

## Warranty Claim Rule

| Step | Requirement |
|------|-------------|
| 1 | Customer reports issue within term |
| 2 | Complaint or dedicated claim record |
| 3 | Verify warranty ACTIVE — vehicle + part/service match |
| 4 | **Approval requirement** — manager for claim acceptance |
| 5 | Warranty JobCard — cost preview zero or reduced — not official credit note |
| 6 | CLOSED with audit |

---

## Complaint Relation

| Link | Rule |
|------|------|
| Complaint during warranty | Category «warranty» |
| Severity | Standard complaint workflow |
| Resolution | Rework under warranty claim |

Per `MOGHARE360_COMPLAINT_HANDLING_RULE.md`.

---

## Return / Defective Part Relation

| Link | Rule |
|------|------|
| Defective part under warranty | Phase 21 return/defective flow |
| Supplier RMA | Supplier credit preview adjustment |
| Stock | Defective hold → supplier return |

Per `MOGHARE360_RETURN_DEFECTIVE_PART_FLOW_RULE.md`.

---

## Validation

| Check | Error |
|-------|-------|
| Claim after end date | Block — offer paid service |
| Wrong vehicle | E-02 |
| No approval | E-04 workflow block |

---

## Audit Requirement

| Event | Detail |
|-------|--------|
| `warranty_registered` | jobcard_id, end_date |
| `warranty_claim_opened` | claim_id |
| `warranty_claim_approved` | approver |
| `warranty_claim_closed` | outcome |
| `erp_crm_history` | Row |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF AFTER-SALES WARRANTY TRACKING RULE**

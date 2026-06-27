# Customer / Vehicle / JobCard Chain Review

## Purpose
This document locks the end-to-end prototype chain from Customer through JobCard.

## Chain Overview
```
Customer (erp_customers)
    ↓
Vehicle (erp_vehicles)
    ↓
Relation (erp_customer_vehicle_relations)
    ↓
JobCard (erp_jobcards)
    ↓
JobCard History (erp_jobcard_change_history)
```

## Mission 15 Foundation (Locked)
| Entity | Test ID | Notes |
|--------|---------|-------|
| Customer | customer_id = 1 | Created via controlled create |
| Vehicle | vehicle_id = 1 | Created via controlled create |
| Relation | relation_id = 1 | OWNER, ACTIVE |

Customer / Vehicle creation belongs to Mission 15 only.
Later missions must link existing records — not duplicate identity data.

## Mission 17 Link (Locked)
| Field | Value |
|-------|-------|
| jobcard_id | 1 |
| jobcard_number | JC-20260621231416-1740 |
| customer_id | 1 |
| vehicle_id | 1 |
| relation_id | 1 |
| jobcard_status | RECEIVED |
| reception_user_id | 10001 |
| created_by_user_id | 10001 |

## Validation Chain (Locked)
JobCard create requires:
- relation_id exists
- relation lifecycle_state = ACTIVE
- customer exists
- vehicle exists
- at least one of customer_complaint or requested_services_summary
- jobcard_status in (DRAFT, RECEIVED)

## History Chain (Locked)
After JobCard create with status RECEIVED:
1. JOBCARD_CREATED
2. JOBCARD_RECEIVED

## Prototype Pages (Locked)
| Page | Type | Mission |
|------|------|---------|
| erp-customer-vehicle-create.php | Controlled create | M15 |
| erp-customer-vehicle-readonly-list.php | Read-only list | M15 |
| erp-jobcard-create.php | Controlled create | M17 |
| erp-jobcard-readonly-list.php | Read-only list | M17 |
| erp-jobcard-detail.php | Read-only detail | M17 |

## Chain Gap (Not Yet Built)
Not in scope of M05–M17:
- Service Operation rows
- Parts / inventory usage
- Purchase request
- Invoice / payment
- QC checklist execution
- Delivery / closure

## Mission 18 Boundary
Mission 18 confirms the chain is valid for foundation review only.
It does not extend the chain into service or finance operations.

## Final Chain Decision
**Customer + Vehicle + Relation → JobCard** is locked and validated.
Next operational links require Mission 19+ design approval.

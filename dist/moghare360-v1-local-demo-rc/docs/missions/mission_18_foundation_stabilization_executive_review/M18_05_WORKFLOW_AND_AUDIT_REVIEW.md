# Workflow and Audit Review

## Purpose
This document reviews workflow and audit behavior confirmed through controlled prototype missions.

## Controlled Write Pattern (Locked)
1. POST received
2. CSRF validated
3. Auth Context confirmed
4. Permission Guard evaluated
5. Input validation
6. Transaction started
7. Primary INSERT
8. Identity fetch by business key
9. History / audit INSERT(s)
10. Transaction committed
11. CSRF token rotated after success

## Mission 15 Audit Events (Locked)
| change_type | Confirmed |
|-------------|-----------|
| CUSTOMER_CREATED | OK |
| CUSTOMER_PHONE_CREATED | OK |
| VEHICLE_CREATED | OK |
| CUSTOMER_VEHICLE_RELATION_CREATED | OK |

## Mission 17 Audit Events (Locked)
| change_type | Confirmed |
|-------------|-----------|
| JOBCARD_CREATED | OK |
| JOBCARD_RECEIVED | OK (when status = RECEIVED) |

## changed_by_user_id Lock
- Mission 15 history: changed_by_user_id = 10001
- Mission 17 history: changed_by_user_id = 10001

## Workflow Scope Lock (Mission 17)
Allowed at create:
- jobcard_status = DRAFT or RECEIVED only

Not implemented:
- IN_PROGRESS transition
- WAITING_PARTS transition
- QC_READY transition
- DELIVERED transition
- CLOSED transition
- CANCELLED transition
- Approval workflow
- Service workflow
- Parts workflow
- Finance workflow
- Delivery workflow

## Rollback Rule (Locked)
Failed controlled creates must:
- Roll back transaction
- Not commit partial data
- Show safe error message only
- Preserve CSRF token on non-success POST (except after successful commit rotation)

## No Workflow Bypass Rule
No mission may bypass Auth, Permission Guard, CSRF, or audit/history on write operations.

## Mission 18 Boundary
Mission 18 reviews workflow and audit only.
No workflow engine or status transition is implemented.

## Final Workflow Decision
Audit/history strategy is proven for Customer / Vehicle and JobCard foundation.
Broader operational workflow remains unimplemented and blocked until future approved missions.

# Service Status Model

## Purpose
This document locks the Service Operation status model for future implementation.

## Suggested Status Values (Locked)

| Status | Meaning |
|--------|---------|
| DRAFT | Service Operation created; not yet assigned or started |
| ASSIGNED | Assignee recorded (placeholder); work not yet started |
| IN_PROGRESS | Active work in progress |
| WAITING_PARTS | Work blocked pending parts (no inventory write in initial scope) |
| DONE | Shop floor work complete; QC and Delivery deferred |
| QC_REJECTED | Reserved for future QC mission |
| CANCELLED | Service Operation cancelled; row retained with history |

## Initial Status on Create (Future — Mission 20)
Default recommended initial status: **DRAFT**

Alternative if assignee provided on create: **ASSIGNED** (Mission 20 may lock one rule only)

## Allowed Transitions (Design — Future Missions)

```
DRAFT → ASSIGNED
DRAFT → IN_PROGRESS (if policy allows skip assign)
DRAFT → CANCELLED

ASSIGNED → IN_PROGRESS
ASSIGNED → CANCELLED

IN_PROGRESS → WAITING_PARTS
IN_PROGRESS → DONE
IN_PROGRESS → CANCELLED

WAITING_PARTS → IN_PROGRESS
WAITING_PARTS → CANCELLED

DONE → QC_REJECTED (future QC mission only)
QC_REJECTED → IN_PROGRESS (future QC mission only)
QC_REJECTED → WAITING_PARTS (future QC mission only)

Any non-terminal → CANCELLED
```

Terminal states for shop floor (initial scope):
- DONE
- CANCELLED

## Status Rules (Locked)

### No Cross-Domain Side Effects
Status change must not trigger:
- Inventory write
- Finance write
- QC write
- Delivery write
- Invoice write

### History Required
Every status change must write erp_service_operation_change_history with:
- old_status
- new_status
- action_code (e.g. SERVICE_OPERATION_STATUS_CHANGED)
- changed_by_user_id
- changed_at

### No Physical Delete
Status CANCELLED replaces physical delete.

## JobCard Status (Locked)
Service status change does not directly mutate JobCard status in Mission 19 or initial Mission 20 scope.

## Mission 19 Boundary
Status model is documented only.
No transition engine is implemented.
No status validation code is created.

## Final Status Decision
Seven-value model locked; DRAFT default on create; QC_REJECTED and post-DONE flows reserved for future missions.

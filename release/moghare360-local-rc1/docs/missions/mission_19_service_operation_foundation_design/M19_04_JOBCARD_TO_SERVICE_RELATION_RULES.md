# JobCard to Service Relation Rules

## Purpose
This document locks the relation rules between JobCard and Service Operation.

## Core Relation (Locked)
- One JobCard → many Service Operations
- One Service Operation → exactly one JobCard

## Mandatory Link Rules (Locked)

### jobcard_id Is Required
- `jobcard_id` is mandatory on every Service Operation.
- Create must reject missing, null, or zero jobcard_id.

### JobCard Must Exist
- Referenced JobCard must exist in dbo.erp_jobcards at write time.
- Create must reject unknown jobcard_id.

### JobCard Must Be Active
- Referenced JobCard must be active (per JobCard lifecycle / is_active rules defined in JobCard foundation).
- Create must reject inactive or invalid JobCard references.

## Status Interaction Rules (Locked)

### No Direct JobCard Status Change
- Service Operation must not directly change JobCard status in Mission 19.
- Service Operation must not directly change JobCard status in initial Mission 20 scope unless explicitly authorized in a future mission charter.
- JobCard status coordination is deferred to a future mission.

## Delete Rules (Locked)

### Physical Delete Forbidden
- Physical DELETE of Service Operation rows is forbidden.
- Physical DELETE of Service Operation history rows is forbidden.
- Deactivation uses is_active and/or status CANCELLED with audit history.

## Validation Rules (Future — Mission 20)
On Service Operation create:
1. Validate jobcard_id is present and integer
2. Confirm JobCard row exists
3. Confirm JobCard is active
4. Reject invalid CSRF
5. Reject failed Auth Context
6. Reject denied Permission Guard

## Read Rules (Future)
- List and detail pages must scope Service Operations to authorized JobCard visibility.
- Detail page must show parent JobCard summary (read-only link).

## History Rules (Future)
Every Service Operation write must record:
- service_operation_id
- jobcard_id (denormalized on history row)
- action_code
- old_status / new_status where applicable
- changed_by_user_id
- changed_at

## Cross-Domain Write Rules (Locked)
Service Operation create and status change must not write:
- Inventory
- Finance
- QC
- Delivery
- Invoice

## Mission 19 Boundary
Relation rules are documented only.
No validation code is created.
No foreign keys are created.

## Final Relation Decision
Service Operation is always a child of an active JobCard; physical delete is forbidden; JobCard status is not mutated by Service Operation in this design phase.

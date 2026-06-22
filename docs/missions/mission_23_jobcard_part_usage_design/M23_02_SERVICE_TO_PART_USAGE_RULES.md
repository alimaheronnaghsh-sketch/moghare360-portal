# Service to Part Usage Rules

## Purpose
This document locks rules linking Service Operation to part usage.

## Mission 23 Boundary
Rules documented only. No validation code or usage writes in Mission 23.

## Core Linkage Rules (Locked)

### JobCard Mandatory
- Every part usage must have valid `jobcard_id`
- JobCard must exist in `dbo.erp_jobcards`
- JobCard `lifecycle_state` must be ACTIVE

### Service Operation Preferred
- `service_operation_id` is nullable on usage record
- When provided, Service Operation must exist in `dbo.erp_service_operations`
- Service Operation `jobcard_id` must equal usage `jobcard_id`
- Service Operation should be `is_active = 1`

### Cross-JobCard Forbidden
- Usage cannot reference Service Operation from a different JobCard
- Usage cannot reference part from inactive catalog row

## Service Operation Status Rules (Locked)

### CANCELLED — Forbidden
Part usage for Service Operation with `service_status = CANCELLED` is **forbidden**.

### DONE — Restricted
Part usage for Service Operation with `service_status = DONE` is **forbidden** in Mission 24 default scope.

Future exception (separate permission, post-M24):
- Requires dedicated permission (e.g. `jobcard.part.use.on_done` — reserved for future mission)
- Must write audit with explicit override reason

### Allowed Statuses for Usage (Mission 24 Default)
- ASSIGNED
- IN_PROGRESS
- WAITING_PARTS

DRAFT usage may be forbidden in M24 unless explicitly authorized in M24 charter.

## Quantity Rules (Locked)
- `quantity` must be a positive number
- Unit aligns with `erp_parts.unit_of_measure` (no UOM conversion in M23/M24)

## Physical Delete Forbidden
- Physical DELETE of part usage rows forbidden
- Deactivate via `is_active` and/or `usage_status = CANCELLED` with history

## Audit Required
Every usage create, return, or reversal must write `erp_jobcard_part_usage_history`.

## Mission 24 Validation Sequence (Future)
1. Validate jobcard_id (exists, active)
2. If service_operation_id provided: validate exists, active, same jobcard_id, status allowed
3. Validate part_id (exists, active)
4. Validate stock_location_id (exists, active)
5. Validate quantity > 0
6. Check quantity_on_hand >= quantity before ISSUE

## Final Service Rule Decision
Usage is always JobCard-scoped; Service Operation link required for validation when provided; CANCELLED forbidden; DONE blocked in M24 unless future override permission.

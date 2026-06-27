# JobCard Part Usage Rules

## Purpose
This document locks the future rules for part consumption against JobCard and Service Operation.

## Mission 21 Boundary
Rules are documented only.
No part usage table, write, or stock deduction is implemented in Mission 21.

## Core Usage Rules (Locked)

### Service Operation Requirement
- Part consumption without Service Operation is **forbidden** unless explicitly authorized in a future mission charter.
- Every part usage must link to a valid JobCard.
- Every part usage should prefer a valid Service Operation link when the work item exists.

### JobCard Link
- `jobcard_id` is mandatory on every future part usage record.
- JobCard must exist and be active (`lifecycle_state = ACTIVE`).

### Service Operation Link (Preferred)
- `service_operation_id` should be set when issuing parts for a specific service line.
- Service Operation must belong to the same JobCard.
- Service Operation `WAITING_PARTS` status may be used as workflow signal in future missions — no auto status change in Mission 21.

## Stock Deduction Timing (Locked)

| Mission | Stock Deduction |
|---------|-----------------|
| Mission 21 | Not allowed |
| Mission 22 | Not allowed |
| Mission 23 | Design only |
| Mission 24 | Controlled prototype allowed per M23 design |

**Rule:** Stock deduction is allowed only after Mission 23 design and Mission 24 prototype approval.

## Quantity Rules (Locked)
- Stock must not go negative.
- Issue quantity must be positive.
- Return quantity must not exceed issued quantity for same usage line (future validation).
- Unit of measure on usage must match part master `unit_of_measure` unless future UOM conversion mission authorizes otherwise.

## Movement Linkage (Future)
Each part usage write should produce:
- One or more `erp_stock_movements` rows with `movement_type = ISSUE` or `RETURN`
- `reference_type` = SERVICE_OPERATION or JOBCARD
- `reference_id` = corresponding id

## Audit / History (Locked)
Every part usage write must record:
- Auth Context user
- Timestamp
- JobCard id
- Service Operation id (when used)
- Part id, quantity, movement id
- Action code in dedicated usage history table (to be designed in Mission 23)

No silent stock change is permitted.

## Forbidden in Mission 21
- Creating part usage rows
- ISSUE / RETURN movement writes tied to JobCard
- Auto-deduct on Service Operation status change
- Finance or invoice side effects from usage

## Final Usage Decision
Part usage is always JobCard-scoped, preferably Service Operation-scoped, with stock deduction deferred to Mission 23 design and Mission 24 prototype.

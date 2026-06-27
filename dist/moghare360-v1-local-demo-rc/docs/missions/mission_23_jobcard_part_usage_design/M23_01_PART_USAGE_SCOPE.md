# Part Usage Scope

## Purpose
This document locks the scope of JobCard / Service Operation part usage design.

## Mission Goal (Locked)
Design part consumption on JobCard / Service Operation before any stock write.

## In Scope (Design Only)
Mission 23 defines:
- Part usage entity plan (`erp_jobcard_part_usage`)
- Part usage history plan (`erp_jobcard_part_usage_history`)
- JobCard and Service Operation linkage rules
- Stock deduction rules via `erp_stock_movements` ISSUE
- Return and reversal rules
- Permission and audit rules
- Finance boundary
- SQL implementation plan (no execution)
- UI plan for Mission 24 (no file creation)
- Testing plan for Mission 24 (no runnable tests)

## Core Scope Rules (Locked)

### Part Usage Header
- One usage record = one part issue event under a JobCard (optionally scoped to Service Operation)
- Quantity is positive on usage row
- Stock impact via movement ledger only

### JobCard Link
- Every part usage must have valid `jobcard_id`
- JobCard must exist and be active

### Service Operation Link (Preferred)
- `service_operation_id` nullable but preferred when work is operation-scoped
- Must belong to same JobCard when provided

### Part and Location
- `part_id` must exist in `dbo.erp_parts` and be active
- `stock_location_id` must exist in `dbo.erp_stock_locations` and be active

## Out of Scope (Locked)
Mission 23 must not:
- Create code
- Execute SQL
- Perform stock write
- Perform stock deduction
- Perform finance write
- Create invoice
- Create payment
- Create purchase request
- Perform delivery write
- Register real part usage rows

## Relationship to Mission 22
Mission 22 created parts master and stock movement table structure without consumption.
Mission 23 designs how usage connects JobCard / Service Operation to ISSUE movements in Mission 24.

## Future Mission Chain (Locked Reference)
- Mission 24 — Part usage controlled prototype + ISSUE movement (per M23 design)
- Mission 25+ — Purchase request (from M21 boundary)
- Mission 27/28 or future finance mission — Part pricing, invoice, payment linkage

## Mission 23 Boundary
Design only. No usage rows. No stock changed.

## Final Scope Decision
Mission 23 = usage design + deduction rules + audit; implementation deferred to Mission 24; finance deferred beyond M24.

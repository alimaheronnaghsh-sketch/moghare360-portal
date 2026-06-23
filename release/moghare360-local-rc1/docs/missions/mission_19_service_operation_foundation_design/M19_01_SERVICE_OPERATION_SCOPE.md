# Service Operation Scope

## Purpose
This document locks the scope of Service Operation foundation design for MOGHARE360 ERP.

## Mission Goal (Locked)
Design Service Operation to connect JobCard to repair shop service work.

## In Scope (Design Only)
Mission 19 defines:
- Service Operation entity plan
- JobCard relation rules
- Service status model
- Technician assignment placeholder
- Permission and workflow rules for future implementation
- SQL implementation plan (no execution)
- UI plan (no file creation)
- Testing plan (no runnable tests)

## Core Scope Rules (Locked)

### JobCard Relation
- Every Service Operation must link to one valid JobCard.
- Every JobCard may have multiple Service Operations.

### Operational Boundary
Service Operation does **not** yet change:
- Inventory
- Finance
- QC
- Delivery
- Invoice

### Mission Boundary
- Mission 19 is design only.
- Mission 19 is the foundation for Mission 20.

## Included in Service Operation Foundation (Future)
A Service Operation represents one discrete repair shop work item under a JobCard:
- service title and description
- optional technician assignment placeholder
- service status
- audit / history trail
- active flag for soft lifecycle control

## Excluded From Service Operation Foundation (Mission 19 and Mission 20 Initial)
Service Operation foundation does not include:
- parts request or reservation
- inventory write
- purchase request
- labor time tracking (detailed)
- customer approval workflow
- invoice or payment
- QC checklist execution
- delivery settlement
- warranty claim
- finance posting

## Relationship to JobCard
JobCard remains the intake and control header.
Service Operation is the first operational work unit under a JobCard.

## Mission 19 Boundary
This is design only.
No table is created.
No Service Operation is created.
No JobCard status is changed by implementation in Mission 19.

## Final Scope Decision
Service Operation foundation = controlled work item linked to JobCard, with status and history, without cross-domain writes.

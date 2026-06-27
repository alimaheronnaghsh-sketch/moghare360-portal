# Mission 17 - JobCard Create Boundary

## Purpose
This document defines the controlled create boundary for Mission 17.

## Write Rules
- POST only
- CSRF required
- Auth Context required
- Permission Guard required
- controlled transaction required
- rollback on failure
- safe error messages only
- no auto sample data
- no auto-submit

## Validation Rules
- relation_id required and integer
- relation must exist
- relation lifecycle_state must be ACTIVE
- linked customer must exist
- linked vehicle must exist
- jobcard_status allowed only DRAFT or RECEIVED
- Mission 17 default status = RECEIVED
- intake_mileage must be integer if provided
- at least one of customer_complaint or requested_services_summary required
- reject invalid CSRF
- reject failed Auth Context
- reject denied Permission Guard

## Identity Retrieval Rule
After INSERT into dbo.erp_jobcards:
- fetch jobcard_id by generated jobcard_number
- do not use OUTPUT INSERTED
- do not use SCOPE_IDENTITY()
- do not use @@IDENTITY
- do not use IDENT_CURRENT

## History Rule
Always write:
- JOBCARD_CREATED

If jobcard_status = RECEIVED, also write:
- JOBCARD_RECEIVED

## Forbidden Scope
Mission 17 must not write:
- Service Operation rows
- Inventory rows
- Finance rows
- Delivery rows
- Invoice rows
- Payment rows
- Customer or Vehicle records

## Customer / Vehicle Rule
JobCard create must link existing Mission 15 foundation records only.

## Status Transition Rule
Mission 17 may create initial status only.
No status transition engine is implemented.

# Mission 20 - Service Operation Create Boundary

## Purpose
This document defines the controlled create boundary for Mission 20.

## Write Rules
- POST only
- CSRF required
- Auth Context required
- Permission Guard required (service.operation.create)
- controlled transaction required
- rollback on failure
- safe error messages only
- no auto sample data
- no auto-submit

## Validation Rules
- jobcard_id required and integer
- JobCard must exist in dbo.erp_jobcards
- JobCard lifecycle_state must be ACTIVE
- Accepts JobCard ID = 1 or any other valid active JobCard
- service_title required (max 200 chars)
- service_status allowed only ASSIGNED or IN_PROGRESS on create
- assigned_to_user_id optional integer when provided
- reject invalid CSRF
- reject failed Auth Context
- reject denied Permission Guard

## Identity Retrieval Rule
After INSERT into dbo.erp_service_operations:
- fetch service_operation_id by composite lookup (jobcard_id, service_title, service_status, created_by_user_id)
- do not use OUTPUT INSERTED
- do not use SCOPE_IDENTITY()
- do not use @@IDENTITY
- do not use IDENT_CURRENT

## Transaction Order
1. BEGIN transaction (odbc_autocommit false)
2. INSERT dbo.erp_service_operations
3. FETCH service_operation_id
4. INSERT dbo.erp_service_operation_change_history
5. COMMIT or ROLLBACK

## History Rule
Always write:
- action_code = SERVICE_OPERATION_CREATED
- old_status = NULL
- new_status = initial service_status

## Forbidden Scope
Mission 20 must not write:
- Inventory rows
- Finance rows
- QC rows
- Delivery rows
- Invoice rows
- Payment rows
- JobCard status changes
- Customer or Vehicle records

## JobCard Status Rule
Service Operation create must not change JobCard status.

## Success Output
Browser create success message: **Service Operation Created OK**

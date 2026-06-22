# Service Operation Data Model Plan

## Purpose
This document defines the planned Service Operation foundation data model.

## Important Rule
Mission 19 does not create or execute SQL.
This is a plan only.

## Proposed Tables (Future — Mission 20+)

### dbo.erp_service_operations
Primary entity for one service work item under a JobCard.

### dbo.erp_service_operation_change_history
Audit trail for Service Operation writes and status changes.

## Proposed Fields: dbo.erp_service_operations

| Field | Type (Suggested) | Nullable | Notes |
|-------|------------------|----------|-------|
| service_operation_id | INT / BIGINT | No | Primary key |
| jobcard_id | INT / BIGINT | No | Required FK to dbo.erp_jobcards |
| service_title | NVARCHAR | No | Short label for the work item |
| service_description | NVARCHAR | Yes | Detailed work description |
| assigned_to_user_id | INT / BIGINT | Yes | Technician placeholder; nullable |
| service_status | NVARCHAR | No | See M19_06 status model |
| created_by_user_id | INT / BIGINT | No | Auth Context user |
| created_at | DATETIME2 | No | Row creation timestamp |
| updated_at | DATETIME2 | No | Last update timestamp |
| is_active | BIT | No | Soft lifecycle; default 1 |

### Minimum Required Fields for Soft Run (Future)
Required on create:
- jobcard_id
- service_title
- service_status
- created_by_user_id
- is_active

Optional on create:
- service_description
- assigned_to_user_id

## Proposed Fields: dbo.erp_service_operation_change_history

| Field | Type (Suggested) | Nullable | Notes |
|-------|------------------|----------|-------|
| history_id | INT / BIGINT | No | Primary key |
| service_operation_id | INT / BIGINT | No | FK to erp_service_operations |
| jobcard_id | INT / BIGINT | No | Denormalized for audit queries |
| action_code | NVARCHAR | No | e.g. SERVICE_OPERATION_CREATED |
| old_status | NVARCHAR | Yes | Previous service_status |
| new_status | NVARCHAR | Yes | New service_status |
| changed_by_user_id | INT / BIGINT | No | Auth Context user |
| changed_at | DATETIME2 | No | Change timestamp |
| change_note | NVARCHAR | Yes | Optional operator note |

### Minimum History on Create (Future — Mission 20)
On Service Operation create, write at least:
- action_code = SERVICE_OPERATION_CREATED
- old_status = NULL
- new_status = initial service_status (typically DRAFT)

## Suggested Constraints (Future SQL)
- Primary keys on both tables
- Foreign key: erp_service_operations.jobcard_id → dbo.erp_jobcards.jobcard_id
- Foreign key: erp_service_operation_change_history.service_operation_id → erp_service_operations.service_operation_id
- Index on jobcard_id (operations and history)
- Index on service_status
- Index on assigned_to_user_id (nullable index or filtered index)
- No physical DELETE policy — use is_active and status CANCELLED

## Identity Retrieval Rule (Future)
Follow locked pattern from M15/M17/M18:
- Do not use OUTPUT INSERTED
- Do not use SCOPE_IDENTITY()
- Do not use @@IDENTITY
- Do not use IDENT_CURRENT
- Approved pattern: INSERT + fetch by generated unique business key if one is added in Mission 20, or fetch by composite unique constraint as defined in Mission 20 SQL plan

## Mission 19 Boundary
Plan only.
No table is created.
No SQL file is created.
No SQL is executed.

## Final Data Model Decision
Two-table design: operations + change history, with mandatory JobCard link and soft active lifecycle.

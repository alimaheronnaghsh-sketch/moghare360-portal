# Phase 1A Access Request Table Discovery Result

## Project
MOGHARE360 ERP

## Phase
Phase 1A

## Purpose
Record the discovered database tables, columns, primary keys, and foreign keys related to Access Request Create UI planning.

This document is discovery-only.

No UI is created in this step.

No runtime behavior is changed in this step.

No database write is performed in this step.

## Discovery Database

- Database: moghare360_ERP
- Schema: dbo

## Discovery Result Summary

The access request lifecycle tables discovered are:

- dbo.core_access_requests
- dbo.core_access_request_items
- dbo.core_access_approvals
- dbo.core_access_approval_rules
- dbo.core_access_change_history
- dbo.core_access_restrictions
- dbo.core_access_suspensions

## Primary Table For Access Request Create UI

The primary table for creating an access request is:

- dbo.core_access_requests

Primary key:

- request_id

Primary key type:

- bigint identity

## Secondary Table For Access Request Items

The secondary table for request detail items is:

- dbo.core_access_request_items

Primary key:

- item_id

Primary key type:

- bigint identity

Foreign key to request header:

- request_id references dbo.core_access_requests.request_id

## dbo.core_access_requests Columns

| Column | Type | Nullable | Identity | Default |
|---|---:|---:|---:|---|
| request_id | bigint | No | Yes | NULL |
| request_number | nvarchar(60) | No | No | NULL |
| request_type | nvarchar(80) | No | No | NULL |
| request_state | nvarchar(60) | No | No | N'DRAFT' |
| priority | nvarchar(40) | No | No | N'NORMAL' |
| subject_user_id | int | No | No | NULL |
| requested_by_user_id | int | No | No | NULL |
| justification | nvarchar(max) | No | No | NULL |
| owner_acknowledged | bit | No | No | 0 |
| is_emergency | bit | No | No | 0 |
| migration_source | nvarchar(60) | Yes | No | NULL |
| submitted_at | datetime2 | Yes | No | NULL |
| decided_at | datetime2 | Yes | No | NULL |
| applied_at | datetime2 | Yes | No | NULL |
| applied_by_user_id | int | Yes | No | NULL |
| cancelled_at | datetime2 | Yes | No | NULL |
| cancelled_by_user_id | int | Yes | No | NULL |
| created_at | datetime2 | No | No | sysutcdatetime() |
| updated_at | datetime2 | Yes | No | NULL |
| row_version | timestamp | No | No | NULL |

## dbo.core_access_request_items Columns

| Column | Type | Nullable | Identity | Default |
|---|---:|---:|---:|---|
| item_id | bigint | No | Yes | NULL |
| request_id | bigint | No | No | NULL |
| item_type | nvarchar(80) | No | No | NULL |
| role_id | int | Yes | No | NULL |
| department_id | int | Yes | No | NULL |
| position_id | int | Yes | No | NULL |
| module_key | nvarchar(160) | Yes | No | NULL |
| permission_key | nvarchar(240) | Yes | No | NULL |
| scope_type | nvarchar(40) | Yes | No | NULL |
| effective_from | datetime2 | No | No | NULL |
| expires_at | datetime2 | Yes | No | NULL |
| is_temporary | bit | No | No | 0 |
| item_decision | nvarchar(40) | No | No | N'PENDING' |
| sort_order | int | No | No | 1 |
| created_at | datetime2 | No | No | sysutcdatetime() |

## Related Tables

### dbo.core_access_approvals

Purpose:

- Stores approval decisions for access requests.

Primary key:

- approval_id

Important foreign keys:

- request_id references dbo.core_access_requests.request_id
- approver_user_id references dbo.core_users.user_id

### dbo.core_access_approval_rules

Purpose:

- Stores approval rule definitions by request type and approver capacity.

Primary key:

- approval_rule_id

Important fields:

- request_type
- approver_capacity
- required_order
- is_required
- is_active
- description

### dbo.core_access_change_history

Purpose:

- Stores access lifecycle change history.

Primary key:

- history_id

Important foreign keys:

- user_id references dbo.core_users.user_id
- request_id references dbo.core_access_requests.request_id
- changed_by_user_id references dbo.core_users.user_id

### dbo.core_access_restrictions

Purpose:

- Stores access restrictions linked to users and requests.

Primary key:

- restriction_id

Important foreign keys:

- user_id references dbo.core_users.user_id
- request_id references dbo.core_access_requests.request_id
- permission_key references dbo.core_permissions.permission_key
- lifted_by_request_id references dbo.core_access_requests.request_id

### dbo.core_access_suspensions

Purpose:

- Stores access suspensions linked to users and requests.

Primary key:

- suspension_id

Important foreign keys:

- user_id references dbo.core_users.user_id
- request_id references dbo.core_access_requests.request_id
- lifted_by_request_id references dbo.core_access_requests.request_id

## Primary Keys Discovered

| Table | Primary Key |
|---|---|
| dbo.core_access_approval_rules | approval_rule_id |
| dbo.core_access_approvals | approval_id |
| dbo.core_access_change_history | history_id |
| dbo.core_access_request_items | item_id |
| dbo.core_access_requests | request_id |
| dbo.core_access_restrictions | restriction_id |
| dbo.core_access_suspensions | suspension_id |

## Foreign Keys Discovered

| Table | Foreign Key | Column | References |
|---|---|---|---|
| dbo.core_access_approvals | FK_core_access_approvals_approver | approver_user_id | dbo.core_users.user_id |
| dbo.core_access_approvals | FK_core_access_approvals_request | request_id | dbo.core_access_requests.request_id |
| dbo.core_access_change_history | FK_core_access_change_history_changed_by | changed_by_user_id | dbo.core_users.user_id |
| dbo.core_access_change_history | FK_core_access_change_history_request | request_id | dbo.core_access_requests.request_id |
| dbo.core_access_change_history | FK_core_access_change_history_user | user_id | dbo.core_users.user_id |
| dbo.core_access_request_items | FK_core_access_request_items_department | department_id | dbo.core_departments.department_id |
| dbo.core_access_request_items | FK_core_access_request_items_position | position_id | dbo.core_positions.position_id |
| dbo.core_access_request_items | FK_core_access_request_items_request | request_id | dbo.core_access_requests.request_id |
| dbo.core_access_request_items | FK_core_access_request_items_role | role_id | dbo.core_roles.role_id |
| dbo.core_access_requests | FK_core_access_requests_applied_by | applied_by_user_id | dbo.core_users.user_id |
| dbo.core_access_requests | FK_core_access_requests_cancelled_by | cancelled_by_user_id | dbo.core_users.user_id |
| dbo.core_access_requests | FK_core_access_requests_requester | requested_by_user_id | dbo.core_users.user_id |
| dbo.core_access_requests | FK_core_access_requests_subject | subject_user_id | dbo.core_users.user_id |
| dbo.core_access_restrictions | FK_core_access_restrictions_lifted_by | lifted_by_request_id | dbo.core_access_requests.request_id |
| dbo.core_access_restrictions | FK_core_access_restrictions_permission | permission_key | dbo.core_permissions.permission_key |
| dbo.core_access_restrictions | FK_core_access_restrictions_request | request_id | dbo.core_access_requests.request_id |
| dbo.core_access_restrictions | FK_core_access_restrictions_user | user_id | dbo.core_users.user_id |
| dbo.core_access_suspensions | FK_core_access_suspensions_lifted_by | lifted_by_request_id | dbo.core_access_requests.request_id |
| dbo.core_access_suspensions | FK_core_access_suspensions_request | request_id | dbo.core_access_requests.request_id |
| dbo.core_access_suspensions | FK_core_access_suspensions_user | user_id | dbo.core_users.user_id |

## Initial Insert Implication

A future Access Request Create UI likely requires writing at least one row into:

- dbo.core_access_requests

A future role or permission request likely also requires writing at least one row into:

- dbo.core_access_request_items

Because dbo.core_access_requests has non-null columns without defaults, the minimum future insert into dbo.core_access_requests must provide:

- request_number
- request_type
- subject_user_id
- requested_by_user_id
- justification

The following values can rely on defaults unless explicitly overridden:

- request_state = DRAFT
- priority = NORMAL
- owner_acknowledged = 0
- is_emergency = 0
- created_at = sysutcdatetime()

Because dbo.core_access_request_items has non-null columns without defaults, the minimum future insert into dbo.core_access_request_items must provide:

- request_id
- item_type
- effective_from

Depending on item_type, one or more of the following may also be required logically:

- role_id
- department_id
- position_id
- module_key
- permission_key
- scope_type

## Request Number Requirement

The field request_number is required and has no database default.

Before implementation, the project must define a safe request_number generation strategy.

Possible future pattern:

- AR-YYYYMMDD-HHMMSS-USERID

This is planning only and not approved as implementation yet.

## Recommended First UI Scope

For the first controlled write UI, keep scope minimal.

Recommended first Access Request Create UI fields:

- request_type
- subject_user_id
- justification
- priority
- item_type
- role_id, only if item_type is ROLE
- permission_key, only if item_type is PERMISSION
- effective_from
- expires_at
- is_temporary

## Required Future Implementation Boundary

The future implementation must:

- use a database transaction
- insert request header into dbo.core_access_requests
- retrieve the new request_id safely
- insert request item into dbo.core_access_request_items if required
- write audit event after successful insert
- roll back on failure
- show only safe user-facing messages
- never display raw SQL errors
- never display config secrets
- never display CSRF token values
- never display session internals

## Not Approved In This Step

The following are not approved now:

- Creating erp-access-request-create.php
- Creating Access Request Create UI
- Creating any form
- Creating any submit handler
- Performing database writes
- Modifying SQL files
- Creating migrations
- Creating users
- Assigning roles
- Modifying permissions
- Modifying auth helper
- Modifying permission helper
- Modifying CSRF helper
- Modifying audit helper
- Modifying dashboard
- Production deployment

## Final Decision

Access Request table discovery is complete.

The primary future write target is:

- dbo.core_access_requests

The secondary future write target is:

- dbo.core_access_request_items

The required next step is:

- Access Request Create UI Task

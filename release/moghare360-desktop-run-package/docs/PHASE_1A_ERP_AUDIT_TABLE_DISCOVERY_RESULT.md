# Phase 1A ERP Audit Table Discovery Result

## Project
MOGHARE360 ERP

## Phase
Phase 1A

## Purpose
Record the discovered SQL Server audit table and column structure before creating the ERP Audit Helper.

This document is discovery-only.

No executable audit helper is created in this step.

No database write is performed in this step.

No SQL file is modified in this step.

## Discovery Database

Database:

- moghare360_ERP

## Discovered Audit Table

The discovered audit table is:

- dbo.core_audit_logs

## Table Columns

The discovered columns are:

| column_id | column_name | data_type | max_length | is_nullable | is_identity |
|---:|---|---|---:|---:|---:|
| 1 | audit_id | bigint | 8 | 0 | 1 |
| 2 | actor_user_id | int | 4 | 1 | 0 |
| 3 | action | nvarchar | 160 | 0 | 0 |
| 4 | entity_type | nvarchar | 100 | 1 | 0 |
| 5 | entity_id | bigint | 8 | 1 | 0 |
| 6 | request_id | bigint | 8 | 1 | 0 |
| 7 | subject_user_id | int | 4 | 1 | 0 |
| 8 | details_json | nvarchar | -1 | 1 | 0 |
| 9 | ip_address | nvarchar | 90 | 1 | 0 |
| 10 | user_agent | nvarchar | 1000 | 1 | 0 |
| 11 | is_emergency | bit | 1 | 0 | 0 |
| 12 | created_at | datetime2 | 7 | 0 | 0 |

## Required Columns For First Audit Insert

The first ERP Audit Helper implementation must handle required non-null columns:

- action
- is_emergency
- created_at

The following identity column must not be inserted manually:

- audit_id

## Nullable Columns Available For Safe Metadata

The following nullable columns may be used when safe data exists:

- actor_user_id
- entity_type
- entity_id
- request_id
- subject_user_id
- details_json
- ip_address
- user_agent

## Mapping Decision

The future ERP Audit Helper should map planned audit fields to the actual table columns as follows:

| Planned Field | Actual Column |
|---|---|
| event_type | action |
| target_entity_type | entity_type |
| target_entity_id | entity_id |
| safe_message and extra metadata | details_json |
| actor_user_id | actor_user_id |
| ip_address | ip_address |
| user_agent | user_agent |
| created_at | created_at |
| is_emergency | is_emergency |

## Initial Allowed Event Actions

The first implementation may support these safe action values:

- ERP_LOGIN_SUCCESS
- ERP_LOGIN_FAILURE
- ERP_LOGOUT
- ERP_ACCESS_DENIED
- ERP_PERMISSION_DENIED
- ERP_AUDIT_TEST

## Insert Boundary

The future Audit Helper may insert only into:

- dbo.core_audit_logs

No other audit table is approved.

No schema change is approved.

No migration is approved.

## Safety Rules For Future Insert

The future audit insert must not store:

- password
- password_hash
- erp_session_token
- database password
- config secret
- full connection string
- SQL error
- PHP stack trace
- private config path
- raw debug dump

## Required Future Validation

Before the first helper implementation is committed, the project must confirm:

- INSERT works with required columns.
- action length is within nvarchar(160).
- entity_type length is within nvarchar(100).
- user_agent length is within nvarchar(1000).
- ip_address length is within nvarchar(90).
- details_json stores safe JSON only.
- created_at uses SYSDATETIME() or safe PHP datetime binding.
- is_emergency defaults to false unless explicitly required.

## Not Approved In This Step

The following are not approved now:

- Creating includes/erp-audit-helper.php
- Performing audit INSERT
- Modifying SQL files
- Creating SQL migrations
- Changing dbo.core_audit_logs schema
- Modifying login behavior
- Modifying logout behavior
- Creating write-enabled UI
- Creating Access Request UI
- Creating users
- Assigning roles
- Modifying permissions
- Production deployment

## Final Decision

The audit table discovery is complete.

The future ERP Audit Helper must target:

- dbo.core_audit_logs

No database write is approved by this document.

The next controlled step is:

- Create ERP Audit Helper implementation

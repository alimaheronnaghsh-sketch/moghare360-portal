# SQL Implementation Plan

## Purpose
This document prepares the future SQL implementation plan for Service Operation foundation.

## Critical Rule (Locked)

**No SQL execution in Mission 19.**

**SQL implementation deferred to Mission 20.**

Mission 19 does not create SQL files.
Mission 19 does not execute SQL.
Mission 19 does not change the database.

## Future SQL Scope (Mission 20)
Mission 20 may create:

- dbo.erp_service_operations
- dbo.erp_service_operation_change_history

## Suggested Table: dbo.erp_service_operations
Planned fields (see M19_03):
- service_operation_id (PK)
- jobcard_id (FK → dbo.erp_jobcards)
- service_title
- service_description
- assigned_to_user_id (nullable)
- service_status
- created_by_user_id
- created_at
- updated_at
- is_active

## Suggested Table: dbo.erp_service_operation_change_history
Planned fields (see M19_03):
- history_id (PK)
- service_operation_id (FK)
- jobcard_id
- action_code
- old_status
- new_status
- changed_by_user_id
- changed_at
- change_note

## Required SQL Controls (Future)
Future SQL must include:
- primary keys on both tables
- foreign key: jobcard_id → dbo.erp_jobcards.jobcard_id
- foreign key: service_operation_id on history table
- created_at and updated_at on operations table
- is_active with default 1
- indexes on jobcard_id, service_status, assigned_to_user_id
- no destructive migration
- no physical DELETE policy in application layer

## Identity Retrieval (Future)
Follow locked pattern from M15/M17/M18:
- No OUTPUT INSERTED
- No SCOPE_IDENTITY()
- No @@IDENTITY
- No IDENT_CURRENT
- INSERT + fetch by defined unique business key or approved composite lookup per Mission 20 SQL charter

## Seed Data Rule (Future)
- No auto sample data in Mission 20 create page
- Test data only via controlled test script with explicit JobCard ID (e.g. jobcard_id = 1)

## Mission 19 Boundary
Plan only.

| Action | Mission 19 |
|--------|------------|
| SQL file creation | Not allowed |
| SQL execution | Not allowed |
| Database change | Not allowed |
| Table creation | Deferred to Mission 20 |

## Final SQL Decision
Mission 19 locks the two-table plan only.
All SQL creation and execution is explicitly deferred to Mission 20.

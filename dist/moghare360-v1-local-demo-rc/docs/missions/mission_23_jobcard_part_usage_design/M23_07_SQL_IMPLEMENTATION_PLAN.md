# SQL Implementation Plan

## Purpose
This document prepares the future SQL implementation plan for JobCard part usage.

## Critical Rule (Locked)

**No SQL execution in Mission 23.**

**SQL implementation deferred to Mission 24.**

Mission 23 does not create SQL files.
Mission 23 does not execute SQL.
Mission 23 does not change the database.

## Future SQL Scope (Mission 24)
Mission 24 may create:

- dbo.erp_jobcard_part_usage
- dbo.erp_jobcard_part_usage_history

Mission 24 may write to existing:
- dbo.erp_stock_movements (ISSUE only, per M24 charter)

## Suggested Table: dbo.erp_jobcard_part_usage

| Field | Type (Suggested) | Nullable | Notes |
|-------|------------------|----------|-------|
| part_usage_id | INT | No | PK, IDENTITY |
| jobcard_id | INT | No | FK → dbo.erp_jobcards |
| service_operation_id | INT | Yes | FK → dbo.erp_service_operations |
| part_id | INT | No | FK → dbo.erp_parts |
| stock_location_id | INT | No | FK → dbo.erp_stock_locations |
| quantity | DECIMAL(18,3) | No | Must be > 0 |
| usage_status | NVARCHAR(30) | No | USED, RETURNED, REVERSED, CANCELLED |
| created_by_user_id | INT | No | Auth Context user |
| created_at | DATETIME2(3) | No | Default SYSUTCDATETIME() |
| reversed_by_usage_id | INT | Yes | Link to compensating usage if used |
| is_active | BIT | No | Default 1 |

### Suggested usage_status CHECK
- USED
- RETURNED
- REVERSED
- CANCELLED

## Suggested Table: dbo.erp_jobcard_part_usage_history

| Field | Type (Suggested) | Nullable | Notes |
|-------|------------------|----------|-------|
| history_id | INT | No | PK, IDENTITY |
| part_usage_id | INT | No | FK → erp_jobcard_part_usage |
| jobcard_id | INT | No | Denormalized for audit queries |
| service_operation_id | INT | Yes | Denormalized |
| part_id | INT | No | Denormalized |
| action_code | NVARCHAR(80) | No | e.g. PART_USAGE_CREATED |
| old_status | NVARCHAR(30) | Yes | Previous usage_status |
| new_status | NVARCHAR(30) | Yes | New usage_status |
| changed_by_user_id | INT | No | Auth Context user |
| changed_at | DATETIME2(3) | No | Default SYSUTCDATETIME() |
| change_note | NVARCHAR(MAX) | Yes | Operator note / reason |

## Suggested Foreign Keys (Future)
- jobcard_id → dbo.erp_jobcards
- service_operation_id → dbo.erp_service_operations
- part_id → dbo.erp_parts
- stock_location_id → dbo.erp_stock_locations
- part_usage_id on history → erp_jobcard_part_usage

## Stock Movement Reference (Future)
On usage create with ISSUE:
```text
erp_stock_movements.reference_type = JOBCARD_PART_USAGE
erp_stock_movements.reference_id   = part_usage_id
erp_stock_movements.movement_type  = ISSUE
```

## Identity Retrieval (Future)
Follow locked pattern:
- No OUTPUT INSERTED / SCOPE_IDENTITY / @@IDENTITY / IDENT_CURRENT
- INSERT usage + fetch by composite unique key or business reference as defined in M24 charter

## Required SQL Controls (Future)
- Primary keys and foreign keys
- CHECK quantity > 0 on usage
- CHECK usage_status enum
- Indexes on jobcard_id, service_operation_id, part_id, usage_status
- No physical DELETE policy
- Idempotent IF NOT EXISTS script pattern

## Mission 23 Boundary
Plan only.

| Action | Mission 23 |
|--------|------------|
| SQL file creation | Not allowed |
| SQL execution | Not allowed |
| Stock write | Not allowed |

## Final SQL Decision
Two-table usage design + ISSUE movement reference; all implementation deferred to Mission 24.

# SQL Implementation Plan

## Purpose
This document defines the SQL implementation plan for QC and delivery foundation.

## Critical Rule (Locked)

**No SQL execution in Mission 29.**

**SQL implementation deferred to Mission 30.**

Mission 29 creates zero SQL files and runs zero scripts against the database.

## Mission 30 Indicative SQL Deliverable
Single idempotent script (indicative path):
`public_html/sql/sqlserver/mission_30_qc_delivery_foundation.sql`

## Planned Objects (Mission 30)

### Tables
1. `dbo.erp_qc_checks`
2. `dbo.erp_qc_check_history`
3. `dbo.erp_delivery_controls`
4. `dbo.erp_delivery_control_history`

## Proposed Fields: dbo.erp_qc_checks

| Field | Type (Suggested) | Nullable | Notes |
|-------|------------------|----------|-------|
| qc_check_id | INT | No | PK, IDENTITY |
| jobcard_id | INT | No | FK → erp_jobcards |
| service_operation_id | INT | Yes | FK → erp_service_operations |
| qc_status | NVARCHAR(30) | No | PENDING, PASSED, FAILED, RECHECK_REQUIRED, CANCELLED |
| checked_by_user_id | INT | No | Inspector |
| checked_at | DATETIME2 | No | Default SYSUTCDATETIME() |
| qc_note | NVARCHAR(MAX) | Yes | Checklist notes |
| is_active | BIT | No | Default 1 |

## Proposed Fields: dbo.erp_qc_check_history

| Field | Type (Suggested) | Nullable |
|-------|------------------|----------|
| history_id | INT | No |
| qc_check_id | INT | No |
| jobcard_id | INT | No |
| action_code | NVARCHAR(80) | No |
| old_status | NVARCHAR(30) | Yes |
| new_status | NVARCHAR(30) | Yes |
| changed_by_user_id | INT | No |
| changed_at | DATETIME2 | No |
| change_note | NVARCHAR(MAX) | Yes |

## Proposed Fields: dbo.erp_delivery_controls

| Field | Type (Suggested) | Nullable | Notes |
|-------|------------------|----------|-------|
| delivery_control_id | INT | No | PK, IDENTITY |
| jobcard_id | INT | No | FK → erp_jobcards |
| qc_check_id | INT | Yes | FK → erp_qc_checks |
| delivery_status | NVARCHAR(30) | No | BLOCKED, READY, RELEASED, CANCELLED |
| delivery_allowed | BIT | No | Gate flag for UI |
| block_reason | NVARCHAR(200) | Yes | Why blocked |
| released_by_user_id | INT | Yes | Set on RELEASED |
| released_at | DATETIME2 | Yes | Set on RELEASED |
| created_at | DATETIME2 | No | Default SYSUTCDATETIME() |
| is_active | BIT | No | Default 1 |

## Delivery Status Model (Locked)

| delivery_status | Meaning |
|-----------------|---------|
| BLOCKED | Cannot deliver |
| READY | Eligible for release |
| RELEASED | Delivery recorded (prototype) |
| CANCELLED | Control cancelled |

## Proposed Fields: dbo.erp_delivery_control_history

| Field | Type (Suggested) | Nullable |
|-------|------------------|----------|
| history_id | INT | No |
| delivery_control_id | INT | No |
| jobcard_id | INT | No |
| action_code | NVARCHAR(80) | No |
| old_status | NVARCHAR(30) | Yes |
| new_status | NVARCHAR(30) | Yes |
| changed_by_user_id | INT | No |
| changed_at | DATETIME2 | No |
| change_note | NVARCHAR(MAX) | Yes |

## Suggested Constraints (Future SQL)
- FK jobcard_id, service_operation_id, qc_check_id
- CHECK qc_status enum (5 values)
- CHECK delivery_status enum (4 values)
- CHECK payment_amount N/A on delivery table
- No physical DELETE

## Identity Retrieval (Locked)
INSERT + composite lookup — no SCOPE_IDENTITY.

## Explicitly NOT in Mission 30 SQL (Locked)
- erp_invoices
- Customer signature blob tables
- Customer Portal tables
- Payment gate enforcement stored procedures
- JobCard status column migration (unless explicitly chartered)

## Mission 29 Deliverable
This plan document only.

## Final SQL Plan Decision
Mission 29 = zero SQL. Mission 30 = four tables per field lists above.

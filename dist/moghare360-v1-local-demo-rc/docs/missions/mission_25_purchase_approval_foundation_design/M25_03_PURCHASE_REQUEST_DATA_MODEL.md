# Purchase Request Data Model Plan

## Purpose
This document defines the planned Purchase Request data model.

## Important Rule
Mission 25 does not create or execute SQL.
This is a plan only.

## Proposed Tables (Future — Mission 26+)

### dbo.erp_purchase_requests
Primary entity for one purchase request line / header for shop floor parts need.

### dbo.erp_purchase_request_history
Audit trail for purchase request status and field changes.

## Proposed Fields: dbo.erp_purchase_requests

| Field | Type (Suggested) | Nullable | Notes |
|-------|------------------|----------|-------|
| purchase_request_id | INT | No | PK, IDENTITY |
| jobcard_id | INT | No | FK → dbo.erp_jobcards |
| service_operation_id | INT | Yes | FK → dbo.erp_service_operations |
| part_id | INT | Yes | FK → dbo.erp_parts (catalog part if known) |
| requested_part_name | NVARCHAR(200) | No | Free text or catalog name snapshot |
| requested_quantity | DECIMAL(18,3) | No | Must be > 0 |
| request_reason | NVARCHAR(1000) | Yes | Why purchase needed |
| request_status | NVARCHAR(30) | No | See status model |
| requested_by_user_id | INT | No | Creator |
| requested_at | DATETIME2(3) | No | Default SYSUTCDATETIME() |
| approved_by_user_id | INT | Yes | Set on APPROVED |
| approved_at | DATETIME2(3) | Yes | Set on APPROVED |
| rejected_by_user_id | INT | Yes | Set on REJECTED |
| rejected_at | DATETIME2(3) | Yes | Set on REJECTED |
| supplier_id | INT | Yes | Placeholder; no supplier master in M26 |
| estimated_unit_cost | DECIMAL(18,4) | Yes | Informational only |
| currency_code | NVARCHAR(10) | Yes | e.g. IRR; informational |
| is_active | BIT | No | Default 1; soft lifecycle |

### Minimum Required on Create (Future M26)
- jobcard_id
- requested_part_name
- requested_quantity
- request_status (DRAFT or SUBMITTED)
- requested_by_user_id

## Purchase Request Status Model (Locked)

| Status | Meaning |
|--------|---------|
| DRAFT | Created; editable per approval rules |
| SUBMITTED | Awaiting approval |
| APPROVED | Approved; no stock/finance side effects in M26 |
| REJECTED | Denied; reason required |
| CANCELLED | Cancelled; no physical delete |
| ORDERED | Reserved for future PO execution |
| RECEIVED | Reserved for future stock receipt |
| CLOSED | Reserved for terminal closure after receipt |

## Proposed Fields: dbo.erp_purchase_request_history

| Field | Type (Suggested) | Nullable | Notes |
|-------|------------------|----------|-------|
| history_id | INT | No | PK, IDENTITY |
| purchase_request_id | INT | No | FK |
| jobcard_id | INT | No | Denormalized |
| service_operation_id | INT | Yes | Denormalized |
| action_code | NVARCHAR(80) | No | e.g. PURCHASE_REQUEST_CREATED |
| old_status | NVARCHAR(30) | Yes | Previous request_status |
| new_status | NVARCHAR(30) | Yes | New request_status |
| changed_by_user_id | INT | No | Auth user |
| changed_at | DATETIME2(3) | No | Default SYSUTCDATETIME() |
| change_note | NVARCHAR(MAX) | Yes | Reason / note |

### Minimum History on Create (Future M26)
- action_code = PURCHASE_REQUEST_CREATED
- new_status = initial request_status

## Suggested Constraints (Future SQL)
- FK jobcard_id, service_operation_id, part_id
- CHECK requested_quantity > 0
- CHECK request_status enum (8 values)
- No physical DELETE — CANCELLED + is_active
- Indexes on jobcard_id, request_status, requested_at

## Identity Retrieval (Future)
Follow locked pattern: INSERT + fetch by business key or composite — no SCOPE_IDENTITY.

## Mission 25 Boundary
Plan only. No tables created.

## Final Data Model Decision
Purchase request + history tables with nullable supplier and informational cost fields only.

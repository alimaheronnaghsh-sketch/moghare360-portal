# SQL Implementation Plan

## Purpose
This document defines the SQL implementation plan for payment foundation.

## Critical Rule (Locked)

**No SQL execution in Mission 27.**

**SQL implementation deferred to Mission 28.**

Mission 27 creates zero SQL files and runs zero scripts against the database.

## Mission 28 Indicative SQL Deliverable
Single idempotent script (indicative path):
`public_html/sql/sqlserver/mission_28_payment_foundation.sql`

## Planned Objects (Mission 28)

### Tables
1. `dbo.erp_payments`
2. `dbo.erp_payment_history`

## Proposed Fields: dbo.erp_payments

| Field | Type (Suggested) | Nullable | Notes |
|-------|------------------|----------|-------|
| payment_id | INT | No | PK, IDENTITY |
| jobcard_id | INT | No | FK → dbo.erp_jobcards |
| customer_id | INT | Yes | Denormalized from JobCard |
| payment_type | NVARCHAR(30) | No | ADVANCE, PARTIAL, FULL, REFUND_PLACEHOLDER |
| payment_method | NVARCHAR(30) | No | CASH, CARD, BANK_TRANSFER, POS, OTHER |
| payment_amount | DECIMAL(18, 2) | No | Must be > 0 |
| currency_code | NVARCHAR(10) | No | e.g. IRR |
| payment_status | NVARCHAR(30) | No | DRAFT, RECEIVED, CANCELLED, REVERSED |
| payment_reference | NVARCHAR(100) | Yes | Receipt / ref number |
| payment_note | NVARCHAR(MAX) | Yes | Free text |
| received_by_user_id | INT | No | Staff who recorded payment |
| received_at | DATETIME2 | No | When payment received |
| created_at | DATETIME2 | No | Default SYSUTCDATETIME() |
| is_active | BIT | No | Default 1 |

## Proposed Fields: dbo.erp_payment_history

| Field | Type (Suggested) | Nullable | Notes |
|-------|------------------|----------|-------|
| history_id | INT | No | PK, IDENTITY |
| payment_id | INT | No | FK → erp_payments |
| jobcard_id | INT | No | Denormalized |
| action_code | NVARCHAR(80) | No | e.g. PAYMENT_CREATED |
| old_status | NVARCHAR(30) | Yes | Previous payment_status |
| new_status | NVARCHAR(30) | Yes | New payment_status |
| changed_by_user_id | INT | No | Auth user |
| changed_at | DATETIME2 | No | Default SYSUTCDATETIME() |
| change_note | NVARCHAR(MAX) | Yes | Reason / note |

## Suggested Constraints (Future SQL)
- FK jobcard_id → dbo.erp_jobcards
- FK payment_id on history
- CHECK payment_amount > 0
- CHECK payment_status enum (4 values)
- CHECK payment_type enum (4 values)
- CHECK payment_method enum (5 values)
- No physical DELETE

## Indexes (Planned)
- IX on jobcard_id
- IX on payment_status
- IX on received_at DESC
- IX on payment_id for history

## Identity Retrieval (Locked)
Follow project rule:
- INSERT + fetch by composite business key
- No SCOPE_IDENTITY, OUTPUT INSERTED, @@IDENTITY, IDENT_CURRENT

## Explicitly NOT in Mission 28 SQL (Locked)
- erp_invoices table
- General ledger / journal tables
- Tax tables
- Supplier AP tables
- Delivery release tables
- Writable outstanding_balance summary table

## Execution Policy (Mission 28)
- Manual SSMS execution only
- `USE [moghare360_ERP]`
- Idempotent IF OBJECT_ID / IF NOT EXISTS pattern

## Mission 27 Deliverable
This plan document only.

## Final SQL Plan Decision
Mission 27 = zero SQL. Mission 28 = erp_payments + erp_payment_history per field list above.

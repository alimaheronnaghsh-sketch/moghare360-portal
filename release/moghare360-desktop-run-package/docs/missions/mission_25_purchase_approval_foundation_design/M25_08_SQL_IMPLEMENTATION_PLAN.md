# SQL Implementation Plan

## Purpose
This document defines the SQL implementation plan for purchase request foundation.

## Critical Rule (Locked)

**No SQL execution in Mission 25.**

**SQL implementation deferred to Mission 26.**

Mission 25 creates zero SQL files and runs zero scripts against the database.

## Mission 26 Indicative SQL Deliverable
Single idempotent script (indicative path):
`public_html/sql/sqlserver/mission_26_purchase_request_foundation.sql`

## Planned Objects (Mission 26)

### Tables
1. `dbo.erp_purchase_requests`
2. `dbo.erp_purchase_request_history`

### Constraints (Planned)
- PK on purchase_request_id, history_id
- FK jobcard_id → dbo.erp_jobcards
- FK service_operation_id → dbo.erp_service_operations (nullable)
- FK part_id → dbo.erp_parts (nullable)
- CHECK requested_quantity > 0
- CHECK request_status in allowed enum (8 values)

### Indexes (Planned)
- IX on jobcard_id
- IX on request_status
- IX on requested_at DESC
- IX on purchase_request_id for history

### Seed Policy (Mission 26)
- No production seed data required
- CLI test creates one controlled test purchase request
- No supplier seed
- No finance seed

## Explicitly Not in Mission 26 SQL (Locked)
- erp_suppliers table
- erp_purchase_orders table
- Finance / AP / ledger tables
- Stock RECEIPT automation triggers
- Approval auto-rules stored procedures

## Identity Retrieval (Locked)
Follow project rule:
- INSERT + fetch by composite business key
- No SCOPE_IDENTITY, OUTPUT INSERTED, @@IDENTITY, IDENT_CURRENT

## Execution Policy (Mission 26)
- Manual SSMS execution only
- `USE [moghare360_ERP]`
- Idempotent IF OBJECT_ID / IF NOT EXISTS pattern
- Document execution in M26_90 test result

## Mission 25 Deliverable
This plan document only.

## Final SQL Plan Decision
Mission 25 = zero SQL. Mission 26 = two tables + constraints + indexes per M25_03 data model.

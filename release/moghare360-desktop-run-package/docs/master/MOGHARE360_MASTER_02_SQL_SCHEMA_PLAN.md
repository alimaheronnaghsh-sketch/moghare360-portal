# MOGHARE360 — Master 02 SQL Schema Plan

**Status:** Database layer planning only — Documentation only  
**SQL:** Not required — no executable SQL in this document

---

## Purpose

Plan the SQL Server schema domains for MOGHARE360 ERP. SQL execution is reserved for later controlled phases in SSMS. This document does not modify `moghare360_ERP` and does not create `.sql` files.

---

## Architecture Prerequisite

All persisted writes must follow:

**UI → Validation Engine → Workflow Engine → Database → Audit Log**

Validation must pass before INSERT/UPDATE. Workflow state must authorize transition before status-changing writes.

---

## Core Domains

### Customer

- Party identity, contact, national ID reference
- Intake channel, notes, satisfaction linkage
- Validation: national ID algorithm, mobile `09XXXXXXXXX`, Persian-only name

### Vehicle

- Plate (Iran standard), VIN (ISO 3779), engine/chassis numbers
- Brand (fixed list), model (cascading), class
- Binding to customer

### Contract

- Customer–vehicle–service terms
- Authorization mode, contract type
- Workflow-linked status

### JobCard

- Service operation unit
- Links customer, vehicle, contract, operations
- State driven by Workflow Engine

### Workflow

- State machine storage: DRAFT → SUBMITTED → UNDER_REVIEW → APPROVED → APPLIED → CLOSED
- Transition log, actor, timestamp, permission key

### Inventory

- Parts, stock pressure, reservations, purchase requests
- Usage tied to JobCard / service execution

### CRM

- Follow-ups, satisfaction records
- Post-delivery engagement

### Finance Preview

- Payment preview records — **not** official accounting
- No tax invoice tables in current product boundary

### HR

- Employees, contracts, attendance preview
- Internal admin scope

### Audit

- Cross-cutting `erp_*_audit` / history tables per module
- Immutable append-only log preferred for compliance review

---

## Required Validation Before Insert

| Domain | Pre-insert checks |
|--------|-------------------|
| Customer | National ID, mobile, Persian name |
| Vehicle | Plate, VIN, engine/chassis, brand/model/class |
| Contract | Customer + vehicle existence, workflow DRAFT |
| JobCard | Valid contract, workflow permission |
| Inventory | Stock rules, reservation conflicts |
| Finance Preview | Preview-only flag; not official ledger |
| All | Session, role, permission, CSRF on write routes |

---

## Required Audit Tables (Conceptual)

- `erp_workflow_transition_log`
- `erp_customer_audit`
- `erp_vehicle_audit`
- `erp_jobcard_audit`
- `erp_inventory_audit`
- `erp_finance_preview_audit`
- `erp_crm_audit`
- `erp_hr_audit`
- `erp_security_access_audit` (read-only security phase reference)

Each audit row: `actor_user_id`, `action`, `entity_type`, `entity_id`, `before_json`, `after_json`, `created_at`, `ip_safe`, `session_ref`

---

## Indexes and Constraints (Conceptual)

- **PK:** surrogate `BIGINT IDENTITY` or `UNIQUEIDENTIFIER` per table standard
- **UK:** national_id (customer), plate+VIN composite (vehicle), jobcard_number
- **FK:** enforce referential integrity Customer → Vehicle → Contract → JobCard
- **CK:** workflow state enum; finance_preview `is_official = 0`
- **IX:** workflow_state, created_at, customer_id, jobcard_id for reporting
- **No DROP** in migration scripts without owner approval

---

## SQL Execution Policy

- Scripts live under `public_html/sql/sqlserver/` when phases authorize creation
- Run manually in SSMS against `moghare360_ERP` on `.\SQLEXPRESS`
- Idempotent patterns: `IF NOT EXISTS` for tables/indexes
- Phase 1–15 baseline already applied; future schema extends via new phased SQL only

---

## Product Boundary

- Documentation only
- No SQL required in MASTER EXECUTION PACK
- No database schema change in this phase
- No official accounting activation
- No payment gateway/billing/tax integration created

---

**END OF SQL SCHEMA PLAN**

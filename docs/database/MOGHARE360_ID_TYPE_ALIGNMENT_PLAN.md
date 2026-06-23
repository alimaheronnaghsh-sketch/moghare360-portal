# MOGHARE360 — ID Type Alignment Plan

**Database:** MOGHARE360_ERP  
**Source:** PHASE 05 SSMS read-only discovery  
**Status:** Planning only — Documentation only

---

## Discovery Metric

| Metric | Value |
|--------|-------|
| Total dual int/bigint logical IDs | **10** |

---

## Dual-Type Logical ID List

Each logical ID name appears with **both `bigint` and `int`** column types across different tables in MOGHARE360_ERP:

| Logical ID | Types detected | Primary domains affected |
|------------|----------------|--------------------------|
| **customer_id** | bigint, int | Customer, CRM, Finance, JobCard |
| **entity_id** | bigint, int | Audit, Rule, cross-cutting references |
| **history_id** | bigint, int | Audit / History tables |
| **jobcard_id** | bigint, int | JobCard, Operation, Finance cost |
| **part_id** | bigint, int | Inventory, JobCard part usage |
| **purchase_request_id** | bigint, int | Inventory / Purchase |
| **stock_location_id** | bigint, int | Inventory / Stock |
| **stock_movement_id** | bigint, int | Inventory / Stock |
| **supplier_id** | bigint, int | Inventory / Purchase |
| **vehicle_id** | bigint, int | Vehicle, Customer bindings |

---

## Risk Analysis

### Mixed Prototype and ERP Evolution

- **Existing prototype tables** (core v0, early missions) often use **`INT`**
- **Newer ERP tables** (phase modules) often use **`BIGINT`**
- Same logical entity referenced with different types in parent/child tables

### Break Scenarios

| Layer | Failure mode |
|-------|--------------|
| SQL joins | Implicit conversion, plan regression |
| FK creation | SQL Server rejects type mismatch |
| PHP | Integer overflow, strict comparison failures |
| API DTOs | Serialization type assumptions |
| Reporting | UNION/join errors across modules |
| Future UI forms | Hidden field type mismatch |

---

## Locked Decisions

### Do not ALTER ID types yet

No `ALTER COLUMN` on primary or foreign key columns without owner-approved migration phase.

### Do not rebuild tables

Structure is advanced (96 tables, 77 FKs). Rebuild to unify types is forbidden.

### Do not create compatibility columns yet

No `_bigint` shadow columns, no parallel ID columns until canonical decision approved.

### First define canonical ID type per domain

| Domain | Recommended canonical for **new** columns | Existing state |
|--------|------------------------------------------|----------------|
| Identity / Access | Document per table | Likely INT |
| Customer / Vehicle | BIGINT preferred for new ERP FKs | Mixed |
| JobCard / Operation | BIGINT | Mixed with INT children |
| Inventory / Stock | BIGINT | Mixed |
| Finance Preview | BIGINT | Mixed |
| Audit / History | Match parent entity type | Mixed |

### Future SQL casting rules

**Future SQL must include explicit casting/compatibility rules only when approved** by ChatGPT after canonical map signoff. No ad-hoc `CAST` in application without documentation.

---

## Alignment Workflow (Before Any SQL)

1. Export PK/FK column types for all 10 logical IDs from SSMS
2. Build parent → child type matrix per relationship
3. Owner approves canonical type per domain
4. New FKs: match parent PK type exactly
5. Migration phase (if ever): separate approved phase with rollback plan

---

## Product Boundary

- **Do not alter ID types yet**
- No database schema change in this document
- No SQL execution

---

**END OF ID TYPE ALIGNMENT PLAN**

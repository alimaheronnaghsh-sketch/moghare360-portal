# MOGHARE360 — Database ID Type Alignment Gap

**Database:** MOGHARE360_ERP  
**Source:** PHASE 04 SSMS read-only discovery  
**Status:** Documentation only

---

## Discovery Metrics

| Metric | Value |
|--------|-------|
| ID type mismatch candidates | **52** |
| Logical IDs using both int and bigint | **10** |
| Critical table PK type rows | 49 |

---

## Risk Analysis

### R-01 — Old Prototype vs New ERP ID Types

- **Old controlled prototype tables** (core v0, early missions) often use **`INT`** identity primary keys
- **Newer ERP tables** (phase 1–15 modules) often use **`BIGINT`** identity primary keys
- Mixed types coexist in the same database without migration unify

### R-02 — Future Join and FK Extension Risk

If new SQL adds FK from BIGINT child to INT parent (or reverse):

- SQL Server may reject the FK definition
- Implicit conversion may cause performance issues
- Application PHP may truncate or overflow on large IDs

### R-03 — API DTO Assumption Risk

Future API layer may assume uniform `bigint` or `int` for all entity IDs. Mixed baseline breaks serialization contracts and client-side type handling.

---

## Logical IDs Using Both INT and BIGINT (10)

These represent **cross-table logical entity families** where PK/FK columns use inconsistent types. Examples likely include:

- User/staff references across `core_*` and `erp_*`
- Customer/vehicle IDs linking early and late tables
- JobCard references spanning operation and inventory modules

> Exact table/column list from user SSMS export should be attached in Phase 05 domain ownership map.

---

## ID Type Mismatch Candidates (52)

Broader set of columns flagged where:

- FK column type ≠ referenced PK type
- PK type differs from sibling tables in same domain
- History table PK type differs from parent operational table

**Do not change ID types yet. Do not alter tables yet.**

---

## Required Future Action

### Step 1 — Identify canonical ID type per domain

| Domain | Recommended canonical (new work) | Existing variance |
|--------|----------------------------------|-------------------|
| core_* / access | Document actual PK types | Likely INT |
| erp_customers, erp_vehicles | Document actual | INT or BIGINT |
| erp_jobcards, operations | Document actual | Likely BIGINT |
| erp_inventory / stock | Document actual | Mixed families |
| Finance preview | Document actual | Mixed |

### Step 2 — Map int-to-bigint coexistence

Create Phase 05 matrix:

- Parent table → PK type
- Child table → FK type
- Mismatch? Y/N
- Resolution: hold / new FK only on matching type / future migration (owner approval)

### Step 3 — Rules for new SQL

- New FKs must match parent PK type exactly
- New tables in ERP domains should prefer **BIGINT** unless extending INT parent
- No `ALTER COLUMN` on PK without explicit owner-approved migration phase

---

## Forbidden (This Phase)

- ALTER TABLE changing ID types
- CAST-heavy views as permanent workaround without documentation
- Assuming all IDs are INT or all BIGINT in application code

---

## Product Boundary

- Documentation only
- No database schema change

---

**END OF ID TYPE ALIGNMENT GAP**

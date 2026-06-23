# MOGHARE360 — Database Baseline Risk and Gap Notes

**Database:** MOGHARE360_ERP  
**Status:** Documentation only — No SQL required  
**Source:** User-provided SSMS inventory + phased development history

---

## Baseline Facts

1. **Existing database is not empty.** MOGHARE360_ERP contains 96 tables and 1224 columns across nine documented domains.
2. **Existing database already contains many ERP modules.** Customer, vehicle, job card, inventory, finance preview, CRM, HR, rule engine, soft-run, and commercial preview tables are all present.
3. **Current baseline appears to include both earlier controlled prototype tables and newer ERP/soft-run/commercial preview tables.** Naming prefixes (`core_*`, `erp_*`) and phased SQL history suggest layered evolution rather than a single greenfield design.

---

## Identified Risks

### R-01 — Duplicate Entities

**Risk:** Duplicate entities if future SQL is created without baseline alignment.

If a future phase creates new `erp_customers_v2` or parallel job card tables without reading this baseline, application code may write to the wrong table, orphan data, or break joins assumed by existing PHP helpers.

**Mitigation:** Always consult `MOGHARE360_DATABASE_DOMAIN_TABLE_MAP.md` before any `CREATE TABLE`. Perform explicit gap analysis.

---

### R-02 — Inconsistent ID Types

**Risk:** Inconsistent ID types between old `INT` tables and newer `BIGINT` tables.

Early core/bootstrap scripts may use `INT IDENTITY` while later phase scripts use `BIGINT`. Joins, ORM mappings, and PHP type handling may fail silently or overflow on large datasets.

**Mitigation:** Document PK/FK types per table in a future column-level inventory phase. Standardize new columns to `BIGINT` where extending existing BIGINT parents.

---

### R-03 — Finance Preview vs Official Accounting

**Risk:** Finance preview tables exist but **official accounting is not active.**

Tables such as `erp_payments`, `erp_payment_records`, and `erp_invoice_previews` support operational preview and costing. They must not be treated as a certified general ledger, tax ledger, or statutory accounting system.

**Mitigation:** Maintain product boundary labels in UI and docs. No official accounting activation without explicit owner phase.

---

### R-04 — Payment Records Without Gateway

**Risk:** Payment records exist but **payment gateway/billing/tax integration is not active.**

`erp_payments` and related tables may store manual or preview payment data. No live gateway, billing engine, or tax authority integration is assumed.

**Mitigation:** Do not wire payment tables to external gateways without dedicated security and compliance phase.

---

### R-05 — Mirror / Public Portal Data Residency

**Risk:** Mirror domain (`moghareh360.ir`) or **public customer portal** must not store business data.

Even if portal UI exists in legacy code, activation would duplicate or expose MOGHARE360_ERP data outside the local server boundary.

**Mitigation:** No public customer portal activation. Mirror remains display-only per master plan.

---

### R-06 — Overlapping Inventory Tables

**Risk:** Duplicate domain overlap between `erp_inventory_*` and `erp_stock_*` / `erp_parts` families.

Multiple table groups may represent evolutionary splits (inventory module vs parts foundation). Application code may reference one set while reports query another.

**Mitigation:** Map active write paths in a future application-to-table trace phase.

---

### R-07 — Soft Run / Commercial Preview Scope Creep

**Risk:** Soft-run and commercial preview tables (`erp_soft_run_*`, `erp_commercial_*`) mistaken for production SaaS infrastructure.

**Mitigation:** No production SaaS activation. These tables support pilot and demo readiness only.

---

## Required Next Checks (Not Done in Phase 02)

Before any incremental SQL, the following SSMS analyses should be performed **by User**:

| Check | Purpose |
|-------|---------|
| Foreign keys | Confirm referential integrity graph |
| Indexes | Identify missing performance indexes |
| Constraints | Document CHECK, UNIQUE, DEFAULT rules |
| Row counts | Distinguish seeded vs production-like data |
| Seed/reference data | Map bootstrap seeds vs operational rows |
| Duplicate domain overlap | Confirm which tables are actively written by PHP |

---

## Gap Analysis Priority

1. **FK graph** — highest priority before new relationships
2. **PK type consistency** — before cross-domain joins
3. **Active write path map** — before new columns on dormant tables
4. **Index review** — before scale testing
5. **Seed vs live data** — before demo package refresh

---

## Product Boundary

- Documentation only
- No database schema change
- No SQL execution
- No production SaaS activation
- No public customer portal activation
- No official accounting activation
- No payment gateway/billing/tax integration created

---

**END OF RISK AND GAP NOTES**

# MOGHARE360 — Database Overlap and Gap Risk

**Database:** MOGHARE360_ERP  
**Source:** PHASE 03 SSMS read-only discovery  
**Status:** Documentation only

---

## Overlap Discovery

| Metric | Value |
|--------|-------|
| Potential overlap tables | **52** |
| Total tables | 96 |
| Overlap ratio | ~54% flagged for review |

"Potential overlap" indicates tables that may serve similar domain purposes, share naming patterns, or represent evolutionary duplicates (e.g. `erp_inventory_*` vs `erp_stock_*`, multiple `*_history` tables per entity).

---

## Risk Register

### R-01 — Duplicate Domain Logic

**Risk:** Duplicate domain logic if new schema is created without alignment.

If Phase 04+ creates new tables without reviewing the 52 overlap candidates, application code may:

- Write to the wrong table family
- Split data across parallel structures
- Break reports that assume single source of truth

**Mitigation:** **No new table design before overlap and gap analysis.**

---

### R-02 — Legacy INT and Newer BIGINT IDs

**Risk:** Legacy `INT` IDs and newer `BIGINT` IDs coexist.

Phased SQL evolution (core v0 → mission foundations → phase 1–15) may have introduced mixed PK types. Joins, PHP type coercion, and future API serialization can fail or truncate.

**Mitigation:** Document PK types per table in Phase 04 gap analysis. Standardize new FKs to parent PK type.

---

### R-03 — Finance/Payment Preview Without Activation

**Risk:** Finance/payment preview tables exist but **official accounting** and **payment gateway** are not active.

Tables such as `erp_payments`, `erp_payment_records`, `erp_invoice_previews`, `erp_jobcard_cost_*` store preview/operational costing data. They must not be promoted to statutory accounting or live billing without dedicated compliance phases.

| Capability | Status |
|------------|--------|
| Finance preview tables | Present (structurally) |
| Official accounting | **Not active** |
| Payment gateway/billing/tax | **Not created** |

---

### R-04 — Structurally Present but Empty

**Risk:** Many tables are structurally present but empty.

~majority of operational ERP tables at 0–1 rows. Risk of:

- Assuming table is deprecated and dropping it
- Building new table for same purpose
- Skipping FK wiring because "no data yet"

**Mitigation:** Treat empty tables as **built but not operationally populated** or **soft-run candidates** (see row count profile).

---

### R-05 — Non-Parent Tables (66)

**Risk:** 66 tables without FK as parent may indicate incomplete graph or intentional leaf design — unclear without per-domain review.

**Mitigation:** Phase 04 domain FK audit.

---

## Overlap Categories (Conceptual)

| Category | Example pattern | Action |
|----------|-----------------|--------|
| Inventory dual families | `erp_inventory_*` + `erp_stock_*` | Map active write path |
| History proliferation | `*_history`, `*_change_history` | Confirm audit strategy |
| Preview vs operational | `*_previews`, `*_snapshots` | Label as preview-only |
| Soft-run vs ERP core | `erp_soft_run_*` vs `erp_jobcards` | Keep pilot scope separate |

---

## Required Next Phase

**PHASE 04 — DATABASE GAP ANALYSIS AND CONTROLLED SQL ROADMAP**

Phase 04 must deliver:

1. Per-domain overlap resolution (which table is authoritative)
2. PK type inventory
3. Missing FK identification
4. Controlled SQL roadmap (incremental only)
5. Explicit "no new table" list for domains already covered

---

## Decision

**No new table design before overlap and gap analysis.**

This decision is locked until Phase 04 completes and ChatGPT approves SQL roadmap.

---

## Product Boundary

- Documentation only
- No database schema change
- No production SaaS activation
- No public customer portal activation
- No official accounting activation
- No payment gateway/billing/tax integration created

---

**END OF OVERLAP AND GAP RISK**

# MOGHARE360 — Database Relationship Health

**Database:** MOGHARE360_ERP  
**Source:** PHASE 03 SSMS read-only discovery  
**Status:** Documentation only

---

## Discovery Metrics

| Metric | Value |
|--------|-------|
| Foreign keys detected | **77** |
| Tables without primary key | **0** |
| Tables without foreign keys as parent | **66** |
| Total tables (baseline) | 96 |

---

## Relationship Graph Summary

Of 96 tables:

- **30 tables** act as FK parents (referenced by at least one foreign key)
- **66 tables** do not appear as FK parents (leaf tables, history tables, or standalone entities)

This is expected in ERP schemas where history/audit tables are children-only and many operational tables are terminal nodes. However, it warrants domain-level FK coverage review before new relationship design.

---

## Risk Classification

### PASS — No Table Missing Primary Key

**Tables without primary key: 0**

Every table in MOGHARE360_ERP has a defined primary key. This eliminates the highest-severity structural integrity gap. Entity identity is enforceable at the database level for all 96 tables.

---

### WATCH — Many Tables Are Not FK Parents

**Tables without foreign keys as parent: 66**

| Concern | Detail |
|---------|--------|
| Isolated domains | Some table groups may lack upward FK linkage to master entities |
| History tables | Expected — `*_history` tables are typically child-only |
| Orphan risk on delete | Without FK from child to parent, application-layer integrity depends on PHP validation |
| Reporting joins | Queries may require implicit joins not enforced by schema |

**Status:** WATCH — not a failure, but requires domain inspection before adding parallel tables.

---

### ACTION LATER — Inspect FK Coverage by Domain Before New SQL Design

Before any incremental SQL phase:

1. Map FK graph per domain (Customer, JobCard, Inventory, Finance, etc.)
2. Identify child tables missing expected parent FK
3. Confirm whether absence is intentional (audit/history) or a gap

---

## Required Future Checks

| Check | Purpose |
|-------|---------|
| **FK trust status** | Detect untrusted FKs after bulk load or replication |
| **Disabled FK status** | Find FKs disabled for maintenance and never re-enabled |
| **Orphan risk** | Sample child rows with no matching parent |
| **Cascade behavior** | Document ON DELETE / ON UPDATE rules per FK |
| **Cross-domain relationships** | e.g. JobCard → Customer, Payment → JobCard, Part Usage → Inventory |

---

## Domain FK Expectations (Conceptual)

| Domain | Expected parent tables | Typical children |
|--------|------------------------|------------------|
| Customer / Vehicle | `erp_customers`, `erp_vehicles` | bindings, phones, history |
| JobCard | `erp_jobcards` | operations, part usage, QC |
| Inventory | `erp_parts`, `erp_stock_locations` | movements, reservations |
| Finance Preview | `erp_jobcards`, `erp_payments` | cost lines, payment records |
| Access | `core_users`, `core_roles` | role_permissions, user_roles |

Gaps between expected and actual FK graph should be documented in **PHASE 04 — DATABASE GAP ANALYSIS AND CONTROLLED SQL ROADMAP**.

---

## Conclusion

Relationship health is **structurally sound** (0 PK gaps, 77 FKs) but **graph coverage is incomplete** from a parent-table perspective (66 non-parent tables). This supports controlled incremental design — not greenfield rebuild.

---

## Product Boundary

- Documentation only
- No database schema change
- No SQL execution

---

**END OF RELATIONSHIP HEALTH**

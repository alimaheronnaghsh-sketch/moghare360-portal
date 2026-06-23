# MOGHARE360 — Database Constraint and Index Health

**Database:** MOGHARE360_ERP  
**Source:** PHASE 03 SSMS read-only discovery  
**Status:** Documentation only

---

## Discovery Metrics

| Constraint / Index Type | Count |
|-------------------------|-------|
| Primary / unique constraints | **105** |
| Check constraints | **31** |
| Default constraints | **301** |
| Index inventory | **291** |

---

## Constraint Health Summary

### Primary and Unique Constraints (105)

- Enforce entity identity and business uniqueness (e.g. permission keys, role codes)
- Align with Phase 03 finding: **0 tables without primary key**
- Unique constraints likely cover alternate keys (codes, reference numbers)

### Check Constraints (31)

- Enforce enumerated values, status ranges, or simple domain rules at DB level
- **Limited relative to application validation scope** — many business rules (national ID algorithm, Iran plate, VIN ISO 3779, Persian-only name) are expected in Validation Engine, not CHECK constraints

### Default Constraints (301)

- Broad coverage — nearly 3 defaults per table on average across 96 tables
- Reduces NULL insertion errors for `created_at`, status defaults, flags
- Supports consistent row shape for seed and application inserts

---

## Index Health Summary

### Index Inventory (291)

- **~3 indexes per table** on average — healthy for OLTP ERP workload
- Likely includes clustered PK indexes plus nonclustered FK/lookup indexes
- Supports join paths identified by 77 foreign keys

---

## Risk Classification

### PASS — Indexes and Defaults Exist Broadly

| Indicator | Assessment |
|-----------|------------|
| 291 indexes | PASS — adequate structural indexing |
| 301 defaults | PASS — column defaults widely applied |
| 105 PK/unique | PASS — identity and uniqueness enforced |

---

### WATCH — Check Constraints May Not Cover All Validation Rules

| DB constraint count | Application validation scope |
|---------------------|------------------------------|
| 31 CHECK constraints | Validation Engine covers customer, vehicle, media, diagnostics rules |

Database CHECK constraints are a **subset** of full business validation. Application must not assume DB enforces all rules.

Examples likely **not** in CHECK constraints:

- National ID Iran algorithm
- Mobile `09XXXXXXXXX`
- Persian-only name
- Iran plate standard
- VIN ISO 3779
- **Camera direct only** / **No upload bypass** (application/media layer)

---

### ACTION LATER — Compare Database Constraints with Validation Engine Requirements

Before implementing Validation Engine:

1. Export full CHECK constraint definitions from SSMS
2. Map each to Validation Engine rule
3. Identify rules that must remain application-only
4. Identify gaps where DB CHECK could safely reinforce (status enums)

---

## Architecture Alignment

All writes must follow:

**UI → Validation Engine → Workflow Engine → Database → Audit Log**

Database constraints are the **last line of defense**, not the primary validation layer. CHECK constraints (31) complement but do not replace Validation Engine and Workflow Engine.

| Layer | Role |
|-------|------|
| UI | Input collection only |
| Validation Engine | Business rules (customer, vehicle, media) |
| Workflow Engine | State authorization |
| Database | PK, FK, CHECK, DEFAULT enforcement |
| Audit Log | Immutable action record |

---

## Index Maintenance Notes (Future)

| Future check | Purpose |
|--------------|---------|
| Duplicate/redundant indexes | Reduce write overhead |
| Missing FK indexes | Child FK columns without supporting index |
| Filtered indexes | Soft-delete or status-filtered queries |
| Index fragmentation | After bulk seed loads |

---

## Conclusion

Constraint and index health is **strong at the structural level** (105 PK/unique, 301 defaults, 291 indexes). CHECK constraint coverage is **narrow** relative to full Validation Engine scope — expected and acceptable if application layer enforces rules.

---

## Product Boundary

- Documentation only
- No database schema change
- No SQL execution

---

**END OF CONSTRAINT AND INDEX HEALTH**

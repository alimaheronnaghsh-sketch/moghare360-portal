# MOGHARE360 — SQL Change Plan Candidates

**Database:** MOGHARE360_ERP  
**Source:** PHASE 05 SSMS read-only discovery  
**Status:** Planning candidates only — **not SQL tasks**

---

## Candidate Summary

| Classification | Count | Meaning |
|----------------|-------|---------|
| **NO_IMMEDIATE_SQL_CHANGE** | **34** | Stable — no SQL action until later phased need |
| **REVIEW_EMPTY_ISOLATED_TABLE** | **44** | Empty + no FK links — purpose review required |
| **REVIEW_EMPTY_TABLE_PURPOSE** | **2** | Empty but related — purpose review required |
| **REVIEW_ISOLATED_POPULATED_TABLE** | **16** | Has rows but isolated — review before expansion |

**Total classified:** 96 tables

---

## Interpretation

### NO_IMMEDIATE_SQL_CHANGE: 34

**34 tables require no immediate SQL change.**

Typically includes:

- Seeded Identity / Access tables (roles, permissions, departments)
- Populated reference data with healthy FKs
- Tables with correct structure serving current demo/soft-run scope

**Action:** Monitor only. Reclassify if gap analysis or application changes create new requirements.

---

### REVIEW_EMPTY_ISOLATED_TABLE: 44

**44 empty isolated tables require purpose review before any SQL.**

"Isolated" = no or minimal FK linkage in discovery graph. "Empty" = 0 rows.

| Risk | Mitigation |
|------|------------|
| Mistaken for deprecated | Classify before any DROP consideration |
| Mistaken for missing feature | Confirm against `app/modules/` scaffold |
| Blind seeding | Forbidden until purpose = required |

**Action:** Assign each table: required / optional / preview-only / deprecated-hold / future (per Phase 04 empty table gap).

---

### REVIEW_EMPTY_TABLE_PURPOSE: 2

**2 empty but related tables require purpose review.**

These tables have FK relationships to populated peers but remain empty — suggesting incomplete workflow path rather than isolation.

| Likely pattern | Example domains |
|----------------|-----------------|
| Child table never written | History detail, schedule line |
| Parent populated, child not | CRM schedule vs record |

**Action:** Trace application write path in future phase; no SQL until path confirmed.

---

### REVIEW_ISOLATED_POPULATED_TABLE: 16

**16 isolated populated tables require review before expansion.**

Have rows (seed/demo) but weak cross-domain FK integration in discovery.

| Risk | Detail |
|------|--------|
| Expansion without FK wiring | New columns/FKs on wrong graph |
| Duplicate logic | Parallel writes to related domain table |
| Demo data mistaken for production | Soft-run/commercial readiness tables |

**Action:** Confirm authoritative role before incremental SQL or application expansion.

---

## Locked Decisions

| Decision | Rule |
|----------|------|
| Review candidates are **not SQL tasks** | Documentation classification only |
| **Empty does not mean useless** | Structure is intentional |
| **Isolated does not mean wrong** | Leaf/history tables expected |
| **No deletion** | No DROP in gap phases |
| **No rebuild** | No greenfield |
| **No blind seeding** | Classification first |
| **No duplicate table creation** | Use ownership map |

---

## Relationship to Controlled SQL Roadmap

Phase 04 roadmap steps 1–2 map directly to this candidate list:

1. Domain ownership confirmation → resolves 38 ambiguous + 63 heuristic rows
2. Empty table classification → resolves 44 + 2 review-empty candidates
3. Later steps → may reclassify 16 isolated-populated and authorize incremental SQL

---

## Product Boundary

- No executable SQL
- No production SaaS activation
- No payment gateway/billing/tax integration created

---

**END OF SQL CHANGE PLAN CANDIDATES**

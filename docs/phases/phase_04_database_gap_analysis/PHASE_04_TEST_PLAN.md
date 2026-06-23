# PHASE 04 — Database Gap Analysis and Controlled SQL Roadmap — Test Plan

**SQL:** No SQL required

---

## Test Cases

### TC-01 — Phase Documentation (5 files)

`PHASE_04_SCOPE.md`, `BOUNDARY.md`, `TEST_PLAN.md`, `SIGNOFF.md`, `VALIDATION_RESULT.md`

### TC-02 — Gap Analysis Documents (8 files)

All listed `docs/database/MOGHARE360_*` and `MOGHARE360_CONTROLLED_SQL_ROADMAP.md`

### TC-03 — Discovery Metrics

| Metric | Expected |
|--------|----------|
| Empty operational tables | 46 |
| ID type mismatch candidates | 52 |
| Logical IDs int+bigint | 10 |
| Disabled/untrusted FKs | 0 |
| Critical validation columns | 32 |
| Duplicate domain candidates | 63 |

### TC-04 — Heuristic Examples Documented

- `core_departments` incorrectly classified as Part
- `erp_hr_employment_contracts` classified as Contract but HR employment

### TC-05 — Required Phrases and Forbidden Changes

No SQL/PHP/public_html changes; product boundary phrases present.

---

**END OF TEST PLAN**

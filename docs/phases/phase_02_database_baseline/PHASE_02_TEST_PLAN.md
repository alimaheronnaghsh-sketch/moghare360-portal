# PHASE 02 — Database Baseline Documentation — Test Plan

**SQL:** No SQL required

---

## Test Objective

Verify Phase 02 created complete database baseline documentation within allowed scope only, with no schema or runtime impact.

---

## Test Cases

### TC-01 — Phase Documentation Exists

| File | Expected |
|------|----------|
| `PHASE_02_SCOPE.md` | Present |
| `PHASE_02_BOUNDARY.md` | Present |
| `PHASE_02_TEST_PLAN.md` | Present |
| `PHASE_02_SIGNOFF.md` | Present |
| `PHASE_02_VALIDATION_RESULT.md` | Present |

### TC-02 — Database Baseline Documents Exist

| File | Expected |
|------|----------|
| `MOGHARE360_DATABASE_BASELINE_SUMMARY.md` | Present |
| `MOGHARE360_DATABASE_DOMAIN_TABLE_MAP.md` | Present |
| `MOGHARE360_DATABASE_BASELINE_RISK_AND_GAP_NOTES.md` | Present |
| `MOGHARE360_DATABASE_BASELINE_DECISION.md` | Present |

### TC-03 — Summary Content

| Check | Expected |
|-------|----------|
| Database name `MOGHARE360_ERP` | Documented |
| Schema `dbo` | Documented |
| 96 detected tables | Documented |
| 1224 detected columns | Documented |
| Warning against duplicate schema | Documented |

### TC-04 — Domain Table Map

| Domain count | Expected |
|--------------|----------|
| Identity / Access / Security | 16 tables |
| Customer / Intake / Contract / Vehicle | 11 tables |
| JobCard / Service / Operation / QC / Delivery | 15 tables |
| Inventory / Parts / Supplier / Purchase | 13 tables |
| Finance Preview / Payment / Invoice | 11 tables |
| CRM / Customer Experience / Upsell | 6 tables |
| HR | 7 tables |
| Rule Engine / Workflow Decisions | 4 tables |
| Reporting / KPI / Soft Run / Commercial Preview | 13 tables |

**Total mapped:** 96 tables

### TC-05 — Forbidden Changes Absent

| Check | Expected |
|-------|----------|
| SQL scripts created | No |
| Database modified | No |
| PHP modified | No |
| `public_html` modified | No |
| Release packages modified | No |

### TC-06 — Required Phrases

- MOGHARE360_ERP
- 96 detected tables
- 1224 detected columns
- Do not rebuild database from scratch
- Future SQL must be controlled, incremental, and based on the current MOGHARE360_ERP baseline
- No SQL required
- No production SaaS activation
- No public customer portal activation
- No official accounting activation
- No payment gateway/billing/tax integration created

---

## Pass Criteria

All TC-01 through TC-06 pass. Git status shows only `docs/phases/phase_02_database_baseline/` and `docs/database/` as new.

---

**END OF TEST PLAN**

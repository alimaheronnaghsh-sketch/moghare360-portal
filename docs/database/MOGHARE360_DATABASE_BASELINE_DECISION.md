# MOGHARE360 — Database Baseline Decision

**Database:** MOGHARE360_ERP  
**Date:** 2026-06-22  
**Status:** Locked baseline decision — Documentation only

---

## Decision Summary

Based on user-provided SSMS inventory (96 tables, 1224 columns) and phased ERP development history, the following decisions are **locked** for all future MOGHARE360 work until superseded by explicit owner approval.

---

## Decision 1 — No Greenfield Rebuild

**Do not rebuild database from scratch.**

MOGHARE360_ERP is an operational baseline with extensive existing schema. Dropping and recreating would destroy seeded roles, permissions, workflow rules, and phased module data.

---

## Decision 2 — No Duplicate Domain Tables

**Do not create new duplicate customer, vehicle, jobcard, inventory, finance, CRM, HR, or workflow tables before gap analysis.**

Before any `CREATE TABLE`, teams must:

1. Read `MOGHARE360_DATABASE_DOMAIN_TABLE_MAP.md`
2. Read `MOGHARE360_DATABASE_BASELINE_RISK_AND_GAP_NOTES.md`
3. Confirm the entity does not already exist under a different name
4. Document the specific gap that requires a new object

---

## Decision 3 — Controlled Incremental SQL

**Future SQL must be controlled, incremental, and based on the current MOGHARE360_ERP baseline.**

Properties of acceptable future SQL:

- Idempotent where possible (`IF NOT EXISTS`)
- Extends existing tables (new columns, indexes, FKs) preferred over parallel tables
- Named and versioned per phase (e.g. `phase_XX_*.sql`)
- Reviewed against domain map and risk notes
- No `DROP` without owner approval

---

## Decision 4 — User-Only SQL Execution

**SQL execution remains User-only in SSMS.**

- Target database: **MOGHARE360_ERP**
- Target server: `.\SQLEXPRESS` (local)
- **Cursor must not execute SQL**
- ChatGPT defines scripts; User runs them; results reported back for validation

---

## Decision 5 — Product Boundaries Unchanged

Database documentation does not activate:

- **No production SaaS activation**
- **No public customer portal activation**
- **No official accounting activation**
- **No payment gateway/billing/tax integration created**

Existing finance and payment tables remain preview/operational scope only.

---

## Decision 6 — Architecture Alignment

Application writes must continue to follow:

**UI → Validation Engine → Workflow Engine → Database → Audit Log**

New tables or columns must integrate with validation and workflow plans, not bypass them.

---

## Approval Chain

| Step | Actor |
|------|-------|
| Gap identified | ChatGPT phase definition |
| SQL drafted | ChatGPT or authorized phase doc |
| SQL executed | User in SSMS |
| Result validated | User → ChatGPT |
| Code changes | Cursor (only when phased and approved) |

---

## Related Documents

- `MOGHARE360_DATABASE_BASELINE_SUMMARY.md`
- `MOGHARE360_DATABASE_DOMAIN_TABLE_MAP.md`
- `MOGHARE360_DATABASE_BASELINE_RISK_AND_GAP_NOTES.md`
- `docs/control/MOGHARE360_EXECUTION_CONTROL_REGISTRY.md`

---

**END OF BASELINE DECISION**

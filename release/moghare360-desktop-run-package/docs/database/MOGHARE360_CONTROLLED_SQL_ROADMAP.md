# MOGHARE360 — Controlled SQL Roadmap

**Database:** MOGHARE360_ERP  
**Status:** SQL roadmap only — **No executable SQL in this phase**  
**SQL:** No SQL required for Cursor

---

## Roadmap Purpose

Define the **order and gates** for future controlled SQL work against MOGHARE360_ERP. This document is a **roadmap only** — not executable SQL, not a migration script, not authorization to run DDL.

---

## Prerequisites (Complete Before Any SQL)

| Phase | Document | Status |
|-------|----------|--------|
| 02 | Baseline inventory | ✅ |
| 03 | Structure health | ✅ |
| 04 | Gap analysis | ✅ |
| 05 | Domain ownership map + SQL change plan | → NEXT |

---

## Future SQL Order

### 1. Domain Ownership Confirmation

- Resolve 63 duplicate domain candidates (manual review)
- Fix heuristic false positives (`core_departments`, `erp_hr_employment_contracts`)
- Publish authoritative table per entity (Phase 05)

### 2. Empty Table Classification

- Classify 46 empty operational tables: required / optional / preview-only / deprecated / future
- **No deletion. No rebuild. No blind seeding.**

### 3. ID Type Alignment Decision

- Document canonical PK type per domain
- Map 10 logical IDs with int+bigint coexistence
- Rule: new FKs match parent PK type; no ALTER yet without owner approval

### 4. FK Relationship Gap Review

- Per-table FK coverage (96 rows) reviewed by domain
- Identify missing FKs worth adding incrementally
- Confirm cascade behavior before any FK ADD

### 5. Validation Constraint Comparison

- Map 32 critical validation columns to Validation Engine rules
- Compare with 31 CHECK constraints
- Add CHECK only for safe status enums — not for National ID/VIN/plate algorithms

### 6. Index Review for Operational Queries

- Review 291 indexes for redundancy and gaps
- Prioritize indexes on FK columns used in workshop/CRM/report queries
- No index DROP without owner approval

### 7. Controlled Incremental SQL Package

- ChatGPT authors idempotent scripts per approved gap only
- Scripts stored under `sql/` or `public_html/sql/sqlserver/` when phase authorizes **file creation**
- Naming: `phase_XX_gap_YY_description.sql`
- **No duplicate tables. No rebuild.**

### 8. SSMS Execution by User Only

- User runs approved SQL in SSMS against **MOGHARE360_ERP** on `.\SQLEXPRESS`
- **Cursor must not execute SQL**
- One script batch at a time with verification

### 9. Post-SQL Verification

- Re-run FK trust check (expect 0 disabled/untrusted)
- Row count spot check
- Application smoke test via `tools/test-phase-*.php` when applicable
- Update gap documentation

---

## Forbidden in All SQL Phases

| Forbidden | Reason |
|-----------|--------|
| **No rebuild** | 96 tables, 77 FKs — destructive |
| **No duplicate tables** | 63 overlap candidates unresolved |
| **No destructive SQL** | No DROP TABLE/COLUMN without owner approval |
| **No production activation** | SaaS, portal, accounting, payment gateway |
| **No Cursor SQL execution** | User + SSMS only |
| Blind seeding of 46 empty tables | Classification required first |
| ID type ALTER without migration plan | 52 mismatch candidates |

---

## SQL Package Content Rules (Future)

When executable SQL is authorized in a later phase:

```
-- Phase: XX
-- Gap: YY
-- Target: MOGHARE360_ERP
-- Idempotent: YES
-- Destructive: NO
-- Owner approval: required
```

- `IF NOT EXISTS` for CREATE
- No `DROP` without explicit approval line in phase prompt
- FK adds must match ID types per alignment decision

---

## Architecture Alignment

All application writes after schema changes must still follow:

**UI → Validation Engine → Workflow Engine → Database → Audit Log**

Schema changes do not bypass Validation Engine or Workflow Engine.

---

## Product Boundary

- Controlled SQL roadmap only
- No executable SQL script in Phase 04
- No SQL execution
- No production SaaS activation
- No public customer portal activation
- No official accounting activation
- No payment gateway/billing/tax integration created

---

**END OF CONTROLLED SQL ROADMAP**

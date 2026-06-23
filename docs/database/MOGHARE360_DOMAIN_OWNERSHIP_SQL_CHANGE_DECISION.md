# MOGHARE360 — Domain Ownership SQL Change Decision

**Database:** MOGHARE360_ERP  
**Date:** 2026-06-23  
**Phase:** PHASE 05 — Domain Ownership Map and SQL Change Plan  
**Status:** Locked decision — Documentation only

---

## Executive Summary

Phase 05 assigns proposed domain ownership to 96 tables, documents 38 ambiguous rows, 10 dual-type logical IDs, 33 cross-domain FKs for review, and SQL change candidates (34 no-immediate-change). **No SQL is authorized by this phase.**

---

## Locked Decisions

### Do not create SQL yet

Phase 05 completes planning. Executable SQL packages require Phase 06+ and ChatGPT approval.

### Do not rebuild database from scratch

96 tables, 77 FKs, 0 PK gaps — structure is production-grade; data is not.

### Do not create duplicate tables

Ownership map defines authoritative tables per domain. No parallel `erp_customers_v2` or stock ledger duplicates.

### Do not alter ID types yet

10 dual int/bigint logical IDs documented in `MOGHARE360_ID_TYPE_ALIGNMENT_PLAN.md`. No `ALTER COLUMN` until owner-approved migration phase.

### Do not delete empty tables

44 `REVIEW_EMPTY_ISOLATED_TABLE` candidates are not deletion targets.

### Do not seed empty tables blindly

Classification required: required / optional / preview-only / deprecated-hold / future.

---

## Future SQL Gates (All Required)

Future SQL must wait for:

1. **Canonical domain ownership approval** — 38 ambiguous rows + owner signoff on `MOGHARE360_DOMAIN_OWNERSHIP_MAP.md`
2. **Canonical ID type decision** — per-domain BIGINT/INT policy for 10 logical IDs
3. **Cross-domain FK ownership review** — 33 CROSS_DOMAIN_FK_REVIEW relationships
4. **Empty/isolated table purpose decision** — 44 + 2 + 16 candidate tables
5. **Validation Engine comparison** — 32 critical columns vs DB CHECK constraints
6. **Controlled SQL package approval** — ChatGPT-authored idempotent scripts only

---

## SQL Execution Authority

| Actor | Role |
|-------|------|
| ChatGPT | Approves ownership map, ID policy, and SQL packages |
| Cursor | **Must not execute SQL** |
| User | **Executes SQL only in SSMS** when ChatGPT provides final approved SQL |

Target: **MOGHARE360_ERP** on DESKTOP-U1P34B8\SQLEXPRESS

---

## Architecture Reminder

All application writes:

**UI → Validation Engine → Workflow Engine → Database → Audit Log**

Domain ownership and SQL changes do not bypass Validation Engine or Workflow Engine.

---

## Next Phase

**PHASE 06 — CANONICAL DOMAIN MODEL AND MODULE CONTRACT PLAN**

Phase 06 must deliver:

- Canonical domain model (approved ownership)
- Module contracts mapping `app/modules/*` to authoritative tables
- API and validation boundaries per domain
- Still documentation-only unless later phases authorize implementation

---

## Product Boundary

- **No production SaaS activation**
- **No public customer portal activation**
- **No official accounting activation**
- **No payment gateway/billing/tax integration created**

---

## Related Documents

- `MOGHARE360_DOMAIN_OWNERSHIP_MAP.md`
- `MOGHARE360_DOMAIN_OWNERSHIP_SUMMARY.md`
- `MOGHARE360_AMBIGUOUS_TABLE_OWNERSHIP_REVIEW.md`
- `MOGHARE360_ID_TYPE_ALIGNMENT_PLAN.md`
- `MOGHARE360_CROSS_DOMAIN_FK_REVIEW.md`
- `MOGHARE360_SQL_CHANGE_PLAN_CANDIDATES.md`
- `MOGHARE360_CONTROLLED_SQL_ROADMAP.md`

---

**END OF DOMAIN OWNERSHIP SQL CHANGE DECISION**

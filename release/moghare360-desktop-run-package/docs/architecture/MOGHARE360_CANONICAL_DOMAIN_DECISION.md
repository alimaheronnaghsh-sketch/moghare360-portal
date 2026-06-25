# MOGHARE360 — Canonical Domain Decision

**Database:** MOGHARE360_ERP  
**Date:** 2026-06-23  
**Phase:** PHASE 06 — Canonical Domain Model and Module Contract Plan  
**Status:** Locked planning decision — Documentation only

---

## Decision Summary

The **canonical domain model** and **module contract matrix** defined in Phase 06 are accepted as the **planning baseline** for all future MOGHARE360 ERP implementation.

---

## Accepted Artifacts

| Document | Role |
|----------|------|
| `MOGHARE360_CANONICAL_DOMAIN_MODEL.md` | 12-domain ownership and responsibilities |
| `MOGHARE360_MODULE_CONTRACT_MATRIX.md` | Per-module gates, tables, readiness |
| `MOGHARE360_MODULE_BOUNDARY_RULES.md` | Cross-module write prohibitions |
| `MOGHARE360_CROSS_DOMAIN_INTERACTION_RULES.md` | 33 cross-domain FK governance |
| `MOGHARE360_CANONICAL_ID_POLICY_DRAFT.md` | ID type draft (not SQL) |
| `MOGHARE360_VALIDATION_WORKFLOW_AUDIT_CONTRACT.md` | Engine contract |

---

## Locked Prohibitions

| Prohibition | Status |
|-------------|--------|
| **Do not create SQL yet** | Until Phase 07+ and ChatGPT approval |
| **Do not alter ID types yet** | Draft policy only |
| **Do not delete empty tables** | 44+ empty candidates classified, not dropped |
| **Do not seed empty tables blindly** | Classification required |
| **Do not activate official accounting** | Finance Preview only |
| **Do not activate payment gateway** | Preview records only |
| **Do not activate public customer portal** | Local ERP only |
| **Do not activate production SaaS behavior** | Soft-run/commercial preview only |

---

## Implementation Rule

**Future implementation must follow canonical module contracts.**

- `app/modules/{domain}/` implements owning module service
- `app/validation/` implements Validation Engine contract
- `app/workflow/` implements Workflow Engine contract
- No module writes another module's owned tables directly
- Legacy `public_html/` changes only in explicitly scoped phases

---

## Architecture Lock

**UI → Validation Engine → Workflow Engine → Database → Audit Log**

**Camera direct only** · **No upload bypass**

---

## Next Phase

**PHASE 07 — VALIDATION RULE MATRIX AND WORKFLOW CONTRACT LOCK**

Phase 07 must:

1. Expand validation rules to full rule matrix per domain
2. Lock workflow transition permissions per entity
3. Map validation rules to Phase 04 critical columns (32)
4. Still documentation-only unless later phases authorize code

---

## SQL Execution (Unchanged)

| Actor | Role |
|-------|------|
| ChatGPT | Approves SQL after contracts locked |
| Cursor | Must not execute SQL |
| User | SSMS only on approved scripts |

---

## Product Boundary

- Canonical domain planning only
- No database schema change
- No backend/frontend implementation in Phase 06

---

**END OF CANONICAL DOMAIN DECISION**

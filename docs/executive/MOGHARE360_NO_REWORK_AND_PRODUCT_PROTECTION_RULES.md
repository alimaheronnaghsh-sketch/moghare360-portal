# MOGHARE360 — No Rework and Product Protection Rules

**Status:** LOCKED  
**Date:** 2026-06-23

---

## NO REWORK RULE

**Do not rebuild existing completed foundations.**

The following are **protected** — extend only via explicit phased scope; no greenfield rewrite:

| Foundation | Status |
|------------|--------|
| Customer Core | Protected |
| Vehicle Core | Protected |
| JobCard Foundation | Protected |
| Operation Engine | Protected |
| Inventory / Purchase Foundation | Protected |
| Finance Preview | Protected |
| CRM Foundation | Protected |
| HR Foundation | Protected |
| Workflow Foundation | Protected |
| Permission Foundation | Protected |
| Audit Foundation | Protected |
| Release Package | Protected |
| Security Audit | Protected |
| Deployment Plan | Protected |
| Soft Run / Pilot Workspace | Protected |

---

## Protection Principles

1. **No duplicate tables** — use MOGHARE360_ERP baseline (96 tables)
2. **No rebuild database from scratch**
3. **No rewrite** of `staff-auth.php`, `access-control.php`, permission model without explicit mission
4. **Incremental change only** — aligned with domain ownership map
5. **Documentation phases 01–09** remain valid reference; Phase 10 PHP path cancelled

---

## Architecture Lock

All new operational work must follow:

**UI → Validation Engine → Workflow Engine → Database → Audit Log**

---

## Media Lock

- **Camera direct only**
- **No upload bypass**

---

## Activation Gates

| Capability | Gate |
|------------|------|
| Public customer portal | **PHASE 22 approval only** |
| Official accounting | **PHASE 23 approval only** |
| Payment gateway / billing / tax | **Not authorized** in roadmap lock |
| Production SaaS | **No production SaaS activation** |

---

## Data Residency Lock

- **Local laptop server is the system of record**
- **All data lives only on local laptop server**
- **moghareh360.ir is Mirror Only** — no data, files, or business logic on domain
- **No cloud database**
- **No host-side customer data**

---

## Phase 10 Cancellation

**PHASE 10 read-only architecture implementation was not executed. PHASE 10 is cancelled before execution.**

Rework is **not** required for Phase 10 cancellation — no PHP was shipped.

---

## Cursor / ChatGPT Rule

- Cursor implements only current authorized phase
- ChatGPT approves scope before implementation and commit
- **Do not alter ID types yet** unless a future phase explicitly authorizes

---

**END OF NO REWORK AND PRODUCT PROTECTION RULES**

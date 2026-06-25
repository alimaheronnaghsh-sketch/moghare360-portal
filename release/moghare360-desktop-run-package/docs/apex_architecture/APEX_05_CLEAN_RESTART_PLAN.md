# APEX 05 — Clean Restart Plan

## Purpose

ApexMahinERP adopts a **clean restart** discipline for physical data design. No database tables, SQL scripts, or migrations may be created until architecture and ownership are formally approved.

This plan converts the official architecture declaration into an ordered execution sequence.

---

## Clean Restart Sequence (Locked)

### Step 1 — Freeze Architecture

**Status: IN PROGRESS (Phase 0)**

Deliverables:

- Architecture freeze statement
- Product scope lock (MVP vs Phase 2+)
- Eight locked domains
- Domain boundary rules
- High-level logical entity map
- Technical intelligence engine position

**Gate:** User / Project Controller review

---

### Step 2 — Design Logical Domain Model Diagram

**Status: PENDING**

Deliverables:

- Per-domain aggregate diagrams
- Cross-domain reference contracts (logical)
- Service boundary sketch
- Event flow for key workflows (JobCard, procurement, finance posting)

**Gate:** Logical model approval

---

### Step 3 — Approval & Sign-Off

**Status: PENDING USER REVIEW**

Deliverables:

- Signed architecture package
- Confirmed MVP scope
- Confirmed domain boundaries
- Confirmed clean restart sequence

**Gate:** Architecture sign-off document approved

---

### Step 4 — Define Data Ownership Rules

**Status: PRELIMINARY (APEX 06); FULL MATRIX PENDING**

Deliverables:

- Data ownership matrix per aggregate/entity
- Write authority map
- Cross-domain reference ID contracts
- Read model vs command model separation

**Gate:** Ownership rules approved before any physical design

---

### Step 5 — Start Physical Schema Design

**Status: NOT STARTED — BLOCKED UNTIL STEPS 1–4 COMPLETE**

Deliverables (future phase only):

- Physical schema per domain
- Migration strategy
- Index and partitioning strategy
- Environment bootstrap scripts

**Gate:** Explicit Project Controller authorization

---

## Hard Rules (Non-Negotiable)

| Rule | Statement |
|------|-----------|
| No tables before steps 1–4 | Physical tables are forbidden until ownership rules are approved |
| No SQL before architecture sign-off | SQL scripts must not be written in Phase 0 or before Step 3 |
| No physical schema before ownership rules | Schema design follows ownership, not the reverse |
| No runtime DB work in Phase 0 | No connection changes, no migrations, no seed data |
| Domain separation preserved | Physical design must respect eight locked domains |

---

## Relationship to MOGHARE360 Soft Run

MOGHARE360 soft-run waves validated controlled operational behavior in a legacy context. ApexMahinERP clean restart **does not** auto-migrate legacy table shapes. Legacy learnings inform requirements; they do not dictate physical schema.

---

## Cursor Statement

Cursor documented the clean restart plan only. **Cursor did not decide the next roadmap step.**

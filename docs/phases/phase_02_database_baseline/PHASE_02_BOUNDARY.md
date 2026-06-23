# PHASE 02 — Database Baseline Documentation — Boundary

**Status:** Documentation-only boundary

---

## Boundary Type

This phase documents the **existing** MOGHARE360_ERP database as observed in user-provided SSMS inventory. It does **not** change the database, create SQL, or alter runtime behavior.

---

## What This Phase Does

| Action | Allowed |
|--------|---------|
| Document table/column inventory summary | Yes |
| Map tables to business domains | Yes |
| Record risks, gaps, and baseline decisions | Yes |
| Create phase validation checklist | Yes |

---

## What This Phase Does NOT Do

| Action | Forbidden |
|--------|-----------|
| Execute SQL in SSMS | Yes |
| Create `.sql` migration scripts | Yes |
| ALTER / CREATE / DROP database objects | Yes |
| Modify PHP, frontend, `public_html` | Yes |
| Activate production SaaS or customer portal | Yes |
| Activate official accounting or payment gateway | Yes |

---

## Data Source Boundary

- Source: user-provided SSMS output (snapshot, not live query by Cursor)
- Cursor does not connect to SQL Server in this phase
- **No database modification**

---

## Runtime Boundary

- **No runtime behavior change**
- Existing application continues to use current schema unchanged
- Future SQL must align with documented baseline (see decision document)

---

## Activation Boundaries (Not in This Phase)

- No production SaaS activation
- No public customer portal activation
- No official accounting activation
- No payment gateway/billing/tax integration created

---

## SQL

**No SQL required** for Phase 02.

---

**END OF BOUNDARY**

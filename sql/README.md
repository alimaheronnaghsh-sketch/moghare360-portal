# MOGHARE360 — SQL Scripts (`sql/`)

## Purpose

Root folder for **future controlled** SQL migration scripts. Distinct from legacy `public_html/sql/sqlserver/` until phased consolidation.

## Status

- **SQL scripts are future controlled SSMS execution only**
- **No executable SQL** in Phase 01 scaffold
- **No SQL required** for scaffold phase
- User executes scripts in `moghare360_ERP` on `.\SQLEXPRESS` only when ChatGPT provides explicit phase scripts

## Rules

- Idempotent patterns preferred (`IF NOT EXISTS`)
- No DROP without owner approval
- No schema change without phased authorization

## Architecture Context

Database writes occur only after:

**UI → Validation Engine → Workflow Engine → Database → Audit Log**

## Product Boundary

- No database schema change in scaffold phase
- No official accounting activation
- No payment gateway/billing/tax integration created

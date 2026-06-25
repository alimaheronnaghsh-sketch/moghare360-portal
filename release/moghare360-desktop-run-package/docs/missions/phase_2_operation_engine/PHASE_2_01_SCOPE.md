# PHASE 2 — Scope

## Purpose

Complete the repair shop operation engine flow:

Customer → Vehicle → JobCard → Service Operation → Technician Flow → QC → Delivery

## Approach

- **Gap-fill only** — M17 (JobCard), M20 (Service), M30 (QC/Delivery), Phase 1 (Customer Core) already exist
- Phase 2 adds `erp_operation_*` orchestration tables and unified flow UI
- Links to existing foundation pages from control center

## In Scope

- 5 new `dbo.erp_operation_*` tables
- Operation control center (read-only)
- JobCard operation flow (list, detail, forms)
- Technician board (read-only)
- Controlled writes: case create, service update, QC decision, delivery check
- CSRF + auth + permission guard (placeholder actions)
- RTL Persian UI

## Out of Scope / Forbidden

- Rebuilding JobCard / Service / QC / Delivery foundations
- Auth / permission model changes
- DROP / RENAME migrations
- Customer portal login
- Accounting / invoice / tax
- Real inventory stock movement
- Modifications to forbidden sensitive files

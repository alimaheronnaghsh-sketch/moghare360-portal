# WAVE 9C — Executive Go/No-Go Decision Workflow & Review Control — Scope

## Status

**WAVE 9C Executive Go/No-Go Decision Workflow & Review Control implemented.**

## Objective

Controlled internal workflow layer for updating executive Soft Run Go/No-Go decision status with full history/audit — without creating new decision records.

## Deliverables

- Workflow helper APIs in `moghare360-executive-go-no-go-decision-helper.php`
- `erp-executive-go-no-go-decision-workflow.php`
- `submit-executive-go-no-go-decision-workflow.php`
- Board and detail navigation links
- `.w9c-*` CSS styles
- CLI test and documentation

## Workflow Capabilities

- Controlled decision status transitions (RECORDED through CLOSED/CANCELLED)
- Controlled decision type review (type may remain unchanged or change to allowed type)
- Management review notes (optional fields)
- History row on every transition
- Read-only decision workflow review table on workflow page

## Write Boundary

- **Allowed writes:** `UPDATE dbo.erp_executive_soft_run_decisions`, `INSERT dbo.erp_executive_soft_run_decision_history` only (via workflow API)
- **No INSERT** of new executive decision records from workflow
- **No writes to:** findings, pilot execution, JobCard, delivery, evidence, authorization, customer, vehicle, payment/accounting

## Boundaries

- No SQL files created or executed
- No DB schema changes
- Does not change WAVE 9B decision create behavior
- Does not approve final delivery or create delivery completion records
- Does not activate public portal, payment/accounting, or production login
- **Cursor did not decide the next roadmap step.**

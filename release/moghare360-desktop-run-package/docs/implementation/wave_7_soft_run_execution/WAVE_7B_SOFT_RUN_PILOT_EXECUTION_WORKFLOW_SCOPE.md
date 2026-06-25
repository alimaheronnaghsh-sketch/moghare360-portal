# WAVE 7B — Soft Run Pilot Execution Workflow — Scope

## Status

**WAVE 7B Soft Run Pilot Execution Workflow implemented.**

## Objective

Create a controlled internal workflow layer for updating Soft Run pilot execution records with full history/audit.

## Deliverables

- Helper workflow APIs in `moghare360-soft-run-pilot-execution-helper.php`
- Workflow page: `erp-soft-run-pilot-execution-workflow.php`
- Workflow submit: `submit-soft-run-pilot-execution-workflow.php`
- Board/detail navigation to workflow page
- CLI test: `tools/test-wave-7b-soft-run-pilot-execution-workflow.php`

## Workflow Capabilities

- Controlled status transition with allowed transition map
- Evidence status update
- Result status update
- History row on every successful workflow update
- Read-only workflow review (transition reference table on workflow page)

## Allowed Transitions

- DRAFT → STARTED, CANCELLED
- STARTED → OBSERVED, BLOCKED, CANCELLED
- OBSERVED → PASSED, FAILED, BLOCKED, CANCELLED
- BLOCKED → STARTED, CANCELLED
- FAILED → OBSERVED
- PASSED → OBSERVED (controlled correction)
- CANCELLED → terminal (no transitions)

## Boundaries

- Controlled workflow updates **only** for pilot execution logs.
- UPDATE only `dbo.erp_soft_run_pilot_executions`.
- INSERT history only `dbo.erp_soft_run_pilot_execution_history`.
- Does **not** perform final vehicle delivery.
- Does **not** create delivery completion records.
- Does **not** activate public portal, payment/accounting, or production login.
- No new SQL files. No schema changes.
- WAVE 6 helpers unchanged.
- **Cursor did not decide the next roadmap step.**

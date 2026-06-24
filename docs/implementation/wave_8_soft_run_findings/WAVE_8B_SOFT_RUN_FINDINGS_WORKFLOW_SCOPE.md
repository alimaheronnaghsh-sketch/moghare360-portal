# WAVE 8B — Soft Run Findings Workflow — Scope

## Status

**WAVE 8B Soft Run Findings Workflow implemented.**

## Objective

Create a controlled internal workflow layer for updating Soft Run findings and corrective action status with full history/audit.

## Deliverables

- Workflow helper APIs in `moghare360-soft-run-finding-helper.php`
- Workflow page: `public_html/erp-soft-run-finding-workflow.php`
- Workflow submit: `public_html/submit-soft-run-finding-workflow.php`
- Board/detail navigation updates
- CLI test: `tools/test-wave-8b-soft-run-finding-workflow.php`

## Write Boundary

- **UPDATE** only `dbo.erp_soft_run_findings`
- **INSERT** only `dbo.erp_soft_run_finding_history` (on workflow update; WAVE 8A create also inserts findings)
- No new finding records created by workflow
- No writes to pilot execution, JobCard, delivery, evidence, authorization, customer, vehicle, or payment/accounting tables

## Boundaries

- Every successful workflow update creates a history row.
- This does **not** perform final vehicle delivery.
- This does **not** create delivery completion records.
- This does **not** activate public portal, payment/accounting, or production login.
- Pilot execution create/workflow/review behavior unchanged.
- Finding create behavior (WAVE 8A) unchanged.

## Roadmap

**Cursor did not decide the next roadmap step.**

# WAVE 8B — Soft Run Findings Workflow — Signoff

## Signoff Statement

**WAVE 8B Soft Run Findings Workflow implemented.**

This introduces controlled workflow updates only for Soft Run finding/corrective action records. Every successful workflow update creates a history row in `dbo.erp_soft_run_finding_history`.

## Product Boundaries

- This does **not** perform final vehicle delivery.
- This does **not** create delivery completion records.
- This does **not** activate public portal.
- This does **not** activate payment/accounting.
- This does **not** activate production login.
- Existing operational rules remain unchanged.
- Pilot execution create/workflow/review behavior remains unchanged.
- Finding create behavior (WAVE 8A) remains unchanged.

## Write Boundary

- UPDATE: `dbo.erp_soft_run_findings` only
- INSERT (workflow): `dbo.erp_soft_run_finding_history` only
- No INSERT of new finding records from workflow layer

## Roadmap

**Cursor did not decide the next roadmap step.** ChatGPT / Project Controller will decide the next controlled step after reviewing Cursor’s execution report.

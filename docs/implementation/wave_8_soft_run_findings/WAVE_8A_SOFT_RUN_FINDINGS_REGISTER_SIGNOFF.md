# WAVE 8A — Soft Run Findings Register — Signoff

## Signoff Statement

**WAVE 8A Soft Run Findings Register Foundation implemented.**

This wave introduces controlled internal DB write only for Soft Run findings and corrective action logs (`dbo.erp_soft_run_findings`, `dbo.erp_soft_run_finding_history`).

## Product Boundaries

- This does **not** perform final vehicle delivery.
- This does **not** create delivery completion records.
- This does **not** activate public portal.
- This does **not** activate payment/accounting.
- This does **not** activate production login.
- This does **not** activate legal final e-signature.
- Existing operational rules remain unchanged.
- Pilot execution create/workflow/review behavior remains unchanged.

## Write Boundary

Writes from WAVE 8A are limited to:

- `dbo.erp_soft_run_findings`
- `dbo.erp_soft_run_finding_history`

History row is created on every finding record creation.

## SQL

User must execute `wave_8a_soft_run_findings_register.sql` in SSMS on `MOGHARE360_ERP`. Cursor did not execute SQL.

## Roadmap

**Cursor did not decide the next roadmap step.** ChatGPT / Project Controller will decide the next controlled step after reviewing Cursor’s execution report.

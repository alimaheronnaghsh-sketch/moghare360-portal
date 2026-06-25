# WAVE 8A — Soft Run Findings Register Foundation — Scope

## Status

**WAVE 8A Soft Run Findings Register Foundation implemented.**

## Objective

Create the controlled internal foundation for recording Soft Run findings, issues, observations, risks, and corrective actions as part of IMPLEMENTATION WAVE 8 — Soft Run Findings & Corrective Action Register.

## Deliverables

- SQL foundation: `public_html/sql/wave_8a_soft_run_findings_register.sql`
- Helper: `public_html/includes/moghare360-soft-run-finding-helper.php`
- Controlled create page: `public_html/erp-soft-run-finding-create.php`
- Controlled submit handler: `public_html/submit-soft-run-finding.php`
- Read-only board: `public_html/erp-soft-run-finding-board.php`
- Read-only detail: `public_html/erp-soft-run-finding-detail.php`
- CLI test: `tools/test-wave-8a-soft-run-finding-register.php`

## Database Tables (new only)

- `dbo.erp_soft_run_findings`
- `dbo.erp_soft_run_finding_history`

## Boundaries

- This introduces **controlled internal DB write only** for Soft Run findings/corrective action logs.
- Writes are limited to the two Soft Run finding tables above.
- This does **not** write to pilot execution, JobCard, delivery, evidence, authorization, customer, vehicle, or payment/accounting tables.
- This does **not** perform final vehicle delivery.
- This does **not** create delivery completion records.
- This does **not** activate public portal.
- This does **not** activate payment/accounting.
- This does **not** activate production login.
- Existing operational rules (WAVE 1–7) remain unchanged.
- Pilot execution create/workflow/review behavior remains unchanged.
- WAVE 7C/7D evaluation behavior is unchanged (navigation links only on optional pages).

## SQL Execution

- SQL targets database `MOGHARE360_ERP`.
- Script is idempotent (`IF OBJECT_ID ... IS NULL CREATE TABLE`).
- **Cursor did not execute SQL** — user must run in SSMS.

## Roadmap

- **Cursor did not decide the next roadmap step.**
- ChatGPT / Project Controller will decide the next controlled step after reviewing Cursor’s execution report.

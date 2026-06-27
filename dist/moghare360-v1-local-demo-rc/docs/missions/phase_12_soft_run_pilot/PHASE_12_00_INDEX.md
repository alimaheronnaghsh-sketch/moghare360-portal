# PHASE 12 — Soft Run Pilot Index

Status: **PENDING USER SQL + TEST**

## SQL
`public_html/sql/sqlserver/phase_12_soft_run_pilot.sql`

## Helper
`public_html/includes/moghare360-pilot-helper.php`

## Pages
- `erp-soft-run-pilot-center.php`
- `erp-pilot-scenario-builder.php` + `submit-pilot-scenario.php`
- `erp-pilot-flow-viewer.php`
- `erp-pilot-data-checklist.php`
- `erp-pilot-feedback.php` + `submit-pilot-feedback.php`
- `erp-soft-run-pilot-report.php`

## Repo Review
Pilot uses **separate tables** (`erp_soft_run_pilot_*`) — no writes to Phase 1–10 operational tables.

## Integration
- `erp-stabilization-dashboard.php` → Pilot Center
- `erp-local-release-candidate.php` → Pilot Center
- `erp-business-command-center.php` → Pilot Center

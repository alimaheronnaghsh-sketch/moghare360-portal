# Mission 30 - SQL Change Plan or Confirmation

## Script
`public_html/sql/sqlserver/mission_30_qc_delivery_foundation.sql`

## Objects Created
- dbo.erp_qc_checks
- dbo.erp_qc_check_history
- dbo.erp_delivery_controls
- dbo.erp_delivery_control_history

## Execution
Manual SSMS only. Idempotent IF OBJECT_ID / IF NOT EXISTS.

## Not Touched
Invoices, customer signature, portal, accounting, supplier, tax, stock, purchase tables.

## Confirmation
SQL file ready for SSMS execution.

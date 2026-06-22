# Mission 28 - SQL Change Plan or Confirmation

## Script
`public_html/sql/sqlserver/mission_28_payment_foundation.sql`

## Execution Policy
- Manual SSMS only
- `USE [moghare360_ERP]`
- Idempotent IF OBJECT_ID / IF NOT EXISTS

## Objects Created
| Object | Type |
|--------|------|
| dbo.erp_payments | Table |
| dbo.erp_payment_history | Table |
| FK_erp_payments_jobcard | FK |
| FK_erp_payment_history_payment | FK |
| CK_erp_payments_status | CHECK (4 statuses) |
| CK_erp_payments_type | CHECK (4 types) |
| CK_erp_payments_method | CHECK (5 methods) |
| CK_erp_payments_amount_positive | CHECK (> 0) |
| IX_erp_payments_jobcard_id | Index |
| IX_erp_payments_payment_status | Index |
| IX_erp_payments_received_at | Index |
| IX_erp_payment_history_payment_id | Index |

## Objects NOT Touched
- erp_invoices
- Ledger / journal tables
- Tax tables
- Supplier AP tables
- erp_purchase_requests
- erp_stock_movements

## Confirmation
SQL file ready for SSMS execution.

# Mission 26 - SQL Change Plan or Confirmation

## Script
`public_html/sql/sqlserver/mission_26_purchase_request_foundation.sql`

## Execution Policy
- Manual SSMS only
- `USE [moghare360_ERP]`
- Idempotent IF OBJECT_ID / IF NOT EXISTS
- Do not auto-run from PHP

## Objects Created
| Object | Type |
|--------|------|
| dbo.erp_purchase_requests | Table |
| dbo.erp_purchase_request_history | Table |
| FK_erp_purchase_requests_jobcard | FK |
| FK_erp_purchase_requests_service_operation | FK |
| FK_erp_purchase_requests_part | FK |
| FK_erp_purchase_request_history_purchase_request | FK |
| CK_erp_purchase_requests_status | CHECK (8 statuses) |
| CK_erp_purchase_requests_quantity_positive | CHECK (> 0) |
| IX_erp_purchase_requests_jobcard_id | Index |
| IX_erp_purchase_requests_request_status | Index |
| IX_erp_purchase_requests_requested_at | Index |
| IX_erp_purchase_request_history_purchase_request_id | Index |

## Objects NOT Touched
- erp_suppliers
- erp_purchase_orders
- Finance / AP / ledger / journal tables
- erp_stock_movements (no RECEIPT)

## Confirmation
SQL file ready for SSMS execution. User must run script before browser create or CLI table checks pass.

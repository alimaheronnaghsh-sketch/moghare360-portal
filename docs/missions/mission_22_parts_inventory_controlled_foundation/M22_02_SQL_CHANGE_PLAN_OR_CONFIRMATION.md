# Mission 22 - SQL Change Plan or Confirmation

## Script
`public_html/sql/sqlserver/mission_22_parts_inventory_foundation.sql`

## Tables
1. dbo.erp_parts — unique part_code
2. dbo.erp_stock_locations — unique location_code, location_type CHECK
3. dbo.erp_stock_movements — movement_type CHECK, quantity > 0, FKs to parts and locations

## Movement Types
SEED, RECEIPT, ISSUE, RETURN, ADJUSTMENT, REVERSAL

## Controlled Seed
INSERT MAIN location if missing:
- location_code = MAIN
- location_name = Main Warehouse
- location_type = WAREHOUSE

No stock movement seed. No consumption.

## Execution
Manual SSMS only. PHP does not auto-run SQL.

## Status
SQL file created and ready for manual SSMS execution.

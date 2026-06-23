# Mission 24 - SQL Change Plan or Confirmation

## Script
`public_html/sql/sqlserver/mission_24_jobcard_part_usage.sql`

## Tables
- dbo.erp_jobcard_part_usage
- dbo.erp_jobcard_part_usage_history

## FKs
- jobcard_id → erp_jobcards
- service_operation_id → erp_service_operations (nullable)
- part_id → erp_parts
- stock_location_id → erp_stock_locations

## Controlled Seed
SEED quantity 5 for part_id=1 at MAIN if:
- part_id=1 exists and active
- MAIN exists
- MISSION_24_TEST_SEED not already present

## Not Touched
Finance, purchase, payment, invoice tables.

## Status
Ready for manual SSMS execution.

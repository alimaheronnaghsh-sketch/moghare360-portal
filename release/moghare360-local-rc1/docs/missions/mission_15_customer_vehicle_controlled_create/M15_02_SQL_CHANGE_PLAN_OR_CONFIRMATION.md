# SQL Change Plan or Confirmation

## Status
PENDING SQL EXECUTION BY USER IN SSMS

## SQL Script Path
public_html/sql/sqlserver/mission_15_customer_vehicle_foundation.sql

## Tables Planned
- dbo.erp_customers
- dbo.erp_customer_phones
- dbo.erp_vehicles
- dbo.erp_customer_vehicle_relations
- dbo.erp_customer_vehicle_change_history

## Indexes Planned
- IX_erp_customers_primary_mobile
- IX_erp_customers_full_name
- IX_erp_vehicles_plate_number
- IX_erp_vehicles_vin
- IX_erp_customer_vehicle_relations_customer_id
- IX_erp_customer_vehicle_relations_vehicle_id
- IX_erp_customer_vehicle_change_history_entity

## Safety Rules
- IF OBJECT_ID(...) IS NULL before CREATE TABLE
- no DROP
- no TRUNCATE
- no destructive migration
- no legacy table modification

## Execution Rule
SQL must be executed manually by user only in SSMS.

Mission 15 PHP must not auto-run the SQL script.

## Confirmation Query
Script ends with:
Mission 15 Customer Vehicle SQL foundation script completed.

## Mission 15 Boundary
SQL file created only until user executes it.

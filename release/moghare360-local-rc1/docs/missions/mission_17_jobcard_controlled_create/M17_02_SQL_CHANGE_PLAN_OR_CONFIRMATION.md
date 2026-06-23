# Mission 17 - SQL Change Plan or Confirmation

## Status
PENDING SQL EXECUTION BY USER IN SSMS

## SQL Script Path
public_html/sql/sqlserver/mission_17_jobcard_foundation.sql

## Tables Planned
- dbo.erp_jobcards
- dbo.erp_jobcard_change_history

## Indexes Planned
- IX_erp_jobcards_jobcard_number
- IX_erp_jobcards_customer_id
- IX_erp_jobcards_vehicle_id
- IX_erp_jobcards_relation_id
- IX_erp_jobcards_jobcard_status
- IX_erp_jobcards_reception_at
- IX_erp_jobcard_change_history_jobcard_id
- IX_erp_jobcard_change_history_change_type

## Foreign Keys Planned
- erp_jobcards.customer_id -> erp_customers.customer_id
- erp_jobcards.vehicle_id -> erp_vehicles.vehicle_id
- erp_jobcards.relation_id -> erp_customer_vehicle_relations.relation_id
- erp_jobcard_change_history.jobcard_id -> erp_jobcards.jobcard_id

## Safety Rules
- IF OBJECT_ID(...) IS NULL before CREATE TABLE
- IF NOT EXISTS before CREATE INDEX
- No DROP
- No TRUNCATE
- No destructive migration
- No legacy table modification

## Execution Rule
SQL must be executed manually by user only in SSMS against moghare360_ERP.

PHP must not auto-run SQL.

## Confirmation After Execution
After manual execution, update:
- docs/missions/mission_17_jobcard_controlled_create/M17_90_TEST_RESULT.md
- docs/missions/mission_17_jobcard_controlled_create/M17_99_MISSION_17_SIGNOFF.md

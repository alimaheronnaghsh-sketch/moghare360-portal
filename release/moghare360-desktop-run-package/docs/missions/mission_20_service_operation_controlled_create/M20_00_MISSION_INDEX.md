# Mission 20 - Service Operation Controlled Create Prototype

## Mission Name
Mission 20 - Service Operation Controlled Create Prototype

## Mission Goal
Introduce ERP Service Operation foundation SQL script and controlled create, read-only list, and read-only detail prototype pages with Auth Context, Permission Guard, CSRF, transaction, and history recording.

## Current Project Phase
Core ERP Foundation + Controlled Admin Prototype

## Dependencies
Completed:
- Mission 17 = JobCard Controlled Create Prototype
- Mission 19 = Service Operation Foundation Design

Available Foundation:
- dbo.erp_jobcards (Mission 17)
- Auth Context
- Permission Guard
- CSRF helper pattern (local page wrapper)
- Controlled create pattern (Mission 15 / 17)

## Created Files
- public_html/sql/sqlserver/mission_20_service_operation_foundation.sql
- public_html/erp-service-operation-create.php
- public_html/erp-service-operation-readonly-list.php
- public_html/erp-service-operation-detail.php
- tools/test-erp-service-operation-foundation.php
- docs/missions/mission_20_service_operation_controlled_create/M20_00_MISSION_INDEX.md
- docs/missions/mission_20_service_operation_controlled_create/M20_01_IMPLEMENTATION_PLAN.md
- docs/missions/mission_20_service_operation_controlled_create/M20_02_SQL_CHANGE_PLAN_OR_CONFIRMATION.md
- docs/missions/mission_20_service_operation_controlled_create/M20_03_SERVICE_OPERATION_CREATE_BOUNDARY.md
- docs/missions/mission_20_service_operation_controlled_create/M20_04_PERMISSION_AND_WORKFLOW_CHECKS.md
- docs/missions/mission_20_service_operation_controlled_create/M20_05_TESTING_PLAN.md
- docs/missions/mission_20_service_operation_controlled_create/M20_90_TEST_RESULT.md
- docs/missions/mission_20_service_operation_controlled_create/M20_99_MISSION_20_SIGNOFF.md

## Mission Boundaries
- Service Operation Controlled Create Prototype only
- No Inventory write
- No Finance write
- No QC write
- No Delivery write
- No Invoice write
- No JobCard status change from Service Operation
- No Customer Portal change
- No legacy file change
- No config/login replacement
- No role assignment
- No permission mutation
- No production deploy

## SQL Execution Rule
SQL script is created only.
User must execute manually in SSMS.
PHP must not auto-run SQL.

## Next Mission
To be assigned by main project controller.
No Mission 21+ may start until Mission 20 is completed, committed, pushed, and reported.

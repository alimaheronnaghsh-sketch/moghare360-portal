# Mission 15 - Customer / Vehicle Controlled Create Prototype

## Mission Name
Mission 15 - Customer / Vehicle Controlled Create Prototype

## Mission Goal
Introduce ERP Customer / Vehicle foundation SQL script and controlled create/list prototype pages with Auth Context, Permission Guard, CSRF, and history recording.

## Current Project Phase
Core ERP Foundation + Controlled Admin Prototype

## Dependencies
Mission 14 completed:
- docs/missions/mission_14_customer_vehicle_foundation_design/

Mission 8 completed:
- includes/erp-auth-context.php

Mission 10 completed:
- includes/erp-permission-guard.php

CSRF helper exists:
- includes/erp-csrf.php

## Created Files
- public_html/sql/sqlserver/mission_15_customer_vehicle_foundation.sql
- public_html/erp-customer-vehicle-create.php
- public_html/erp-customer-vehicle-readonly-list.php
- tools/test-erp-customer-vehicle-foundation.php
- docs/missions/mission_15_customer_vehicle_controlled_create/M15_00_MISSION_INDEX.md
- docs/missions/mission_15_customer_vehicle_controlled_create/M15_01_IMPLEMENTATION_PLAN.md
- docs/missions/mission_15_customer_vehicle_controlled_create/M15_02_SQL_CHANGE_PLAN_OR_CONFIRMATION.md
- docs/missions/mission_15_customer_vehicle_controlled_create/M15_03_CREATE_FORM_BOUNDARY.md
- docs/missions/mission_15_customer_vehicle_controlled_create/M15_04_SECURITY_AND_AUDIT_RULES.md
- docs/missions/mission_15_customer_vehicle_controlled_create/M15_05_TESTING_PLAN.md
- docs/missions/mission_15_customer_vehicle_controlled_create/M15_90_TEST_RESULT.md
- docs/missions/mission_15_customer_vehicle_controlled_create/M15_99_MISSION_15_SIGNOFF.md

## Mission Boundaries
- Controlled Customer / Vehicle Create Prototype only
- No legacy customer file change
- No Customer Portal change
- No customer login
- No tenant implementation
- No production deploy
- No migration
- No Codex ZIP copy/use
- No core_user_roles write
- No access request workflow write
- No role assignment
- No permission mutation
- No config/login replacement

## SQL Execution Rule
SQL script is created only.
User must execute manually in SSMS.
PHP must not auto-run SQL.

## Next Mission
Mission 16 - Reception / JobCard Foundation Design

Mission 16 must not start until Mission 15 is completed, committed, pushed, and reported.

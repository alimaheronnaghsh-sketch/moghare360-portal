# Mission 17 - JobCard Controlled Create Prototype

## Mission Name
Mission 17 - JobCard Controlled Create Prototype

## Mission Goal
Introduce ERP JobCard foundation SQL script and controlled create, read-only list, and read-only detail prototype pages with Auth Context, Permission Guard, CSRF, and history recording.

## Current Project Phase
Core ERP Foundation + Controlled Admin Prototype

## Dependencies
Mission 15 completed:
- Customer / Vehicle foundation tables exist
- Controlled create pattern proven

Mission 16 completed:
- docs/missions/mission_16_reception_jobcard_foundation_design/

Mission 8 completed:
- includes/erp-auth-context.php

Mission 10 completed:
- includes/erp-permission-guard.php

CSRF helper may exist:
- includes/erp-csrf.php

## Created Files
- public_html/sql/sqlserver/mission_17_jobcard_foundation.sql
- public_html/erp-jobcard-create.php
- public_html/erp-jobcard-readonly-list.php
- public_html/erp-jobcard-detail.php
- tools/test-erp-jobcard-foundation.php
- docs/missions/mission_17_jobcard_controlled_create/M17_00_MISSION_INDEX.md
- docs/missions/mission_17_jobcard_controlled_create/M17_01_IMPLEMENTATION_PLAN.md
- docs/missions/mission_17_jobcard_controlled_create/M17_02_SQL_CHANGE_PLAN_OR_CONFIRMATION.md
- docs/missions/mission_17_jobcard_controlled_create/M17_03_JOBCARD_CREATE_BOUNDARY.md
- docs/missions/mission_17_jobcard_controlled_create/M17_04_PERMISSION_AND_WORKFLOW_CHECKS.md
- docs/missions/mission_17_jobcard_controlled_create/M17_05_TESTING_PLAN.md
- docs/missions/mission_17_jobcard_controlled_create/M17_90_TEST_RESULT.md
- docs/missions/mission_17_jobcard_controlled_create/M17_99_MISSION_17_SIGNOFF.md

## Mission Boundaries
- Controlled JobCard Create Prototype only
- No Service Operation write
- No Inventory write
- No Finance write
- No Delivery write
- No Customer Portal change
- No legacy file change
- No customer login
- No tenant implementation
- No production deploy
- No migration
- No config/login replacement
- No core_user_roles write
- No access request workflow write
- No role assignment
- No permission mutation

## SQL Execution Rule
SQL script is created only.
User must execute manually in SSMS.
PHP must not auto-run SQL.

## Next Mission
To be assigned by main project controller.

Mission 18 or later must not start until Mission 17 is completed, committed, pushed, and reported.

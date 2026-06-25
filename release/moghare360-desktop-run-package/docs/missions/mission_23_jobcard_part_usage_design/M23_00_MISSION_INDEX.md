# Mission 23 - JobCard Part Usage Design

## Mission Name
Mission 23 - JobCard Part Usage Design

## Mission Goal
Design JobCard / Service Operation part usage and stock deduction rules before any stock write implementation.

## Current Project Phase
Core ERP Foundation + Controlled Admin Prototype — Part Usage Design Gate

## Mission Type
Design Lock only.

## Dependencies
Completed:
- Mission 20 = Service Operation Controlled Create Prototype
- Mission 21 = Parts / Inventory Foundation Design
- Mission 22 = Parts / Inventory Controlled Foundation Prototype

Available Foundation:
- dbo.erp_jobcards (Mission 17)
- dbo.erp_service_operations (Mission 20)
- dbo.erp_parts, dbo.erp_stock_locations, dbo.erp_stock_movements (Mission 22)
- Auth Context, Permission Guard, CSRF, controlled transaction, audit pattern

## Created Files
- M23_00_MISSION_INDEX.md
- M23_01_PART_USAGE_SCOPE.md
- M23_02_SERVICE_TO_PART_USAGE_RULES.md
- M23_03_STOCK_DEDUCTION_RULES.md
- M23_04_RETURN_AND_REVERSAL_RULES.md
- M23_05_PERMISSION_AND_AUDIT_RULES.md
- M23_06_FINANCE_BOUNDARY.md
- M23_07_SQL_IMPLEMENTATION_PLAN.md
- M23_08_UI_PLAN.md
- M23_09_TESTING_PLAN.md
- M23_99_MISSION_23_SIGNOFF.md

## Mission Boundary
Mission 23 is design only.

No code is created.
No PHP operational file is created.
No SQL is created.
No SQL is executed.
No database is changed.
No stock write is performed.
No stock deduction is performed.
No finance write is performed.
No JobCard part usage row is created.
No forbidden file is changed.

## Locked Design Areas
- Part usage scope
- Service Operation to part usage rules
- Stock deduction rules
- Return and reversal rules
- Permission and audit rules
- Finance boundary
- SQL implementation plan (deferred)
- UI plan (deferred)
- Testing plan (deferred)

## Next Mission
Mission 24 - JobCard Part Usage Controlled Prototype (indicative)

Mission 24 must not start until Mission 23 is completed, committed, pushed, and reported.

## Final Decision
Mission 23 locks part usage design as prerequisite for Mission 24 controlled implementation.

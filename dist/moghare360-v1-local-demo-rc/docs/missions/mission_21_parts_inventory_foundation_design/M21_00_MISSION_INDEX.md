# Mission 21 - Parts / Inventory Foundation Design

## Mission Name
Mission 21 - Parts / Inventory Foundation Design

## Mission Goal
Design the foundation for parts master data, stock locations, stock movements, and future part usage linkage to JobCard / Service Operation.

## Current Project Phase
Core ERP Foundation + Controlled Admin Prototype — Parts / Inventory Design Gate

## Mission Type
Design Lock only.

## Dependencies
Completed:
- Mission 05 through Mission 20 = Completed and signed off

Available Foundation:
- Customer / Vehicle foundation (Mission 14–15)
- JobCard foundation (Mission 16–17)
- Service Operation foundation (Mission 19–20)
- Auth Context, Permission Guard, CSRF, controlled transaction, audit / history pattern
- dbo.erp_jobcards, dbo.erp_service_operations (Mission 17 / 20)

## Created Files
- M21_00_MISSION_INDEX.md
- M21_01_PARTS_INVENTORY_SCOPE.md
- M21_02_CURRENT_LEGACY_INVENTORY_REVIEW.md
- M21_03_PART_MASTER_DATA_MODEL_PLAN.md
- M21_04_STOCK_LOCATION_MODEL_PLAN.md
- M21_05_STOCK_MOVEMENT_MODEL_PLAN.md
- M21_06_JOBCARD_PART_USAGE_RULES.md
- M21_07_PURCHASE_REQUEST_BOUNDARY.md
- M21_08_PERMISSION_AND_AUDIT_RULES.md
- M21_09_SQL_IMPLEMENTATION_PLAN.md
- M21_10_UI_PLAN.md
- M21_11_TESTING_PLAN.md
- M21_99_MISSION_21_SIGNOFF.md

## Mission Boundary
Mission 21 is design only.

No code is created.
No PHP operational file is created.
No SQL is created.
No SQL is executed.
No database is changed.
No stock movement write is performed.
No stock deduction is performed.
No purchase request write is performed.
No finance write is performed.
No JobCard part consumption is performed.
No legacy inventory file or table is modified.
No forbidden file is changed.

## Locked Design Areas
- Parts / Inventory scope
- Legacy inventory review (read-only)
- Part master data model plan
- Stock location model plan
- Stock movement model plan
- JobCard part usage rules
- Purchase request boundary
- Permission and audit rules
- SQL implementation plan (deferred)
- UI plan (deferred)
- Testing plan (deferred)

## Next Mission
Mission 22 - Parts Master Controlled Create Prototype (indicative)

Mission 22 must not start until Mission 21 is completed, committed, pushed, and reported.

## Future Mission Chain (Locked Reference)
- Mission 22 — Part master / stock foundation SQL + controlled create prototype
- Mission 23 — Part usage / stock deduction design
- Mission 24 — Part usage controlled prototype
- Mission 25 — Purchase request design and implementation

## Final Decision
Mission 21 locks Parts / Inventory foundation design as prerequisite for Mission 22.

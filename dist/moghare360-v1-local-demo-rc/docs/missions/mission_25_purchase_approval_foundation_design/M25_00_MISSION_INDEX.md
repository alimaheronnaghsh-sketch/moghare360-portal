# Mission 25 - Purchase Approval Foundation Design

## Mission Name
Mission 25 - Purchase Approval Foundation Design

## Mission Goal
Design purchase request and purchase approval workflow for when a required part is not available in stock.

## Current Project Phase
Core ERP Foundation + Controlled Admin Prototype — Purchase Approval Design Gate

## Mission Type
Design Lock only.

## Dependencies
Completed:
- Mission 20 = Service Operation Controlled Create Prototype
- Mission 22 = Parts / Inventory Controlled Foundation Prototype
- Mission 23 = JobCard Part Usage Design
- Mission 24 = JobCard Part Usage Controlled Prototype

Available Foundation:
- dbo.erp_jobcards, dbo.erp_service_operations
- dbo.erp_parts, dbo.erp_stock_locations, dbo.erp_stock_movements
- dbo.erp_jobcard_part_usage (Mission 24)
- Auth Context, Permission Guard, CSRF, controlled transaction, audit pattern

## Created Files
- M25_00_MISSION_INDEX.md
- M25_01_PURCHASE_APPROVAL_SCOPE.md
- M25_02_WAITING_PARTS_FLOW.md
- M25_03_PURCHASE_REQUEST_DATA_MODEL.md
- M25_04_APPROVAL_RULES.md
- M25_05_SUPPLIER_BOUNDARY.md
- M25_06_FINANCE_BOUNDARY.md
- M25_07_PERMISSION_AND_AUDIT_RULES.md
- M25_08_SQL_IMPLEMENTATION_PLAN.md
- M25_09_UI_PLAN.md
- M25_10_TESTING_PLAN.md
- M25_99_MISSION_25_SIGNOFF.md

## Mission Boundary
Mission 25 is design only.

No code is created.
No PHP operational file is created.
No SQL is created.
No SQL is executed.
No database is changed.
No supplier contract is implemented.
No supplier payment is performed.
No finance write is performed.
No stock receipt is performed.
No real purchase execution is performed.
No automatic approval is implemented.
No forbidden file is changed.

## Locked Design Areas
- Purchase approval scope
- Waiting parts flow
- Purchase request data model
- Approval rules
- Supplier boundary
- Finance boundary
- Permission and audit rules
- SQL implementation plan (deferred)
- UI plan (deferred)
- Testing plan (deferred)

## Next Mission
Mission 26 - Purchase Request Controlled Prototype (indicative)

Mission 26 must not start until Mission 25 is completed, committed, pushed, and reported.

## Final Decision
Mission 25 locks purchase request and approval foundation design as prerequisite for Mission 26.

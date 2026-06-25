# Mission 27 - Finance / Payment Foundation Design

## Mission Name
Mission 27 - Finance / Payment Foundation Design

## Mission Goal
Design financial foundation for Soft Run including advance payment, customer payment, outstanding balance, and settlement.

## Current Project Phase
Core ERP Foundation + Controlled Admin Prototype — Finance / Payment Design Gate

## Mission Type
Design Lock only.

## Dependencies
Completed:
- Mission 17+ JobCard foundation
- Mission 20 = Service Operation Controlled Create Prototype
- Mission 24 = JobCard Part Usage Controlled Prototype
- Mission 25 = Purchase Approval Foundation Design
- Mission 26 = Purchase Request Controlled Prototype

Available Foundation:
- dbo.erp_jobcards, dbo.erp_customers, dbo.erp_vehicles
- dbo.erp_service_operations, dbo.erp_purchase_requests
- Auth Context, Permission Guard, CSRF, controlled transaction, audit pattern

## Created Files
- M27_00_MISSION_INDEX.md
- M27_01_FINANCE_SCOPE.md
- M27_02_PAYMENT_FLOW.md
- M27_03_RECEIVABLES_MODEL.md
- M27_04_JOBCARD_FINANCE_LINK_RULES.md
- M27_05_PAYMENT_STATUS_MODEL.md
- M27_06_PERMISSION_AND_AUDIT_RULES.md
- M27_07_SQL_IMPLEMENTATION_PLAN.md
- M27_08_UI_PLAN.md
- M27_09_REPORTING_PLAN.md
- M27_10_TESTING_PLAN.md
- M27_99_MISSION_27_SIGNOFF.md

## Mission Boundary
Mission 27 is design only.

No code is created.
No PHP operational file is created.
No SQL is created.
No SQL is executed.
No database is changed.
No actual payment is registered.
No invoice is created or finalized.
No accounting export is performed.
No supplier payment is performed.
No tax logic is implemented.
No delivery dependency is introduced.
No forbidden file is changed.

## Locked Design Areas
- Finance scope and boundaries
- Payment flow
- Receivables model
- JobCard finance link rules
- Payment status model
- Permission and audit rules
- SQL implementation plan (deferred)
- UI plan (deferred)
- Reporting plan (deferred)
- Testing plan (deferred)

## Next Mission
Mission 28 - Payment Controlled Prototype (indicative)

Mission 28 must not start until Mission 27 is completed, committed, pushed, and reported.

## Final Decision
Mission 27 locks customer payment and receivables foundation design as prerequisite for Mission 28 Soft Run finance prototype.

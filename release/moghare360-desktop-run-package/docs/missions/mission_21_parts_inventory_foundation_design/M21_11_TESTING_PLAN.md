# Testing Plan

## Purpose
This document defines the future test plan for Parts / Inventory implementation in Mission 22.

## Mission 21 Boundary
Testing is planned only.
No runnable test file is created in Mission 21.
No SQL is executed in Mission 21.

## Future Tests (Mission 22)

### 1. SQL Object Existence
Validate:
- dbo.erp_parts exists
- dbo.erp_stock_locations exists (if included in M22 SQL)
- dbo.erp_stock_movements exists (structure only if included)
- columns, PKs, unique constraints, indexes

### 2. Controlled Part Create
- Create part via erp-part-create.php
- Auth Context + Permission Guard + CSRF
- Transaction + audit if history table included
- part_code uniqueness enforced

### 3. Parts List OK
- erp-part-readonly-list.php shows created part
- parts.list permission enforced

### 4. Stock Read-Only List OK
- erp-stock-readonly-list.php loads without error
- stock.view permission enforced
- No write actions present

### 5. No Stock Consumption
Confirm:
- No ISSUE movement created by Mission 22 prototype
- No JobCard part usage rows
- No stock deduction

### 6. No Finance Write
Confirm no finance table writes during M22 tests.

### 7. No Purchase Approval
Confirm no purchase request rows or approval workflow writes.

### 8. No Forbidden Files Changed
Confirm no changes to:
- config.php, config.example.php, staff-auth.php, access-control.php
- Customer Portal files
- Legacy inventory PHP/SQL files

## Recommended Additional Tests (Mission 22)
- Permission Guard denial for unauthorized user
- CSRF rejection on create POST
- Transaction rollback on simulated failure
- CLI foundation test (if added in M22)

## Mission 21 Test Result
Mission 21 performs no executable tests.
All tests deferred to Mission 22.

## Final Testing Decision
Eight core future tests locked; consumption, finance, and purchase explicitly excluded from M22 pass criteria.

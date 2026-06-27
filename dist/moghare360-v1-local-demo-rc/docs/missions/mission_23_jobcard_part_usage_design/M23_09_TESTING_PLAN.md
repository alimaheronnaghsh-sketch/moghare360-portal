# Testing Plan

## Purpose
This document defines the future test plan for JobCard part usage implementation in Mission 24.

## Mission 23 Boundary
Testing planned only. No runnable tests in Mission 23.

## Prerequisites (Mission 24)
1. Mission 22 SQL executed (parts, locations, movements tables)
2. Mission 20 SQL executed (service operations)
3. Mission 17 SQL executed (jobcards)
4. JobCard jobcard_id = 1 exists
5. At least one active part exists (e.g. M22-TEST-001)
6. MAIN stock location exists
7. Sufficient SEED or RECEIPT movement for negative-stock test (if ISSUE tested)

## Future Tests (Mission 24)

### 1. SQL Object Existence
- dbo.erp_jobcard_part_usage
- dbo.erp_jobcard_part_usage_history
- FKs and constraints

### 2. Register Part Usage Against JobCard ID = 1
- Controlled POST via erp-jobcard-part-use.php
- usage_status = USED
- quantity > 0

### 3. Service Operation Same JobCard Validation
- Reject service_operation_id from different JobCard
- Accept valid service_operation_id on same JobCard

### 4. Stock Does Not Go Negative
- Reject usage when quantity_on_hand insufficient
- Accept usage when sufficient stock exists

### 5. Stock Movement ISSUE (If Designed in M24)
- One ISSUE row per usage
- reference_type = JOBCARD_PART_USAGE
- reference_id = part_usage_id

### 6. Part Usage List Page
- erp-jobcard-part-readonly-list.php shows registered usage

### 7. Audit / History Exists
- History contains PART_USAGE_CREATED (or equivalent)
- old_status / new_status on create

### 8. No Finance Write
Confirm no finance table writes during usage test.

### 9. No Invoice / Payment Write
Confirm no invoice or payment rows created.

### 10. No Forbidden Files Changed
config, staff-auth, access-control, Customer Portal, legacy inventory unchanged.

## Recommended Additional Tests (Mission 24)
- Permission Guard denial
- CSRF rejection
- CANCELLED Service Operation rejection
- DONE Service Operation rejection (default)
- Transaction rollback on movement failure
- CLI foundation test (if added in M24)

## Mission 23 Test Result
No executable tests in Mission 23.

## Final Testing Decision
Ten core Mission 24 tests locked; finance and invoice explicitly excluded from pass criteria.

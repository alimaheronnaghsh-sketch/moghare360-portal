# Testing Plan

## Purpose
This document defines the testing plan for purchase request foundation.

## Mission 25 Boundary
Testing planned only. **No runnable tests in Mission 25.**

## Mission 26 Test Deliverable (Indicative)
- CLI: `tools/test-erp-purchase-request-foundation.php`
- Doc: `docs/missions/mission_26_purchase_request_controlled/M26_90_TEST_RESULT.md`

## Future Test Cases (Mission 26)

### 1. SQL Object Existence
- Verify `dbo.erp_purchase_requests` exists
- Verify `dbo.erp_purchase_request_history` exists
- Verify expected columns per M25_03

### 2. Create One Purchase Request Linked to JobCard/Service
- Create request with valid jobcard_id
- Optional service_operation_id and part_id
- Initial status DRAFT or SUBMITTED
- Assert purchase_request_id returned via composite lookup

### 3. Status = DRAFT or SUBMITTED
- Assert request_status matches create input
- No APPROVED without explicit approve action (future)

### 4. List Page OK
- HTTP or CLI smoke: list page loads
- Created request appears in list

### 5. Detail Page OK
- Detail page shows request fields
- History section present

### 6. Audit / History OK
- At least one history row: PURCHASE_REQUEST_CREATED
- changed_by_user_id populated
- new_status matches initial status

### 7. No Supplier Payment
- Assert no payment tables written
- supplier_id NULL on test row

### 8. No Finance Write
- Assert no AP / ledger / journal rows
- estimated_unit_cost does not trigger posting

### 9. No Stock Receipt
- Assert no RECEIPT movement for purchase request
- quantity_on_hand unchanged by purchase request create

### 10. No Automatic Approval
- SUBMITTED remains SUBMITTED after create
- No APPROVED without approve action

### 11. No Forbidden Files Changed
- Git diff excludes config.php, staff-auth.php, access-control.php, Customer Portal, legacy inventory

## Test Data Policy (Mission 26)
- Use existing JobCard from prior mission test data
- Use part_id=1 if catalog part referenced
- Mark test request with identifiable requested_part_name (e.g. M26-TEST-PR-001)

## Pass Criteria (Mission 26)
All test cases pass; M26_90 = PASSED; user confirms; then M26_99 signoff.

## Mission 25 Deliverable
This testing plan document only.

## Final Testing Decision
Mission 26 CLI + manual page smoke; verify create/list/detail/history; verify no supplier payment, finance write, stock receipt, or auto-approval.

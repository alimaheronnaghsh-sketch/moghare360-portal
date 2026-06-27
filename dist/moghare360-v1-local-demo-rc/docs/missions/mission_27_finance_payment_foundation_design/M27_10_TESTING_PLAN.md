# Testing Plan

## Purpose
This document defines the testing plan for payment foundation.

## Mission 27 Boundary
Testing planned only. **No runnable tests in Mission 27.**

## Mission 28 Test Deliverable (Indicative)
- CLI: `tools/test-erp-payment-foundation.php`
- Doc: `docs/missions/mission_28_payment_controlled/M28_90_TEST_RESULT.md`

## Future Test Cases (Mission 28)

### 1. SQL Object Existence
- Verify `dbo.erp_payments` exists
- Verify `dbo.erp_payment_history` exists
- Verify expected columns per M27_07

### 2. Create One Payment for JobCard ID = 1
- Create payment with valid jobcard_id = 1
- payment_amount > 0
- Initial status DRAFT or RECEIVED
- Assert payment_id returned via composite lookup

### 3. Show Payment List
- List page loads
- Created payment appears

### 4. Show JobCard Payment Summary
- Summary page for jobcard_id = 1
- total_received reflects RECEIVED payment(s)
- outstanding placeholder displayed appropriately

### 5. Audit / History OK
- At least one history row: PAYMENT_CREATED
- changed_by_user_id populated

### 6. No Invoice Finalization
- Assert no invoice tables written
- No invoice number generated

### 7. No Accounting Export
- Assert no ledger/journal/export artifacts

### 8. No Supplier Payment
- Assert no AP / supplier payment tables touched

### 9. No Tax Logic
- Assert no tax calculation tables or fields written

### 10. No Delivery Dependency
- Payment create does not set delivery flags

### 11. No Forbidden Files Changed
- Git diff excludes config.php, staff-auth.php, access-control.php, Customer Portal, legacy inventory

### 12. CLI Read-Only
- Test performs no writes

## Test Data Policy (Mission 28)
- Use JobCard jobcard_id = 1 from prior mission test data
- Sample payment_amount e.g. 1000000 IRR
- Mark with payment_note e.g. M28-TEST-PAY-001

## Pass Criteria (Mission 28)
All test cases pass; M28_90 = PASSED; user confirms; then M28_99 signoff.

## Mission 27 Deliverable
This testing plan document only.

## Final Testing Decision
Mission 28 CLI + browser smoke; verify create/list/summary/history; verify no invoice, export, supplier, tax, or delivery side effects.

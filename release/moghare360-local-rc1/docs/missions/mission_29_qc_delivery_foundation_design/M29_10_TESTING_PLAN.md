# Testing Plan

## Purpose
This document defines the testing plan for QC and delivery foundation.

## Mission 29 Boundary
Testing planned only. **No runnable tests in Mission 29.**

## Mission 30 Test Deliverable (Indicative)
- CLI: `tools/test-erp-qc-delivery-foundation.php`
- Doc: `docs/missions/mission_30_qc_delivery_controlled/M30_90_TEST_RESULT.md`

## Future Test Cases (Mission 30)

### 1. SQL Object Existence
- dbo.erp_qc_checks
- dbo.erp_qc_check_history
- dbo.erp_delivery_controls
- dbo.erp_delivery_control_history

### 2. QC Controlled Prototype OK
- Create QC check for JobCard
- Pass or fail with history

### 3. Delivery Controlled Prototype OK
- Delivery control row for JobCard
- delivery_allowed / block_reason set correctly

### 4. Soft Run Gate Page OK
- erp-soft-run-readiness.php loads
- Shows aggregated context

### 5. Foundation Data Present
- Customer exists
- Vehicle exists
- JobCard exists
- Service operation exists (optional)
- Part usage optional
- Payment optional or documented

### 6. QC Passed
- qc_status = PASSED after explicit pass action

### 7. Delivery Allowed or Blocked with Reason
- delivery_allowed reflects QC + payment review
- block_reason populated when blocked

### 8. Audit / History Exists
- QC history rows
- Delivery control history rows

### 9. Negative Checks (Must Remain True)
- No final invoice
- No customer portal change
- No production deploy
- No forbidden files changed
- CLI performs no writes (read-only validation)

## Test Data Policy (Mission 30)
- Use JobCard jobcard_id = 1 from prior missions
- Optional payment from M28 test
- QC note e.g. M30-TEST-QC-001

## Pass Criteria (Mission 30)
All test cases pass; M30_90 = PASSED; user confirms; M30_99 signoff.

## Mission 29 Deliverable
This testing plan document only.

## Final Testing Decision
Mission 30 validates SQL, QC, delivery, Soft Run gate, history, and boundary negatives.

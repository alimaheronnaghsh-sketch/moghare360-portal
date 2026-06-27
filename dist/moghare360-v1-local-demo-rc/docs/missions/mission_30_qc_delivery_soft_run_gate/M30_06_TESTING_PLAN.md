# Mission 30 - Testing Plan

## Steps
1. Run mission_30_qc_delivery_foundation.sql in SSMS
2. CLI test (tables + jobcard + service op)
3. Browser: erp-qc-check.php — QC PASSED for jobcard 1
4. Browser: erp-delivery-control.php — view + optional release
5. Browser: erp-soft-run-readiness.php?jobcard_id=1
6. Re-run CLI

## Pass Criteria
- QC PASSED exists
- Delivery control READY or RELEASED
- Histories exist
- Soft Run READY (after QC pass + delivery allowed)
- Overall CLI OK

## Signoff
Update M30_90 and M30_99 after user confirmation.

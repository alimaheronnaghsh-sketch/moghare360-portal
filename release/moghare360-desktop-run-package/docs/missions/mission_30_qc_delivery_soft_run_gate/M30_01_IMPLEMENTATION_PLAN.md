# Mission 30 - Implementation Plan

## Deliverables
1. SQL: erp_qc_checks, erp_qc_check_history, erp_delivery_controls, erp_delivery_control_history
2. erp-qc-check.php — QC create + delivery sync in transaction
3. erp-delivery-control.php — view + controlled release
4. erp-soft-run-readiness.php — read-only gate
5. tools/test-erp-qc-delivery-foundation.php

## QC Create Flow
1. Auth + CSRF + qc.check.create
2. Insert qc_checks (PASSED or FAILED)
3. Insert qc_check_history (QC_CHECK_CREATED)
4. Upsert delivery_controls (READY or BLOCKED)
5. Insert delivery_control_history (DELIVERY_READY or DELIVERY_BLOCKED)

## Delivery Release Flow
1. Auth + CSRF + delivery.control.release
2. Only if delivery_allowed=1 and delivery_status=READY
3. UPDATE RELEASED + released_by_user_id + released_at
4. History DELIVERY_RELEASED

## Soft Run Gate
Read-only aggregated checks → SOFT RUN READY or SOFT RUN BLOCKED

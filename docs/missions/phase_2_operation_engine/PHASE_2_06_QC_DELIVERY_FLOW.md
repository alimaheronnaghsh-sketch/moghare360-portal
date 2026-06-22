# PHASE 2 — QC & Delivery Flow

## Operation Flow Page

`erp-jobcard-operation-flow.php`

- List mode: recent cases + create form
- Detail mode: linked Phase 1 + JobCard data, steps, QC, delivery, history

## Write Handlers

| Handler | CSRF Purpose | Action |
|---------|--------------|--------|
| `submit-operation-case-create.php` | `operation_case_create` | New case + optional first step |
| `submit-service-status-update.php` | `operation_service_update` | Add/update service step |
| `submit-qc-decision.php` | `operation_qc_decision` | QC pass/fail/hold |
| `submit-delivery-final-check.php` | `operation_delivery_check` | Delivery readiness |

## QC Rules

| Decision | Stage | Status |
|----------|-------|--------|
| PASSED | READY_FOR_DELIVERY | QC_PASSED |
| FAILED_RETURN_TO_SERVICE | SERVICE | RETURNED_FROM_QC |
| FAILED_RETURN_TO_DIAGNOSIS | DIAGNOSIS | RETURNED_FROM_QC |
| HOLD | (unchanged) | QC_HOLD |

## Delivery Rules

- `is_ready_for_delivery = true` requires latest QC decision = PASSED
- Outcomes: READY_FOR_DELIVERY, DELIVERED, HOLD
- Updates case stage/status on ready

## Service Auto-Transition

- All non-cancelled steps DONE → case stage = QC
- Step IN_PROGRESS from RECEPTION/DIAGNOSIS → stage = SERVICE

## M30 Relationship

Phase 2 operation QC is **orchestration-level**. Existing `erp-qc-check.php` (M30) remains for JobCard-level QC foundation — not replaced.

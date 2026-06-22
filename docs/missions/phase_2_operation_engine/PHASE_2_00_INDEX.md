# PHASE 2 — Operation Engine Index

Status: **PENDING USER TEST**

## Built Files

### SQL
- `public_html/sql/sqlserver/phase_2_operation_engine.sql`

### PHP Pages
- `public_html/erp-operation-control-center.php` — read-only dashboard
- `public_html/erp-technician-board.php` — read-only technician board
- `public_html/erp-jobcard-operation-flow.php` — list, detail, controlled forms
- `public_html/submit-operation-case-create.php`
- `public_html/submit-service-status-update.php`
- `public_html/submit-qc-decision.php`
- `public_html/submit-delivery-final-check.php`

### Helper
- `public_html/includes/erp-operation-engine-helper.php`

### CSS
- `public_html/assets/moghare360-ui/moghare360-operation-engine.css`

### Test Tool
- `tools/test-phase-2-operation-engine.php`

## Browser URLs

Base: `http://localhost:8080/moghare360/`

| Page | URL |
|------|-----|
| Control Center | `erp-operation-control-center.php` |
| Technician Board | `erp-technician-board.php` |
| Operation Flow | `erp-jobcard-operation-flow.php` |

## Reused (Not Rebuilt)

| Area | Existing Files / Tables |
|------|-------------------------|
| JobCard M17 | `erp-jobcard-create.php`, `erp_jobcards` |
| Service M20 | `erp-service-operation-*.php`, `erp_service_operations` |
| QC/Delivery M30 | `erp-qc-check.php`, `erp-delivery-control.php`, `erp_qc_checks`, `erp_delivery_controls` |
| Customer Core P1 | `erp_customer_intakes`, `erp_customer_contracts`, `erp_customer_vehicle_bindings` |

Phase 2 adds **orchestration layer** tables (`erp_operation_*`) that link the full flow without duplicating M17–M30.

## Docs

- `PHASE_2_01_SCOPE.md`
- `PHASE_2_02_REPO_GAP_REVIEW.md`
- `PHASE_2_03_SQL_FOUNDATION.md`
- `PHASE_2_04_OPERATION_CONTROL_CENTER.md`
- `PHASE_2_05_TECHNICIAN_BOARD.md`
- `PHASE_2_06_QC_DELIVERY_FLOW.md`
- `PHASE_2_90_TEST_RESULT.md`
- `PHASE_2_99_SIGNOFF.md`

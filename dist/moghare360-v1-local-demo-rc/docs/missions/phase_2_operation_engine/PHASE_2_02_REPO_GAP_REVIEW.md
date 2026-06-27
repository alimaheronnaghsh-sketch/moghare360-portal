# PHASE 2 — Repo Gap Review

## Existing JobCard (M17)

| File | Role |
|------|------|
| `erp-jobcard-create.php` | Controlled create |
| `erp-jobcard-detail.php` | Read-only detail |
| `erp-jobcard-readonly-list.php` | List |
| `erp-jobcard-*-ux.php` | UX layer (M33) |

**Table:** `dbo.erp_jobcards`

## Existing Service Operation (M20)

| File | Role |
|------|------|
| `erp-service-operation-create.php` | Controlled create |
| `erp-service-operation-detail.php` | Detail |
| `erp-service-operation-readonly-list.php` | List |

**Table:** `dbo.erp_service_operations`

## Existing QC / Delivery (M30)

| File | Role |
|------|------|
| `erp-qc-check.php` | QC controlled create |
| `erp-delivery-control.php` | Delivery control |

**Tables:** `dbo.erp_qc_checks`, `dbo.erp_delivery_controls`

## Phase 1 Customer Core

**Tables:** `erp_customer_intakes`, `erp_customer_contracts`, `erp_customer_vehicle_bindings`, `erp_customer_core_history`

**Pages:** `erp-customer-core-dashboard.php`, `erp-jobcard-operation-flow.php` links intake/contract/binding

## Gap Identified

No unified **operation case** orchestrating full stage flow across foundations. M17–M30 work per-entity; Phase 2 adds:

- `erp_operation_cases` — master operation record with `current_stage`
- `erp_operation_service_steps` — technician workflow steps
- `erp_operation_qc_decisions` — QC decisions at operation level
- `erp_operation_delivery_checks` — delivery readiness
- `erp_operation_history` — cross-entity audit

## Helpers Reused

- `erp-auth-context.php` — DB + auth
- `erp-permission-guard.php` — placeholder guard
- `erp-csrf.php` — CSRF tokens
- Pattern from `erp-customer-core-helper.php` (Phase 1)

## Decision

- **No duplicate** JobCard/Service/QC tables
- **No modification** of M17/M20/M30 PHP pages
- **New** orchestration layer only

# PHASE 3 — Repo Gap Review

## Phase 1 (reuse)

| Asset | Use in Rule Engine |
|-------|-------------------|
| `erp_customer_contracts` | contract_type, threshold, approval flags |
| `erp_customer_contract_acceptances` | context only |
| `erp-customer-core-helper.php` | pattern reference |

## Phase 2 (reuse)

| Asset | Use in Rule Engine |
|-------|-------------------|
| `erp_operation_cases` | operation_case_id, contract_id linkage |
| `erp_operation_service_steps` | service_step_id context |
| `erp-operation-engine-helper.php` | pattern reference |

## M17–M30 Foundations (not rebuilt)

- `erp_jobcards`, `erp_service_operations`, `erp_qc_checks`, `erp_delivery_controls` remain as-is
- Rule Engine orchestrates decisions without replacing M30 pages

## M22 Inventory (read-only)

- `erp_parts` — part lookup by id/code
- `erp_stock_movements` — estimated available qty (no ISSUE write)

## Gap Filled

No prior `erp_rule_*` tables existed. Phase 3 adds decision layer only.

## Helpers Reused

- `erp-auth-context.php`, `erp-permission-guard.php`, `erp-csrf.php`
- New: `erp-rule-engine.php` (non-sensitive)

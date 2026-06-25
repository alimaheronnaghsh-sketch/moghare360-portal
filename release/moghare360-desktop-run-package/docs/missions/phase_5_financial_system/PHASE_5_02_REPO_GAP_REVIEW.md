# PHASE 5 — Repo Gap Review

## Legacy / Foundation

| Asset | Status | Phase 5 Usage |
|-------|--------|---------------|
| `dbo.Payments` / `dbo.erp_payments` | May exist | Not modified; Phase 5 uses `erp_payment_records` extension when legacy exists |
| `dbo.JobCard` | Foundation | Referenced via `jobcard_id` on cost headers |
| `dbo.erp_service_operations` | M20 | Read-only in price list |
| `dbo.erp_parts` | M22 | Not modified |

## Phase 1–4 Tables

Verified at runtime via test tool; finance links via `operation_case_id`, `customer_id`, `inventory_item_id`.

## Helpers Reused (not modified)

`erp-auth-context.php`, `erp-csrf.php`, `erp-permission-guard.php`, plus phase helpers unchanged.

## New Phase 5 Tables

All prefixed `erp_finance_*`, `erp_jobcard_cost_*`, `erp_payment_records`, `erp_invoice_previews`, `erp_financial_summary_snapshots`, `erp_finance_history`.

## Duplicates Avoided

- No DROP/RENAME on legacy Payments
- `erp_payment_records` created as extension when `erp_payments` already exists

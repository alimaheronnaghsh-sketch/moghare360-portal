# PHASE 5 — Financial Summary

## Dashboard

`erp-finance-control-center.php` aggregates from `erp_jobcard_cost_headers`:

- Total payable / paid / remaining
- Counts: UNPAID, PARTIAL_PAID, PAID+OVERPAID

## Snapshots Table

`erp_financial_summary_snapshots` supports scopes: JOBCARD, CUSTOMER, DAILY, GLOBAL.

Helper `pricing_create_global_snapshot()` available for future controlled snapshot writes.

## Payment Status Badges

| Status | Label |
|--------|-------|
| UNPAID | پرداخت‌نشده |
| PARTIAL_PAID | پرداخت جزئی |
| PAID | پرداخت‌شده |
| OVERPAID | پرداخت مازاد |

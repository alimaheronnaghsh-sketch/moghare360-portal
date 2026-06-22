# PHASE 5 — Pricing Engine

Helper: `public_html/includes/erp-pricing-engine.php`

## Key Functions

- `pricing_calculate_line_total()` — line math with floor at zero
- `pricing_recalculate_cost_header()` — aggregate lines by type, update totals
- `pricing_update_payment_status()` — UNPAID / PARTIAL_PAID / PAID / OVERPAID
- `pricing_get_or_create_cost_header()` — idempotent header per operation_case or jobcard
- `pricing_generate_*_code()` — COST-, PAY-, INV-PREV- prefixes

## Pages

- `erp-service-price-list.php` — CRUD for service prices, labour rates, margin rules (self-POST)

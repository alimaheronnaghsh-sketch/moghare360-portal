# PHASE 5 — Payment Tracking

Pages:
- `erp-payment-tracking.php` — list headers, filter by payment_status, payment form
- `submit-payment-record.php` — controlled write

## Submit Rules

- Required: `cost_header_id`, `payment_amount` > 0
- Auto code: `PAY-YYYYMMDD-HHMMSS-random4`
- Inserts `erp_payment_records` with status RECORDED
- Recalculates cost header and payment_status
- CSRF: `finance_payment_record`

## Payment Methods

CASH, CARD, BANK_TRANSFER, POS_PLACEHOLDER, CREDIT, OTHER (tracking only — no gateway)

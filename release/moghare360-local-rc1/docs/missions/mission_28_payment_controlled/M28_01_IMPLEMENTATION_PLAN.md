# Mission 28 - Implementation Plan

## Deliverables
1. SQL: erp_payments + erp_payment_history
2. erp-payment-create.php — payment + history in transaction
3. erp-payment-readonly-list.php — read-only list
4. erp-jobcard-payment-summary.php — calculated totals read-only
5. tools/test-erp-payment-foundation.php — CLI validation

## Create Flow
1. Auth Context (user_id = 10001)
2. Permission Guard (`payment.create`)
3. CSRF validation
4. Validate active jobcard_id, payment_type, payment_method, payment_amount > 0
5. currency_code default IRR
6. payment_status fixed RECEIVED
7. Transaction: INSERT payment + INSERT history (PAYMENT_RECEIVED)
8. Composite lookup for payment_id
9. Commit

## Summary Flow
- SUM(payment_amount) WHERE payment_status = RECEIVED AND is_active = 1
- COUNT(*) for payment_count
- Outstanding placeholder TBD (no expected_total in M28)

## Success Message
Payment Created OK

# Payment Status UX Rules

## Payment Preview Detail
`erp-payment-preview-detail-ux.php`

## Fields
- payment_id, type, method, amount, status, received_at
- Payment history from erp_payment_history
- JobCard binding card

## Notice
"This is payment preview only, not accounting ledger."

## Guard
- payment.view for detail
- payment.summary.view for workbench/jobcard preview

## Final Payment UX Decision
Payment visibility without ledger semantics.

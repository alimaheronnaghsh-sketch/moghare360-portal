# JobCard Financial Summary Rules

## Page
`erp-jobcard-finance-preview-ux.php`

## ID
- `?jobcard_id=` default 1

## Sections
1. JobCard + customer/vehicle summary
2. Payment summary: payment_count, total_received, latest_payment
3. Service operation summary
4. Part usage summary
5. Purchase request summary
6. Balance preview (placeholder estimated_total)

## Balance Preview
- estimated_total = informational placeholder from service/part counts
- total_received = actual read-only from erp_payments
- balance_preview = estimated - received when estimate defined

## Final Summary Decision
Read-only financial snapshot with explicit placeholder labeling.

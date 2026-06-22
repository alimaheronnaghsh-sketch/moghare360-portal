# Mission 24 - Part Usage Boundary

## POST Rules
Auth Context, CSRF, Permission Guard (jobcard.part.use + stock.issue.create)

## Validation
- jobcard_id required, active JobCard
- service_operation_id optional; if set — same JobCard, allowed status
- part_id required, active
- stock_location_id required, active
- quantity positive decimal

## Transaction Order
1. Stock check (on-hand >= quantity)
2. INSERT erp_jobcard_part_usage (USED)
3. Fetch part_usage_id
4. INSERT erp_stock_movements (ISSUE, JOBCARD_PART_USAGE)
5. INSERT history (JOBCARD_PART_USED)
6. Negative stock guard
7. COMMIT

## Forbidden Writes
Finance, invoice, payment, purchase, delivery

## Identity
Fetch part_usage_id by composite lookup — no SCOPE_IDENTITY

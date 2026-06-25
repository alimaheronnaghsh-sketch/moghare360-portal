# JobCard Finance Link Rules

## Purpose
This document locks rules linking payments to JobCards.

## Mission 27 Boundary
Rules documented only. No JobCard or payment writes in Mission 27.

## Mandatory Link (Locked)
- Every payment **must** reference valid `jobcard_id`
- FK: `erp_payments.jobcard_id` → `dbo.erp_jobcards(jobcard_id)`

## JobCard Validity (Locked)
- JobCard must exist
- JobCard `lifecycle_state` must be **ACTIVE** at payment create time (Mission 28)
- Closed or cancelled JobCard: no new payment without future override policy

## customer_id (Optional Denormalization)
- May be copied from JobCard → customer at insert time
- Nullable on table; validation ensures consistency when populated
- Summary queries may join JobCard for customer display

## Side Effect Prohibitions (Locked)

### Payment Must NOT Change JobCard Status
- Recording payment does not auto-transition `jobcard_status`
- No silent move to PAID / CLOSED / DELIVERED

### Payment Must NOT Release Delivery
- No delivery flag set on payment
- Delivery dependency explicitly out of scope for M27/M28

### Payment Summary Is Read-Only Calculation
- `erp-jobcard-payment-summary.php` (M28) — SELECT aggregates only
- No summary table with writable balance column in M28 initial scope

## Physical Delete Forbidden (Locked)
- Payments use `is_active` + status CANCELLED / REVERSED
- No `DELETE FROM erp_payments`

## One JobCard, Many Payments
- Multiple ADVANCE / PARTIAL payments allowed per JobCard
- FULL payment does not block additional PARTIAL in future unless business rule added later

## Relationship to Service Operations
- Payment is JobCard-level in M28
- Optional future link to service_operation_id not in M27/M28 initial scope

## Mission 27 Boundary
Link rules documented only.

## Final JobCard Finance Decision
Payment always tied to active JobCard; no status/delivery side effects; summary calculated read-only; no physical delete.

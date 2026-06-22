# Mission 24 - Stock Deduction Safety

## Ledger Only
No direct balance column updates. On-hand = SUM(movements).

## Signed Movement Sum
Positive: SEED, RECEIPT, RETURN, ADJUSTMENT  
Negative: ISSUE, REVERSAL

## Pre-Issue Check
Reject if quantity_on_hand < requested quantity with safe message: Insufficient stock.

## Post-Issue Guard
Before COMMIT, recalculate on-hand; rollback if negative.

## ISSUE Row
- movement_type = ISSUE
- reference_type = JOBCARD_PART_USAGE
- reference_id = part_usage_id
- quantity > 0

## Mission 24 Boundary
No RETURN or REVERSAL from PHP in this mission.

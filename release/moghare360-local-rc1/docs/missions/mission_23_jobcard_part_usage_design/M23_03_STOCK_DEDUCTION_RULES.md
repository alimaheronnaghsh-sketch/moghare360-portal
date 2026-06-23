# Stock Deduction Rules

## Purpose
This document locks stock deduction rules for JobCard part usage.

## Mission 23 Boundary
Design only. No stock write or deduction in Mission 23.

## Deduction Mechanism (Locked)

### Movement Ledger Only
Stock deduction occurs **only** through `dbo.erp_stock_movements`.
No direct UPDATE to a balance column.
On-hand quantity is **always** calculated from movement ledger sum.

### Issue Movement
When part usage is registered (Mission 24):
| Field | Value |
|-------|-------|
| movement_type | ISSUE |
| reference_type | JOBCARD_PART_USAGE |
| reference_id | part_usage_id |
| part_id | from usage |
| stock_location_id | from usage |
| quantity | positive DECIMAL |
| created_by_user_id | Auth Context user |

## Pre-Issue Validation (Locked)
Before INSERT of ISSUE movement:
1. Calculate `quantity_on_hand` for `(part_id, stock_location_id)` from ledger
2. Confirm `quantity_on_hand >= usage.quantity`
3. If insufficient stock → reject transaction (rollback usage + movement)

## Negative Stock Forbidden
- `quantity_on_hand` must never go negative after ISSUE
- Application layer enforces before commit
- No override in Mission 24 without explicit future mission approval

## Transaction Order (Future — Mission 24)
1. BEGIN transaction
2. INSERT `erp_jobcard_part_usage` (usage_status = USED)
3. INSERT `erp_jobcard_part_usage_history` (action e.g. PART_USAGE_CREATED)
4. INSERT `erp_stock_movements` (ISSUE, reference to part_usage_id)
5. COMMIT or ROLLBACK all

## No Silent Stock Change
Every ISSUE must:
- Have Auth Context + Permission Guard + CSRF
- Run inside controlled transaction
- Leave audit trail on usage and movement

## Forbidden Patterns
- Direct UPDATE of computed balance table (none exists; must not be introduced)
- ISSUE without usage row
- ISSUE without reference_type / reference_id
- ISSUE when Service Operation status is CANCELLED

## Relationship to Mission 22 Stock List
Mission 22 read-only stock list already aggregates movements with signed CASE.
ISSUE subtracts quantity in that model — consistent with M23 design.

## Mission 23 Boundary
No ISSUE rows created in Mission 23.

## Final Deduction Decision
Deduction = ISSUE movement linked to part_usage_id; pre-check on-hand; negative stock forbidden; ledger-only balance.

# Return and Reversal Rules

## Purpose
This document locks return and reversal rules for part usage corrections.

## Mission 23 Boundary
Design only. No RETURN or REVERSAL movements in Mission 23.

## Correction Philosophy (Locked)
- Physical DELETE of part usage forbidden
- Corrections via RETURN movement and/or usage status change with history
- Full reversal chain must remain auditable

## Return Rules (Locked)

### RETURN Movement
When parts are physically returned to stock after usage:
| Field | Value |
|-------|-------|
| movement_type | RETURN |
| reference_type | JOBCARD_PART_USAGE |
| reference_id | original part_usage_id (or return usage id per M24 charter) |
| quantity | positive (returned amount) |
| created_by_user_id | Auth Context user |

### Usage Status on Return
- `usage_status` transitions: USED → RETURNED (partial return may need future split usage — deferred)
- History row with action_code e.g. PART_USAGE_RETURNED

### Return Validation
- RETURN quantity must not exceed originally issued quantity minus prior returns
- RETURN increases quantity_on_hand via ledger only

## Reversal Rules (Locked)

### REVERSAL Movement
When a usage must be voided administratively:
| Field | Value |
|-------|-------|
| movement_type | REVERSAL |
| reference_type | JOBCARD_PART_USAGE |
| reference_id | original part_usage_id |
| quantity | positive (mirrors voided issue amount) |
| movement_note | required reason text |

### Usage Status on Reversal
- `usage_status` → REVERSED
- `reversed_by_usage_id` may link to compensating record if M24 uses split rows
- Physical row retained with `is_active` policy per M24 SQL charter

### Reversal Requirements
- `changed_by_user_id` on history mandatory
- `change_note` / `movement_note` mandatory (reason)
- Permission: `jobcard.part.reverse` and `stock.return.create` or dedicated reverse permission

## CANCELLED Usage Status
- Usage cancelled before ISSUE committed: status CANCELLED, no movement
- Usage cancelled after ISSUE: must use RETURN or REVERSAL, not DELETE

## Financial Return (Locked)
Financial return, credit note, or invoice adjustment is **out of scope** for Mission 23 and Mission 24.
Finance linkage deferred to Mission 27/28 or future finance mission.

## Forbidden
- DELETE FROM erp_jobcard_part_usage
- DELETE FROM erp_stock_movements
- Silent RETURN without audit
- RETURN without reference to original usage

## Mission 24 Scope Note
Mission 24 may implement USED + ISSUE only.
RETURN and REVERSAL UI may be deferred to Mission 24b or Mission 25+ unless explicitly included in M24 charter.

## Final Return/Reversal Decision
RETURN for physical return; REVERSAL for administrative void; both ledger-backed; reason required; no financial return in M23/M24.

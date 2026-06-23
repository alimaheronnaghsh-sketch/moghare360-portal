# Payment Status Model

## Purpose
This document locks payment status, types, and methods for future implementation.

## Mission 27 Boundary
Enums documented only. No status engine in Mission 27.

## Payment Status Model (Locked)

| Status | Meaning |
|--------|---------|
| DRAFT | Created; not yet counted in receivable summary |
| RECEIVED | Confirmed received; counts toward total_received |
| CANCELLED | Voided before or after receive; no physical delete |
| REVERSED | Reversal/refund flow; reserved for future controlled mission |

## Status Transition Rules (Design)

### DRAFT
- Editable only by creator or owner (future policy)
- May transition to RECEIVED (future `payment.receive` or create-as-RECEIVED in M28)
- May transition to CANCELLED

### RECEIVED
- Counts in receivable calculation
- **Immutable** — no amount edit
- REVERSED transition reserved for future refund mission

### CANCELLED
- Does not count in total_received
- History required
- No physical delete

### REVERSED
- Reserved for future controlled reversal flow
- Not implemented in Mission 28 initial scope unless explicitly chartered

## Payment Types (Locked)

| payment_type | Description |
|--------------|-------------|
| ADVANCE | Pre-payment before service completion |
| PARTIAL | Partial settlement |
| FULL | Full settlement |
| REFUND_PLACEHOLDER | Reserved; not implemented in M28 |

## Payment Methods (Locked)

| payment_method | Description |
|----------------|-------------|
| CASH | Cash collection |
| CARD | Card payment |
| BANK_TRANSFER | Bank transfer |
| POS | Point of sale terminal |
| OTHER | Other method with note |

## Validation Rules (Future M28)
- `payment_amount` > 0
- `payment_type` in allowed enum
- `payment_method` in allowed enum
- `payment_status` in allowed enum
- Initial create: DRAFT or RECEIVED only (M28 charter)

## History on Status Change (Locked)
Every transition writes `erp_payment_history` with action_code, old_status, new_status, changed_by_user_id, change_note.

## Mission 27 Boundary
Status model documented only.

## Final Status Decision
Four statuses; four payment types; five payment methods; RECEIVED immutable; CANCELLED/REVERSED replace delete.

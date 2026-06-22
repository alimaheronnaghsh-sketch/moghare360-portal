# Receivables Model

## Purpose
This document locks the conceptual receivables model for customer payments.

## Mission 27 Boundary
Model documented only. No receivable tables or writes in Mission 27.

## Core Principles (Locked)

### 1. Expected Total (Future)
- JobCard may have `expected_total_amount` in a future field or related table
- Not required for Mission 28 initial prototype unless explicitly chartered
- Informational planning amount for outstanding calculation

### 2. Payments Reduce Outstanding (Calculated)
- Outstanding balance = expected_total − sum(RECEIVED payment amounts)
- If expected_total is NULL: outstanding shown as placeholder (e.g. "—" or "TBD")
- **Balance is calculated, not directly overwritten**

### 3. Payment Immutability (Locked)
- Payment records **immutable after creation** in Mission 28
- No UPDATE of `payment_amount` after RECEIVED
- Corrections via CANCELLED or REVERSED status + history (future controlled flow)
- **Physical delete forbidden**

### 4. Reversal / Refund (Future)
- REVERSED status with audit history
- REFUND_PLACEHOLDER type reserved
- Not simple DELETE

## Conceptual Entities

### Payment Record (Future — erp_payments)
Atomic customer payment event linked to JobCard.

### Payment History (Future — erp_payment_history)
Audit trail for status changes.

### JobCard Payment Summary (Future — Read-Only View)
Aggregates per JobCard:
- `total_received` = SUM(payment_amount) WHERE payment_status = RECEIVED AND is_active = 1
- `payment_count` = COUNT of RECEIVED payments
- `outstanding_balance` = calculated (expected_total − total_received) when expected_total available
- `last_payment_at` = MAX(received_at) for RECEIVED

## Calculation Rules (Locked)

```
total_received(jobcard_id) =
  SUM(payment_amount)
  FROM erp_payments
  WHERE jobcard_id = ?
    AND payment_status = N'RECEIVED'
    AND is_active = 1

outstanding_balance(jobcard_id) =
  expected_total_amount(jobcard_id) - total_received(jobcard_id)
  WHEN expected_total_amount IS NOT NULL
  ELSE NULL (placeholder)
```

## DRAFT Payments
- DRAFT payments do **not** affect total_received until transitioned to RECEIVED (future mission or M28 extension)
- Mission 28 initial scope may create directly as RECEIVED or DRAFT per charter

## Currency
- `currency_code` on each payment (e.g. IRR)
- Multi-currency conversion out of scope for M27/M28

## Relationship to Purchase (M26)
- Purchase request `estimated_unit_cost` is informational only
- Customer receivables and supplier payables remain separate domains

## Mission 27 Boundary
Receivables model documented only. No tables. No calculations executed.

## Final Receivables Decision
Calculated outstanding from payments; immutable payment rows; no direct balance overwrite; reversal/refund via status not delete.

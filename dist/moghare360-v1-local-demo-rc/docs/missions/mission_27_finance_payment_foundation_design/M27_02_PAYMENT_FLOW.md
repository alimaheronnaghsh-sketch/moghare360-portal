# Payment Flow

## Purpose
This document locks the customer payment flow for Soft Run finance foundation.

## Mission 27 Boundary
Flow documented only. No payment engine or writes in Mission 27.

## Flow Overview (Locked)

```
JobCard exists (ACTIVE)
  → Customer payment can be registered in future Mission 28
  → Payment type: ADVANCE / PARTIAL / FULL (REFUND_PLACEHOLDER reserved)
  → Payment method: CASH / CARD / BANK_TRANSFER / POS / OTHER
  → Payment status: DRAFT or RECEIVED (Mission 28 initial scope)
  → Payment affects customer receivable summary (calculated read-only)
  → No final invoice in Mission 27 or Mission 28
  → No accounting export
  → No supplier payment
```

## Stage Definitions

### 1. JobCard Context
- Valid `jobcard_id` required for every payment
- JobCard must be ACTIVE (lifecycle)
- Customer context derived from JobCard → customer_id (denormalized optional on payment row)

### 2. Payment Registration (Future — Mission 28)
- Shop staff records customer payment against JobCard
- Initial status: **DRAFT** or **RECEIVED** (per M28 charter)
- `payment_amount` > 0, `currency_code` set (e.g. IRR)
- Immutable after creation — no field edit; cancel/reverse via status change only (future)

### 3. Payment Types (Locked Design)

| Type | Meaning |
|------|---------|
| ADVANCE | Payment before work completion |
| PARTIAL | Partial settlement against expected total |
| FULL | Full settlement (may equal outstanding) |
| REFUND_PLACEHOLDER | Reserved for future refund flow; not implemented in M28 |

### 4. Receivable Impact (Calculated)
- Each RECEIVED payment increases total received on JobCard
- Outstanding = expected_total (future field) − sum(RECEIVED payments)
- If expected_total not yet defined in M28, summary shows received only + placeholder outstanding

### 5. No Invoice Finalization (Locked)
- Mission 27 and Mission 28 do not create finalized invoice documents
- No invoice number sequence in M28
- Payment records stand alone for Soft Run visibility

### 6. No Accounting Export (Locked)
- No journal entry
- No GL posting
- No export file generation

### 7. No Supplier Payment (Locked)
- Customer payment flow is independent of purchase/supplier AP
- Purchase request (M26) remains without finance write

### 8. No Delivery Dependency (Locked)
- Payment does not auto-release vehicle for delivery
- Delivery gate designed in future mission if needed

## Reversal / Refund (Future)
- REVERSED status reserved
- Physical delete forbidden
- Refund flow separate from simple cancel

## Mission 27 Boundary
No payment rows created. No receivable writes. No invoice.

## Final Flow Decision
JobCard → customer payment (M28) → calculated receivable summary only; no invoice, export, supplier payment, or delivery side effects.

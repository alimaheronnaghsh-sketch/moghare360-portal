# Payment Before Delivery Rules

## Purpose
This document locks payment-before-delivery rules for Soft Run gate design.

## Mission 29 Boundary
Rules designed only. No payment enforcement execution in Mission 29.

## Core Rules (Locked)

### 1. Delivery Reviews Payment Summary
- Before delivery allow/block decision, system reads **calculated** payment summary (M28 pattern)
- `total_received` = SUM(RECEIVED payments) for jobcard_id
- No new payment created by delivery flow

### 2. Design-Only in Mission 29
- Mission 29 documents rules; Mission 30 may display block_reason referencing payment
- **No payment gate execution** in Mission 29 (no code blocks release automatically unless M30 charters display-only vs enforce)

### 3. Full Payment Required vs Optional (Soft Run Gate)
- Soft Run Gate page (`erp-soft-run-readiness.php` — M30) must **show** whether full payment is required or optional (configuration/display flag — design)
- Policy options (design labels):
  - `PAYMENT_OPTIONAL` — delivery may proceed with partial/zero payment (prototype default for M30 unless user charters strict)
  - `PAYMENT_FULL_REQUIRED` — block_reason = PAYMENT_INSUFFICIENT when total_received < expected_total
- `expected_total` may be placeholder TBD (consistent with M28)

### 4. Delivery Must Not Create Payment
- `erp-delivery-control.php` has no INSERT into `erp_payments`
- Payment creation remains `erp-payment-create.php` only

### 5. Payment Must Not Auto-Release Delivery
- Recording payment (M28) does not set `delivery_allowed = 1`
- Does not transition delivery_status to RELEASED
- Staff must perform explicit delivery release action (future M30) with permission

### 6. No Final Invoice (Locked)
- Mission 29 and Mission 30 do not create finalized invoices
- Payment summary does not generate invoice document
- Outstanding balance remains calculated placeholder

## Block Reason Examples (Design — Future M30)

| block_reason | Condition |
|--------------|-----------|
| QC_NOT_PASSED | qc_status ≠ PASSED |
| QC_PENDING | QC not completed |
| PAYMENT_INSUFFICIENT | Policy FULL_REQUIRED and received < expected |
| MANUAL_HOLD | Staff hold — future |

## Mission 30 Indicative Behavior
- Display payment summary on Soft Run readiness page
- Set `delivery_allowed` and `block_reason` based on QC + payment review (controlled write in M30)
- No automatic payment creation

## Mission 29 Boundary
Payment-delivery rules documented only.

## Final Payment-Delivery Decision
Delivery reads payment summary; no cross-write; full payment policy visible on Soft Run gate; invoice finalization out of scope.

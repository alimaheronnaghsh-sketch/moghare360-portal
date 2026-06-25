# Delivery Flow

## Purpose
This document locks the delivery flow from service completion to vehicle release.

## Mission 29 Boundary
Flow documented only. No delivery engine or writes in Mission 29.

## Flow Overview (Locked)

```
JobCard service work completed
  → QC required
  → QC status = PASSED (future Mission 30)
  → Payment status reviewed (read-only summary from M28)
  → Delivery allowed or blocked (delivery_control record)
  → Delivery record created in future Mission 30
  → No final invoice in Mission 29
  → No Customer Portal in Mission 29
```

## Stage Definitions

### 1. Service Done
- Operational signal: JobCard / service operation indicates work complete
- Design status: `SERVICE_DONE` or `QC_PENDING` (see M29_04)
- Mission 29 does not change live status

### 2. QC Required
- QC check created (future M30): initial `qc_status` = PENDING
- Inspector completes checklist (design items in M29_02)
- Outcome: PASSED, FAILED, or RECHECK_REQUIRED

### 3. QC PASSED
- Prerequisite for delivery readiness (design)
- FAILED blocks delivery until RECHECK_REQUIRED → PASSED

### 4. Payment Status Reviewed
- Read-only: `total_received` from `erp_payments` (M28)
- Payment before delivery rules apply (M29_06) — design only in M29
- Payment does **not** auto-release delivery
- Delivery does **not** create payment

### 5. Delivery Allowed or Blocked
- `erp_delivery_controls` row (future M30)
- `delivery_status`: BLOCKED | READY | RELEASED | CANCELLED
- `delivery_allowed`: BIT flag for gate display
- `block_reason` when blocked (e.g. QC_FAILED, PAYMENT_INSUFFICIENT — design labels)

### 6. Delivery Record (Future — Mission 30)
- Internal prototype only
- Audit/history required on release
- No Customer Portal handoff in M30

### 7. No Final Invoice (Locked)
- Mission 29 and Mission 30 do not finalize invoices
- No invoice number, no tax document

## Explicit Prohibitions in Flow
- No customer signature in Mission 29
- No production deploy
- No silent delivery release
- No payment gate bypass without audit

## Mission 29 Boundary
No delivery rows. No QC writes. No invoice.

## Final Flow Decision
Service done → QC → payment review → delivery allow/block → M30 prototype record; portal and invoice deferred.

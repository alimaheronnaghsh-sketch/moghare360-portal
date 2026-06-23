# Customer Confirmation Boundary

## Purpose
This document locks the customer confirmation boundary for QC and delivery.

## Mission 29 Boundary
Boundary documented only. No customer confirmation implementation.

## Customer Confirmation Scope (Locked)

### What Mission 29 Defines
- Customer must be informed/acknowledged before delivery (business rule — design)
- Delivery without future audit trail is **not allowed** (design requirement for M30+)
- Customer identity available via JobCard → customer_id (existing M17)

### What Mission 29 Must NOT Do
- Implement customer signature capture
- Implement digital signature pad / image upload
- Modify Customer Portal
- Send customer notifications (SMS/email)
- Create customer-facing delivery acceptance page

## Mission 30 Prototype Boundary (Locked)
- **Internal admin prototype only**
- Staff may record "customer acknowledged" in `change_note` or future field — not production signature
- No production signature workflow in Mission 30 unless explicitly re-chartered

## Audit Requirement (Design)
Future delivery release must have:
- `released_by_user_id`
- `released_at`
- History row in `erp_delivery_control_history`
- Optional `customer_confirmation_note` (text only in M30 prototype)

## No Signature Bypass (Locked)
- No silent delivery release without staff user in audit
- No automatic DELIVERED status without controlled write + permission `delivery.control.release`

## Customer Portal (Locked)
- Customer Portal files are **forbidden** to modify in Mission 29 and Mission 30
- Customer self-service delivery confirmation deferred to future production mission

## Mission 29 Boundary
Customer confirmation = boundary rule only.

## Final Customer Boundary Decision
Signature deferred; internal prototype audit only in M30; portal untouched; no delivery without audit trail.

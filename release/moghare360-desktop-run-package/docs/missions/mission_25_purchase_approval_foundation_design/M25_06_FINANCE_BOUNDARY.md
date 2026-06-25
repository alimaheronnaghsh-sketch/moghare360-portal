# Finance Boundary

## Purpose
This document locks the finance boundary for purchase approval design.

## Mission 25 Boundary
No finance integration designed in detail. Explicit prohibitions locked.

## Mission 25 and Mission 26 Prohibitions (Locked)
- No supplier payment
- No accounts payable (AP) posting
- No general ledger / journal entry
- No invoice from supplier
- No tax calculation or withholding
- No accounting export
- No automatic finance write on APPROVED

## Informational Fields Only (Locked)

### estimated_unit_cost
- Optional on purchase request
- **Informational only** — not a commitment, not a posted cost
- Does not create accrual or liability

### currency_code
- Optional (e.g. IRR)
- Display / planning only
- No FX conversion in M25/M26

## APPROVED Status Finance Rule (Locked)
Transition to APPROVED must **not**:
- Create payment record
- Create AP invoice
- Post to ledger
- Trigger budget commitment (future feature)

## Stock Receipt Finance Rule (Locked)
Future stock RECEIPT must not auto-post finance in purchase approval missions.
Cost capitalization deferred to future finance mission.

## Future Finance Linkage (Indicative)
Purchase financial impact designed in:
- Dedicated future finance mission (e.g. Mission 27/28 or finance charter)

Possible future links (not designed in M25):
- APPROVED request → committed budget
- RECEIVED → inventory valuation
- Supplier invoice → AP payment

## Relationship to Part Usage (M24)
Part usage ISSUE has no finance write.
Purchase request is separate operational path when stock unavailable.

## Mission 25 Boundary
Finance boundary documented only. No finance tables or writes.

## Final Finance Decision
estimated_unit_cost is informational; all monetary posting deferred; M25 and M26 forbid finance write.

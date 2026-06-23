# Finance Boundary

## Purpose
This document locks the finance boundary for JobCard part usage design.

## Mission 23 Boundary
Finance integration is documented as out of scope only.
No finance tables, pricing rules, or posting logic are designed in detail in Mission 23.

## Mission 24 Rule (Locked)
Part usage in Mission 24 performs **no Finance Write**:
- No general ledger posting
- No accounts receivable / payable entry
- No cost of goods sold posting
- No tax calculation
- No invoice line creation
- No payment record

## Out of Scope for Mission 23 and Mission 24

| Domain | Status |
|--------|--------|
| Part pricing on usage | Deferred |
| Customer invoice from parts | Deferred |
| Payment collection | Deferred |
| Tax | Deferred |
| Accounting export | Deferred |
| Supplier payment | Deferred |
| Purchase request | Mission 25+ (per M21) |
| Delivery settlement | Future mission |

## Future Finance Linkage (Indicative)
Part usage financial impact designed only in:
- **Mission 27/28** or dedicated future finance mission charter

Possible future links (not designed in M23):
- usage row → invoice line
- usage cost → job costing report
- usage → COGS journal entry

## Operational vs Financial Separation (Locked)
Mission 23 / 24 scope = operational stock deduction and usage tracking only.
Financial value of issued parts remains unposted until explicit finance mission approval.

## Pricing on Part Master
`erp_parts` in Mission 22 has no unit cost field in current schema.
Adding cost/price fields requires separate mission approval — not part of M23.

## Final Finance Decision
Part usage design is operationally complete at stock ledger level in M24; all monetary flows deferred to Mission 27/28 or future finance mission.

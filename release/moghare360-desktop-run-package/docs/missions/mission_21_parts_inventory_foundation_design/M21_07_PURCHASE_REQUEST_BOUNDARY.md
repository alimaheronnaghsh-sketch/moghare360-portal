# Purchase Request Boundary

## Purpose
This document locks the Purchase Request boundary relative to Parts / Inventory foundation.

## Mission 21 Rule
Mission 21 records the boundary only.
No Purchase Request is created.
No supplier workflow is implemented.

## When Purchase Is Needed (Future)
If a required part is not available in stock:
- Shop floor may need a purchase request
- Purchase design and implementation belong to **Mission 25** (and later finance missions)
- Mission 21 does not define purchase table structure in detail

## Mission 21 Boundary (Locked)
Mission 21 must not:
- Create purchase request tables
- Create purchase request PHP pages
- Write purchase request rows
- Approve purchases
- Create supplier payment
- Post finance entries from purchase

## Relationship to Inventory Design

```
Part not in stock
  → Future: purchase.request.create (Mission 25+)
  → NOT in Mission 21 or Mission 22 scope
```

Mission 22 scope (indicative):
- Part master create
- Stock location master (structure)
- Read-only stock list (no consumption)

## Separation from Finance (Locked)
- No supplier payment in Mission 21
- No finance write in Mission 21
- No invoice write in Mission 21
- Purchase-to-finance bridge deferred to post-M25 missions

## Permission Placeholder (Future — Mission 25)
- `purchase.request.create` — reserved in M21_08; not implemented in M21

## Service Operation WAITING_PARTS
`WAITING_PARTS` on Service Operation is a status placeholder.
It does not auto-create purchase requests in Mission 21 or Mission 22.

## Final Boundary Decision
Mission 21 locks purchase as out-of-scope.
Purchase Request design starts in Mission 25 only after parts foundation (M22) and usage design (M23–24) gates.

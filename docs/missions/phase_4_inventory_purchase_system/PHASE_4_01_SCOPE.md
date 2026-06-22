# PHASE 4 — Scope

## Goal

Transform parts from simple registration into a real inventory and purchase flow:

**In stock:** Parts Catalog → Stock Availability → Reservation → Movement History

**Out of stock:** Parts Catalog → Purchase Request → Supplier Flow → Status Lifecycle → Pending Receive

## In Scope

- Parts catalog foundation (`erp_inventory_items`)
- Stock board (read-only availability)
- Controlled part reservation
- Controlled purchase request
- Supplier management foundation
- Purchase status lifecycle
- Pending receive foundation
- Stock movement history
- Safe Rule Engine links (no rewrite)

## Out of Scope

- Auth / permission model changes
- Destructive DB migration (DROP / RENAME)
- Legacy customer portal changes
- Real accounting, invoice, tax, supplier payment
- External API / barcode hardware
- Automatic stock deduction without controlled form
- SaaS / tenant activation

## Stock Rules

- `available_to_reserve = max(0, available_qty - reserved_qty)`
- Reservation increases `reserved_qty` only; does not decrease `available_qty`
- `PENDING_RECEIVE` increases `pending_receive_qty`
- `RECEIVED` increases `available_qty` and decreases `pending_receive_qty`

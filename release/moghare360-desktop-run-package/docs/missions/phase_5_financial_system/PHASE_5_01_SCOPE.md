# PHASE 5 — Scope

## Goal

Build a real financial layer for MOGHARE360 without official accounting, tax, or final invoice.

## In Scope

- Service / labour / parts pricing
- JobCard cost engine
- Payment tracking (UNPAID / PARTIAL_PAID / PAID / OVERPAID)
- Internal invoice preview (non-official)
- Financial summary snapshots
- Finance control center

## Out of Scope

- Tax engine, VAT, official invoice numbers
- Accounting ledger, export to accounting software
- Bank gateway, card reader, real treasury
- Auth/permission rewrites
- Legacy Payments table modification

## Calculation Rules

- `line_total = max(0, qty * unit_price - discount_amount)`
- `payable_total = service + labour + parts - discount`
- `remaining_total = payable_total - paid_total`
- Payment status derived from paid vs payable totals

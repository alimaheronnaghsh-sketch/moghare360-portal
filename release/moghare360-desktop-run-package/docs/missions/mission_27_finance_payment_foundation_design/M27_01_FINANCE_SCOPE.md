# Finance Scope

## Purpose
This document locks the scope of Finance / Payment foundation design for MOGHARE360 ERP Soft Run.

## Mission Goal (Locked)
Design financial foundation for Soft Run including advance payment, customer payment, outstanding balance, and settlement.

## In Scope (Design Only)
Mission 27 defines:
- Customer payment foundation (`erp_payments` plan)
- Payment history plan (`erp_payment_history`)
- Receivables model (calculated balance, not direct overwrite)
- JobCard ↔ payment link rules
- Payment status model (DRAFT, RECEIVED, CANCELLED, REVERSED)
- Payment types and methods (design enums)
- Permission and audit rules
- SQL implementation plan (no execution)
- UI plan for Mission 28 (no file creation)
- Reporting plan (summary views only)
- Testing plan for Mission 28 (no runnable tests)

## Core Scope Rules (Locked)

### Customer Payments (Future — Mission 28)
- Payment linked to valid active JobCard
- Types: ADVANCE, PARTIAL, FULL, REFUND_PLACEHOLDER
- Methods: CASH, CARD, BANK_TRANSFER, POS, OTHER
- Status flow: DRAFT → RECEIVED (initial M28 scope); CANCELLED / REVERSED deferred or controlled

### Receivables Summary
- Outstanding balance = calculated from expected total (future) minus received payments
- No direct balance column overwrite in Mission 28 initial scope
- Payment summary read-only on JobCard

### Soft Run Finance
- Supports shop floor collection visibility
- Does not replace full accounting system
- Does not export to external ledger

## Out of Scope (Locked)
Mission 27 must not:
- Execute SQL
- Create code or PHP operational files
- Register actual payments
- Finalize invoices
- Perform accounting export
- Process supplier payment (AP)
- Implement tax logic (VAT, withholding, etc.)
- Introduce delivery release dependency
- Modify forbidden or legacy files

## Relationship to Prior Missions

| Prior Mission | Relationship |
|---------------|--------------|
| M25/M26 | Purchase requests — supplier/finance side separate from customer payments |
| M24 | Part usage — no customer charge in M24/M26 |
| M17 | JobCard is mandatory payment context |

## Explicit Separation (Locked)

| Domain | Mission 27/28 Scope |
|--------|---------------------|
| Customer receivables | In scope (design / M28 prototype) |
| Supplier payables | Out of scope |
| Purchase payment | Out of scope (M25/M26) |
| Invoice finalization | Out of scope |
| General ledger | Out of scope |
| Tax calculation | Out of scope |
| Delivery gate | Out of scope |

## Future Mission Chain (Locked Reference)
- Mission 28 — Payment controlled create + list + JobCard summary
- Future mission — Invoice draft/finalization
- Future mission — Accounting export / ledger integration
- Future mission — Supplier AP payment
- Future mission — Tax engine

## Mission 27 Boundary
Design only. No payment rows. No invoices. No exports.

## Final Scope Decision
Mission 27 = customer payment + receivables design for Soft Run; execution deferred to Mission 28; invoice, tax, supplier, and ledger remain out of scope.

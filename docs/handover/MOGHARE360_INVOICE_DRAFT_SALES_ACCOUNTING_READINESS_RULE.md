# MOGHARE360 — Invoice Draft / Sales Accounting Readiness Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Invoice Draft Purpose

Prepare **sales invoice documents** in draft/preview form from completed JobCards — **sales accounting readiness only**, not official fiscal posting.

**Invoice remains draft/readiness only until owner/accounting approval** — LOCKED.

---

## Sales Accounting Readiness Only

| Capability | Phase 23 |
|------------|----------|
| Invoice draft generation | Planned |
| Line items from JobCard | Planned |
| Draft PDF local archive | Planned — local only |
| Official accounting ledger | **NOT activated** |
| Fiscal invoice serial (مالیاتی) | **NOT issued** |

---

## Relation to JobCard

| Rule | Detail |
|------|--------|
| **Relation to JobCard** | One draft per CLOSED JobCard (policy) |
| Preconditions | Delivery complete, QC pass, contract APPLIED |
| Customer / vehicle | From JobCard refs |
| Ceiling | Contract ceiling reflected in draft total |

---

## Relation to Service Operations

| Source | Lines |
|--------|-------|
| **Service operations** | Labor lines — service catalog descriptions |
| Operation history | Audit link to performed steps |

---

## Relation to Consumed Parts

| Source | Lines |
|--------|-------|
| **Consumed parts** | Part lines from Phase 21 consumption |
| Qty and unit sell price | Pricing engine preview |

Per `MOGHARE360_INVENTORY_TO_FINANCE_BINDING_RULE.md`.

---

## Relation to Payment Tracking Preview

| Rule | Detail |
|------|--------|
| Draft total | Matches payment tracking preview amount |
| Partial payment preview | Notes on draft — not settlement |
| **No payment gateway** | FORBIDDEN |

---

## No Tax / Billing Activation

| Integration | Status |
|-------------|--------|
| **No tax/billing activation** | LOCKED |
| VAT calculation | NOT active |
| Electronic billing platform | NOT connected |

---

## No Official Accounting Activation

| Action | Status |
|--------|--------|
| AR post | E-10 |
| Revenue recognition GL | E-10 |
| **No official accounting activation in PHASE 23** | LOCKED |

Future activation requires separate owner gate beyond Phase 23.

---

## Workflow (Planning)

| State | Meaning |
|-------|---------|
| DRAFT | Generated, editable preview |
| REVIEWED | Manager checked |
| APPROVED_FOR_ISSUE | Owner ready — still not official until accounting gate |
| VOID | Cancelled draft |

---

## Audit Requirement

| Event | Detail |
|-------|--------|
| `invoice_draft_created` | jobcard_id |
| `invoice_draft_approved` | approver |
| `official_accounting_blocked` | E-10 attempts |
| Finance history tables | Domain append |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF INVOICE DRAFT / SALES ACCOUNTING READINESS RULE**

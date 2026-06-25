# MOGHARE360 — Customer / Supplier Credit Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Purpose

Unified **credit preview** for customers (receivable) and suppliers (payable) — planning and owner review — without official accounting activation.

Builds on Phase 21 supplier credit preview; extends customer side for Phase 23 handover.

---

## Customer Credit Preview

| Element | Rule |
|---------|------|
| **Customer credit preview** | Open invoice draft totals − payment preview |
| Credit limit planning | Owner-set limit per customer class |
| Over-limit JobCard | Manager approval before delivery |
| Official AR ledger | **NOT active** |

---

## Supplier Credit Preview

| Element | Rule |
|---------|------|
| **Supplier credit preview** | Per Phase 21 — open PR + received unpaid |
| Payment preview applied | Reduces open balance |
| Official AP ledger | **NOT active** |

Per `MOGHARE360_SUPPLIER_CREDIT_PREVIEW_RULE.md`.

---

## Relation to JobCard

| Link | Rule |
|------|------|
| **Relation to JobCard** | Customer credit line per CLOSED JobCard draft |
| Delivery block | Unpaid preview + over credit limit policy |
| Ceiling | Phase 19 contract ceiling separate from credit limit |

---

## Relation to Purchase Request

| Link | Rule |
|------|------|
| **Relation to purchase request** | Supplier preview increases on PR APPROVED / receipt |
| JobCard-origin PR | Links spend to job cost preview |

---

## Relation to Payment Tracking

| Link | Rule |
|------|------|
| Customer payment preview | Reduces customer open preview |
| Supplier payment preview | Reduces supplier open preview |
| No double-count | Single source of payment records |

---

## Aging Preview Concept

| Bucket | Customer | Supplier |
|--------|----------|----------|
| Current | 0–30 days | 0–30 days |
| 31–60 | Preview | Preview |
| 61–90 | Preview | Preview |
| 90+ | Owner review flag | Owner review flag |

Display: «پیش‌نمایش سن بدهی» — not official statement.

---

## Owner / Admin Review Requirement

| Trigger | Action |
|---------|--------|
| Customer 90+ preview | Owner review |
| Supplier 90+ preview | Owner review |
| Credit limit override | Owner approval + audit |

---

## No Official Accounting Activation

| Action | Status |
|--------|--------|
| AR/AP post | E-10 |
| Official balance confirmation | NOT in Phase 23 |

---

## Audit Requirement

| Event | Detail |
|-------|--------|
| `customer_credit_preview_updated` | customer_id |
| `supplier_credit_preview_updated` | supplier_id |
| `credit_limit_override` | approver |
| `erp_finance_history` | Domain append (preview) |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF CUSTOMER / SUPPLIER CREDIT RULE**

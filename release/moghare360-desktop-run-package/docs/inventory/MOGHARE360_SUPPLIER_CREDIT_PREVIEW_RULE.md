# MOGHARE360 — Supplier Credit Preview Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Principle

**Supplier credit remains preview/planning only in PHASE 21.**  
**No official accounting activation** — no AP ledger, no payment posting.

---

## Supplier Credit Preview Scope

| Capability | Phase 21 |
|------------|----------|
| Open balance planning | Preview display |
| Purchase accrual preview | On receipt — preview only |
| Payment applied preview | Manual note — not gateway |
| Official supplier accounting | Phase 23+ gate |
| Payment gateway | FORBIDDEN |

---

## Supplier Open Balance Planning

| Field | Rule |
|-------|------|
| `supplier_id` | FK |
| Preview open balance | Sum unreceived PR + received unpaid preview |
| Currency | IRR default |
| Last updated | Preview calculation timestamp |

Display label: **«پیش‌نمایش — حسابداری رسمی فعال نیست»**

---

## Purchase Request Relation

| Event | Preview effect |
|-------|----------------|
| PR APPROVED | Preview committed obligation (planning) |
| PR cancelled | Reverse preview |
| Receipt | Move to received-unpaid preview bucket |

---

## Received Goods Relation

| Event | Preview effect |
|-------|----------------|
| Goods received | Increase supplier preview payable |
| Return to supplier | Decrease preview |
| Defective claim | Hold in preview pending resolution |

---

## Payment Tracking Preview Relation

| Field | Rule |
|-------|------|
| Payment note | Manual — date, amount, method (cash/transfer) |
| Preview paid flag | Reduces open balance preview |
| No bank integration | LOCKED |

Aligned with Phase 20 delivery payment preview.

---

## Aging Preview Concept

| Bucket | Planning |
|--------|----------|
| Current | 0–30 days |
| 31–60 | Preview bucket |
| 61–90 | Preview bucket |
| 90+ | Highlight for owner review |

**Aging preview concept** — not official statement.

---

## Validation

| Check | Rule |
|-------|------|
| Attempt official AP post | E-10 PRODUCTION_BOUNDARY_BLOCKED |
| Payment gateway call | E-10 |

---

## Audit Requirement

| Event | Detail |
|-------|--------|
| `supplier_credit_preview_updated` | supplier_id, delta |
| `supplier_payment_preview_recorded` | amount, note |
| `official_accounting_blocked` | if attempted |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF SUPPLIER CREDIT PREVIEW RULE**

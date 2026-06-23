# MOGHARE360 — Pricing Engine Finalization Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Pricing Engine Purpose

Finalize **pricing readiness** for services and parts — authoritative preview for JobCard costing and invoice draft — without official GL or tax posting.

**Finance remains readiness / preview until owner approval** — LOCKED.

---

## Service Pricing Readiness

| Element | Rule |
|---------|------|
| Service catalog prices | Reference table — dropdown-linked |
| Labor rate | Per service category or flat rate — owner config (future) |
| Currency | IRR |
| Updates | **Owner/admin approval requirement** — version bump |
| Free-text price on JobCard | FORBIDDEN — catalog or approved override |

---

## Parts Margin Readiness

| Element | Rule |
|---------|------|
| Part cost | From purchase receipt preview (Phase 21) |
| Margin rule | Owner-defined % or fixed markup — planning |
| Sell price preview | Cost + margin on consumption line |
| Below-cost sell | Manager approval + audit |

---

## Discount / Approval Rule

| Discount type | Gate |
|---------------|------|
| Line discount | Manager approval above threshold |
| JobCard discount | Owner approval above threshold |
| **Cost ceiling relation** | Phase 19 — discount cannot bypass ceiling without out-of-contract approval |
| Auto discount from CRM score | FORBIDDEN — Phase 22 |

---

## JobCard Relation

| Rule | Detail |
|------|--------|
| **JobCard relation** | All priced lines attach to JobCard cost preview |
| Operation lines | Labor from service pricing |
| Part lines | From consumption + margin |
| Running total | Updates finance preview header |

---

## Owner / Admin Approval

| Change | Approver |
|--------|----------|
| Catalog price change | Owner |
| Emergency price override on JobCard | Manager + audit |
| Margin policy change | Owner |

---

## Audit Requirement

| Event | Detail |
|-------|--------|
| `price_catalog_updated` | version, approver |
| `price_override_applied` | jobcard_id, reason |
| `pricing_preview_calculated` | jobcard_id |

---

## No Official Accounting Activation

| Action | Status |
|--------|--------|
| GL revenue post | E-10 — FORBIDDEN |
| Official invoice number | NOT in Phase 23 |
| Tax line | NOT activated |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF PRICING ENGINE FINALIZATION RULE**

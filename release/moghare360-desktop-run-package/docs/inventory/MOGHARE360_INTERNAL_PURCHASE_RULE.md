# MOGHARE360 — Internal Purchase Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Internal Purchase Request Definition

**Internal purchase** — procurement from **local suppliers / same-city** sources with short lead time. Separated from external/import path.

**Internal and external purchase must be separated** — LOCKED.

---

## Use Cases

| Origin | Description |
|--------|-------------|
| **JobCard** | Part needed for active job — stock zero |
| **Warehouse reorder** | Below minimum stock level |
| **Owner/admin decision** | Strategic stock buy |

---

## Required Fields

| Field | Control |
|-------|---------|
| Purchase type | `INTERNAL` (enum) |
| Part ref or catalog line | Dropdown/search |
| Quantity | Positive numeric |
| Supplier | Supplier dropdown — local flag |
| Warehouse destination | Main or secondary |
| **JobCard ref** | Required when origin = JobCard |
| Urgency | Dropdown |
| Justification note | Free text |
| Requested by | Session user |

---

## Workflow States

Aligned with permission workflow matrix:

| State | Gate |
|-------|------|
| DRAFT | Request being built |
| SUBMITTED | `purchase.submit` |
| UNDER_REVIEW | Reviewer assigned |
| APPROVED | `purchase.approve` |
| REJECTED | Return to DRAFT with reason |
| APPLIED | PO issued / order placed — `purchase.apply` |
| CLOSED | Received and closed — `purchase.close` |

**Purchase request must follow workflow** — no skip.

---

## Approval Requirement

| Rule | Detail |
|------|--------|
| Manager approval | Required above owner-defined amount threshold |
| Contract ceiling | JobCard-origin PR checks Phase 19 ceiling preview |
| Out-of-contract part | Out-of-contract approval if not in authorized scope |

---

## Receipt Confirmation

| Step | Rule |
|------|--------|
| Goods received | Receipt record — qty, warehouse, date |
| Stock increase | On receipt APPLIED |
| Partial receipt | Partial close; remainder open |
| Audit | `internal_purchase_received` |

---

## Audit Requirement

| Event | Detail |
|-------|--------|
| `purchase_request_created` | type=INTERNAL |
| `purchase_submitted` / `approved` / `rejected` | Standard workflow |
| `purchase_received` | Stock in |
| `erp_purchase_request_history` | Domain row |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF INTERNAL PURCHASE RULE**

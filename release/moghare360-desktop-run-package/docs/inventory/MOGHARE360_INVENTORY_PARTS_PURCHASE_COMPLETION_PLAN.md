# MOGHARE360 — Inventory / Parts / Purchase Completion Plan

**Database:** MOGHARE360_ERP  
**Status:** Planning baseline — PHASE 21  
**Implementation:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Purpose of Inventory Completion

Complete the **inventory, parts, and purchase** layer so live workshop operations (Phase 20) can reserve stock, consume parts on JobCards, request internal/external procurement, handle returns/defectives, and feed **finance preview** — without official accounting or payment gateway activation.

---

## Scope: Part Request to Finance Preview

```
Part need identified (JobCard / operation / reorder)
    │
    ├── Stock available? ──► Stock reservation (JobCard-bound)
    │         │
    │         └── Consumption on service operation
    │
    └── Stock unavailable? ──► Purchase request
              │
              ├── Internal purchase (local supplier)
              └── External purchase (import / long-lead)
                        │
                        └── Receipt → stock → reservation
    │
    ├── Return / defective flow (if applicable)
    │
    └── Finance preview (JobCard cost, supplier credit preview)
```

---

## JobCard Dependency

| Rule | Detail |
|------|--------|
| **Part reservation must bind to JobCard** | LOCKED |
| **Part consumption must bind to JobCard** | LOCKED |
| JobCard state | APPROVED/APPLIED for consumption (policy) |
| Contract ceiling | Phase 19 — preview total vs ceiling |
| Day-end report | Phase 20 — inventory/parts blocks section |

---

## Service Operation Dependency

| Rule | Detail |
|------|--------|
| **Consumption binds to service operation** | LOCKED |
| Operation step | Technician executes; inventory posts on consume |
| No orphan consumption | E-01 without operation ref |

---

## Warehouse Dependency

| Rule | Detail |
|------|--------|
| **Multi-warehouse planning** | Main, secondary, technician hold, defective hold |
| Every stock movement | Source + destination warehouse |
| Transfers | Audited warehouse transfer |

Per `MOGHARE360_MULTI_WAREHOUSE_FOUNDATION_RULE.md`.

---

## Supplier Dependency

| Rule | Detail |
|------|--------|
| Supplier master | Dropdown — part category, supplier ref |
| Internal vs external | Separate purchase paths |
| **Supplier credit preview only** | No official AP accounting in Phase 21 |

---

## Workflow Requirement

**UI → Validation Engine → Workflow Engine → Database → Audit Log**

| Process | Workflow |
|---------|----------|
| Reservation | DRAFT → RESERVED |
| Consumption | RESERVED → CONSUMED |
| Purchase request | DRAFT → SUBMITTED → APPROVED → APPLIED → CLOSED |
| Return/defective | Approval workflow |
| Direct consume without reservation | Manager approval |

Per `MOGHARE360_PERMISSION_WORKFLOW_GATE_MATRIX.md` — `purchase.submit`, `purchase.approve`, `inventory.reserve`.

---

## Audit Requirement

| Domain history | Events |
|--------------|--------|
| `erp_inventory_purchase_history` | Stock moves, reservations |
| `erp_purchase_request_history` | PR lifecycle |
| `erp_jobcard_part_usage_history` | Consumption on JobCard |
| Audit log | All mutations — no skip (E-09) |

---

## Local-Only Data Principle

- Stock, suppliers, purchase records on local MOGHARE360_ERP only
- moghareh360.ir = Mirror Only — no inventory data on host
- No cloud inventory SaaS

---

## Product Boundary (Phase 21)

| Capability | Status |
|------------|--------|
| Supplier credit | Preview/planning only |
| Inventory-to-finance | Preview/planning only |
| Official accounting | NOT active |
| Payment gateway / tax / billing | NOT active |
| Public portal | NOT active |
| SaaS | NOT active |

---

## Phase 21 Module Documents

| Module | Document |
|--------|----------|
| Multi-warehouse | `MOGHARE360_MULTI_WAREHOUSE_FOUNDATION_RULE.md` |
| Reservation | `MOGHARE360_STOCK_RESERVATION_RULE.md` |
| Consumption | `MOGHARE360_PART_CONSUMPTION_RULE.md` |
| Internal purchase | `MOGHARE360_INTERNAL_PURCHASE_RULE.md` |
| External purchase | `MOGHARE360_EXTERNAL_PURCHASE_RULE.md` |
| Supplier credit preview | `MOGHARE360_SUPPLIER_CREDIT_PREVIEW_RULE.md` |
| Return/defective | `MOGHARE360_RETURN_DEFECTIVE_PART_FLOW_RULE.md` |
| Finance binding | `MOGHARE360_INVENTORY_TO_FINANCE_BINDING_RULE.md` |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF INVENTORY / PARTS / PURCHASE COMPLETION PLAN**

# MOGHARE360 — Part Consumption Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Binding Rules

| Binding | Requirement |
|---------|-------------|
| **Part consumption must bind to JobCard** | `jobcard_id` mandatory |
| **Part consumption must bind to service operation** | `operation_id` / step ref mandatory |
| Part ref | `part_id` from catalog |
| Warehouse | Source warehouse or tech hold |

---

## Consume Reserved Stock When Available

| Rule | Detail |
|------|--------|
| **Must consume reserved stock when available** | Match RESERVED row for JobCard + part |
| Partial consume | Split reservation or partial CONSUMED |
| Qty validation | Consume qty ≤ reserved qty (unless approval) |

---

## Direct Consumption Without Reservation

| Rule | Detail |
|------|--------|
| **Direct consumption without reservation requires approval** | Manager workflow |
| Use case | Emergency part issue; stock found without prior reserve |
| Audit | `consumption_unreserved_approved` |
| Still binds | JobCard + operation |

---

## Consumption Must Affect Stock

| Effect | Rule |
|--------|------|
| On-hand qty | Decrease at warehouse |
| Reserved qty | Decrease if from reservation |
| Available qty | Recalculate |
| **No negative stock without approval** | Block or override workflow |

---

## Consumption Must Create Audit / History

| Target | Content |
|--------|---------|
| `erp_jobcard_part_usage_history` | jobcard_id, part, qty, operation, actor, timestamp |
| Inventory history | Stock movement OUT |
| Audit log | `part_consumed` |

**No audit bypass** — E-09 rollback if audit fails.

---

## JobCard Cost Preview Visibility

| Rule | Detail |
|------|--------|
| **Visible in JobCard cost preview** | Unit cost × qty added to preview total |
| Contract ceiling | Phase 19 — cumulative preview vs ceiling |
| Technician view | Phase 20 — no financial detail unless approved |

Per `MOGHARE360_INVENTORY_TO_FINANCE_BINDING_RULE.md`.

---

## Validation

| Check | Error |
|-------|-------|
| Missing JobCard/operation | E-01 |
| JobCard wrong state | E-04 |
| Insufficient stock | E-07 / block |
| Unreserved without approval | E-05 / workflow block |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF PART CONSUMPTION RULE**

# MOGHARE360 — Stock Reservation Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Stock Reservation Purpose

Reserve physical stock for a JobCard before consumption — preventing double allocation and giving accurate available-to-promise for other jobs.

---

## Binding Rules

| Binding | Requirement |
|---------|-------------|
| **Reservation must bind to JobCard** | `jobcard_id` mandatory |
| **Reservation must bind to part** | `part_id` from parts catalog |
| **Reservation must bind to warehouse** | `warehouse_id` — source location |

No orphan reservations.

---

## Reservation States

| State | Meaning |
|-------|---------|
| **DRAFT** | Reservation being prepared |
| **RESERVED** | Stock locked for JobCard |
| **RELEASED** | Reservation cancelled; stock returned to available |
| **CONSUMED** | Stock issued to operation — terminal for reservation row |
| **CANCELLED** | Voided before consume — audit reason required |

Illegal transitions rejected by Workflow Engine.

---

## Reserved Stock Must Not Be Double-Consumed

| Rule | Enforcement |
|------|-------------|
| One RESERVED row per part/qty/JobCard slot (policy) | Unique constraint planning |
| Second consume on same reservation | E-03 / block |
| Consume qty > reserved qty | E-02 block unless split reservation |

---

## Reservation Expiry / Cancellation

| Scenario | Rule |
|----------|------|
| JobCard cancelled | Auto-release reservations — workflow hook |
| JobCard CLOSED with unused reserve | Release or consume policy |
| Expiry SLA | Owner policy — e.g. 7 days RESERVED → auto-release with audit |
| Manual cancel | `inventory.reserve` permission + reason |

---

## Preconditions

| Check | Rule |
|-------|------|
| JobCard state | APPROVED or APPLIED for reserve (policy) |
| Available stock | Sufficient at warehouse |
| Contract | Part within scope or out-of-contract APPLIED |

---

## Audit Requirement

| Event | Detail |
|-------|--------|
| `reservation_created` | jobcard_id, part, qty, warehouse |
| `reservation_reserved` | State → RESERVED |
| `reservation_released` | Reason |
| `reservation_consumed` | Links to consumption row |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF STOCK RESERVATION RULE**

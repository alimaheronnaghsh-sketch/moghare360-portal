# MOGHARE360 — Contract-to-Workflow Binding Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Principle

Contract state and authorization rules are **first-class workflow inputs**. Operations, cost accumulation, and JobCard transitions must respect contract bindings.

**UI → Validation Engine → Workflow Engine → Database → Audit Log**

---

## Entity Bindings

### Contract Must Bind to Customer

| Rule | Detail |
|------|--------|
| FK | `customer_id` mandatory on contract |
| Validation | Customer master must exist and pass Phase 17 validators |
| No orphan contract | E-01 |

### Contract Must Bind to Vehicle

| Rule | Detail |
|------|--------|
| FK | `vehicle_id` mandatory on service contract |
| Validation | Vehicle registered and linked to customer policy |
| Plate/VIN | From validated vehicle master |

### Contract Must Bind to JobCard

| Rule | Detail |
|------|--------|
| FK | `jobcard_id` mandatory |
| One active applied contract | Per JobCard operational path (policy) |
| JobCard refs | Customer/vehicle on JobCard must match contract |

---

## Contract Must Bind to Workflow States

| Contract state | JobCard / operation effect |
|----------------|---------------------------|
| DRAFT | JobCard may exist; no paid operations |
| SUBMITTED | Under review |
| UNDER_REVIEW | Awaiting manager |
| APPROVED | Ready to apply |
| **APPLIED** | **Contract authorization affects allowed operations** |
| CLOSED | Contract complete — read-only |

Out-of-contract approval uses parallel state machine per `MOGHARE360_OUT_OF_CONTRACT_APPROVAL_RULE.md`.

---

## Contract Authorization Affects Allowed Operations

| Check | Enforcer |
|-------|----------|
| Authorization level | Operation Engine — allowed/forbidden op types |
| Authorized services list | Validation Engine — service catalog membership |
| Inspection only | Block chargeable ops |
| Written approval required | Block until acceptance + PDF |

---

## Out-of-Contract Work Requires Workflow Approval

| Scenario | Gate |
|----------|------|
| New service | Out-of-contract approval APPLIED |
| Scope change from diagnostic | Same |
| **No execution before approval** | Workflow Engine E-04 |

---

## Cost Ceiling Violation Requires Workflow Approval

| Scenario | Gate |
|----------|------|
| Preview total > ceiling | Block + approval request |
| **Approval required above ceiling** | Out-of-contract or ceiling amendment workflow |
| Finance preview | Read-only total for comparison |

---

## Contract Acceptance Must Be Audited

| Event | Required |
|-------|----------|
| Initial acceptance | `customer_acceptance_recorded` |
| Contract APPLIED | `contract_applied` |
| Amendment acceptance | `customer_acceptance_recorded` + version bump |
| **Contract acceptance must be audited** | LOCKED — E-09 if audit fail |

---

## Integration Matrix

| Module | Contract check |
|--------|----------------|
| Validation Engine | Fields, ceiling, storage terms, acceptance presence |
| Workflow Engine | Contract + out-of-contract states |
| Operation Engine | Authorization level + scope |
| Inventory | Part reservation vs ceiling preview |
| Finance Preview | Running total — not official accounting |
| Media (Phase 18) | Contract APPLIED before chargeable work media policy |
| Audit Log | All contract events |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF CONTRACT-TO-WORKFLOW BINDING RULE**

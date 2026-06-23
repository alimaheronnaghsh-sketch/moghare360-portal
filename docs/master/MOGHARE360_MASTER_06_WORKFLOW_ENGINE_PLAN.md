# MOGHARE360 — Master 06 Workflow Engine Plan

**Status:** Planning only — Documentation only  
**SQL:** Not required

---

## Purpose

Define the Workflow Engine states, transitions, and core business flow. **No module without Workflow Engine** — all operational entities participate in workflow.

---

## Workflow States

| State | Meaning |
|-------|---------|
| `DRAFT` | Editable, not submitted |
| `SUBMITTED` | Awaiting review |
| `UNDER_REVIEW` | Active review |
| `APPROVED` | Approved for application |
| `APPLIED` | Changes applied to operations |
| `CLOSED` | Terminal; read-only |

---

## Core Business Flow

```
Customer
  → Vehicle
    → Contract
      → JobCard
        → Workflow Engine
          → Service Execution
            → Inventory Usage
              → QC
                → Delivery
                  → Finance Preview
                    → CRM Follow-up
```

Each arrow implies workflow state progression and audit at transition.

---

## State Transition Rules

| From | To | Condition |
|------|-----|-----------|
| DRAFT | SUBMITTED | Validation pass + submit permission |
| SUBMITTED | UNDER_REVIEW | Reviewer assigned |
| UNDER_REVIEW | APPROVED | Approve permission |
| UNDER_REVIEW | DRAFT | Reject with reason |
| APPROVED | APPLIED | Apply permission + preconditions met |
| APPLIED | CLOSED | Close permission + QC/delivery complete |

Illegal transitions rejected at Workflow Engine.

---

## Permission Per Transition

| Transition | Typical permission key |
|------------|-------------------------|
| submit | `{entity}.submit` |
| review | `{entity}.review` |
| approve | `{entity}.approve` |
| reject | `{entity}.reject` |
| apply | `{entity}.apply` |
| close | `{entity}.close` |

Entity: `customer`, `vehicle`, `contract`, `jobcard`, `inventory`, `finance_preview`, `crm`

---

## Audit Per Transition

Every transition writes:

- `entity_type`, `entity_id`
- `from_state`, `to_state`
- `actor_user_id`, `permission_used`
- `reason` (optional on reject)
- `created_at`

Stored in `erp_workflow_transition_log` (conceptual — see SQL Schema Plan).

---

## Module Dependency Rule

**No module without Workflow Engine.**

- Customer intake → DRAFT contract
- JobCard → cannot skip to APPLIED without APPROVED
- Inventory usage → requires APPLIED JobCard
- Finance Preview → post-delivery, preview only
- CRM follow-up → after CLOSED or APPLIED per policy

---

## Integration with Validation

```
UI → Validation Engine → Workflow Engine → Database → Audit Log
```

Validation must pass before SUBMITTED. Workflow checks before every state-changing write.

---

## Product Boundary

- Documentation only
- No Workflow Engine implementation in this phase
- No official accounting activation
- No payment gateway/billing/tax integration created

---

**END OF WORKFLOW ENGINE PLAN**

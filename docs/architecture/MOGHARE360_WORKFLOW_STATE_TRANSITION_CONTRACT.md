# MOGHARE360 — Workflow State Transition Contract

**Status:** Locked planning baseline — Documentation only

---

## Canonical States

`DRAFT` · `SUBMITTED` · `UNDER_REVIEW` · `APPROVED` · `APPLIED` · `CLOSED` · `REJECTED` · `CANCELLED`

---

## Allowed Transition Matrix

| From | To | Actor | Permission | Validation | Audit | Workflow Engine |
|------|-----|-------|------------|------------|-------|-----------------|
| **DRAFT** | **SUBMITTED** | Creator / clerk | `{entity}.submit` | All required fields + format rules pass | `submit` event | Required |
| **SUBMITTED** | **UNDER_REVIEW** | Reviewer assigner | `{entity}.review` | Entity complete for review | `review_start` | Required |
| **UNDER_REVIEW** | **APPROVED** | Approver | `{entity}.approve` | Business rules + rule engine pass | `approve` event | Required |
| **UNDER_REVIEW** | **REJECTED** | Reviewer | `{entity}.reject` | Reject reason required | `reject` event | Required |
| **APPROVED** | **APPLIED** | Operator / system | `{entity}.apply` | Preconditions met (e.g. QC) | `apply` event | Required |
| **APPLIED** | **CLOSED** | Closer | `{entity}.close` | Completion checks pass | `close` event | Required |
| **DRAFT** | **CANCELLED** | Creator | `{entity}.cancel` | Cancel reason optional per policy | `cancel` event | Required |
| **SUBMITTED** | **CANCELLED** | Creator / admin | `{entity}.cancel` | Not under active review | `cancel` event | Required |

### Standard Happy Path

```
DRAFT → SUBMITTED → UNDER_REVIEW → APPROVED → APPLIED → CLOSED
```

### Reject Path

```
UNDER_REVIEW → REJECTED → (manual return to DRAFT by authorized actor per policy)
```

---

## Forbidden Transitions

| From | To | Reason |
|------|-----|--------|
| **DRAFT** | **APPROVED** | Skips validation and review |
| **DRAFT** | **APPLIED** | Skips entire approval chain |
| **SUBMITTED** | **APPLIED** | Skips review and approval |
| **UNDER_REVIEW** | **APPLIED** | Skips approval |
| **REJECTED** | **APPLIED** | Rejected entity cannot apply |
| **CANCELLED** | **APPLIED** | Cancelled entity terminated |
| **CLOSED** | **APPLIED** | Terminal state |
| **CLOSED** | any active state | CLOSED is terminal — no reopen without admin policy phase |

Additional forbidden: any transition not listed in **Allowed** matrix unless future owner-approved exception documented.

---

## Per-Transition Requirements (Summary)

Every **allowed** transition requires **all** of:

1. **Actor requirement** — authenticated session with valid user
2. **Permission requirement** — role + permission key for transition
3. **Validation requirement** — Validation Engine pass for payload
4. **Audit requirement** — audit event append with old/new state
5. **Workflow engine requirement** — Workflow Engine authorizes transition; UI cannot set state directly

---

## Entity Applicability

| Entity family | Uses full state machine |
|---------------|-------------------------|
| Customer intake / contract | Yes |
| JobCard | Yes |
| Purchase request | Yes |
| Payment preview | Yes (preview scope) |
| Access request | Yes |
| CRM follow-up | Simplified variant |
| Audit history rows | No (append-only) |

---

## REJECTED and CANCELLED Semantics

| State | Meaning |
|-------|---------|
| **REJECTED** | Review failed; entity may return to DRAFT for correction |
| **CANCELLED** | Withdrawn before completion; terminal for that attempt |

Neither may transition to **APPLIED** or **CLOSED** without new entity or explicit reopen policy (future phase).

---

## Product Boundary

- Workflow contract — planning only
- No runtime Workflow Engine implementation in Phase 07

---

**END OF WORKFLOW STATE TRANSITION CONTRACT**

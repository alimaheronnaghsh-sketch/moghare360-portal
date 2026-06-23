# MOGHARE360 — Workflow Audit Event Contract

**Status:** Locked planning baseline — Documentation only

---

## Purpose

Define mandatory audit event shape for all controlled workflow actions. **Audit logging cannot be skipped for controlled workflow actions.**

- **Audit / History domain** records event evidence
- **Domain owner** remains responsible for business meaning of `reference_entity`

---

## Audit Event Fields

| Field | Type (conceptual) | Description |
|-------|-------------------|-------------|
| **audit_id** | bigint identity | Surrogate PK |
| **actor_user_id** | int/bigint | Session user — matches canonical ID policy |
| **source_module** | string | Canonical domain / module name |
| **reference_entity** | string | Table or entity type (e.g. `jobcard`) |
| **reference_id** | int/bigint | Entity PK |
| **old_state** | string | Workflow state before |
| **new_state** | string | Workflow state after |
| **action** | string | Action verb (see below) |
| **action_result** | string | `success` / `failed` / `blocked` |
| **validation_result** | string | `pass` / `fail` + code if fail |
| **permission_result** | string | `pass` / `fail` + permission key if fail |
| **workflow_result** | string | `pass` / `fail` + reason if fail |
| **created_at** | datetime | Server timestamp |
| **notes** | string | Optional reason, reject message, diagnostic |

---

## Audit-Required Actions

| Action | When | Minimum fields |
|--------|------|----------------|
| **create draft** | New entity DRAFT | actor, entity, new_state=DRAFT |
| **submit** | DRAFT→SUBMITTED | old/new state, validation pass |
| **review** | SUBMITTED→UNDER_REVIEW | actor, permission |
| **approve** | UNDER_REVIEW→APPROVED | old/new state |
| **reject** | UNDER_REVIEW→REJECTED | notes required |
| **apply** | APPROVED→APPLIED | preconditions met |
| **close** | APPLIED→CLOSED | completion ref |
| **cancel** | →CANCELLED | cancel reason if policy requires |
| **permission denied** | Any gate fail | permission_result=fail |
| **validation failed** | Validation Engine fail | validation_result=fail |
| **cross-domain write blocked** | E-06 | action_result=blocked |
| **media rule violation** | E-07 | validation_result=fail, notes |

---

## Storage Mapping

| Event type | Primary storage |
|------------|-----------------|
| Security/access | `core_audit_logs` |
| Domain entity change | `erp_{domain}_history` or `*_change_history` |
| Rule evaluation | `erp_rule_audit_history` |
| Workflow transition | `erp_workflow_transition_log` (conceptual) + domain history |

---

## Append Rules

1. Audit append **after** successful database write for mutations (or in same transaction)
2. Failed validation/permission — audit **attempt** event without entity write
3. Immutable — no UPDATE/DELETE on audit rows (soft-delete policy future phase only)
4. Actor always from session — no anonymous audit

---

## Integration Flow

```
Workflow Engine approves transition
  → Database write (transaction)
  → Audit service append (same transaction if possible)
  → Commit
```

If audit append fails → **rollback** database write (E-09 AUDIT_REQUIRED).

---

## Product Boundary

- Contract only — no audit table schema change
- **Do not create SQL yet**

---

**END OF WORKFLOW AUDIT EVENT CONTRACT**

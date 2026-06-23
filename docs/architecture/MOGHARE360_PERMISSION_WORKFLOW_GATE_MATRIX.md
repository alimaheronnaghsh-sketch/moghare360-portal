# MOGHARE360 — Permission Workflow Gate Matrix

**Status:** Locked planning baseline — Documentation only

---

## Important Boundary

- **Permission model is not modified in this phase**
- **Existing permissions are only documented conceptually** (e.g. `{entity}.submit`)
- **No new user/role/permission creation**
- **No auth architecture change**

Conceptual permission keys align with `core_permissions` / `core_role_permissions` in MOGHARE360_ERP but Phase 07 does not ALTER seed data or auth files.

---

## Gate Definitions

| Gate | Required action | Permission concept | Workflow precondition | Validation precondition | Audit event | Forbidden bypass |
|------|-----------------|-------------------|----------------------|-------------------------|-------------|------------------|
| **Submit gate** | DRAFT → SUBMITTED | `{entity}.submit` | State = DRAFT | All required fields + formats | `submit` | Direct SUBMITTED insert |
| **Review gate** | SUBMITTED → UNDER_REVIEW | `{entity}.review` | State = SUBMITTED | Entity review-ready | `review_start` | Self-review without assignment |
| **Approve gate** | UNDER_REVIEW → APPROVED | `{entity}.approve` | State = UNDER_REVIEW | Rule engine pass if required | `approve` | DRAFT→APPROVED skip |
| **Reject gate** | UNDER_REVIEW → REJECTED | `{entity}.reject` | State = UNDER_REVIEW | Reject reason present | `reject` | Silent reject |
| **Apply gate** | APPROVED → APPLIED | `{entity}.apply` | State = APPROVED | Domain preconditions (QC, etc.) | `apply` | SUBMITTED→APPLIED |
| **Close gate** | APPLIED → CLOSED | `{entity}.close` | State = APPLIED | Completion checks | `close` | CLOSED reopen |
| **Cancel gate** | DRAFT/SUBMITTED → CANCELLED | `{entity}.cancel` | DRAFT or SUBMITTED | Not in active review | `cancel` | Cancel after APPROVED |
| **Read-only gate** | GET/list/detail | `{entity}.read` | Any non-secret state | N/A | Optional access log | Write via read route |
| **Admin override gate** | Policy exception | `{entity}.admin` or platform owner | Owner-approved policy only | Full validation still required | `admin_override` | Routine bypass of validation |

---

## Gate Flow Diagram

```
[Read gate] ──view──► Entity
[Submit gate] ──► SUBMITTED
[Review gate] ──► UNDER_REVIEW
[Approve/Reject gate] ──► APPROVED | REJECTED
[Apply gate] ──► APPLIED
[Close gate] ──► CLOSED
[Cancel gate] ──► CANCELLED (from DRAFT/SUBMITTED)
```

---

## Entity Permission Examples (Conceptual)

| Entity | Submit | Approve | Apply | Close |
|--------|--------|---------|-------|-------|
| Customer intake | `customer.submit` | `customer.approve` | `customer.apply` | `customer.close` |
| JobCard | `jobcard.submit` | `jobcard.approve` | `jobcard.apply` | `jobcard.close` |
| Purchase request | `purchase.submit` | `purchase.approve` | `purchase.apply` | `purchase.close` |
| Payment preview | `finance.preview.submit` | `finance.preview.approve` | N/A (preview) | `finance.preview.close` |

---

## Forbidden Bypass (All Gates)

| Bypass | Blocked by |
|--------|------------|
| UI sets workflow state in hidden field | Workflow Engine |
| POST without permission check | Permission guard |
| POST without validation | Validation Engine |
| POST without audit | Audit service |
| Admin override without audit | Admin override gate policy |

---

## Integration with Existing Auth

Future implementation reads permissions from existing session/context (`access-control.php` pattern) — **files not modified in Phase 07**.

---

## Product Boundary

- **Do not modify permission model yet**
- Conceptual documentation only

---

**END OF PERMISSION WORKFLOW GATE MATRIX**

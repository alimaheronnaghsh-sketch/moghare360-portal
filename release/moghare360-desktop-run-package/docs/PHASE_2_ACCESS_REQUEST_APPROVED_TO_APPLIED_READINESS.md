# MOGHARE360 ERP - Phase 2 APPROVED to APPLIED Readiness

Status: Readiness Completed - Apply Design Required
Scope: Preparation and Inspection Only

## 1. Purpose
Prepare the next controlled Access Request workflow transition from APPROVED to APPLIED.

This document records confirmed SSMS state for `request_id = 4` and defines why implementation must remain blocked until Apply Design Lock is completed.

## 2. Current Confirmed State

### Confirmed request
- request_id: 4
- request_number: AR-20260620-084634-10001
- request_type: ROLE_GRANT
- request_state: APPROVED
- subject_user_id: 10001
- requested_by_user_id: 10001
- submitted_at: 2026-06-21 15:00:13.874
- decided_at: 2026-06-21 17:23:32.189
- applied_at: NULL
- applied_by_user_id: NULL
- updated_at: 2026-06-21 17:23:32.189

### Confirmed request item
- request_id: 4
- item_type: ROLE_GRANT
- item_decision: PENDING
- is_temporary: 0
- sort_order: 1
- effective_from: 2026-06-20 00:00:00.000
- expires_at: NULL

### Confirmed approval
- approval_id: 1
- request_id: 4
- approver_user_id: 10001
- approver_capacity: OWNER
- decision: APPROVED
- comment: Controlled prototype approval for UNDER_REVIEW to APPROVED
- decided_at: 2026-06-21 17:23:32.186
- created_at: 2026-06-21 17:23:32.186

### Confirmed history
- ACCESS_REQUEST_SUBMITTED exists
- ACCESS_REQUEST_UNDER_REVIEW exists
- ACCESS_REQUEST_APPROVED exists

### Confirmed visible access-related tables from SSMS
- core_access_approval_rules
- core_access_approvals
- core_access_change_history
- core_access_request_items
- core_access_requests

### Previous workflow path for request_id 4
- DRAFT -> SUBMITTED
- SUBMITTED -> UNDER_REVIEW
- UNDER_REVIEW -> APPROVED

## 3. Files Inspected
- `docs/PHASE_2_ACCESS_REQUEST_UNDER_REVIEW_TO_APPROVED_IMPLEMENTATION_TEST_RESULT.md`
- `docs/PHASE_2_ACCESS_REQUEST_PERMISSION_NAMING_ALIGNMENT.md`
- `docs/PHASE_2_ACCESS_REQUEST_APPROVAL_INSERT_SHAPE_LOCK.md`
- `includes/erp-workflow-engine.php`
- `includes/erp-permission-check.php`
- `includes/erp-auth-context.php`
- `public_html/erp-access-request-approve-transition.php`

## 4. Workflow Engine Status

Inspected `includes/erp-workflow-engine.php` function `erp_workflow_get_allowed_transitions()`.

Current allowed transitions for `access_request`:

```
DRAFT -> SUBMITTED
SUBMITTED -> UNDER_REVIEW
UNDER_REVIEW -> APPROVED
```

**Status: BLOCKED**

`access_request: APPROVED -> APPLIED` is **not** present in the workflow engine.

Implementation of the next transition is blocked until workflow engine transition rules are explicitly updated in a separate approved step.

Database CHECK constraint `CK_core_access_requests_state` already allows `APPLIED`, but workflow engine validation and Apply Design Lock must be completed before any write implementation.

## 5. Proposed Next Transition

Transition:
APPROVED -> APPLIED

Proposed permission:
access.request.apply

Proposed history change_type:
ACCESS_REQUEST_APPLIED

Candidate test request:
- request_id: 4
- request_number: AR-20260620-084634-10001

Potential request update fields for future design only:
- request_state = APPLIED
- applied_at
- applied_by_user_id
- updated_at

Potential request item behavior for future design only:
- item_decision may need to move from PENDING to APPROVED

## 6. Required Safety Chain

Browser Form
-> CSRF Validation
-> Auth Check
-> Permission Check
-> Workflow Engine
-> SQL Transaction
-> State-Based Concurrency Guard
-> Assignment Target Write Decision
-> Request State Update
-> Request Item Decision Update if approved
-> History Insert
-> Commit or Rollback

## 7. Implementation Blockers

1. **Workflow engine does not allow APPROVED -> APPLIED** - transition rule update is required in a separate step.

2. **Assignment target table is not identified** - `APPROVED -> APPLIED` must remain blocked until the assignment target table is explicitly identified and the Apply design is locked. Candidate tables such as `core_user_roles` or equivalent assignment storage have not been approved for write in this phase.

3. **APPLIED is not approved to perform real role assignment** - even if request state can move to APPLIED, actual role assignment behavior is not approved in this readiness step.

4. **Apply Design Lock is missing** - no approved document yet defines:
   - assignment target table
   - assignment row shape
   - whether `item_decision` changes on apply
   - whether `applied_by_user_id` source is current actor
   - whether history JSON includes `applied_at`
   - duplicate apply protection rules

5. **Permission naming is proposed but apply behavior is not locked** - `access.request.apply` is the proposed permission from prior alignment work, but apply execution scope is not approved.

6. **Request item decision remains PENDING** - confirmed `item_decision = PENDING` for `request_id = 4`. Whether apply changes item decision must be separately approved.

7. **Separate apply transition page decision pending** - prior transitions used separate pages. A separate apply page should be preferred, but must be explicitly approved.

## 8. Locked Decision

**APPROVED -> APPLIED must remain blocked until the assignment target table is explicitly identified and the Apply Design Lock is completed.**

**APPLIED is not yet approved to perform real role assignment.**

The next safe step is **Apply Design Lock**, not implementation.

## 9. Explicit Non-Approval

This readiness document does not approve:

- Production apply execution
- Role assignment
- Permission assignment
- User creation
- Tenant behavior change
- Update to `core_access_requests`
- Update to `core_access_request_items`
- Insert into assignment tables
- Workflow engine changes
- Runtime PHP changes
- Database schema changes

## 10. Sign-Off

No runtime behavior was changed.

No database data or schema was changed.

This document only prepares the next controlled workflow step and confirms that Apply Design Lock is required before implementation.

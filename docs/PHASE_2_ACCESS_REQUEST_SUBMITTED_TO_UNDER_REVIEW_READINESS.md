# MOGHARE360 ERP - Phase 2 SUBMITTED to UNDER_REVIEW Readiness

## 1. Purpose
Prepare the next controlled Access Request workflow transition from SUBMITTED to UNDER_REVIEW.

## 2. Current Confirmed State
- request_id: 4
- expected current request_state: SUBMITTED
- previous transition: DRAFT -> SUBMITTED
- previous history change_type: ACCESS_REQUEST_SUBMITTED
- duplicate submit protection: confirmed

## 3. Files Inspected
- `docs/PHASE_2_ACCESS_REQUEST_STATE_AND_HELPER_LOCK.md`
- `docs/PHASE_2_ACCESS_REQUEST_DRAFT_TO_SUBMITTED_IMPLEMENTATION_TEST_RESULT.md`
- `includes/erp-workflow-engine.php`
- `includes/erp-permission-check.php`
- `includes/erp-auth-context.php`
- `includes/erp-csrf.php`
- `public_html/erp-access-request-transition.php`

## 4. Workflow Engine Status

Inspected `includes/erp-workflow-engine.php` function `erp_workflow_get_allowed_transitions()`.

Current allowed transitions for `access_request`:

```
DRAFT -> SUBMITTED
```

**Status: BLOCKED**

`access_request: SUBMITTED -> UNDER_REVIEW` is **not** allowed by the current workflow engine.

Implementation of the next transition is blocked until workflow engine transition rules are explicitly updated in a separate approved step.

Database CHECK constraint `CK_core_access_requests_state` already allows `UNDER_REVIEW`, but workflow engine validation must be updated separately before any write implementation.

## 5. Proposed Next Transition

Transition:
SUBMITTED -> UNDER_REVIEW

Proposed permission:
access_request.review

Proposed history change_type:
ACCESS_REQUEST_UNDER_REVIEW

Allowed database changes for next implementation:
- update dbo.core_access_requests.request_state
- update dbo.core_access_requests.updated_at
- insert one row into dbo.core_access_change_history

Forbidden:
- role assignment
- permission assignment
- user creation
- tenant change
- customer portal change
- update core_access_request_items
- insert core_access_approvals
- touch core_user_roles or equivalent assignment tables

## 6. Required Safety Chain

Browser Form
-> CSRF Validation
-> Auth Check
-> Permission Check
-> Workflow Engine
-> SQL Transaction
-> State-Based Concurrency Guard
-> State Update
-> History Insert
-> Commit or Rollback

## 7. Implementation Blockers

1. **Workflow engine does not allow SUBMITTED -> UNDER_REVIEW** - `erp_workflow_get_allowed_transitions()` currently allows only `DRAFT -> SUBMITTED`. Workflow engine update is required in a separate step before implementation.

2. **`access_request.review` permission not confirmed** - no reference to `access_request.review` was found in inspected project files. Permission existence and approval must be confirmed before implementation. Phase 2 prototype currently uses platform owner fallback in `erp_permission_check.php`, but the permission key itself should be explicitly approved.

3. **`ACCESS_REQUEST_UNDER_REVIEW` change_type not yet approved** - `core_access_change_history.change_type` has no CHECK constraint, but this value is not yet observed in history data and must be approved before use, following the same pattern used for `ACCESS_REQUEST_SUBMITTED`.

4. **Transition page design decision pending** - `public_html/erp-access-request-transition.php` is currently hardcoded for `DRAFT -> SUBMITTED` with form key `access_request_submit`, permission `access_request.submit`, and history type `ACCESS_REQUEST_SUBMITTED`. A separate decision is required on whether to extend this page for multiple transitions or create a separate controlled transition page for `SUBMITTED -> UNDER_REVIEW`.

5. **Candidate row state dependency** - `request_id = 4` is now expected to be `SUBMITTED`. The next transition must re-read and verify `request_state = SUBMITTED` inside the transaction before update.

6. **History JSON shape not yet defined** - `before_json` / `after_json` shape for `SUBMITTED -> UNDER_REVIEW` must be defined before implementation.

## 8. Sign-Off

No runtime behavior was changed.

This document only prepares the next controlled workflow step.

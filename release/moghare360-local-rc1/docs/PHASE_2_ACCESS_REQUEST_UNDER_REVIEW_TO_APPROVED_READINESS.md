# MOGHARE360 ERP - Phase 2 UNDER_REVIEW to APPROVED Readiness

## 1. Purpose
Prepare the next controlled Access Request workflow transition from UNDER_REVIEW to APPROVED.

## 2. Current Confirmed State
- request_id: 4
- confirmed current request_state: UNDER_REVIEW
- previous transition: SUBMITTED -> UNDER_REVIEW
- previous history change_type: ACCESS_REQUEST_UNDER_REVIEW
- duplicate submit protection: confirmed

Confirmed SQL snapshot:
- request_id: 4
- request_number: AR-20260620-084634-10001
- request_type: ROLE_GRANT
- request_state: UNDER_REVIEW
- subject_user_id: 10001
- requested_by_user_id: 10001
- submitted_at: 2026-06-21 15:00:13.874
- decided_at: NULL
- applied_at: NULL
- updated_at: 2026-06-21 16:29:50.052

Confirmed history:
- ACCESS_REQUEST_UNDER_REVIEW exists
- ACCESS_REQUEST_SUBMITTED exists

Confirmed approvals:
- No rows found in dbo.core_access_approvals for request_id = 4

## 3. Files Inspected
- `docs/PHASE_2_ACCESS_REQUEST_STATE_AND_HELPER_LOCK.md`
- `docs/PHASE_2_ACCESS_REQUEST_PERMISSION_NAMING_ALIGNMENT.md`
- `docs/PHASE_2_ACCESS_REQUEST_SUBMITTED_TO_UNDER_REVIEW_IMPLEMENTATION_TEST_RESULT.md`
- `docs/PHASE_2_WORKFLOW_ENGINE_SUBMITTED_TO_UNDER_REVIEW_TEST_RESULT.md`
- `includes/erp-workflow-engine.php`
- `includes/erp-permission-check.php`
- `includes/erp-auth-context.php`
- `includes/erp-csrf.php`
- `public_html/erp-access-request-review-transition.php`

## 4. Workflow Engine Status

Inspected `includes/erp-workflow-engine.php` function `erp_workflow_get_allowed_transitions()`.

Current allowed transitions for `access_request`:

```
DRAFT -> SUBMITTED
SUBMITTED -> UNDER_REVIEW
```

**Status: BLOCKED**

`access_request: UNDER_REVIEW -> APPROVED` is **not** allowed by the current workflow engine.

Implementation of the next transition is blocked until workflow engine transition rules are explicitly updated in a separate approved step.

Database CHECK constraint `CK_core_access_requests_state` already allows `APPROVED`, but workflow engine validation must be updated separately before any write implementation.

## 5. Proposed Next Transition

Transition:
UNDER_REVIEW -> APPROVED

Proposed permission:
access.request.approve

Proposed approval decision:
APPROVED

Proposed history change_type:
ACCESS_REQUEST_APPROVED

Allowed database changes for next implementation only after approval:
- update dbo.core_access_requests.request_state
- update dbo.core_access_requests.decided_at
- update dbo.core_access_requests.updated_at
- insert one row into dbo.core_access_approvals
- insert one row into dbo.core_access_change_history

Forbidden:
- role assignment
- permission assignment
- user creation
- tenant change
- customer portal change
- update core_access_request_items unless explicitly approved later
- touch core_user_roles or equivalent assignment tables

## 6. Required Safety Chain

Browser Form
-> CSRF Validation
-> Auth Check
-> Permission Check
-> Workflow Engine
-> SQL Transaction
-> State-Based Concurrency Guard
-> Approval Insert
-> State Update
-> History Insert
-> Commit or Rollback

## 7. Implementation Blockers

1. **Workflow engine does not allow UNDER_REVIEW -> APPROVED** - `erp_workflow_get_allowed_transitions()` currently allows only `DRAFT -> SUBMITTED` and `SUBMITTED -> UNDER_REVIEW`. Workflow engine update is required in a separate step before implementation.

2. **Approval row shape must be approved** - approval insert is required for `UNDER_REVIEW -> APPROVED`, but approval row shape must be locked before implementation. `dbo.core_access_approvals` columns include `request_id`, `approver_user_id`, `approver_capacity`, `decision`, `comment`, and `decided_at`. No approval rows exist yet in inspected data. Mandatory values and nullability must be confirmed before insert implementation.

3. **`approver_capacity` value must be approved** - no observed approval rows exist to define the approved capacity value for the first controlled approval insert.

4. **`item_decision` behavior not yet approved** - current inspected `item_decision` value is `PENDING`. Whether `UNDER_REVIEW -> APPROVED` should leave item rows unchanged or update `item_decision` later must be decided separately.

5. **Separate approval page decision pending** - prior controlled transitions used separate pages (`erp-access-request-transition.php` for submit, `erp-access-request-review-transition.php` for review). A separate approval transition page should be preferred over extending the review page, but this must be explicitly approved.

6. **Duplicate approval protection must be defined** - implementation must block repeat approval when `request_state` is no longer `UNDER_REVIEW` and must prevent duplicate `core_access_approvals` rows for the same approved transition.

7. **History JSON shape not yet defined** - `before_json` / `after_json` for `UNDER_REVIEW -> APPROVED` must define at minimum `request_state`, `decided_at`, and `updated_at` before implementation.

8. **Transaction scope not yet approved** - next implementation must define whether approval insert, request state update, and history insert must all succeed or all rollback together.

## 8. Sign-Off

No runtime behavior was changed.

This document only prepares the next controlled workflow step.

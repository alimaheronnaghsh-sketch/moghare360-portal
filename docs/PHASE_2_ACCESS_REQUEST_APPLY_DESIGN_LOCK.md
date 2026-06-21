# MOGHARE360 ERP - Phase 2 Access Request Apply Design Lock

Status: Design Locked - State-Only Apply Approved for Phase 2
Scope: Design Lock Only

## 1. Purpose
Lock the design for APPROVED → APPLIED before any access change is applied.

This document defines the controlled prototype boundary for the Apply transition. It locks state-only apply for Phase 2. It does not approve real role assignment, runtime changes, or assignment table writes.

Inspected inputs:
- `docs/PHASE_2_ACCESS_REQUEST_APPROVED_TO_APPLIED_READINESS.md`
- `docs/PHASE_2_ACCESS_REQUEST_UNDER_REVIEW_TO_APPROVED_IMPLEMENTATION_TEST_RESULT.md`
- `docs/PHASE_2_ACCESS_REQUEST_PERMISSION_NAMING_ALIGNMENT.md`
- `includes/erp-workflow-engine.php`
- `includes/erp-permission-check.php`
- `includes/erp-auth-context.php`
- `public_html/erp-access-request-approve-transition.php`

## 2. Current Confirmed Request
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

Confirmed prior workflow path for this request:
- DRAFT → SUBMITTED
- SUBMITTED → UNDER_REVIEW
- UNDER_REVIEW → APPROVED

## 3. Current Confirmed Request Item
- request_id: 4
- item_type: ROLE_GRANT
- item_decision: PENDING
- is_temporary: 0
- sort_order: 1
- effective_from: 2026-06-20 00:00:00.000
- expires_at: NULL

## 4. Apply Meaning Decision
APPROVED means the request has been approved.
APPLIED must mean the approved access request has been executed.

For Phase 2 controlled prototype:
APPLIED is approved as a state-only workflow transition. Real role assignment through `core_user_roles` is explicitly deferred.

Locked semantic boundary:
- APPROVED = approval decision recorded in `core_access_approvals`; request is ready for apply.
- APPLIED = apply action completed for the request lifecycle.
- APPLIED does **not** mean a role was assigned in Phase 2 state-only apply.

## 5. Proposed Permission
access.request.apply

Confirmed from `docs/PHASE_2_ACCESS_REQUEST_PERMISSION_NAMING_ALIGNMENT.md`:
- `access.request.apply` exists in `dbo.core_permissions`.
- Option A naming standard is approved for controlled continuation.
- Mapping for this transition: APPROVED → APPLIED uses `access.request.apply`.

Role coverage status:
- `access.request.approve` role coverage is confirmed for `owner` and `system_admin`.
- `access.request.apply` role coverage is **not yet confirmed** from SSMS and remains a blocker.

Prototype note from `includes/erp-permission-check.php`:
- Platform owner prototype user `10001` may pass permission checks during controlled prototype only.
- This is not production authorization and must not be treated as apply permission coverage proof.

## 6. Proposed Workflow Transition
APPROVED → APPLIED

Inspected `includes/erp-workflow-engine.php` function `erp_workflow_get_allowed_transitions()`:

Current allowed transitions for `access_request`:
```
DRAFT → SUBMITTED
SUBMITTED → UNDER_REVIEW
UNDER_REVIEW → APPROVED
```

Workflow engine status:
- `APPROVED → APPLIED` is **not present**.
- `erp_workflow_require_transition('access_request', 'APPROVED', 'APPLIED')` would be denied today.

Database note from readiness inspection:
- `CK_core_access_requests_state` already allows `APPLIED`.
- Workflow engine update is still required in a separate approved step before implementation.

## 7. Proposed History Change Type
ACCESS_REQUEST_APPLIED

Proposed history row shape, aligned to the approved transition pattern in `public_html/erp-access-request-approve-transition.php`:

```
user_id = subject_user_id from the target request
request_id = target request_id
change_type = ACCESS_REQUEST_APPLIED
entity_type = core_access_requests
entity_id = request_id
before_json = {"request_state":"APPROVED","applied_at":null,"applied_by_user_id":null,"updated_at":"<previous>"}
after_json = {"request_state":"APPLIED","applied_at":"<timestamp>","applied_by_user_id":"<actor>","updated_at":"<timestamp>"}
changed_by_user_id = current authenticated user_id
changed_at = SYSDATETIME()
```

## 8. Assignment Target Table

Confirmed assignment target table:
core_user_roles

Confirmed columns:
- user_role_id: bigint, identity, not null
- user_id: int, not null
- role_id: int, not null
- granted_by_request_id: bigint, not null
- effective_from: datetime2, not null
- expires_at: datetime2, nullable
- revoked_at: datetime2, nullable
- revoked_by_request_id: bigint, nullable
- is_temporary: bit
- created_at: datetime2

Confirmed foreign keys:
- granted_by_request_id references core_access_requests.request_id
- revoked_by_request_id references core_access_requests.request_id
- role_id references core_roles.role_id
- user_id references core_users.user_id

Confirmed existing rows:
- user_id 10001 already has role_id 12
- user_id 10001 already has role_id 17
- existing rows were granted by request_id 1
- existing rows are not revoked

Active/inactive handling note:
- Active assignment is implied when `revoked_at` is NULL.
- `expires_at` may define temporary expiry but expiry handling is not locked for Phase 2 apply.

Duplicate assignment rules note:
- Existing rows for `user_id 10001` with `role_id 12` and `role_id 17` demonstrate prior grants via `request_id 1`.
- Duplicate active role protection for real assignment is not locked in this document.

Audit/history relationship note:
- `granted_by_request_id` and `revoked_by_request_id` link assignment rows to access request workflow.
- Real assignment audit rules remain deferred.

Phase 2 write rule for this table:
- **No INSERT or UPDATE to `core_user_roles` in Phase 2 state-only apply.**

## Phase 2 Apply Decision

For Phase 2 controlled prototype, APPROVED → APPLIED is approved as a state-only workflow transition only.

State-only APPLIED means:
- mark the approved request as APPLIED
- set applied_at
- set applied_by_user_id
- set updated_at
- insert ACCESS_REQUEST_APPLIED history

State-only APPLIED does NOT mean:
- insert into core_user_roles
- update core_user_roles
- assign roles
- activate permissions
- update core_access_request_items
- change item_decision
- create users
- change tenant behavior

## Real Role Assignment Decision

Real Role Assignment is explicitly deferred.

Reason:
Although core_user_roles exists and is structurally suitable as the assignment target table, real assignment requires a separate design for:
- request item role_id mapping
- duplicate active role protection
- temporary role expiry handling
- item_decision update rule
- rollback or compensation rule
- audit relation between core_user_roles and access request workflow
- revocation workflow

## 9. State-Only Apply Option
Approved for Phase 2 controlled implementation:
- update `dbo.core_access_requests.request_state = APPLIED`
- update `dbo.core_access_requests.applied_at`
- update `dbo.core_access_requests.applied_by_user_id`
- update `dbo.core_access_requests.updated_at`
- insert one `dbo.core_access_change_history` row

Proposed state-only UPDATE guard, aligned to approval transition pattern:
```
WHERE request_id = ?
  AND request_state = N'APPROVED'
```

Proposed actor source, aligned to `public_html/erp-access-request-approve-transition.php`:
- `applied_by_user_id` = current authenticated `current_user_id` from `erp_auth_require_current_user()`
- prototype actor today: `10001`

Proposed candidate page pattern:
- separate page only, not extension of approve page
- candidate name: `public_html/erp-access-request-apply-transition.php`
- candidate CSRF form key: `access_request_apply`
- candidate transition action: `apply_access_request`

Forbidden in state-only apply:
- role assignment
- permission assignment
- user creation
- tenant change
- customer portal change
- updating `core_access_request_items`
- touching assignment tables

Not updated in state-only apply:
- `submitted_at`
- `decided_at`
- `core_access_approvals`

## 10. Real Apply Option
Blocked and deferred to a separate approved design and implementation phase.

Real apply would require:
- confirmed assignment table (confirmed: `core_user_roles`)
- duplicate assignment protection
- effective_from/expires_at mapping from `core_access_request_items`
- active/inactive rule
- item_decision rule
- audit/history rule
- rollback/compensation rule

Open design questions for real apply:
- Does `item_decision` move from `PENDING` to `APPROVED` on apply?
- Which role identifier from the request item maps to the assignment row?
- How are temporary grants (`is_temporary = 1`) handled?
- How is duplicate assignment blocked if the subject already holds the role?
- What history or audit row is written for the assignment table change?

## 11. Duplicate Apply Protection
Next implementation must block apply if:
- request_state is not APPROVED
- applied_at is not NULL
- applied_by_user_id is not NULL
- ACCESS_REQUEST_APPLIED history already exists for the same request_id

Proposed protection mechanism, aligned to approval transition:
- pre-update read requires `request_state = APPROVED`
- pre-update read requires `applied_at IS NULL`
- pre-update read requires `applied_by_user_id IS NULL`
- pre-history check blocks existing `ACCESS_REQUEST_APPLIED` row for the same `request_id`
- UPDATE uses `WHERE request_state = APPROVED`
- transaction rolls back if request UPDATE or history INSERT fails

## 12. Required Safety Chain
Browser Form
→ CSRF Validation
→ Auth Check
→ Permission Check
→ Workflow Engine
→ SQL Transaction
→ State-Based Concurrency Guard
→ Apply Design Gate
→ State Update
→ History Insert
→ Commit or Rollback

Apply Design Gate rule:
- Assignment target table is confirmed as `core_user_roles` for future real assignment only.
- Phase 2 state-only apply is approved and must not write to `core_user_roles`.
- Real assignment apply remains blocked and deferred.

Reference chain from approved transition page:
1. `erp_csrf_require_valid_token(...)`
2. `erp_auth_require_current_user()`
3. `erp_permission_require($context, 'access.request.apply')`
4. `erp_workflow_require_transition('access_request', 'APPROVED', 'APPLIED')`
5. SQL transaction open
6. Read target request row
7. State and duplicate apply re-check
8. Apply Design Gate
9. UPDATE `core_access_requests`
10. INSERT `core_access_change_history`
11. Commit or rollback

## 13. Implementation Blockers

Remaining blockers before State-Only Apply implementation:
- workflow engine must allow APPROVED → APPLIED
- access.request.apply role coverage must be confirmed
- duplicate apply protection must be implemented and tested

Blockers for Real Role Assignment:
- real assignment design is deferred
- core_user_roles writes are forbidden in Phase 2 state-only apply
- core_access_request_items updates are forbidden until explicitly approved

## 14. Final Decision

APPROVED → APPLIED may proceed only as a Phase 2 state-only controlled prototype.

Real role assignment through core_user_roles remains blocked and deferred to a separate approved design and implementation phase.

## 15. Sign-Off
No runtime behavior was changed.
No database data or schema was changed.
This document only locks the Apply design boundary before implementation.

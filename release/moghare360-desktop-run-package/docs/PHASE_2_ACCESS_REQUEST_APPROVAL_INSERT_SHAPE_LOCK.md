# MOGHARE360 ERP - Phase 2 Access Request Approval Insert Shape Lock

## 1. Purpose
Lock the insert shape for dbo.core_access_approvals before implementing UNDER_REVIEW -> APPROVED.

## 2. Current Request State
- request_id: 4
- request_number: AR-20260620-084634-10001
- current request_state: UNDER_REVIEW
- next transition: UNDER_REVIEW -> APPROVED

## 3. Approval Table Columns

Confirmed dbo.core_access_approvals columns:
- approval_id: bigint, NOT NULL, IDENTITY
- request_id: bigint, NOT NULL
- approver_user_id: int, NOT NULL
- approver_capacity: nvarchar(80), NOT NULL
- decision: nvarchar(40), NOT NULL
- comment: nvarchar(max), NOT NULL
- decided_at: datetime2, NOT NULL
- created_at: datetime2, NOT NULL

Confirmed CHECK constraints:
- CK_core_access_approvals_capacity allows:
  - OWNER
  - OPERATIONS_MANAGER
  - SYSTEM_ADMIN
  - DEPARTMENT_MANAGER

- CK_core_access_approvals_decision allows:
  - APPROVED
  - REJECTED
  - PARTIAL

Confirmed current approvals for request_id = 4:
- No rows found in dbo.core_access_approvals

## 4. Approved Decision Value

Proposed decision:
APPROVED

Reason:
CK_core_access_approvals_decision allows APPROVED, REJECTED, PARTIAL.

## 5. Approved Permission

Permission for this transition:
access.request.approve

Confirmed role coverage already exists for:
- department_manager
- operations_manager
- owner
- system_admin

Controlled prototype actor:
- user_id: 10001
- role coverage: owner + system_admin

## 6. Proposed Approval Row Shape

Proposed insert for next implementation:

- request_id = target request_id
- approver_user_id = current authenticated user_id
- approver_capacity = OWNER

Reason:
CK_core_access_approvals_capacity requires uppercase OWNER.
- decision = APPROVED
- comment = Controlled prototype approval for UNDER_REVIEW to APPROVED
- decided_at = same transaction timestamp
- created_at = same transaction timestamp

## 7. Proposed Request Update Shape

Allowed request update for next implementation:

- request_state = APPROVED
- decided_at = same transaction timestamp
- updated_at = same transaction timestamp

Forbidden:
- changing submitted_at
- changing applied_at
- assigning roles
- assigning permissions
- creating users
- changing tenant behavior
- updating core_access_request_items
- touching core_user_roles or equivalent assignment tables

## 8. Proposed History Shape

Proposed change_type:
ACCESS_REQUEST_APPROVED

Proposed before_json:
- request_state
- decided_at
- updated_at

Proposed after_json:
- request_state
- decided_at
- updated_at

## 9. Duplicate Approval Protection

Next implementation must block approval if:
- request_state is not UNDER_REVIEW
- an APPROVED row already exists in core_access_approvals for the same request_id

## 10. Implementation Blockers

Still blocked until:
- workflow engine allows UNDER_REVIEW -> APPROVED
- transaction scope is confirmed

## 11. Locked Approval Insert Shape

- request_id = target request_id
- approver_user_id = current authenticated user_id
- approver_capacity = OWNER
- decision = APPROVED
- comment = Controlled prototype approval for UNDER_REVIEW to APPROVED
- decided_at = same transaction timestamp
- created_at = same transaction timestamp

## 12. Sign-Off

No runtime behavior was changed.

No database data or schema was changed.

This document only locks the approval insert shape before implementation.

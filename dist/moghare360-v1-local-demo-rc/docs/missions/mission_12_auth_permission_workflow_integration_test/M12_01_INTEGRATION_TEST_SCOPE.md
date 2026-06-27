# Integration Test Scope

## What Integration Is Being Tested
Mission 12 validates that three completed foundation layers work together for the locked test user and locked test request:

- Auth Context loads user, roles, and permissions
- Permission Guard evaluates approve and apply actions
- Workflow Read-Only data for request_id = 4 remains visible and complete

## Auth Context Scope
Mission 12 confirms:

- user_id = 10001 loads
- username = mahin.paradigm.owner
- roles = owner, system_admin
- permissions count > 0

Uses Mission 8 ODBC-compatible helper functions only.

## Permission Guard Scope
Mission 12 confirms:

- guard access.request.approve = OK
- guard access.request.apply = OK

Uses Mission 10 read-only guard evaluation only.
No action execution.

## Workflow Read-Only Scope
Mission 12 confirms:

- request_id = 4 visible
- request_state = APPLIED
- workflow timeline includes:
  - ACCESS_REQUEST_SUBMITTED
  - ACCESS_REQUEST_UNDER_REVIEW
  - ACCESS_REQUEST_APPROVED
  - ACCESS_REQUEST_APPLIED
- core_user_roles count for user_id 10001 = 2
- Real Assignment = NOT PERFORMED

Uses SELECT-only queries aligned with Mission 5 workflow viewer.

## Why This Test Is Required Before UI Consolidation
Before admin UI pages are consolidated, the project must confirm:

- auth data source is stable
- permission guard evaluation works against real permission data
- workflow read-only state remains locked and unchanged
- no accidental write path is introduced during integration testing

## No Write Boundary
Mission 12 performs:

- SELECT only
- read-only guard evaluation
- read-only browser display

Mission 12 does not perform:

- workflow transition
- role assignment
- permission change
- audit INSERT
- database write of any kind

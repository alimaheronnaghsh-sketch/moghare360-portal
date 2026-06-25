# Phase 1A Access Request Create UI Test Result

## Project
MOGHARE360 ERP

## Phase
Phase 1A

## Test Target

- erp-access-request-create.php

## Test Type

Local browser test with controlled database write.

## Test URL

- http://localhost:8080/moghareh360/erp-access-request-create.php

## Security Foundation Used

The tested page used:

- ERP Config Loader
- ERP Auth Helper
- ERP Permission Helper
- ERP CSRF Helper
- ERP Audit Helper

The tested page did not use:

- staff-auth.php
- access-control.php
- config.php
- config.example.php
- old portal login
- old portal session keys

## Initial Browser GET Test

Result:

PASSED

Confirmed:

- Page loaded successfully.
- ERP login was required.
- Platform Owner session was accepted.
- Form displayed successfully.
- CSRF hidden input was generated.
- No raw PHP error was displayed.
- No SQL error was displayed.
- No secret was displayed.

## Browser Required Field Test

Result:

PASSED

Confirmed:

- Browser-side required validation blocked empty submit.
- No database write occurred.
- No raw SQL error was displayed.
- No secret was displayed.

## Server-Side Validation Test

Test input:

- subject_user_id: 1
- justification: test validation only
- item_type: ROLE
- role_id: empty

Observed result:

- Validation failed.
- Invalid role.

Result:

PASSED

Confirmed:

- POST reached PHP.
- CSRF token was accepted.
- Server-side validation worked.
- Invalid role request was blocked.
- No database write occurred.
- No raw SQL error was displayed.
- No secret was displayed.

## Constraint Discovery

The first write attempt failed safely with:

- ERP request could not be completed.

Database investigation confirmed that initial values did not match SQL Server CHECK constraints.

Discovered valid constraints included:

### dbo.core_access_requests.request_type

Allowed values include:

- ONBOARDING
- ROLE_GRANT
- TEMPORARY_ROLE_GRANT
- DEPARTMENT_ASSIGN
- POSITION_ASSIGN
- PROMOTION
- ACCESS_UPGRADE
- ACCESS_DOWNGRADE
- SUSPENSION
- ACCESS_RESTRICTION
- OFFBOARDING
- EMERGENCY

### dbo.core_access_request_items.item_type

Allowed values include:

- ROLE_GRANT
- ROLE_REVOKE
- DEPARTMENT_SET
- POSITION_SET
- SUSPENSION_CREATE
- RESTRICTION_CREATE
- LIFECYCLE_STATE_SET

### dbo.core_access_requests.priority

Allowed values:

- NORMAL
- URGENT

## Constraint Alignment Fix

The page was updated to align with real database constraints.

Final controlled prototype scope:

- request_type: ROLE_GRANT, TEMPORARY_ROLE_GRANT
- priority: NORMAL, URGENT
- item_type: ROLE_GRANT only

Removed from first prototype scope:

- ACCESS_GRANT
- ACCESS_CHANGE
- ACCESS_REMOVE
- ROLE
- PERMISSION
- LOW
- HIGH
- EMERGENCY

## Successful Controlled Write Test

Test input:

- Request Type: ROLE_GRANT
- Subject User ID: 10001
- Justification: Phase 1A controlled write test
- Priority: NORMAL
- Item Type: ROLE_GRANT
- Role ID: 14
- Effective From: 2026-06-20
- Expires At: empty
- Temporary Access: unchecked

Observed browser result:

- ERP access request created.

Result:

PASSED

## Database Verification Result

### dbo.core_access_requests

Confirmed row:

- request_id: 4
- request_number: AR-20260620-084634-10001
- request_type: ROLE_GRANT
- request_state: DRAFT
- priority: NORMAL
- subject_user_id: 10001
- requested_by_user_id: 10001
- justification: Phase 1A controlled write test
- created_at: 2026-06-20 06:46:34.383

### dbo.core_access_request_items

Confirmed row:

- item_id: 2
- request_id: 4
- item_type: ROLE_GRANT
- role_id: 14
- permission_key: NULL
- effective_from: 2026-06-20 00:00:00.000
- expires_at: NULL
- is_temporary: 0
- item_decision: PENDING
- created_at: 2026-06-20 06:46:34.383

### dbo.core_audit_logs

Confirmed row:

- audit_id: 18
- actor_user_id: 10001
- action: ERP_ACCESS_REQUEST_CREATED
- entity_type: core_access_requests
- entity_id: 4
- request_id: 4
- subject_user_id: 10001
- created_at: 2026-06-20 10:16:34.389

## Confirmed Write Behavior

Confirmed:

- GET page works.
- POST reaches PHP.
- CSRF token is accepted.
- Server-side validation works.
- SQL Server CHECK constraints are respected.
- Database transaction insert works.
- One request header row was inserted.
- One request item row was inserted.
- One audit row was inserted.
- Safe success message was shown.
- Internal request_id was not displayed in the UI.
- Raw SQL errors were not displayed.
- Stack traces were not displayed.
- Config secrets were not displayed.
- CSRF token values were not displayed.
- Session internals were not displayed.
- Old portal authentication was not used.

## Approved Write Tables Used

The implementation wrote to:

- dbo.core_access_requests
- dbo.core_access_request_items
- dbo.core_audit_logs through ERP Audit Helper

No other table write was confirmed in this test.

## Known Limitation

This first prototype supports ROLE_GRANT only.

The following are not yet implemented:

- PERMISSION item requests
- ROLE_REVOKE
- approval submission flow
- approval decision UI
- request listing UI
- request detail UI
- browser-based CSRF tamper test
- duplicate request prevention
- production hardening

## Files Changed In This Test Step

Only this test result document was created:

- docs/PHASE_1A_ACCESS_REQUEST_CREATE_UI_TEST_RESULT.md

## Files Not Changed In This Test Step

The following files were not changed in this documentation step:

- erp-access-request-create.php
- includes/erp-config-loader.php
- includes/erp-auth-helper.php
- includes/erp-permission-helper.php
- includes/erp-csrf-helper.php
- includes/erp-audit-helper.php
- erp-admin-dashboard.php
- erp-admin-protected-test.php
- erp-admin-login.php
- erp-admin-logout.php
- staff-auth.php
- access-control.php
- config.php
- config.example.php
- private/erp-config.php
- SQL files

## Final Status

PASSED

## Decision

The Phase 1A Access Request Create UI local prototype test is approved.

The page successfully created a controlled ROLE_GRANT access request with matching request item and audit log.

The required next step is:

- Commit erp-access-request-create.php and docs/PHASE_1A_ACCESS_REQUEST_CREATE_UI_TEST_RESULT.md together

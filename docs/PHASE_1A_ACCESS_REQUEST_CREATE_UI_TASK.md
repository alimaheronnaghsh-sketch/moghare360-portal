# Phase 1A Access Request Create UI Task

## Project
MOGHARE360 ERP

## Phase
Phase 1A

## Purpose
Define the controlled future task for creating the first write-enabled ERP UI:

- Access Request Create UI

This document is a task definition only.

No UI is created in this step.

No runtime behavior is changed in this step.

No database write is performed in this step.

## Approved Planning Documents

The following planning and discovery documents are complete:

- docs/PHASE_1A_ACCESS_REQUEST_CREATE_UI_PLAN.md
- docs/PHASE_1A_ACCESS_REQUEST_TABLE_DISCOVERY_RESULT.md
- docs/PHASE_1A_CSRF_PROTECTION_COMPLETION_REVIEW.md
- docs/PHASE_1A_AUDIT_WRITE_COMPLETION_REVIEW.md
- docs/PHASE_1A_AUTH_AND_PERMISSION_FOUNDATION_COMPLETION_REVIEW.md

## Future UI File

The future Access Request Create UI file will be:

- erp-access-request-create.php

This file is not created in this step.

## Approved Future Write Targets

The future implementation may write to the following tables only:

- dbo.core_access_requests
- dbo.core_access_request_items
- dbo.core_audit_logs

No other table write is approved for the first Access Request Create UI.

## Required Future Dependencies

The future UI must use:

- includes/erp-config-loader.php
- includes/erp-auth-helper.php
- includes/erp-permission-helper.php
- includes/erp-csrf-helper.php
- includes/erp-audit-helper.php

The future UI must not use:

- staff-auth.php
- access-control.php
- config.php
- config.example.php
- old portal login
- old portal session keys

## Required Future Access Control

The future page must require:

- active ERP login
- Platform Owner-only boundary for this local prototype step
- ERP session validation through ERP Auth Helper
- ERP role validation through ERP Permission Helper

Future allowed role candidates:

- owner
- system_admin

No non-owner access is approved in this first write UI task.

## Required Future CSRF Rules

The future page must:

- generate CSRF token on GET form display
- include hidden input named erp_csrf_token
- validate CSRF token on POST
- reject missing CSRF token
- reject invalid CSRF token
- display only safe generic security failure message
- never display expected token
- never display submitted token
- never display session internals

Suggested CSRF purpose:

- access_request_create

## Required Future Insert Into dbo.core_access_requests

The future implementation must insert one request header row into:

- dbo.core_access_requests

Minimum required fields:

- request_number
- request_type
- subject_user_id
- requested_by_user_id
- justification

Fields that may use database defaults:

- request_state = DRAFT
- priority = NORMAL
- owner_acknowledged = 0
- is_emergency = 0
- created_at = sysutcdatetime()

Optional field that may be explicitly provided:

- priority

Fields that must not be inserted manually:

- request_id
- row_version

## Required Future Insert Into dbo.core_access_request_items

The future implementation must insert one request item row into:

- dbo.core_access_request_items

Minimum required fields:

- request_id
- item_type
- effective_from

Depending on item_type, the future implementation may also insert:

- role_id
- department_id
- position_id
- module_key
- permission_key
- scope_type
- expires_at
- is_temporary

Fields that may use database defaults:

- item_decision = PENDING
- sort_order = 1
- created_at = sysutcdatetime()

Fields that must not be inserted manually:

- item_id

## Approved First UI Scope

The first UI should support a minimal controlled request.

Recommended first supported item types:

- ROLE
- PERMISSION

Recommended form fields:

- request_type
- subject_user_id
- justification
- priority
- item_type
- role_id
- permission_key
- effective_from
- expires_at
- is_temporary

## Required Request Number Strategy

Because request_number is required and has no database default, the future implementation must generate it safely.

Approved local prototype pattern:

- AR-YYYYMMDD-HHMMSS-USERID

The future implementation must ensure the generated request_number length does not exceed nvarchar(60).

## Required Future Transaction Boundary

The future POST implementation must use a database transaction.

Required sequence:

1. Validate active ERP login
2. Validate required role
3. Validate CSRF token
4. Validate submitted input
5. Open database connection
6. Begin transaction
7. Insert into dbo.core_access_requests
8. Retrieve new request_id safely
9. Insert into dbo.core_access_request_items
10. Write audit event
11. Commit transaction
12. Show safe success message

On failure:

1. Roll back transaction
2. Show only safe user-facing error message
3. Do not display raw SQL errors
4. Do not display stack traces
5. Do not display config secrets

## Required Future Audit Event

After successful creation, the future implementation must write a safe audit event.

Audit action:

- ERP_ACCESS_REQUEST_CREATED

Audit entity_type:

- core_access_requests

Audit entity_id:

- created request_id

Audit details may include:

- request_number
- request_type
- subject_user_id
- requested_by_user_id
- item_type
- role_id if used
- permission_key if used
- priority

Audit details must not include:

- password
- password_hash
- CSRF token
- session token
- database password
- config secrets
- SQL errors
- stack trace

## Required Future Success Message

After successful creation, show only:

- ERP access request created.

No internal ID display is approved in the first implementation.

## Required Future Failure Message

For database or unexpected failure, show only:

- ERP request could not be completed.

For CSRF/security failure, use the CSRF helper failure behavior.

Raw database errors must never be displayed.

## Required Future Tests

After the future UI is implemented, the project must test:

- unauthenticated access is blocked
- unauthorized access is blocked
- authorized GET loads form
- CSRF hidden input exists
- POST without CSRF fails
- POST with invalid CSRF fails
- POST with valid CSRF continues
- invalid subject_user_id fails safely
- invalid item_type fails safely
- invalid role_id fails safely when item_type is ROLE
- invalid permission_key fails safely when item_type is PERMISSION
- valid ROLE request creates exactly one request header row
- valid ROLE request creates exactly one request item row
- valid PERMISSION request creates exactly one request header row
- valid PERMISSION request creates exactly one request item row
- successful creation writes audit event
- transaction rolls back safely on failure
- raw SQL errors are not displayed
- secrets are not displayed
- old portal auth is not used

## Not Approved In This Step

The following are not approved now:

- Creating erp-access-request-create.php
- Creating Access Request Create UI
- Creating any form
- Creating any submit handler
- Performing database writes
- Modifying SQL files
- Creating migrations
- Creating users
- Assigning roles
- Modifying permissions
- Modifying auth helper
- Modifying permission helper
- Modifying CSRF helper
- Modifying audit helper
- Modifying dashboard
- Production deployment

## Final Decision

This document only approves the controlled future task for creating Access Request Create UI.

No write-enabled UI is created.

No runtime behavior is changed.

No database write is performed.

The required next step is:

- Create erp-access-request-create.php in controlled prototype scope

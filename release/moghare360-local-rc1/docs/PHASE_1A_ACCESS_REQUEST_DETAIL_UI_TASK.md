# Phase 1A Access Request Detail UI Task

## Project
MOGHARE360 ERP

## Phase
Phase 1A

## Purpose
Define the controlled future task for creating a read-only ERP UI for viewing one access request in detail.

This document is a task definition only.

No UI is created in this step.

No runtime behavior is changed in this step.

No database write is performed in this step.

## Approved Planning Document

The following planning document is complete:

- docs/PHASE_1A_ACCESS_REQUEST_DETAIL_UI_PLAN.md

## Completed Related Scope

The following related Phase 1A scope is complete:

- Access Request Create UI
- Access Request Create UI Test Result
- Access Request Create UI Completion Review
- Access Request List UI
- Access Request List UI Test Result
- Access Request List UI Completion Review

## Future UI File

The future Access Request Detail UI file will be:

- erp-access-request-detail.php

This file is not created in this step.

## Allowed Future Change

Only this file may be created in the future implementation task:

- erp-access-request-detail.php

No existing file may be modified during the first Access Request Detail UI creation task.

## Future UI Type

The future UI must be:

- read-only
- SELECT-only
- browser-based
- protected by ERP Auth Helper
- protected by ERP Permission Helper

The future UI must not perform:

- INSERT
- UPDATE
- DELETE
- MERGE
- transaction
- workflow state change
- audit write
- user modification
- role assignment
- permission modification

## Required Future Dependencies

The future UI must use:

- includes/erp-config-loader.php
- includes/erp-auth-helper.php
- includes/erp-permission-helper.php

The future UI must not use:

- includes/erp-csrf-helper.php for the first detail implementation
- includes/erp-audit-helper.php for the first detail implementation
- staff-auth.php
- access-control.php
- config.php
- config.example.php
- old portal login
- old portal session keys

## Required Future Access Control

The future page must require:

- active ERP login
- ERP session validation through ERP Auth Helper
- ERP role validation through ERP Permission Helper

For local prototype scope, allowed roles:

- owner
- system_admin

No public access is approved.

No customer access is approved.

No old portal access is approved.

## Required Future Request Identifier

The future page must accept one request identifier:

- request_id

The request_id must be:

- required
- numeric
- positive integer
- validated before database query

If request_id is missing, invalid, zero, negative, or non-existing, the page must show only:

- ERP access request was not found.

## Approved Future Read Tables

The future implementation may read from:

- dbo.core_access_requests
- dbo.core_access_request_items
- dbo.core_users
- dbo.core_roles

No other table read is approved for the first implementation.

## Required Future Queries

The future implementation must use fixed SELECT-only parameterized queries.

Required queries:

1. Header query by request_id:
   - read from dbo.core_access_requests
   - LEFT JOIN dbo.core_users as subject user
   - LEFT JOIN dbo.core_users as requester user

2. Items query by request_id:
   - read from dbo.core_access_request_items
   - LEFT JOIN dbo.core_roles for role display
   - order by sort_order then item_id

The queries must not use:

- dynamic SQL
- raw user-provided ORDER BY
- raw user-provided WHERE
- unvalidated filters
- write statements

## Required Future Display Sections

The first detail UI must display:

### Request Header

- request_id
- request_number
- request_type
- request_state
- priority
- subject_user_id
- subject username
- subject full name
- requested_by_user_id
- requester username
- requester full name
- justification
- owner_acknowledged
- is_emergency
- submitted_at
- decided_at
- applied_at
- cancelled_at
- created_at
- updated_at

### Request Items

- item_id
- item_type
- role_id
- role_name
- department_id
- position_id
- module_key
- permission_key
- scope_type
- effective_from
- expires_at
- is_temporary
- item_decision
- sort_order
- created_at

## Required Future Output Safety

The future page must escape all displayed values.

The future page must not display:

- password_hash
- database password
- config secrets
- private config path
- CSRF token values
- session internals
- raw SQL errors
- PHP stack traces
- ODBC diagnostic details

## Required Future Error Behavior

If the database query fails, show only:

- ERP access request detail could not be loaded.

If the request is not found, show only:

- ERP access request was not found.

The page must not show:

- SQL text
- SQLSTATE
- ODBC error details
- stack trace
- config values
- private paths

## Required Future Navigation

The future page may include links to:

- ERP Admin Dashboard
- Access Request List UI
- Access Request Create UI

The future page must not include write action buttons.

Not approved:

- Approve
- Reject
- Submit
- Cancel
- Apply
- Delete
- Edit
- Assign role
- Modify permission

## Required Future Tests

After the future UI is implemented, the project must test:

- unauthenticated access is blocked
- unauthorized access is blocked
- owner/system_admin access loads page
- missing request_id fails safely
- invalid request_id fails safely
- non-existing request_id fails safely
- valid request_id loads detail page
- request_id 4 loads the previously created ROLE_GRANT request
- header details are displayed safely
- item details are displayed safely
- subject user is displayed safely
- requester user is displayed safely
- role name is displayed safely
- no password_hash is displayed
- no raw SQL error is displayed
- no config secret is displayed
- no CSRF token is displayed
- no session internals are displayed
- no write button is present
- no write action is performed
- no audit write is performed
- old portal auth is not used

## Not Approved In This Step

The following are not approved now:

- Creating erp-access-request-detail.php
- Creating Access Request Detail UI
- Creating approval UI
- Creating reject UI
- Creating submit workflow
- Creating cancel workflow
- Creating apply workflow
- Creating any form
- Creating any submit handler
- Performing database writes
- Writing audit records
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
- Modifying Access Request Create UI
- Modifying Access Request List UI
- Production deployment

## Final Decision

This document only approves the controlled future task for creating the Access Request Detail UI.

No UI is created.

No runtime behavior is changed.

No database write is performed.

The required next step is:

- Create erp-access-request-detail.php in controlled read-only prototype scope

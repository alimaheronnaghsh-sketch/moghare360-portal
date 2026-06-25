# Phase 1A Access Request List UI Task

## Project
MOGHARE360 ERP

## Phase
Phase 1A

## Purpose
Define the controlled future task for creating a read-only ERP UI for listing access requests.

This document is a task definition only.

No UI is created in this step.

No runtime behavior is changed in this step.

No database write is performed in this step.

## Approved Planning Document

The following planning document is complete:

- docs/PHASE_1A_ACCESS_REQUEST_LIST_UI_PLAN.md

## Completed Related Scope

The following related Phase 1A scope is complete:

- Access Request Create UI
- Access Request Create UI Test Result
- Access Request Create UI Completion Review

## Future UI File

The future Access Request List UI file will be:

- erp-access-request-list.php

This file is not created in this step.

## Allowed Future Change

Only this file may be created in the future implementation task:

- erp-access-request-list.php

No existing file may be modified during the first Access Request List UI creation task.

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

- includes/erp-csrf-helper.php for the first list implementation
- includes/erp-audit-helper.php for the first list implementation
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

## Approved Future Read Tables

The future implementation may read from:

- dbo.core_access_requests
- dbo.core_access_request_items
- dbo.core_users
- dbo.core_roles

No other table read is approved for the first implementation unless explicitly required by the query and documented later.

## Required Future Query

The future implementation must use a fixed SELECT-only query.

The query should:

- select TOP 50 newest access requests
- start from dbo.core_access_requests
- LEFT JOIN dbo.core_access_request_items
- LEFT JOIN dbo.core_users as subject user
- LEFT JOIN dbo.core_users as requester user
- LEFT JOIN dbo.core_roles for role display
- order by request_id descending or created_at descending

The query must not use:

- dynamic SQL
- raw user-provided ORDER BY
- raw user-provided WHERE
- unvalidated filters
- write statements

## Required Future Display Columns

The first list UI should display:

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
- justification summary
- created_at
- item_type
- role_id
- role_name
- item_decision

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

- ERP request list could not be loaded.

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
- page performs SELECT-only behavior
- page lists the previously created ROLE_GRANT request
- request_number displays safely
- subject user displays safely
- requester user displays safely
- role name displays safely
- item_decision displays safely
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

- Creating erp-access-request-list.php
- Creating Access Request List UI
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
- Production deployment

## Final Decision

This document only approves the controlled future task for creating the Access Request List UI.

No UI is created.

No runtime behavior is changed.

No database write is performed.

The required next step is:

- Create erp-access-request-list.php in controlled read-only prototype scope

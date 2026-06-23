# Phase 1A Access Request List UI Plan

## Project
MOGHARE360 ERP

## Phase
Phase 1A

## Purpose
Plan the controlled read-only ERP UI for listing access requests created in the access lifecycle workflow.

This document is planning-only.

No UI is created in this step.

No runtime behavior is changed in this step.

No database write is performed in this step.

## Current Completed Foundation

The following Phase 1A foundation is complete for local prototype scope:

- ERP Config Loader
- ERP Admin Login Prototype
- ERP Admin Logout Prototype
- ERP Auth Helper
- ERP Permission Helper
- ERP CSRF Helper
- ERP Audit Helper
- Protected Read-Only ERP Admin Area
- Access Request Create UI
- Access Request Create UI Completion Review

## Future UI Scope

The future planned read-only UI will be:

- Access Request List UI

Future candidate file:

- erp-access-request-list.php

This file is not created in this step.

## Main Boundary

The Access Request List UI must be read-only.

It must not:

- create access requests
- update access requests
- approve access requests
- reject access requests
- submit requests for approval
- cancel requests
- apply access changes
- write audit records unless separately approved
- modify users
- assign roles
- modify permissions
- perform INSERT, UPDATE, DELETE, or workflow state change

## Required Future Dependencies

The future Access Request List UI must use:

- includes/erp-config-loader.php
- includes/erp-auth-helper.php
- includes/erp-permission-helper.php

The future page may use:

- includes/erp-audit-helper.php only if read-view audit logging is later approved

The future page should not need:

- includes/erp-csrf-helper.php

Reason:

- The planned list UI is read-only and does not submit write actions.

The future page must not use:

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

For local prototype scope, allowed role candidates:

- owner
- system_admin

No public access is approved.

No old portal access is approved.

## Primary Read Tables

The future UI may read from:

- dbo.core_access_requests
- dbo.core_access_request_items
- dbo.core_users
- dbo.core_roles

Optional future read tables:

- dbo.core_access_approvals
- dbo.core_access_approval_rules
- dbo.core_audit_logs

No write tables are approved in this plan.

## Minimum List Columns

The first list view should show:

- request_id
- request_number
- request_type
- request_state
- priority
- subject_user_id
- subject username or full name
- requested_by_user_id
- requester username or full name
- justification summary
- created_at
- item_type
- role_id
- role_name
- item_decision

## Display Rules

The page must display safe escaped output only.

The page must not display:

- password_hash
- database password
- config secrets
- CSRF token values
- session internals
- raw SQL errors
- PHP stack traces
- private config path

## Future Query Boundary

The future implementation should use a SELECT-only query.

Suggested read pattern:

- Start from dbo.core_access_requests
- LEFT JOIN dbo.core_access_request_items
- LEFT JOIN dbo.core_users as subject user
- LEFT JOIN dbo.core_users as requester user
- LEFT JOIN dbo.core_roles for role display when role_id is present

The future query must not use dynamic SQL.

The future query must not accept raw ORDER BY or raw WHERE values from user input.

## Future Filters

The first version may include no filters.

If filters are added later, they must be controlled whitelist filters only.

Possible future safe filters:

- request_state
- request_type
- priority
- subject_user_id
- requested_by_user_id
- created date range

No filter implementation is approved in this plan.

## Future Pagination

The first version may show TOP 50 newest records.

Future pagination may be added later.

If pagination is added, it must validate numeric page and page size safely.

## Future Row Actions

The first list UI should not include write actions.

Allowed future read-only row action:

- View Details

Not approved in first list implementation:

- Approve
- Reject
- Submit
- Cancel
- Apply
- Delete
- Edit
- Assign role
- Modify permission

## Future Navigation

The future page may include links to:

- ERP Admin Dashboard
- Access Request Create UI
- Future Access Request Detail UI, when approved

The page must not link to unapproved write actions.

## Future Test Requirements

After implementation, the project must test:

- unauthenticated access is blocked
- unauthorized access is blocked
- authorized owner/system_admin access loads page
- page performs SELECT-only behavior
- page lists the previously created ROLE_GRANT request
- request_number is displayed safely
- subject user is displayed safely
- requester user is displayed safely
- role name is displayed safely
- no raw SQL errors are displayed
- no secrets are displayed
- no password_hash is displayed
- no CSRF token value is displayed
- no write button is present
- no write action is performed
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
- Writing audit records for read views
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

This document only approves planning the controlled read-only Access Request List UI.

No UI is created.

No runtime behavior is changed.

No database write is performed.

The required next step is:

- Access Request List UI Task

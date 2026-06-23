# Phase 1A Access Request Detail UI Plan

## Project
MOGHARE360 ERP

## Phase
Phase 1A

## Purpose
Plan the controlled read-only ERP UI for viewing one access request in detail.

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
- Access Request List UI
- Access Request List UI Completion Review

## Future UI Scope

The future planned read-only UI will be:

- Access Request Detail UI

Future candidate file:

- erp-access-request-detail.php

This file is not created in this step.

## Main Boundary

The Access Request Detail UI must be read-only.

It must not:

- create access requests
- update access requests
- approve access requests
- reject access requests
- submit requests for approval
- cancel requests
- apply access changes
- delete records
- write audit records unless separately approved
- modify users
- assign roles
- modify permissions
- perform INSERT, UPDATE, DELETE, MERGE, or workflow state change

## Required Future Dependencies

The future Access Request Detail UI must use:

- includes/erp-config-loader.php
- includes/erp-auth-helper.php
- includes/erp-permission-helper.php

The future page may use:

- includes/erp-audit-helper.php only if read-view audit logging is later approved

The future page should not need:

- includes/erp-csrf-helper.php

Reason:

- The planned detail UI is read-only and does not submit write actions.

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

## Future Request Identifier

The future page may accept one request identifier:

- request_id

The request_id must be:

- required
- numeric
- positive integer
- validated before database query

Invalid request_id must show a safe message:

- ERP access request was not found.

The page must not display raw errors for invalid identifiers.

## Primary Read Tables

The future UI may read from:

- dbo.core_access_requests
- dbo.core_access_request_items
- dbo.core_users
- dbo.core_roles

Optional future read tables:

- dbo.core_access_approvals
- dbo.core_access_approval_rules
- dbo.core_access_change_history
- dbo.core_audit_logs

No write tables are approved in this plan.

## Minimum Detail Sections

The first detail view should show:

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

### Audit Summary

Audit summary is not approved for first implementation unless separately planned.

## Display Rules

The page must display safe escaped output only.

The page must not display:

- password_hash
- database password
- config secrets
- private config path
- CSRF token values
- session internals
- raw SQL errors
- PHP stack traces
- ODBC diagnostic details

## Future Query Boundary

The future implementation should use fixed SELECT-only queries.

The future queries should:

- fetch request header by validated request_id
- fetch request items by validated request_id
- use parameterized queries
- not use dynamic SQL
- not use raw user-provided ORDER BY
- not use raw user-provided WHERE
- not perform write statements

## Future Not Found Behavior

If request_id does not exist, show only:

- ERP access request was not found.

The page must not show:

- SQL text
- SQLSTATE
- ODBC error details
- stack trace
- config values
- private paths

## Future Error Behavior

If the database query fails, show only:

- ERP access request detail could not be loaded.

The page must not show:

- SQL text
- SQLSTATE
- ODBC error details
- stack trace
- config values
- private paths

## Future Row Actions

The first detail UI must not include write actions.

Allowed future read-only navigation:

- Back to Access Request List UI
- Back to ERP Admin Dashboard
- Access Request Create UI

Not approved in first detail implementation:

- Approve
- Reject
- Submit
- Cancel
- Apply
- Delete
- Edit
- Assign role
- Modify permission

## Future Test Requirements

After implementation, the project must test:

- unauthenticated access is blocked
- unauthorized access is blocked
- authorized owner/system_admin access loads page
- missing request_id fails safely
- invalid request_id fails safely
- non-existing request_id fails safely
- valid request_id loads detail page
- header details are displayed safely
- item details are displayed safely
- previously created ROLE_GRANT request is displayed
- no raw SQL errors are displayed
- no secrets are displayed
- no password_hash is displayed
- no CSRF token value is displayed
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
- Modifying Access Request Create UI
- Modifying Access Request List UI
- Production deployment

## Final Decision

This document only approves planning the controlled read-only Access Request Detail UI.

No UI is created.

No runtime behavior is changed.

No database write is performed.

The required next step is:

- Access Request Detail UI Task

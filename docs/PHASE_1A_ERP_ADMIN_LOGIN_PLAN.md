# Phase 1A ERP Admin Login Plan

## Project
MOGHARE360 ERP

## Phase
Phase 1A

## Purpose
Design the future independent ERP Admin Login flow without replacing or modifying the current portal login.

This document is planning-only.

No login implementation is approved in this step.

## Current Status
Phase 0 is closed.

The approved next phase is:

Phase 1A: ERP Admin Login Plan

## Main Rule
The ERP Admin Login must be independent from the existing portal login.

The following files must not be changed in Phase 1A planning:

- staff-auth.php
- access-control.php
- config.php
- config.example.php

## Proposed Future Login File

Future implementation file:

- public_html/erp-admin-login.php

This file is not created in this step.

## Login Scope
The first ERP Admin Login prototype must only support:

- Platform Owner login
- user_id = 10001
- username = mahin.paradigm.owner

No general staff login is approved in this phase.

No customer login is approved in this phase.

## Authentication Source
The future login must authenticate against:

- dbo.core_users

Required conditions:

- username must exist
- is_login_enabled = 1
- is_system_owner = 1
- password_hash must be verified securely
- user must have active owner or system_admin role

## Session Boundary
The future ERP Admin session must be separate from the old portal session.

Suggested future session keys:

- erp_user_id
- erp_username
- erp_full_name
- erp_is_system_owner
- erp_login_time
- erp_session_token

No existing portal session key should be reused.

## Permission Boundary
After successful login, permission checks must be based on:

- core_users
- core_user_roles
- core_roles
- core_role_permissions
- core_permissions

No direct role assignment is allowed from login.

## Audit Requirements
Every login action must create an audit log later.

Future audit events:

- ERP_LOGIN_SUCCESS
- ERP_LOGIN_FAILED_USERNAME_NOT_FOUND
- ERP_LOGIN_FAILED_DISABLED_USER
- ERP_LOGIN_FAILED_PASSWORD
- ERP_LOGIN_FAILED_ROLE_REQUIRED
- ERP_LOGOUT

Audit must include:

- user_id when available
- username attempted
- event type
- IP address
- user agent
- created_at
- success/failure state

## Security Requirements

The future login must:

- never display password_hash
- never display config secrets
- never expose SQL errors to browser
- use prepared statements or safe parameter handling
- use password_verify
- use session_regenerate_id after successful login
- rate limit failed attempts later
- log failed attempts later

## Local Development Connection
Current local diagnostic pages use:

- PHP ODBC
- Trusted_Connection
- SELECT only
- No config.php

For real login implementation, Safe Config Strategy must be designed first.

## Required Documents Before Implementation
Before creating any login PHP file, these documents must exist:

1. Phase 1A ERP Admin Login Plan
2. Phase 1A Safe Config Strategy
3. Phase 1A Session and Auth Boundary Plan
4. Phase 1A Login Audit Plan
5. Phase 1A Permission Check Layer Plan

## Not Approved in This Step

The following are not approved:

- Creating erp-admin-login.php
- Replacing current login
- Modifying staff-auth.php
- Modifying access-control.php
- Modifying config.php
- Modifying config.example.php
- Creating new users
- Assigning roles
- Creating write UI
- Migrating staff_users
- Customer access
- Production deployment

## Future Implementation Order

The correct future order is:

1. Safe Config Strategy
2. Session and Auth Boundary Plan
3. Login Audit Plan
4. Permission Check Layer Plan
5. ERP Admin Login Prototype
6. Local test
7. Test result document
8. Commit and Push
9. Review before any write-enabled UI

## Final Decision
This document only approves planning for ERP Admin Login.

No executable login file is created.

No runtime behavior is changed.

No user or role data is changed.

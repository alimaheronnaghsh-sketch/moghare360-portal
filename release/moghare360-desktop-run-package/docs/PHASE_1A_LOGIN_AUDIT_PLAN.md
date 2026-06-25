# Phase 1A Login Audit Plan

## Project
MOGHARE360 ERP

## Phase
Phase 1A

## Purpose
Define the future ERP Admin Login audit requirements before any executable login or audit-writing file is created.

This document is planning-only.

No audit implementation is approved in this step.

## Current Status

Phase 0 is closed.

The following Phase 1A planning documents exist:

- docs/PHASE_1A_ERP_ADMIN_LOGIN_PLAN.md
- docs/PHASE_1A_SAFE_CONFIG_STRATEGY.md
- docs/PHASE_1A_SESSION_AND_AUTH_BOUNDARY_PLAN.md

This document defines the future login audit boundary.

## Main Rule

Every future ERP Admin login-related action must be auditable.

No login action may happen silently.

## Audit Table

Future login audit events must be written to:

- dbo.core_audit_logs

No new table is approved in this step.

## Future Login Audit Events

The future ERP Admin Login must support these audit event types:

- ERP_LOGIN_SUCCESS
- ERP_LOGIN_FAILED_USERNAME_NOT_FOUND
- ERP_LOGIN_FAILED_DISABLED_USER
- ERP_LOGIN_FAILED_PASSWORD
- ERP_LOGIN_FAILED_ROLE_REQUIRED
- ERP_LOGIN_FAILED_SYSTEM_OWNER_REQUIRED
- ERP_LOGOUT
- ERP_SESSION_TIMEOUT
- ERP_SESSION_INVALID

## Required Audit Fields

Every future audit event must record:

- audit event type
- username attempted
- user_id when available
- success or failure status
- IP address
- user agent
- request path
- created_at
- error category
- safe message
- related session token hash when available

## Sensitive Data Rules

Audit logs must never store:

- raw password
- password_hash
- config secret
- database password
- full connection string
- session token raw value
- private key
- API key

## Browser Error Rules

Login failure messages shown to the browser must stay generic.

Allowed browser message:

- Invalid login attempt.

Not allowed browser messages:

- Username not found
- Password is wrong
- User disabled
- Role missing
- SQL error
- Connection string error
- password_hash value

Detailed failure reason is for audit only, not for browser display.

## Audit Flow

Future login attempt flow:

1. Receive username and password.
2. Validate request shape.
3. Look up username safely.
4. Verify enabled status.
5. Verify password.
6. Verify system owner/admin role.
7. Create success or failure audit event.
8. For success, create ERP session.
9. For failure, show generic error.

## Login Success Audit

For ERP_LOGIN_SUCCESS, audit must record:

- user_id
- username
- roles checked
- IP address
- user agent
- login timestamp
- request path
- success = 1

## Login Failure Audit

For login failures, audit must record:

- attempted username
- user_id if available
- failure event type
- IP address
- user agent
- request path
- success = 0
- safe failure category

## Logout Audit

Future ERP logout must write:

- ERP_LOGOUT

It must record:

- user_id
- username
- session duration when available
- IP address
- user agent
- logout timestamp

## Session Failure Audit

Future invalid or expired session must write:

- ERP_SESSION_TIMEOUT
- ERP_SESSION_INVALID

It must record:

- user_id when available
- request path
- IP address
- user agent
- safe failure reason

## Rate Limit Future Requirement

Rate limiting is not implemented in this step.

Future design must support:

- failed login count
- cooldown window
- IP-based throttling
- username-based throttling
- audit on repeated failure

## Not Approved in This Step

The following are not approved:

- Creating audit-writing PHP code
- Creating erp-admin-login.php
- Creating erp-admin-logout.php
- Modifying existing login
- Modifying staff-auth.php
- Modifying access-control.php
- Modifying config.php
- Modifying config.example.php
- Modifying SQL files
- Creating users
- Assigning roles
- Creating write UI
- Migrating staff_users
- Production deployment

## Future Implementation Order

After this document is approved:

1. Phase 1A Permission Check Layer Plan
2. Safe config implementation plan
3. Session helper implementation plan
4. Audit helper implementation plan
5. ERP Admin Login prototype
6. ERP Admin Logout prototype
7. Local test
8. Login audit test document
9. Commit and Push

## Final Decision

This document only approves the ERP Admin Login audit design.

No executable audit writer is created.

No runtime behavior is changed.

No login implementation is approved.

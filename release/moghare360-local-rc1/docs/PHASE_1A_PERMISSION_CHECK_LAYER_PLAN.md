# Phase 1A Permission Check Layer Plan

## Project
MOGHARE360 ERP

## Phase
Phase 1A

## Purpose
Define the future ERP permission check layer before any executable login, session, or write-enabled ERP page is created.

This document is planning-only.

No permission implementation is approved in this step.

## Current Status

Phase 0 is closed.

The following Phase 1A planning documents exist:

- docs/PHASE_1A_ERP_ADMIN_LOGIN_PLAN.md
- docs/PHASE_1A_SAFE_CONFIG_STRATEGY.md
- docs/PHASE_1A_SESSION_AND_AUTH_BOUNDARY_PLAN.md
- docs/PHASE_1A_LOGIN_AUDIT_PLAN.md

This document defines the future ERP permission check boundary.

## Main Rule

No ERP page may rely only on login status.

Every future ERP admin page must check both:

1. Authentication
2. Authorization

Authentication means the ERP user is logged in.

Authorization means the ERP user has the required role or permission.

## Permission Source Tables

The future permission check layer must read from:

- dbo.core_users
- dbo.core_user_roles
- dbo.core_roles
- dbo.core_role_permissions
- dbo.core_permissions

No direct role assignment is allowed by the permission layer.

No permission modification is allowed by the permission layer.

## Minimum Access Requirement

The first ERP Admin Login prototype is limited to:

- Platform Owner
- user_id = 10001
- username = mahin.paradigm.owner

Required access:

- is_login_enabled = 1
- is_system_owner = 1
- active role = owner or system_admin

## Future Permission Check Types

The future permission layer must support:

1. Role-based check
2. Permission-based check
3. System-owner check
4. Login-enabled check
5. Session-valid check

## Suggested Future Helper Functions

Planning only. Not created now.

Suggested future functions:

- erp_require_login()
- erp_require_system_owner()
- erp_require_role($roleName)
- erp_require_permission($permissionCode)
- erp_current_user()
- erp_current_user_roles()
- erp_current_user_permissions()

## Page Protection Rule

Every future ERP admin page must define its required permission.

Example future pattern:

- Dashboard page requires: ERP_ADMIN_VIEW
- Access request list requires: ACCESS_REQUEST_VIEW
- Access request create requires: ACCESS_REQUEST_CREATE
- Approval queue requires: ACCESS_APPROVAL_VIEW
- Approval action requires: ACCESS_APPROVAL_DECIDE

No page may be left unprotected.

## Denied Access Behavior

If authentication fails:

- redirect to ERP Admin Login page

If authorization fails:

- show generic access denied page

Browser must not show:

- SQL errors
- role internals
- permission internals
- password_hash
- config secrets
- stack trace

Allowed message:

- Access denied.

## Audit Requirement

Every denied access event must be auditable later.

Future audit event types:

- ERP_ACCESS_DENIED_NOT_LOGGED_IN
- ERP_ACCESS_DENIED_ROLE_MISSING
- ERP_ACCESS_DENIED_PERMISSION_MISSING
- ERP_ACCESS_DENIED_DISABLED_USER
- ERP_ACCESS_DENIED_INVALID_SESSION

Audit must include:

- user_id when available
- username when available
- required role or permission
- request path
- IP address
- user agent
- created_at
- safe failure category

## Caching Rule

Permissions may be loaded into ERP session after login.

But future protected pages must be able to re-check critical access from database when needed.

High-risk operations must not rely only on stale session permissions.

## Write Operation Rule

Before any write-enabled UI is created, the permission layer must exist.

The first write-enabled ERP page must not create users or assign roles directly.

The first write-enabled ERP page should be:

- Access Request Create

It must require:

- ACCESS_REQUEST_CREATE

## Not Approved in This Step

The following are not approved:

- Creating permission helper PHP
- Creating erp-auth.php
- Creating erp-permissions.php
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
- Customer access
- Production deployment

## Future Implementation Order

After this document is approved:

1. Safe config implementation plan
2. Session helper implementation plan
3. Audit helper implementation plan
4. Permission helper implementation plan
5. ERP Admin Login prototype
6. ERP Admin Logout prototype
7. Local test
8. Test result document
9. Commit and Push
10. Review before first write-enabled UI

## Final Decision

This document only approves the ERP permission check layer design.

No executable permission helper is created.

No runtime behavior is changed.

No login implementation is approved.

No write-enabled UI is approved.

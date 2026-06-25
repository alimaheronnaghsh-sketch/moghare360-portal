# Phase 1A ERP Permission Helper Plan

## Project
MOGHARE360 ERP

## Phase
Phase 1A

## Purpose
Define the plan for the future ERP Permission Helper before any write-enabled ERP UI is created.

This document is planning-only.

No executable permission helper is created in this step.

## Current Completed Boundary

The following protected read-only ERP admin area is complete:

- ERP Admin Login Prototype
- ERP Admin Logout Prototype
- ERP Auth Helper
- ERP Protected Test Page
- ERP Admin Dashboard
- Protected Read-Only Admin Area Completion Review

## Future Permission Helper File

The future permission helper file will be:

- includes/erp-permission-helper.php

This file is not created in this step.

## Main Rule

Authentication is not authorization.

ERP Auth Helper confirms that an ERP user is logged in.

ERP Permission Helper must confirm that the logged-in ERP user has permission to perform or view a specific ERP capability.

## Permission Source

The future helper may use ERP session roles first.

Later, it may validate against database-backed permissions after a separate approved task.

No database-backed permission check is approved in this step.

## Future Helper Responsibilities

The future ERP Permission Helper must:

- depend on ERP Auth Helper
- require an active ERP login session
- read current ERP user data safely
- check whether the current user has required role or permission
- support owner/system_admin bypass rules if approved
- return true or false safely
- provide access denied behavior
- never display password_hash
- never display erp_session_token
- never display database password
- never display config secrets
- never display SQL errors
- never expose internal role/permission stack traces
- never create users
- never assign roles
- never modify permissions
- never perform write operations

## Suggested Future Functions

Planning only. Not created now.

- erp_permission_user_roles()
- erp_permission_has_role(string $role): bool
- erp_permission_has_any_role(array $roles): bool
- erp_permission_is_system_owner(): bool
- erp_permission_require_role(string $role): void
- erp_permission_require_any_role(array $roles): void
- erp_permission_access_denied(): void

## Initial Allowed Role Logic

The first local prototype may use these roles from ERP session only:

- owner
- system_admin

Initial permission behavior:

- owner may access Platform Owner-only pages
- system_admin may access system admin read-only pages
- users without required roles must be blocked

## Future Access Denied Behavior

If a logged-in ERP user lacks permission:

The helper must show only a generic safe message:

- ERP access denied.

The helper must not show:

- internal permission rules
- SQL errors
- stack trace
- session token
- password_hash
- config secrets

## Not Approved in This Step

The following are not approved now:

- Creating includes/erp-permission-helper.php
- Modifying includes/erp-auth-helper.php
- Modifying erp-admin-dashboard.php
- Modifying erp-admin-protected-test.php
- Modifying erp-admin-login.php
- Modifying erp-admin-logout.php
- Creating write-enabled UI
- Creating Access Request UI
- Creating users
- Assigning roles
- Modifying permissions
- Creating migrations
- Adding database-backed permission checks
- Production deployment

## Future Implementation Order

After this document is approved:

1. Create ERP Permission Helper Task document
2. Create includes/erp-permission-helper.php
3. Create local permission helper test
4. Test owner role access
5. Test system_admin role access
6. Test denied access behavior
7. Create Permission Helper Test Result document
8. Commit and Push
9. Review before any Access Request UI planning

## Final Decision

This document only approves the ERP Permission Helper design.

No executable permission helper is created.

No runtime behavior is changed.

No write-enabled UI is approved.

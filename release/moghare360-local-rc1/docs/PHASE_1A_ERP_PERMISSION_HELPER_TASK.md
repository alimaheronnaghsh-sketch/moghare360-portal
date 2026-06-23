# Phase 1A ERP Permission Helper Task

## Project
MOGHARE360 ERP

## Phase
Phase 1A

## Purpose
Define the exact controlled future task for creating the ERP Permission Helper.

This document is a task definition only.

No executable permission helper is created in this step.

## Current Approved Plan

The following planning document exists:

- docs/PHASE_1A_ERP_PERMISSION_HELPER_PLAN.md

The protected read-only ERP admin area is complete locally.

## Future Permission Helper File

The future permission helper file will be:

- includes/erp-permission-helper.php

This file is not created in this step.

## Allowed Future Change

Only this file may be created in the future permission helper creation task:

- includes/erp-permission-helper.php

No existing file may be modified during the permission helper creation task.

## Future Helper Dependency

The future permission helper must depend on:

- includes/erp-auth-helper.php

It must not depend on:

- staff-auth.php
- access-control.php
- config.php
- config.example.php
- old portal login session keys

## Future Helper Requirements

The future permission helper must:

- require an active ERP login session
- read safe ERP current user data from ERP Auth Helper
- read roles from current ERP session data
- check whether the current ERP user has a required role
- check whether the current ERP user has any role from a required role list
- identify whether the current ERP user is system owner
- provide safe access denied behavior
- return true or false safely
- never display erp_session_token
- never display password_hash
- never display database password
- never display config secrets
- never display SQL errors
- never display PHP stack trace
- never create users
- never assign roles
- never modify permissions
- never perform write operations
- never connect to the database
- never execute SQL

## ERP Session Role Source

The first implementation must use ERP session roles only.

Allowed session roles:

- owner
- system_admin

No database-backed permission check is allowed in the first implementation.

## Required Future Functions

The future helper must implement:

- erp_permission_user_roles(): array
- erp_permission_has_role(string $role): bool
- erp_permission_has_any_role(array $roles): bool
- erp_permission_is_system_owner(): bool
- erp_permission_require_role(string $role): void
- erp_permission_require_any_role(array $roles): void
- erp_permission_access_denied(): void

## Future Access Denied Behavior

If access is denied, the helper must show only this safe message:

- ERP access denied.

The helper must not show:

- required role internals
- current role internals
- session token
- password_hash
- config secrets
- SQL errors
- stack trace

## Future Test Requirements

After creating the helper later, the project must test:

- helper loads successfully
- owner role is detected
- system_admin role is detected
- has role returns true for existing role
- has role returns false for missing role
- has any role returns true for matching list
- has any role returns false for non-matching list
- system owner detection works
- access denied behavior is safe
- no database connection is used
- no SQL is executed
- no secrets are displayed
- no write operation is performed

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

## Final Decision

This document only approves preparing the ERP Permission Helper creation task.

No executable permission helper is created.

No runtime behavior is changed.

No write-enabled UI is approved.

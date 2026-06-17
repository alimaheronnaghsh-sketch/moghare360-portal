# Phase 1A ERP Admin Login Prototype Task

## Project
MOGHARE360 ERP

## Phase
Phase 1A

## Purpose
Define the exact controlled future task for creating the first ERP Admin Login prototype.

This document is a task definition only.

No executable login file is created in this step.

## Current Status

The following Phase 1A documents and safe config files exist:

- docs/PHASE_1A_ERP_ADMIN_LOGIN_PLAN.md
- docs/PHASE_1A_SAFE_CONFIG_STRATEGY.md
- docs/PHASE_1A_SESSION_AND_AUTH_BOUNDARY_PLAN.md
- docs/PHASE_1A_LOGIN_AUDIT_PLAN.md
- docs/PHASE_1A_PERMISSION_CHECK_LAYER_PLAN.md
- docs/PHASE_1A_SAFE_CONFIG_IMPLEMENTATION_PLAN.md
- docs/PHASE_1A_ERP_CONFIG_LOADER_PLAN.md
- docs/PHASE_1A_ERP_CONFIG_LOADER_TASK.md
- includes/erp-config-loader.php
- tools/test-erp-config-loader.php
- docs/PHASE_1A_ERP_CONFIG_LOADER_TEST_RESULT.md
- private/erp-config.example.php
- private/erp-config.php local-only and ignored by Git

## Future Login File

The future login prototype file will be:

- erp-admin-login.php

This file is not created in this step.

## Main Rule

The ERP Admin Login prototype must be independent from the existing portal login.

It must not replace, modify, or depend on the old portal login.

## Allowed Future Scope

The first login prototype may support only:

- Platform Owner
- user_id = 10001
- username = mahin.paradigm.owner
- is_system_owner = 1
- is_login_enabled = 1
- role = owner or system_admin

No general staff login is approved.

No customer login is approved.

## Authentication Source

The future login must authenticate against:

- dbo.core_users

Required future checks:

1. username exists
2. password verifies with password_verify
3. is_login_enabled = 1
4. is_system_owner = 1
5. active role includes owner or system_admin

## Config Usage

The future login must use:

- includes/erp-config-loader.php
- private/erp-config.php

It must not use:

- config.php
- config.example.php

## Session Rules

The future login must use ERP-specific session keys only:

- erp_user_id
- erp_username
- erp_full_name
- erp_is_system_owner
- erp_roles
- erp_login_time
- erp_last_activity
- erp_session_token

It must call:

- session_regenerate_id(true)

after successful login.

## Audit Rules

The future login prototype must prepare for audit events.

Future audit events:

- ERP_LOGIN_SUCCESS
- ERP_LOGIN_FAILED_USERNAME_NOT_FOUND
- ERP_LOGIN_FAILED_DISABLED_USER
- ERP_LOGIN_FAILED_PASSWORD
- ERP_LOGIN_FAILED_ROLE_REQUIRED
- ERP_LOGIN_FAILED_SYSTEM_OWNER_REQUIRED

If audit writing is not implemented in the first prototype, the limitation must be documented in the test result.

## Browser Safety Rules

The login page must not display:

- password_hash
- database password
- connection string
- config secrets
- SQL errors
- stack trace
- role internals

Allowed browser failure message:

- Invalid login attempt.

## Not Approved in This Step

The following are not approved now:

- Creating erp-admin-login.php
- Creating erp-admin-logout.php
- Creating write-enabled access UI
- Modifying old login
- Modifying staff-auth.php
- Modifying access-control.php
- Modifying config.php
- Modifying config.example.php
- Modifying SQL files
- Creating users
- Assigning roles
- Migrating staff_users
- Customer login
- Production deployment

## Future Implementation Order

After this document is approved:

1. Create ERP Admin Login prototype file
2. Use safe config loader
3. Authenticate Platform Owner only
4. Create independent ERP session keys
5. Test invalid username
6. Test invalid password
7. Test valid Platform Owner login
8. Confirm old portal login is unchanged
9. Create login prototype test result document
10. Commit and Push safe files only

## Final Decision

This document only approves preparing the ERP Admin Login prototype task.

No executable login file is created.

No runtime behavior is changed.

No user or role data is changed.

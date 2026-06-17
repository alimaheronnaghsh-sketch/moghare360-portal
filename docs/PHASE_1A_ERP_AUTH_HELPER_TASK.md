# Phase 1A ERP Auth Helper Task

## Project
MOGHARE360 ERP

## Phase
Phase 1A

## Purpose
Define the exact controlled future task for creating the ERP Auth Helper.

This document is a task definition only.

No executable auth helper is created in this step.

## Current Status

The following planning document exists:

- docs/PHASE_1A_ERP_AUTH_HELPER_PLAN.md

The following prototype files exist:

- erp-admin-login.php
- erp-admin-logout.php

## Future Auth Helper File

The future auth helper file will be:

- includes/erp-auth-helper.php

This file is not created in this step.

## Allowed Future Change

Only this file may be created in the future auth helper task:

- includes/erp-auth-helper.php

No existing file may be modified during the auth helper creation task.

## Future Helper Requirements

The future helper must:

- start or resume PHP session safely
- validate ERP-specific session keys
- confirm ERP user is logged in
- provide current ERP user data
- update last activity timestamp
- support future timeout logic
- clear only ERP-specific session keys when needed
- redirect unauthenticated users to erp-admin-login.php
- never use old portal session keys
- never include staff-auth.php
- never include access-control.php
- never include config.php
- never display secrets
- never display password_hash
- never display SQL errors
- never create users
- never assign roles
- never perform write operations

## ERP Session Keys

The helper must work only with these keys:

- erp_user_id
- erp_username
- erp_full_name
- erp_is_system_owner
- erp_roles
- erp_login_time
- erp_last_activity
- erp_session_token

## Suggested Future Functions

The helper may define:

- erp_auth_start_session()
- erp_auth_is_logged_in()
- erp_auth_require_login()
- erp_auth_current_user()
- erp_auth_logout_keys()
- erp_auth_touch_activity()

## Future Test Requirement

After creating the helper later, the project must test:

- logged-out state
- logged-in state after ERP Admin Login
- current user data
- logout key cleanup
- no old portal session dependency
- no secret display
- no SQL access
- no write operation

## Not Approved in This Step

The following are not approved now:

- Creating includes/erp-auth-helper.php
- Modifying erp-admin-login.php
- Modifying erp-admin-logout.php
- Creating protected dashboard
- Creating write-enabled UI
- Modifying old portal login
- Modifying staff-auth.php
- Modifying access-control.php
- Modifying config.php
- Modifying config.example.php
- Modifying SQL files
- Creating users
- Assigning roles
- Migrating staff_users
- Production deployment

## Final Decision

This document only approves preparing the ERP Auth Helper task.

No executable auth helper is created.

No runtime behavior is changed.

No write-enabled UI is approved.

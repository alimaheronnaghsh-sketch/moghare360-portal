# Phase 1A ERP Auth Helper Plan

## Project
MOGHARE360 ERP

## Phase
Phase 1A

## Purpose
Define the future ERP Auth Helper before protecting ERP admin pages or creating any write-enabled UI.

This document is planning-only.

No executable auth helper is created in this step.

## Current Status

The ERP Admin Login and Logout prototypes exist:

- erp-admin-login.php
- erp-admin-logout.php

The prototypes are local-only and Platform Owner-only.

## Future Auth Helper File

The future auth helper file will be:

- includes/erp-auth-helper.php

This file is not created in this step.

## Main Rule

ERP protected pages must not rely only on login form success.

Every protected ERP page must validate ERP-specific session keys before showing protected content.

## ERP Session Keys

The future helper must validate:

- erp_user_id
- erp_username
- erp_full_name
- erp_is_system_owner
- erp_roles
- erp_login_time
- erp_last_activity
- erp_session_token

## Future Helper Responsibilities

The future ERP auth helper must:

- start or resume PHP session safely
- check ERP session keys
- confirm user is logged in
- confirm session token exists
- confirm last activity exists
- support idle timeout later
- provide current ERP user data
- redirect unauthenticated users to erp-admin-login.php
- never use old portal login session keys
- never include staff-auth.php
- never include access-control.php
- never include config.php

## Suggested Future Functions

Planning only. Not created now.

- erp_auth_start_session()
- erp_auth_is_logged_in()
- erp_auth_require_login()
- erp_auth_current_user()
- erp_auth_logout_keys()
- erp_auth_touch_activity()

## Protected Page Behavior

If ERP user is logged in:

- allow page execution

If ERP user is not logged in:

- redirect to erp-admin-login.php
- or show generic message:
  ERP login required.

## Safety Rules

The future helper must not display:

- password_hash
- database password
- connection string
- config secret
- SQL error
- stack trace

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

## Future Implementation Order

After this document is approved:

1. Create ERP Auth Helper Task document
2. Create includes/erp-auth-helper.php
3. Create local auth helper test
4. Test logged-in session behavior
5. Test logged-out behavior
6. Create auth helper test result document
7. Commit and Push
8. Review before protected ERP admin dashboard

## Final Decision

This document only approves the ERP Auth Helper design.

No executable auth helper is created.

No runtime behavior is changed.

No write-enabled UI is approved.

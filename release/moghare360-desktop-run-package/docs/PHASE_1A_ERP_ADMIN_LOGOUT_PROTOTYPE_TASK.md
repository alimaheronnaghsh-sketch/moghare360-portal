# Phase 1A ERP Admin Logout Prototype Task

## Project
MOGHARE360 ERP

## Phase
Phase 1A

## Purpose
Define the exact controlled future task for creating the ERP Admin Logout prototype.

This document is a task definition only.

No executable logout file is created in this step.

## Current Status

The ERP Admin Login prototype exists:

- erp-admin-login.php

The login prototype was tested successfully for Platform Owner only.

## Future Logout File

The future logout prototype file will be:

- erp-admin-logout.php

This file is not created in this step.

## Main Rule

ERP logout must clear only ERP-specific session keys.

It must not damage or clear unrelated old portal session keys.

## ERP Session Keys To Clear

The future logout must clear:

- erp_user_id
- erp_username
- erp_full_name
- erp_is_system_owner
- erp_roles
- erp_login_time
- erp_last_activity
- erp_session_token

## Future Logout Behavior

The future logout prototype must:

- start PHP session
- unset only ERP-specific session keys
- not destroy the whole session in this prototype
- not touch old portal session keys
- not modify database
- not write audit in this prototype
- show safe message:
  ERP Admin Logout OK

## Not Approved in This Step

The following are not approved now:

- Creating erp-admin-logout.php
- Modifying erp-admin-login.php
- Modifying old portal login
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

1. Create erp-admin-logout.php
2. Test logout after ERP login
3. Confirm ERP session keys are cleared
4. Confirm old portal login is unchanged
5. Create logout test result document
6. Commit and Push

## Final Decision

This document only approves preparing the ERP Admin Logout prototype task.

No executable logout file is created.

No runtime behavior is changed.

No user or role data is changed.

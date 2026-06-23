# Phase 1A ERP Protected Page Task

## Project
MOGHARE360 ERP

## Phase
Phase 1A

## Purpose
Define the exact controlled future task for creating the first protected ERP admin test page.

This document is a task definition only.

No protected page is created in this step.

## Current Approved Plan

The following planning document exists:

- docs/PHASE_1A_ERP_PROTECTED_PAGE_PLAN.md

The following helper exists and has passed local CLI testing:

- includes/erp-auth-helper.php
- tools/test-erp-auth-helper.php
- docs/PHASE_1A_ERP_AUTH_HELPER_TEST_RESULT.md

## Future Protected Page File

The future protected test page will be:

- erp-admin-protected-test.php

This file is not created in this step.

## Allowed Future Change

Only this file may be created in the future protected page creation task:

- erp-admin-protected-test.php

No existing file may be modified during the protected page creation task.

## Future Protected Page Requirements

The future page must:

- include only includes/erp-auth-helper.php
- call erp_auth_require_login()
- call erp_auth_current_user()
- show protected content only after ERP login
- display ERP Protected Page OK
- display safe current ERP user data
- not display erp_session_token
- not display password_hash
- not display database password
- not display config secrets
- not connect to the database
- not execute SQL
- not write audit records
- not create users
- not assign roles
- not modify permissions
- not include staff-auth.php
- not include access-control.php
- not include config.php
- not use old portal session keys
- not create write-enabled UI

## Safe Display Fields

The future protected page may display:

- user_id
- username
- full_name
- is_system_owner
- roles
- login_time
- last_activity

The future protected page must not display:

- erp_session_token
- password_hash
- database password
- connection string
- SQL error
- PHP stack trace
- config secret
- private config path

## Future Logged-Out Test

When the ERP user is logged out:

Expected behavior:

- redirect to erp-admin-login.php

or, if redirect is not possible:

- show ERP login required.

## Future Logged-In Test

When the ERP user is logged in:

Expected behavior:

- show ERP Protected Page OK
- show safe current ERP user data
- hide session token
- hide password_hash
- perform no database operation
- perform no write operation

## Future Logout Protection Test

After ERP logout:

Expected behavior:

- access to erp-admin-protected-test.php must be blocked again

## Not Approved in This Step

The following are not approved now:

- Creating erp-admin-protected-test.php
- Creating erp-admin-dashboard.php
- Creating ERP dashboard UI
- Creating write-enabled UI
- Modifying login logic
- Modifying logout logic
- Modifying auth helper
- Modifying old portal login
- Creating users
- Assigning roles
- Creating migrations
- Production deployment

## Final Decision

This document only approves preparing the ERP Protected Page creation task.

No protected page is created.

No dashboard is created.

No write-enabled UI is approved.

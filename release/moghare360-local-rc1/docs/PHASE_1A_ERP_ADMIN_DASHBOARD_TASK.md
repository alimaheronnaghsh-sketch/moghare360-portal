# Phase 1A ERP Admin Dashboard Task

## Project
MOGHARE360 ERP

## Phase
Phase 1A

## Purpose
Define the exact controlled future task for creating the first protected read-only ERP Admin Dashboard.

This document is a task definition only.

No dashboard page is created in this step.

## Current Approved Plan

The following planning document exists:

- docs/PHASE_1A_ERP_ADMIN_DASHBOARD_PLAN.md

The following protected ERP components exist and have passed local testing:

- erp-admin-login.php
- erp-admin-logout.php
- includes/erp-auth-helper.php
- erp-admin-protected-test.php
- docs/PHASE_1A_ERP_PROTECTED_PAGE_TEST_RESULT.md

## Future Dashboard File

The future dashboard file will be:

- erp-admin-dashboard.php

This file is not created in this step.

## Allowed Future Change

Only this file may be created in the future dashboard creation task:

- erp-admin-dashboard.php

No existing file may be modified during the dashboard creation task.

## Future Dashboard Requirements

The future dashboard must:

- include only includes/erp-auth-helper.php
- call erp_auth_require_login()
- call erp_auth_current_user()
- show dashboard content only after ERP login
- display ERP Admin Dashboard OK
- display safe current ERP user data
- provide safe navigation links
- provide ERP logout link
- not display erp_session_token
- not display password_hash
- not display database password
- not display config secrets
- not display SQL errors
- not display PHP stack trace
- not create forms
- not create write buttons
- not create users
- not assign roles
- not modify permissions
- not include staff-auth.php
- not include access-control.php
- not include config.php
- not use old portal session keys
- not create write-enabled UI

## Safe Display Fields

The future dashboard may display:

- user_id
- username
- full_name
- is_system_owner
- roles
- login_time
- last_activity

## Safe Navigation Links

The future dashboard may link to these safe existing pages if present:

- erp-admin-readonly-dashboard.php
- erp-access-lifecycle-readonly-dashboard.php
- erp-bootstrap-status.php
- erp-admin-protected-test.php
- erp-admin-logout.php

## Forbidden UI Elements

The future dashboard must not include:

- create user form
- edit user form
- role assignment form
- permission assignment form
- access request create form
- approval action button
- delete button
- save button
- submit button
- SQL execution form
- migration trigger
- production deployment action

## Future Logged-Out Test

When the ERP user is logged out:

Expected behavior:

- redirect to erp-admin-login.php

or, if redirect is not possible:

- show ERP login required.

## Future Logged-In Test

When the ERP user is logged in:

Expected behavior:

- show ERP Admin Dashboard OK
- show safe current ERP user data
- show safe navigation links
- hide session token
- hide password_hash
- perform no write operation

## Not Approved in This Step

The following are not approved now:

- Creating erp-admin-dashboard.php
- Modifying erp-admin-login.php
- Modifying erp-admin-logout.php
- Modifying erp-admin-protected-test.php
- Modifying includes/erp-auth-helper.php
- Creating write-enabled UI
- Creating users
- Assigning roles
- Creating migrations
- Production deployment

## Final Decision

This document only approves preparing the ERP Admin Dashboard creation task.

No dashboard page is created.

No write-enabled UI is approved.

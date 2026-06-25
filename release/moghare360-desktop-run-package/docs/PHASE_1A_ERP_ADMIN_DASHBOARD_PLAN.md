# Phase 1A ERP Admin Dashboard Plan

## Project
MOGHARE360 ERP

## Phase
Phase 1A

## Purpose
Define the plan for creating the first protected ERP Admin Dashboard after ERP login, logout, auth helper, and protected page tests have passed.

This document is planning-only.

No dashboard page is created in this step.

## Current Completed Items

The following Phase 1A items are completed:

- ERP Admin Login Prototype
- ERP Admin Logout Prototype
- ERP Auth Helper
- ERP Auth Helper Local CLI Test
- ERP Protected Test Page
- ERP Protected Page Browser Test

## Future Dashboard File

The future dashboard file may be:

- erp-admin-dashboard.php

This file is not created in this step.

## Main Rule

The first ERP Admin Dashboard must be protected by the ERP Auth Helper and must remain read-only.

No write-enabled ERP UI is approved in this step.

## Future Dashboard Requirements

The future ERP Admin Dashboard must:

- include only includes/erp-auth-helper.php
- call erp_auth_require_login()
- call erp_auth_current_user()
- show dashboard content only after ERP login
- display safe current ERP user information
- provide links to existing safe ERP pages
- provide logout link
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

## Allowed Dashboard Content

The future dashboard may display:

- ERP Admin Dashboard OK
- user_id
- username
- full_name
- is_system_owner
- roles
- login_time
- last_activity
- links to read-only diagnostics
- link to ERP Protected Test Page
- link to ERP Logout

## Not Approved Dashboard Content

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

## Suggested Safe Links

The future dashboard may link to existing safe pages if they exist:

- erp-admin-readonly-dashboard.php
- erp-access-lifecycle-readonly-dashboard.php
- erp-bootstrap-status.php
- erp-admin-protected-test.php
- erp-admin-logout.php

## Future Logged-Out Behavior

When the ERP user is logged out:

Expected behavior:

- redirect to erp-admin-login.php

or, if redirect is not possible:

- show ERP login required.

## Future Logged-In Behavior

When the ERP user is logged in:

Expected behavior:

- show ERP Admin Dashboard OK
- show safe current ERP user data
- show safe navigation links
- hide session token
- hide password_hash
- perform no write operation

## Safety Confirmation Required

The future dashboard test must confirm:

- No session token is displayed.
- No password_hash is displayed.
- No config secret is displayed.
- No database password is displayed.
- No SQL error is displayed.
- No PHP stack trace is displayed.
- No write-enabled UI is created.
- No form is created.
- No write button is created.
- No user is created.
- No role is assigned.
- No permission is modified.
- Old portal login is not used.

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

## Future Implementation Order

After this document is approved:

1. Create ERP Admin Dashboard Task document
2. Create erp-admin-dashboard.php
3. Copy dashboard to XAMPP local path
4. Test logged-out behavior
5. Test logged-in behavior
6. Confirm safe links
7. Confirm no secrets are displayed
8. Confirm no write UI exists
9. Create ERP Admin Dashboard Test Result document
10. Commit and Push
11. Review before any Access Request UI planning

## Final Decision

This document only approves the plan for the first protected read-only ERP Admin Dashboard.

No dashboard page is created.

No write-enabled UI is approved.

# Phase 1A ERP Protected Page Plan

## Project
MOGHARE360 ERP

## Phase
Phase 1A

## Purpose
Define the plan for creating the first protected ERP admin page using the ERP Auth Helper.

This document is planning-only.

No protected page is created in this step.

## Current Completed Items

The following Phase 1A items are completed:

- Safe Config Strategy
- Private Config Example
- Local Private Config
- ERP Config Loader
- ERP Config Loader Local Test
- ERP Admin Login Prototype
- ERP Admin Logout Prototype
- ERP Auth Helper Plan
- ERP Auth Helper Task
- ERP Auth Helper Implementation
- ERP Auth Helper Local Test
- ERP Auth Helper Test Result

## Future Protected Page File

The future protected page may be:

- erp-admin-protected-test.php

This file is not created in this step.

## Main Rule

The first protected page must prove that ERP session validation works before any ERP dashboard or write-enabled UI is created.

## Future Protected Page Requirements

The future protected page must:

- include only includes/erp-auth-helper.php
- call erp_auth_require_login()
- show protected content only after ERP login
- display safe current ERP user information
- not display session token
- not display password_hash
- not display config secrets
- not connect to the database
- not execute SQL
- not write audit records
- not create users
- not assign roles
- not modify permissions
- not include old portal auth files
- not use old portal session keys

## Required Logged-Out Behavior

If the ERP user is not logged in:

- the page must redirect to erp-admin-login.php

or show only this generic message if redirect is not possible:

- ERP login required.

## Required Logged-In Behavior

If the ERP user is logged in:

The page may show:

- ERP Protected Page OK
- user_id
- username
- full_name
- is_system_owner
- roles
- login_time
- last_activity

The page must not show:

- erp_session_token
- password_hash
- database password
- connection string
- PHP stack trace
- SQL error
- config path containing secrets

## Future Test Flow

The future test must confirm:

1. Open protected page while logged out
2. Confirm redirect to erp-admin-login.php or generic login required message
3. Login using ERP Admin Login prototype
4. Open protected page while logged in
5. Confirm ERP Protected Page OK
6. Confirm safe user data is displayed
7. Confirm session token is not displayed
8. Confirm old portal login is not used
9. Confirm no database connection is used
10. Confirm no write operation is performed
11. Logout using ERP Admin Logout prototype
12. Open protected page again
13. Confirm access is blocked again

## Safety Rules

The future protected page must not modify:

- erp-admin-login.php
- erp-admin-logout.php
- includes/erp-auth-helper.php
- includes/erp-config-loader.php
- staff-auth.php
- access-control.php
- config.php
- config.example.php
- private/erp-config.php
- SQL files

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

## Future Implementation Order

After this document is approved:

1. Create ERP Protected Page Task document
2. Create erp-admin-protected-test.php
3. Copy the page to XAMPP local path
4. Test logged-out behavior
5. Test logged-in behavior
6. Test logout protection
7. Create Protected Page Test Result document
8. Commit and Push
9. Review before ERP Admin Dashboard Plan

## Final Decision

This document only approves the plan for the first protected ERP page.

No protected page is created.

No dashboard is created.

No write-enabled UI is approved.

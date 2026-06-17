# Phase 1A Session and Auth Boundary Plan

## Project
MOGHARE360 ERP

## Phase
Phase 1A

## Purpose
Define the future ERP Admin session and authentication boundary before any executable ERP login file is created.

This document is planning-only.

No session implementation is approved in this step.

## Current Status

Phase 0 is closed.

The following Phase 1A planning documents exist:

- docs/PHASE_1A_ERP_ADMIN_LOGIN_PLAN.md
- docs/PHASE_1A_SAFE_CONFIG_STRATEGY.md

This document defines the future session and authentication boundary.

## Main Rule

The ERP Admin session must be fully independent from the existing portal session.

The current portal login must not be replaced or modified.

Do not modify:

- staff-auth.php
- access-control.php
- config.php
- config.example.php

## Existing Portal Boundary

The existing portal authentication remains unchanged.

Phase 1A must not reuse unknown old portal session keys.

Phase 1A must not depend on staff_users migration.

Phase 1A must not create staff login.

## Future ERP Admin Login Scope

The first ERP Admin Login prototype is limited to:

- Platform Owner only
- user_id = 10001
- username = mahin.paradigm.owner
- is_system_owner = 1
- is_login_enabled = 1
- role must include owner or system_admin

No general staff login is approved.

No customer login is approved.

## Future ERP Session Keys

Suggested ERP-specific session keys:

- erp_user_id
- erp_username
- erp_full_name
- erp_is_system_owner
- erp_roles
- erp_permissions
- erp_login_time
- erp_last_activity
- erp_session_token

These keys must not conflict with existing portal session keys.

## Session Creation Rules

After successful ERP login, the future implementation must:

- start a dedicated ERP session flow
- verify username and password
- verify login enabled status
- verify Platform Owner or system_admin role
- call session_regenerate_id(true)
- set ERP-specific session keys
- set login timestamp
- set last activity timestamp
- redirect only to ERP admin area

## Session Validation Rules

Every future ERP admin page must check:

- erp_user_id exists
- erp_username exists
- erp_session_token exists
- erp_last_activity is valid
- user still exists in core_users
- is_login_enabled = 1
- required role or permission exists

No ERP admin page may rely only on a browser cookie.

## Logout Rules

Future ERP logout must:

- clear only ERP session keys
- not damage unrelated portal session keys unless explicitly approved
- record logout audit later
- redirect to ERP Admin Login page

## Timeout Rules

Future ERP session timeout must be designed before implementation.

Suggested future local default:

- idle timeout: 30 minutes
- absolute session lifetime: 8 hours

These values are not implemented in this step.

## Auth Failure Rules

Future authentication failure must not reveal:

- whether username exists
- password_hash
- SQL errors
- config secrets
- role internals
- stack traces

Allowed browser message:

- Invalid login attempt.

## Required Future Auth Checks

Future login must check:

1. Username exists
2. Password is valid
3. User is enabled
4. User is system owner or allowed admin
5. User has owner or system_admin role
6. Session is regenerated
7. ERP session keys are set
8. Audit event is prepared

## Not Approved in This Step

The following are not approved:

- Creating erp-admin-login.php
- Creating erp-admin-logout.php
- Creating ERP session helper PHP
- Modifying existing login
- Modifying staff-auth.php
- Modifying access-control.php
- Modifying config.php
- Modifying config.example.php
- Creating users
- Assigning roles
- Creating write UI
- Migrating staff_users
- Customer login
- Production deployment

## Future Implementation Order

After this document is approved:

1. Phase 1A Login Audit Plan
2. Phase 1A Permission Check Layer Plan
3. Safe config implementation plan
4. Session helper implementation plan
5. ERP Admin Login prototype
6. ERP Admin Logout prototype
7. Local test
8. Test result document
9. Commit and Push

## Final Decision

This document only approves the ERP Admin session and authentication boundary.

No executable session file is created.

No runtime behavior is changed.

No login implementation is approved.

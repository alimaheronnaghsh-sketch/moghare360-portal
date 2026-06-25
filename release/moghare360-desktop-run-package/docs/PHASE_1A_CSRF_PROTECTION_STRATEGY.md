# Phase 1A CSRF Protection Strategy

## Project
MOGHARE360 ERP

## Phase
Phase 1A

## Purpose
Define the CSRF protection strategy before any write-enabled ERP UI is created.

This document is planning-only.

No executable CSRF helper is created in this step.

No runtime behavior is changed in this step.

## Current Completed Foundation

The following Phase 1A foundation areas are complete:

- ERP Config Loader
- ERP Admin Login Prototype
- ERP Admin Logout Prototype
- ERP Auth Helper
- ERP Permission Helper
- ERP Protected Read-Only Admin Area
- ERP Audit Helper
- ERP Audit Write Completion Review

## Future CSRF Helper File

The future CSRF helper file may be:

- includes/erp-csrf-helper.php

This file is not created in this step.

## Main Rule

No write-enabled ERP form may be created before CSRF protection is planned, implemented, and tested.

Every future write action must require a valid CSRF token.

## CSRF Scope

The CSRF protection layer must apply to all future ERP write actions, including:

- Access Request Create UI
- User management UI
- Role assignment UI
- Permission assignment UI
- Approval action UI
- Rejection action UI
- Any future form that performs INSERT, UPDATE, DELETE, or workflow state change

## Token Storage

The future CSRF helper should store CSRF tokens only in ERP-specific PHP session data.

The future helper must not use old portal session keys.

Suggested session key:

- erp_csrf_tokens

## Token Principles

Future CSRF tokens must be:

- generated using cryptographically secure randomness
- bound to ERP session context
- validated before write actions
- single-purpose where practical
- time-limited if timeout is implemented
- removed or rotated after successful validation where appropriate

## Future Helper Responsibilities

The future ERP CSRF Helper must:

- depend on ERP Auth Helper
- require an active ERP session for protected write actions
- generate safe CSRF tokens
- store tokens in ERP session only
- render hidden token input safely
- validate submitted tokens safely
- reject missing tokens
- reject invalid tokens
- reject expired tokens if expiry is implemented
- never display token internals
- never display session internals
- never display password_hash
- never display database password
- never display config secrets
- never display SQL errors
- never display PHP stack traces
- never perform database writes
- never create users
- never assign roles
- never modify permissions

## Suggested Future Functions

Planning only. Not created now.

- erp_csrf_start(): void
- erp_csrf_generate(string $purpose): string
- erp_csrf_input(string $purpose): string
- erp_csrf_validate(string $purpose, ?string $token): bool
- erp_csrf_require_valid(string $purpose, ?string $token): void
- erp_csrf_clear(string $purpose): void
- erp_csrf_access_denied(): void

## Future Form Requirement

Every future write-enabled ERP form must include a CSRF hidden input.

Suggested hidden input name:

- erp_csrf_token

Every future write request must validate:

- active ERP login
- required permission
- valid CSRF token
- input validation
- audit availability
- safe database write boundary

## Future CSRF Failure Behavior

If CSRF validation fails, the system must show only a generic safe message:

- ERP security validation failed.

The system must not show:

- expected token
- submitted token
- token storage
- session internals
- debug details
- stack trace
- SQL errors
- config secrets

## Relationship To Audit

Future failed CSRF validations should be auditable after audit integration is approved for the target flow.

Potential audit action:

- ERP_CSRF_VALIDATION_FAILED

No audit integration is approved in this step.

## Not Approved in This Step

The following are not approved now:

- Creating includes/erp-csrf-helper.php
- Modifying includes/erp-auth-helper.php
- Modifying includes/erp-permission-helper.php
- Modifying includes/erp-audit-helper.php
- Modifying erp-admin-dashboard.php
- Creating Access Request UI
- Creating write-enabled UI
- Creating database writes beyond approved audit tests
- Creating users
- Assigning roles
- Modifying permissions
- Creating migrations
- Modifying SQL files
- Production deployment

## Future Implementation Order

After this document is approved:

1. Create CSRF Protection Task document
2. Create includes/erp-csrf-helper.php
3. Create local CSRF helper test
4. Test token generation
5. Test token validation success
6. Test missing token failure
7. Test invalid token failure
8. Confirm no token internals are displayed
9. Create CSRF Helper Test Result document
10. Review before Access Request Create UI planning

## Final Decision

This document only approves planning the ERP CSRF protection layer.

No executable CSRF helper is created.

No write-enabled UI is approved.

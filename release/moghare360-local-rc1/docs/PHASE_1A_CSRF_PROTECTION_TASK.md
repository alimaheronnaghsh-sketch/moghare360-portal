# Phase 1A CSRF Protection Task

## Project
MOGHARE360 ERP

## Phase
Phase 1A

## Purpose
Define the exact controlled future task for creating the ERP CSRF Helper before any write-enabled ERP UI is created.

This document is a task definition only.

No executable CSRF helper is created in this step.

No runtime behavior is changed in this step.

## Current Approved Strategy

The following planning document exists:

- docs/PHASE_1A_CSRF_PROTECTION_STRATEGY.md

The following foundation is complete:

- ERP Auth Helper
- ERP Permission Helper
- ERP Audit Helper
- Protected Read-Only ERP Admin Area
- Audit Write Completion Review

## Future CSRF Helper File

The future CSRF helper file will be:

- includes/erp-csrf-helper.php

This file is not created in this step.

## Allowed Future Change

Only this file may be created in the future CSRF helper creation task:

- includes/erp-csrf-helper.php

No existing file may be modified during the first CSRF helper creation task.

## Future Helper Dependency

The future CSRF helper must depend on:

- includes/erp-auth-helper.php

The future CSRF helper must not depend on:

- staff-auth.php
- access-control.php
- config.php
- config.example.php
- old portal login session keys

## Session Storage Boundary

The future helper must store CSRF tokens only in ERP-specific PHP session data.

Approved future session key:

- erp_csrf_tokens

No old portal session keys may be used.

## Required Future Functions

The future CSRF helper must implement:

- erp_csrf_start(): void
- erp_csrf_generate(string $purpose): string
- erp_csrf_input(string $purpose): string
- erp_csrf_validate(string $purpose, ?string $token): bool
- erp_csrf_require_valid(string $purpose, ?string $token): void
- erp_csrf_clear(string $purpose): void
- erp_csrf_access_denied(): void

## Future Helper Requirements

The future CSRF helper must:

- require ERP session handling through ERP Auth Helper
- generate cryptographically secure tokens
- bind tokens to a named purpose
- store tokens only under erp_csrf_tokens
- render hidden input safely
- validate submitted tokens safely
- reject missing tokens
- reject invalid tokens
- clear purpose-specific tokens when required
- never display expected tokens
- never display submitted tokens
- never display token storage internals
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
- never create write-enabled UI

## Approved Hidden Input Name

Future write-enabled forms should use:

- erp_csrf_token

## Future Access Denied Behavior

If CSRF validation fails, the helper must show only this safe message:

- ERP security validation failed.

The helper must not show:

- expected token
- submitted token
- token purpose details
- session internals
- debug details
- SQL errors
- stack trace
- config secrets

## Future Test Requirements

After creating the helper later, the project must test:

- helper loads successfully
- token generation returns a non-empty string
- token is stored under erp_csrf_tokens
- hidden input is rendered safely
- valid token passes validation
- missing token fails validation
- invalid token fails validation
- clearing token works
- token internals are not displayed
- no database connection is used
- no SQL is executed
- no write operation is performed
- no write-enabled UI is created

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

## Final Decision

This document only approves preparing the ERP CSRF Helper creation task.

No executable CSRF helper is created.

No runtime behavior is changed.

No write-enabled UI is approved.

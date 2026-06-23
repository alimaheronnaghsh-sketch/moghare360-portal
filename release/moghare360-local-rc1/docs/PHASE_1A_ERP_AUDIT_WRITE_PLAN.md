# Phase 1A ERP Audit Write Plan

## Project
MOGHARE360 ERP

## Phase
Phase 1A

## Purpose
Define the plan for future ERP audit write implementation before any write-enabled ERP UI is created.

This document is planning-only.

No executable audit helper is created in this step.

No runtime behavior is changed in this step.

## Current Completed Foundation

The following Phase 1A foundation areas are complete:

- ERP Config Loader
- ERP Admin Login Prototype
- ERP Admin Logout Prototype
- ERP Auth Helper
- ERP Permission Helper
- ERP Protected Test Page
- ERP Admin Dashboard
- Auth and Permission Foundation Completion Review

## Future Audit Helper File

The future audit helper file may be:

- includes/erp-audit-helper.php

This file is not created in this step.

## Main Rule

No write-enabled ERP UI may be created before audit write behavior is planned, implemented, and tested.

Every future write action must create an audit record.

## Audit Scope

The first audit write implementation should support safe internal logging for ERP security and access lifecycle events.

Initial audit event candidates:

- ERP_LOGIN_SUCCESS
- ERP_LOGIN_FAILURE
- ERP_LOGOUT
- ERP_ACCESS_DENIED
- ERP_PERMISSION_DENIED
- ERP_ACCESS_REQUEST_CREATED
- ERP_ACCESS_REQUEST_APPROVED
- ERP_ACCESS_REQUEST_REJECTED

Only planning is approved now.

## Audit Data Principles

Audit records should capture only safe operational metadata.

Allowed audit fields may include:

- actor_user_id
- actor_username
- event_type
- event_result
- target_entity_type
- target_entity_id
- request_number
- ip_address
- user_agent
- created_at
- safe_message

Audit records must not store:

- password
- password_hash
- session token
- database password
- config secret
- full connection string
- raw SQL error
- PHP stack trace
- private config path
- sensitive internal debug dump

## Future Helper Responsibilities

The future ERP Audit Helper must:

- depend on safe config loading only when database write is required
- use ERP Auth Helper when actor context is available
- write audit records safely
- never display database errors to browser
- never display secrets
- never store password_hash
- never store erp_session_token
- never store real passwords
- support success and failure audit records
- support unauthenticated failure audit records where appropriate
- return safe true or false status
- fail closed for sensitive write actions if audit write is required and fails

## Suggested Future Functions

Planning only. Not created now.

- erp_audit_safe_actor(): array
- erp_audit_client_context(): array
- erp_audit_write(array $event): bool
- erp_audit_login_success(int $userId, string $username): bool
- erp_audit_login_failure(string $username): bool
- erp_audit_logout(): bool
- erp_audit_access_denied(string $eventType): bool

## Database Boundary

The future audit helper may write to an existing audit table only after a separate approved task confirms:

- target audit table name
- allowed columns
- required columns
- safe INSERT statement
- transaction behavior if needed
- error handling
- no schema migration unless separately approved

No SQL change is approved in this step.

No database write is approved in this step.

## Error Handling

The future audit helper must:

- never expose SQL errors to browser
- never expose stack traces to browser
- return safe failure status
- log internally only if safe local logging is approved
- keep user-facing messages generic

## Relationship To Write UI

Before any future write-enabled UI is created:

- permission check must pass
- CSRF protection must pass
- audit write must be available
- write action must be auditable
- failure must be safely handled

## Not Approved in This Step

The following are not approved now:

- Creating includes/erp-audit-helper.php
- Modifying erp-admin-login.php
- Modifying erp-admin-logout.php
- Modifying includes/erp-auth-helper.php
- Modifying includes/erp-permission-helper.php
- Modifying erp-admin-dashboard.php
- Creating database-backed audit writes
- Creating SQL migrations
- Modifying SQL files
- Creating Access Request UI
- Creating write-enabled UI
- Creating users
- Assigning roles
- Modifying permissions
- Production deployment

## Future Implementation Order

After this document is approved:

1. Create ERP Audit Write Task document
2. Confirm target audit table and columns
3. Create ERP Audit Helper implementation
4. Create local audit helper test
5. Test safe audit insert
6. Test safe failure handling
7. Create Audit Write Test Result document
8. Review before CSRF strategy
9. Review before Access Request Create UI planning

## Final Decision

This document only approves planning the ERP audit write layer.

No executable audit helper is created.

No database write is performed.

No write-enabled UI is approved.

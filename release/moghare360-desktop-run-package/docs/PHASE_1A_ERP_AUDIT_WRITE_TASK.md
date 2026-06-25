# Phase 1A ERP Audit Write Task

## Project
MOGHARE360 ERP

## Phase
Phase 1A

## Purpose
Define the exact controlled future task for creating the ERP Audit Helper.

This document is a task definition only.

No executable audit helper is created in this step.

No database write is performed in this step.

## Current Approved Plan

The following planning document exists:

- docs/PHASE_1A_ERP_AUDIT_WRITE_PLAN.md

The following foundation is complete:

- ERP Config Loader
- ERP Auth Helper
- ERP Permission Helper
- Protected Read-Only ERP Admin Area
- Auth and Permission Foundation Completion Review

## Future Audit Helper File

The future audit helper file will be:

- includes/erp-audit-helper.php

This file is not created in this step.

## Allowed Future Change

Only this file may be created in the future audit helper creation task:

- includes/erp-audit-helper.php

No existing file may be modified during the first audit helper creation task.

## Future Helper Dependencies

The future audit helper may depend on:

- includes/erp-config-loader.php
- includes/erp-auth-helper.php

The future audit helper must not depend on:

- staff-auth.php
- access-control.php
- config.php
- config.example.php
- old portal login session keys

## Required Database Boundary Before Implementation

Before creating the audit helper implementation, the project must confirm the target audit table and columns.

The first target candidate is:

- dbo.core_audit_log

If this table name or column structure is different, implementation must stop and a table discovery step must be performed.

## Expected Safe Audit Columns

The future audit helper may write only safe operational metadata.

Expected candidate columns may include:

- actor_user_id
- actor_username
- event_type
- event_result
- target_entity_type
- target_entity_id
- request_number
- ip_address
- user_agent
- safe_message
- created_at

The implementation must match actual database columns only.

No schema migration is approved by this task.

## Future Helper Requirements

The future ERP Audit Helper must:

- load ERP private config safely through ERP Config Loader
- connect to SQL Server using the existing safe config approach
- use ODBC only if that is the configured driver
- write safe audit records
- support authenticated actor context
- support unauthenticated event context
- return true or false safely
- never display SQL errors to browser
- never display PHP stack traces
- never display config secrets
- never store passwords
- never store password_hash
- never store erp_session_token
- never store database password
- never store full connection string
- never create users
- never assign roles
- never modify permissions
- never create migrations
- never create write-enabled UI

## Required Future Functions

The future audit helper should implement:

- erp_audit_safe_actor(): array
- erp_audit_client_context(): array
- erp_audit_write(array $event): bool
- erp_audit_login_success(int $userId, string $username): bool
- erp_audit_login_failure(string $username): bool
- erp_audit_logout(): bool
- erp_audit_access_denied(string $eventType): bool

## Initial Allowed Event Types

The first implementation may support:

- ERP_LOGIN_SUCCESS
- ERP_LOGIN_FAILURE
- ERP_LOGOUT
- ERP_ACCESS_DENIED
- ERP_PERMISSION_DENIED
- ERP_AUDIT_TEST

## Error Handling Rules

The future helper must:

- catch database connection failures
- catch INSERT failures
- return false on failure
- never echo raw errors
- never expose SQLSTATE details to browser
- never expose stack trace
- allow CLI tests to report only safe OK or FAIL results

## Future Test Requirements

After creating the helper later, the project must test:

- helper loads successfully
- safe actor context works while logged out
- safe actor context works while logged in
- client context returns safe metadata
- audit test event can be inserted safely
- audit helper returns true on successful insert
- audit helper returns false safely on invalid input
- no secrets are displayed
- no password_hash is stored
- no erp_session_token is stored
- no write-enabled UI is created

## Not Approved in This Step

The following are not approved now:

- Creating includes/erp-audit-helper.php
- Modifying erp-admin-login.php
- Modifying erp-admin-logout.php
- Modifying includes/erp-auth-helper.php
- Modifying includes/erp-permission-helper.php
- Modifying erp-admin-dashboard.php
- Creating SQL migrations
- Modifying SQL files
- Creating Access Request UI
- Creating write-enabled UI
- Creating users
- Assigning roles
- Modifying permissions
- Production deployment

## Final Decision

This document only approves preparing the ERP Audit Helper creation task.

No executable audit helper is created.

No database write is performed.

No write-enabled UI is approved.

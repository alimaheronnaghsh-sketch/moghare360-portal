# Phase 1A Safe Config Implementation Plan

## Project
MOGHARE360 ERP

## Phase
Phase 1A

## Purpose
Define the future safe config implementation steps before creating any real executable config or ERP Admin Login file.

This document is planning-only.

No config file is created in this step.

## Current Status

Phase 0 is closed.

The following Phase 1A planning documents exist:

- docs/PHASE_1A_ERP_ADMIN_LOGIN_PLAN.md
- docs/PHASE_1A_SAFE_CONFIG_STRATEGY.md
- docs/PHASE_1A_SESSION_AND_AUTH_BOUNDARY_PLAN.md
- docs/PHASE_1A_LOGIN_AUDIT_PLAN.md
- docs/PHASE_1A_PERMISSION_CHECK_LAYER_PLAN.md

This document defines the future safe config implementation plan.

## Main Rule

Real secrets must never be committed to GitHub.

No secret-bearing file may be placed inside public_html or any public web root.

## Future Private Config Location

Suggested future private path:

- private/erp-config.php

Suggested future example path:

- private/erp-config.example.php

These files are not created in this step.

## Future Git Ignore Requirement

Before creating real local config, .gitignore must later include:

- private/erp-config.php
- *.local.php
- .env

.gitignore is not modified in this step.

## Future Config Values

The future ERP config must define:

- ERP database server
- ERP database name
- ERP connection method
- ERP trusted connection flag
- ERP database username when needed
- ERP database password when needed
- ERP environment name
- ERP debug mode flag
- ERP error display policy

## Local Development Config Strategy

For local development, preferred strategy:

- SQL Server SQLEXPRESS
- Database moghare360_ERP
- ODBC connection
- Trusted_Connection where possible
- No database password in code
- No secret displayed in browser

## Production Config Strategy

For production, preferred strategy:

- private config outside public_html
- environment variables where possible
- no real secret in GitHub
- no secret in browser output
- no direct public access to config files

## Future Config Loader

Suggested future helper file:

- includes/erp-config-loader.php

This file is not created in this step.

The future loader must:

- load config from private location
- fail safely if config is missing
- never expose secrets
- return generic browser errors
- log detailed error internally later
- support local and production environments

## Future Safety Checks

Before implementing ERP Admin Login, confirm:

- private/erp-config.php exists locally only
- private/erp-config.php is ignored by Git
- private/erp-config.example.php contains placeholders only
- no secret exists in public_html
- no password_hash is displayed
- no connection string is displayed
- missing config does not expose sensitive data
- local ODBC connection still works
- existing portal config remains untouched

## Not Approved in This Step

The following are not approved:

- Creating private/erp-config.php
- Creating private/erp-config.example.php
- Creating includes/erp-config-loader.php
- Modifying .gitignore
- Modifying config.php
- Modifying config.example.php
- Modifying staff-auth.php
- Modifying access-control.php
- Creating erp-admin-login.php
- Creating login implementation
- Creating users
- Assigning roles
- Creating write UI
- Migrating staff_users
- Production deployment

## Future Implementation Order

After this document is approved:

1. Create safe config prototype plan
2. Update .gitignore plan
3. Create private config example
4. Create local ignored config
5. Create config loader
6. Test config loading locally
7. Create config test result document
8. Commit only safe files
9. Review before ERP Admin Login prototype

## Final Decision

This document only approves the safe config implementation plan.

No executable config file is created.

No secret is added.

No runtime behavior is changed.

No login implementation is approved.

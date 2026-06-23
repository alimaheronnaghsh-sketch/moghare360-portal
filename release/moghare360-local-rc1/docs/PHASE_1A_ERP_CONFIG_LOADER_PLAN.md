# Phase 1A ERP Config Loader Plan

## Project
MOGHARE360 ERP

## Phase
Phase 1A

## Purpose
Define the future ERP config loader before creating any executable loader, ERP Admin Login file, or write-enabled UI.

This document is planning-only.

No config loader is created in this step.

## Current Status

The following Phase 1A files and documents exist:

- docs/PHASE_1A_SAFE_CONFIG_STRATEGY.md
- docs/PHASE_1A_SAFE_CONFIG_IMPLEMENTATION_PLAN.md
- docs/PHASE_1A_PRIVATE_CONFIG_EXAMPLE_PLAN.md
- private/erp-config.example.php
- private/erp-config.php local-only and ignored
- docs/PHASE_1A_LOCAL_PRIVATE_CONFIG_TEST_RESULT.md

## Main Rule

The future ERP config loader must load ERP configuration safely from a private location outside public_html.

It must never expose secrets to browser output.

## Future Loader File

Suggested future file:

- includes/erp-config-loader.php

This file is not created in this step.

## Future Loader Responsibilities

The future config loader must:

- locate private/erp-config.php
- confirm the config file exists
- load and validate config array
- validate required keys
- support local environment
- support production environment later
- provide safe generic error messages
- never display secrets
- never display password_hash
- never display full connection strings
- never modify existing portal config files

## Required Config Keys

The loader must validate these keys:

- environment
- debug
- database.server
- database.name
- database.driver
- database.trusted_connection
- database.username
- database.password
- security.display_errors_to_browser
- security.log_errors_internally

## Safe Failure Behavior

If config is missing or invalid, browser output must be generic:

Configuration error. Please contact system administrator.

Not allowed in browser:

- local file paths containing secrets
- database password
- database username when sensitive
- full connection string
- stack trace
- SQL Server internal error
- PHP warning with secret path

## Local Config Rule

For current local development:

- driver = odbc
- server = localhost\\SQLEXPRESS
- database = moghare360_ERP
- trusted_connection = true
- username = empty
- password = empty

## Not Approved in This Step

The following are not approved:

- Creating includes/erp-config-loader.php
- Modifying private/erp-config.php
- Modifying private/erp-config.example.php
- Creating erp-admin-login.php
- Creating login implementation
- Modifying staff-auth.php
- Modifying access-control.php
- Modifying config.php
- Modifying config.example.php
- Modifying SQL files
- Creating users
- Assigning roles
- Creating write UI
- Migrating staff_users
- Production deployment

## Future Implementation Order

After this document is approved:

1. Create ERP config loader task document
2. Create includes/erp-config-loader.php
3. Test loader with local private config
4. Create config loader test page or CLI test
5. Create config loader test result document
6. Commit only safe files
7. Review before ERP Admin Login prototype

## Final Decision

This document only approves the ERP config loader design.

No executable config loader is created.

No secret is added.

No runtime behavior is changed.

No login implementation is approved.

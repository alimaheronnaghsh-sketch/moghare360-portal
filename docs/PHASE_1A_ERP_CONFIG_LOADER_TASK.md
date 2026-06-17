# Phase 1A ERP Config Loader Task

## Project
MOGHARE360 ERP

## Phase
Phase 1A

## Purpose
Define the exact controlled future task for creating the ERP config loader.

This document is a task definition only.

No executable config loader is created in this step.

## Current Status

The following planning document exists:

- docs/PHASE_1A_ERP_CONFIG_LOADER_PLAN.md

The following config files exist:

- private/erp-config.example.php
- private/erp-config.php local-only and ignored by Git

## Future Loader File

The future loader file will be:

- includes/erp-config-loader.php

This file is not created in this step.

## Allowed Future Change

Only this file may be created in the future loader task:

- includes/erp-config-loader.php

No existing file may be modified during the loader creation task.

## Future Loader Requirements

The future loader must:

- load private/erp-config.php from the private folder
- validate that config returns an array
- validate required keys
- return safe generic errors
- never display secrets
- never display password_hash
- never display full connection strings
- never modify existing portal config files
- never modify login logic

## Required Config Keys

The future loader must validate:

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

## Future Test Requirement

After creating the loader later, the project must test:

- config file exists
- config array loads
- required keys exist
- local database settings are available internally
- no secret is displayed in browser
- no config.php was modified
- no portal login was modified

## Not Approved in This Step

The following are not approved now:

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

## Final Decision

This document only approves preparing the ERP config loader task.

No executable loader is created.

No secret is added.

No runtime behavior is changed.

No login implementation is approved.

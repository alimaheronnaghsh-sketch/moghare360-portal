# Phase 1A Safe Config Strategy

## Project
MOGHARE360 ERP

## Phase
Phase 1A

## Purpose
Define a safe configuration strategy for the future ERP Admin Login before any executable login file is created.

This document is planning-only.

No config implementation is approved in this step.

## Current Status

Phase 0 is closed.

The following document exists:

- docs/PHASE_1A_ERP_ADMIN_LOGIN_PLAN.md

The next required planning document is:

- docs/PHASE_1A_SAFE_CONFIG_STRATEGY.md

## Main Rule

The future ERP Admin Login must not expose database credentials, password hashes, or production secrets.

The existing portal config files must not be changed during this planning phase.

Do not modify:

- config.php
- config.example.php
- staff-auth.php
- access-control.php

## Current Local Connection Pattern

Current V0 diagnostic pages use:

- PHP ODBC
- Trusted_Connection
- SELECT only
- No config.php
- No visible secrets

This is acceptable for local diagnostic pages only.

## Future ERP Config Boundary

Future ERP Admin Login must use a separate ERP configuration boundary.

Suggested future file:

- private/erp-config.php

This file is not created in this step.

Suggested future example file:

- private/erp-config.example.php

This file is not created in this step.

## Config Storage Rules

Future real secrets must never be committed to GitHub.

Allowed in GitHub later:

- example config files
- placeholder values
- documented environment variable names

Not allowed in GitHub:

- real database password
- real password_hash
- production connection string
- API secret
- encryption key
- SMTP password
- SMS gateway key

## Local Development Strategy

For local development, the future ERP config may use:

- Windows Trusted_Connection
- local ODBC DSN
- environment variables
- machine-local ignored config file

Preferred local strategy:

- Trusted_Connection where possible
- no database password in code
- no secret displayed in browser

## Production Strategy

For production, the future ERP config must use one of these safe options:

1. Environment variables
2. Server-side private config outside public_html
3. Secret manager
4. API service boundary

Production secrets must not exist inside public_html.

## Public Directory Rule

No secret-bearing config file may be placed inside:

- public_html
- web root
- htdocs public execution path

Public PHP files may include or load config only from a private non-public location.

## Error Handling Rule

Future config loading must not display raw errors to users.

Allowed browser message:

- Configuration error. Please contact system administrator.

Not allowed browser output:

- connection string
- database username
- database password
- file system secret path
- SQL Server internal error
- stack trace with secret values

## Required Future Checks

Before implementing login, the project must confirm:

- config file is outside public path
- real secrets are gitignored
- example config contains placeholders only
- no password_hash displayed
- no connection string displayed
- failed config load does not expose sensitive data
- local connection works
- production strategy is documented

## Suggested Future Files

Planning only. Not created now.

Future private files:

- private/erp-config.php
- private/erp-config.example.php

Future Git ignore update:

- private/erp-config.php
- *.local.php
- .env

Future helper file:

- includes/erp-config-loader.php

These files are not created in this step.

## Not Approved in This Step

The following are not approved:

- Creating private/erp-config.php
- Creating private/erp-config.example.php
- Creating .env
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

## Future Implementation Order

After this document is approved:

1. Phase 1A Session and Auth Boundary Plan
2. Phase 1A Login Audit Plan
3. Phase 1A Permission Check Layer Plan
4. Safe config implementation plan
5. Local-only config prototype
6. ERP Admin Login prototype
7. Local test
8. Test result document
9. Commit and Push

## Final Decision

This document only approves the safe config strategy.

No executable config file is created.

No runtime behavior is changed.

No secret is added.

No login implementation is approved.

# Phase 1A Private Config Example Plan

## Project
MOGHARE360 ERP

## Phase
Phase 1A

## Purpose
Define the future private ERP config example file before creating any real config file or ERP Admin Login implementation.

This document is planning-only.

No config file is created in this step.

## Current Status

The following Phase 1A documents exist:

- docs/PHASE_1A_SAFE_CONFIG_STRATEGY.md
- docs/PHASE_1A_SAFE_CONFIG_IMPLEMENTATION_PLAN.md
- docs/PHASE_1A_GIT_IGNORE_UPDATE_PLAN.md
- docs/PHASE_1A_GIT_IGNORE_UPDATE_TASK.md

.gitignore has been updated to ignore:

- private/erp-config.php
- *.local.php
- .env
- .env.*

## Main Rule

Only placeholder example config files may be committed.

Real secrets must never be committed.

## Future Example File

Suggested future example file:

- private/erp-config.example.php

This file is not created in this step.

## Future Real Local Config File

Suggested future local-only file:

- private/erp-config.php

This file is not created in this step.

This file must remain ignored by Git.

## Example Config Content Rules

The future example config may contain:

- placeholder server name
- placeholder database name
- placeholder connection method
- placeholder trusted connection flag
- placeholder username
- placeholder password
- placeholder environment name
- placeholder debug mode
- placeholder error display policy

The future example config must not contain:

- real database password
- real connection string with password
- real production secret
- real API key
- real encryption key
- real password_hash

## Suggested Future Example Structure

Planning only. Not created now.

```php
<?php

return [
    'environment' => 'local',
    'debug' => false,

    'database' => [
        'server' => 'localhost\\SQLEXPRESS',
        'name' => 'moghare360_ERP',
        'driver' => 'odbc',
        'trusted_connection' => true,
        'username' => '',
        'password' => '',
    ],

    'security' => [
        'display_errors_to_browser' => false,
        'log_errors_internally' => true,
    ],
];
```

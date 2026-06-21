# Mission 10 Implementation Plan

## Why Permission Guard Helper Is Needed
Mission 9 locked the Permission Enforcement design and Guard Map.

Mission 10 implements the helper layer that evaluates whether an authenticated user may perform a mapped action before any future page or workflow handler executes that action.

## How It Uses Auth Context
The Permission Guard helper depends on Mission 8 Auth Context:

- `erp_auth_can()` checks active permissions for the current user
- `erp_auth_create_local_odbc_connection()` provides local ODBC-compatible database access
- `erp_auth_current_roles()` and related helpers support test validation

The guard does not replace Auth Context.
It consumes Auth Context permission evaluation only.

## Why It Does Not Execute Real Actions
Mission 10 is helper implementation and validation only.

The helper returns allowed or denied metadata.
It does not:

- submit access requests
- approve access requests
- apply access requests
- open admin write handlers
- mutate database rows

## Why No Workflow State Changes Happen in Mission 10
Workflow transitions require future guarded write handlers with CSRF and audit.

Mission 10 only records whether an action would require workflow state validation through map metadata.
It does not read or change request state for enforcement execution.

## Why No Database Write Happens
Mission 10 is read-only by design.

All database access is SELECT-only through existing Auth Context helpers.
No INSERT, UPDATE, DELETE, or MERGE is performed.

## Implementation Files
- `includes/erp-permission-guard.php` - guard helper
- `tools/test-erp-permission-guard.php` - CLI validation
- `public_html/erp-permission-guard-readonly-test.php` - browser read-only validation

## Local Compatibility
Mission 10 follows Mission 8 ODBC-compatible connection pattern.

No PDO-only runtime dependency is introduced.

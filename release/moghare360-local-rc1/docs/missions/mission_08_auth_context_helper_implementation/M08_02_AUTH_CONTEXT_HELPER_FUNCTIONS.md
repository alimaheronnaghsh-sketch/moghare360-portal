# Mission 8 Auth Context Helper Functions

Project: MOGHARE360 ERP
Mission: Mission 8
Document Type: Helper Function Reference
Scope: Auth Context Helper Implementation

## Helper File
includes/erp-auth-context.php

## Functions

### erp_auth_context_start(): void
Input: none
Output: none
Behavior: starts PHP session safely only if no session exists.

### erp_auth_current_user_id(): ?int
Input: none
Output: current user id or controlled local fallback user_id = 10001
Behavior: reads approved ERP session key `erp_user_id`; if absent, uses controlled local test fallback only.

### erp_auth_load_current_user(PDO $pdo): ?array
Input: PDO connection
Output: user array or null
Fields returned:
- user_id
- username
- full_name
- is_system_owner
- is_login_enabled
- lifecycle_state
Behavior: SELECT from dbo.core_users by current user id. Does not return password_hash.

### erp_auth_current_roles(PDO $pdo, int $userId): array
Input: PDO connection, user id
Output: list of active role_key values
Behavior: SELECT from dbo.core_user_roles joined with dbo.core_roles using active role filters.

### erp_auth_current_permissions(PDO $pdo, int $userId): array
Input: PDO connection, user id
Output: list of active permission_key values
Behavior: SELECT from dbo.core_user_roles, dbo.core_role_permissions, dbo.core_permissions.

### erp_auth_is_system_owner(PDO $pdo, int $userId): bool
Input: PDO connection, user id
Output: true if is_system_owner = 1 or owner role is active
Behavior: SELECT only.

### erp_auth_can(PDO $pdo, int $userId, string $permissionKey): bool
Input: PDO connection, user id, permission key
Output: true if permission exists in current permissions
Behavior: SELECT only through permission loading.

### erp_auth_require_login(): void
Input: none
Output: none
Behavior: starts context and throws if no user id exists.

### erp_auth_tenant_context(): array
Input: none
Output:
- tenant_operational = false
- current_runtime = moghare360
- future_branding = moghareh360
Behavior: placeholder only. No tenant query.

### erp_auth_logout_keys(): array
Input: none
Output: list of ERP session keys future logout must clear
Behavior: documentation/helper only in Mission 8.

## Read-Only Guarantees
- No INSERT
- No UPDATE
- No DELETE
- No MERGE
- No password_hash display
- No config secret display
- No HTML output in helper file

## Future Production Notes
- Remove controlled local fallback before production deploy
- Replace fallback with real session population from approved login flow
- Add lifecycle_state enforcement in future login integration
- Add session timeout enforcement in future security mission
- Add tenant context loading only after tenant isolation is approved

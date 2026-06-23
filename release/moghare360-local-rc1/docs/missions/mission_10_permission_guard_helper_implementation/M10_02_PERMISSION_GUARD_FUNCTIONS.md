# Permission Guard Functions

## erp_guard_action_map()

### Input
None.

### Output
Array keyed by action key. Each entry contains:

- action_key
- mode
- required_permission
- csrf_required
- audit_required
- workflow_state_required
- notes

### Read-Only Guarantee
Returns static map metadata only.
No database access.
No write.

## erp_guard_can($db, int $userId, string $permissionKey): bool

### Input
- `$db` - ODBC connection resource from Auth Context
- `$userId` - authenticated user id
- `$permissionKey` - permission key to evaluate

### Output
Boolean permission result.

### Behavior
Delegates to `erp_auth_can()` for real permissions.
Returns false for placeholder permissions.

### Read-Only Guarantee
SELECT-only through Auth Context.
No write.

## erp_guard_action($db, int $userId, string $actionKey, array $context = []): array

### Input
- `$db` - ODBC connection resource
- `$userId` - authenticated user id
- `$actionKey` - mapped action key
- `$context` - optional future context placeholder

### Output
Guard result shape:

```php
[
  'allowed' => bool,
  'action_key' => string,
  'required_permission' => string,
  'reason' => string,
  'mode' => string,
  'csrf_required' => bool,
  'audit_required' => bool,
  'workflow_state_required' => bool,
  'read_only_evaluation' => true
]
```

Placeholder actions may also include:

- `placeholder` => true

### Read-Only Guarantee
Evaluates permission only.
Does not execute the action.
Does not change workflow state.

## erp_guard_require($db, int $userId, string $actionKey, array $context = []): void

### Input
Same as `erp_guard_action()`.

### Output
None on success.

### Behavior
Throws `RuntimeException` when guard denies the action.
Throws when action requires placeholder permission not yet enforced in production.

### Read-Only Guarantee
Validation only.
No action execution.

## erp_guard_denied_response(string $actionKey, string $reason): array

### Input
- `$actionKey` - denied action key
- `$reason` - internal reason text

### Output
Safe denied response metadata:

- allowed = false
- action_key
- message = safe user-facing access denied message
- reason
- read_only_evaluation = true

### Read-Only Guarantee
Returns response metadata only.
No write.
No audit insert in Mission 10.

## Future Production Notes
- Future pages should call `erp_guard_require()` before write handlers
- Future denied audit hook can consume `erp_guard_denied_response()`
- Placeholder permissions must be replaced before production enforcement
- Workflow state validation should be added in a future mission before write execution

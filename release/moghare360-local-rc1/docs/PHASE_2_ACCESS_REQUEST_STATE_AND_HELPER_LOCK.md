# MOGHARE360 ERP - Phase 2 Access Request State and Helper Lock

## 1. Purpose
Lock exact workflow state values and helper function names before implementing the first controlled Access Request Workflow Transition.

## 2. Files Inspected
- `includes/erp-auth-context.php`
- `includes/erp-csrf.php`
- `includes/erp-permission-check.php`
- `includes/erp-workflow-engine.php`
- `includes/erp-auth-helper.php`
- `includes/erp-permission-helper.php`
- `includes/erp-csrf-helper.php`
- `includes/erp-audit-helper.php`
- `erp-access-request-create.php`
- `erp-access-request-list.php`
- `erp-access-request-detail.php`

## 3. Existing Helper Functions

### `includes/erp-auth-context.php`
- `erp_auth_start_session_if_needed()`
- `erp_auth_get_current_context()`
- `erp_auth_require_current_user()`
- `erp_auth_is_system_owner(array $context)`
- `erp_auth_get_user_roles(array $context)`
- `erp_auth_get_user_permissions(array $context)`

### `includes/erp-csrf.php`
- `erp_csrf_start_session_if_needed()`
- `erp_csrf_create_token(string $form_key)`
- `erp_csrf_validate_token(string $form_key, string $token)`
- `erp_csrf_require_valid_token(string $form_key, string $token)`

### `includes/erp-permission-check.php`
- `erp_permission_normalize_key(string $permission_key)`
- `erp_permission_context_has_role(array $context, string $role_key)`
- `erp_permission_context_has_permission(array $context, string $permission_key)`
- `erp_permission_is_platform_owner_prototype(array $context)`
- `erp_permission_user_has(array $context, string $permission_key)`
- `erp_permission_require(array $context, string $permission_key)`

### `includes/erp-workflow-engine.php`
- `erp_workflow_normalize_value(string $value, string $label)`
- `erp_workflow_get_allowed_transitions()`
- `erp_workflow_can_transition(string $entity, string $from_state, string $to_state)`
- `erp_workflow_require_transition(string $entity, string $from_state, string $to_state)`
- `erp_workflow_build_transition_result(string $entity, string $from_state, string $to_state)`

### `includes/erp-auth-helper.php`
- `erp_auth_start_session()`
- `erp_auth_session_keys()`
- `erp_auth_is_logged_in()`
- `erp_auth_require_login()`
- `erp_auth_current_user()`
- `erp_auth_logout_keys()`
- `erp_auth_touch_activity()`

### `includes/erp-permission-helper.php`
- `erp_permission_user_roles()`
- `erp_permission_has_role(string $role)`
- `erp_permission_has_any_role(array $roles)`
- `erp_permission_is_system_owner()`
- `erp_permission_require_role(string $role)`
- `erp_permission_require_any_role(array $roles)`
- `erp_permission_access_denied()`

### `includes/erp-csrf-helper.php`
- `erp_csrf_start()`
- `erp_csrf_normalize_purpose(string $purpose)`
- `erp_csrf_generate(string $purpose)`
- `erp_csrf_input(string $purpose)`
- `erp_csrf_validate(string $purpose, ?string $token)`
- `erp_csrf_require_valid(string $purpose, ?string $token)`
- `erp_csrf_clear(string $purpose)`
- `erp_csrf_access_denied()`

### `includes/erp-audit-helper.php`
- `erp_audit_safe_actor()`
- `erp_audit_client_context()`
- `erp_audit_sanitize_string(?string $value, int $maxLength)`
- `erp_audit_safe_json(array $details)`
- `erp_audit_connection()`
- `erp_audit_write(array $event)`
- `erp_audit_login_success(int $userId, string $username)`
- `erp_audit_login_failure(string $username)`
- `erp_audit_logout()`
- `erp_audit_access_denied(string $eventType)`

## 4. Current Access Request Page Stack

All three Access Request pages also load `includes/erp-config-loader.php` and call `erp_load_config()` indirectly through page-local database connection helpers.

### `erp-access-request-create.php`
**Helper stack:** Phase 1A
- Config: `erp-config-loader.php`
- Auth: `erp-auth-helper.php`
- Permission: `erp-permission-helper.php`
- CSRF: `erp-csrf-helper.php`
- Audit: `erp-audit-helper.php`

**Runtime guards used:**
- `erp_auth_require_login()`
- `erp_permission_require_any_role(['owner', 'system_admin'])`
- `erp_csrf_require_valid('access_request_create', ...)` on POST only
- `erp_csrf_input('access_request_create')`
- `erp_audit_write([...])` on approved create write path

**Not used:** Phase 2 workflow engine

### `erp-access-request-list.php`
**Helper stack:** Phase 1A
- Config: `erp-config-loader.php`
- Auth: `erp-auth-helper.php`
- Permission: `erp-permission-helper.php`

**Runtime guards used:**
- `erp_auth_require_login()`
- `erp_permission_require_any_role(['owner', 'system_admin'])`

**Not used:** CSRF, workflow engine, audit helper

### `erp-access-request-detail.php`
**Helper stack:** Phase 1A
- Config: `erp-config-loader.php`
- Auth: `erp-auth-helper.php`
- Permission: `erp-permission-helper.php`

**Runtime guards used:**
- `erp_auth_require_login()`
- `erp_permission_require_any_role(['owner', 'system_admin'])`

**Not used:** CSRF, workflow engine, audit helper

## 5. Transition Prototype Stack

The signed-off transition prototype in `public_html/erp-access-request-transition.php` uses the **Phase 2 helper stack**:

- Auth: `includes/erp-auth-context.php`
  - `erp_auth_get_current_context()`
  - `erp_auth_require_current_user()`
- CSRF: `includes/erp-csrf.php`
  - `erp_csrf_create_token()`
  - `erp_csrf_require_valid_token()`
- Permission: `includes/erp-permission-check.php`
  - `erp_permission_require($context, 'access_request.submit')`
- Workflow: `includes/erp-workflow-engine.php`
  - `erp_workflow_build_transition_result('access_request', 'DRAFT', 'SUBMITTED')`

**Prototype constants locked:**
- Form key: `access_request_submit`
- Permission: `access_request.submit`
- Entity: `access_request`
- From state: `DRAFT`
- To state: `SUBMITTED`

**Not used in prototype:** `erp-audit-helper.php`, database connection, state update, history insert

**Locked decision for next implementation:** the first controlled write-enabled transition must follow the Phase 2 stack above, not the Phase 1A role-based stack used by create/list/detail.

## 6. Required SQL State Values

**Status: Locked from confirmed read-only SSMS inspection**

### `request_state`

**Confirmed values in data:**

| Value | Row count |
|-------|-----------|
| `APPLIED` | 1 |
| `DRAFT` | 1 |

**Allowed by CHECK constraint `CK_core_access_requests_state`:**
- `DRAFT`
- `SUBMITTED`
- `UNDER_REVIEW`
- `APPROVED`
- `PARTIALLY_APPROVED`
- `REJECTED`
- `APPLIED`
- `CANCELLED`

### `request_type`

**Confirmed values in data:**

| Value | Row count |
|-------|-----------|
| `EMERGENCY` | 1 |
| `ROLE_GRANT` | 1 |

**Allowed by CHECK constraint:**
- `ONBOARDING`
- `ROLE_GRANT`
- `TEMPORARY_ROLE_GRANT`
- `DEPARTMENT_ASSIGN`
- `POSITION_ASSIGN`
- `PROMOTION`
- `ACCESS_UPGRADE`
- `ACCESS_DOWNGRADE`
- `SUSPENSION`
- `ACCESS_RESTRICTION`
- `OFFBOARDING`
- `EMERGENCY`

### `item_decision`

**Confirmed values in data:**

| Value | Row count |
|-------|-----------|
| `PENDING` | 1 |

**Allowed by CHECK constraint:**
- `PENDING`
- `APPROVED`
- `REJECTED`

### approval `decision` (`core_access_approvals`)

**Confirmed values in data:**
- No rows found in `core_access_approvals`

**Allowed by CHECK constraint:**
- `APPROVED`
- `REJECTED`
- `PARTIAL`

### history `change_type` (`core_access_change_history`)

**Confirmed values in data:**

| Value | Row count |
|-------|-----------|
| `BOOTSTRAP_USER_UPSERT` | 1 |
| `ROLE_GRANTED` | 2 |

**Confirmed constraint status:**
- `core_access_change_history.change_type` has no CHECK constraint
- `ACCESS_REQUEST_SUBMITTED` is not observed yet, but is allowed because `change_type` is unconstrained

## 7. First Allowed Transition Candidate

**Only this transition is allowed for the next implementation:**

```
DRAFT → SUBMITTED
```

**Entity:** `access_request`  
**Permission:** `access_request.submit`

**Confirmed:**
- SUBMITTED is allowed by CK_core_access_requests_state
- Candidate row: `request_id = 4`
- `request_number = AR-20260620-084634-10001`
- `request_type = ROLE_GRANT`
- Current `request_state = DRAFT`
- `subject_user_id = 10001`
- `requested_by_user_id = 10001`
- `submitted_at = NULL`
- `updated_at = NULL`

**Allowed database changes for the next implementation:**
- Update `dbo.core_access_requests.request_state`
- Update `dbo.core_access_requests.submitted_at` if currently NULL
- Update `dbo.core_access_requests.updated_at`
- Insert one row into `dbo.core_access_change_history`

**Forbidden:**
- Direct role assignment
- Direct permission change
- User creation
- Tenant change
- Customer portal change
- Update to `core_access_request_items.item_decision`
- Insert into `core_access_approvals` unless explicitly approved later
- Touching `core_user_roles` or equivalent assignment tables

## 8. Implementation Blockers

The following must be resolved before code implementation:

1. **No dedicated history helper** — no function exists for `core_access_change_history` insert; approved insert shape must be defined.
2. **`change_type` value needs design approval** — `ACCESS_REQUEST_SUBMITTED` is technically allowed because `change_type` is unconstrained, but it is not yet observed in existing history rows and must be approved before use.
3. **`before_json` / `after_json` shape** — must be defined before history insert implementation.
4. **Audit table requirement unclear** — `erp_audit_write()` writes to `core_audit_logs`; whether transition also requires audit log insert is not locked.
5. **Auth stack split** — create/list/detail use Phase 1A login session; transition uses Phase 2 auth context; write implementation must use Phase 2 stack consistently.
6. **CSRF field naming split** — Phase 1A uses `erp_csrf_token`; Phase 2 prototype uses `csrf_token`; next implementation must follow Phase 2 transition convention.
7. **Concurrency / `row_version` approach** — `row_version` exists on `core_access_requests`; optimistic concurrency or state-based re-check approach must be defined before UPDATE.
8. **Database connection helper for transition write** — no shared Phase 2 DB helper exists yet; must be designed without modifying forbidden config/auth files.

## 9. Final Locked Decision

The next implementation may build a controlled `DRAFT → SUBMITTED` transition for `request_id`-based form submission only, if it uses:

- Phase 2 auth context
- Phase 2 CSRF
- Phase 2 permission check
- Phase 2 workflow engine
- SQL transaction
- `row_version` or state-based concurrency protection
- `core_access_change_history` insert
- No role assignment
- No permission assignment
- No approval insert

## 10. Sign-Off

No runtime behavior was changed.

This document locks state/helper information for the next controlled code step.

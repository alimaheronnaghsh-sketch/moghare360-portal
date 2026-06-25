# MOGHARE360 ERP - Phase 2 Access Request Transition Technical Inventory

## 1. Purpose
Prepare the first controlled Access Request Workflow Transition without changing runtime behavior.

## 2. Files Inspected
- `includes/erp-auth-context.php`
- `includes/erp-csrf.php`
- `includes/erp-permission-check.php`
- `includes/erp-workflow-engine.php`
- `erp-access-request-create.php`
- `erp-access-request-list.php`
- `erp-access-request-detail.php`

Related files referenced by the Access Request pages but not listed in the inspection set above:
- `includes/erp-config-loader.php`
- `includes/erp-auth-helper.php`
- `includes/erp-permission-helper.php`
- `includes/erp-csrf-helper.php`
- `includes/erp-audit-helper.php`
- `public_html/erp-access-request-transition.php`

## 3. Helper Functions Found

### `includes/erp-auth-context.php` (Phase 2 prototype)
- `erp_auth_start_session_if_needed()`
- `erp_auth_get_current_context()`
- `erp_auth_require_current_user()`
- `erp_auth_is_system_owner(array $context)`
- `erp_auth_get_user_roles(array $context)`
- `erp_auth_get_user_permissions(array $context)`

### `includes/erp-csrf.php` (Phase 2 prototype)
- `erp_csrf_start_session_if_needed()`
- `erp_csrf_create_token(string $form_key)`
- `erp_csrf_validate_token(string $form_key, string $token)`
- `erp_csrf_require_valid_token(string $form_key, string $token)`

### `includes/erp-permission-check.php` (Phase 2 prototype)
- `erp_permission_normalize_key(string $permission_key)`
- `erp_permission_context_has_role(array $context, string $role_key)`
- `erp_permission_context_has_permission(array $context, string $permission_key)`
- `erp_permission_is_platform_owner_prototype(array $context)`
- `erp_permission_user_has(array $context, string $permission_key)`
- `erp_permission_require(array $context, string $permission_key)`

### `includes/erp-workflow-engine.php` (Phase 2 prototype)
- `erp_workflow_normalize_value(string $value, string $label)`
- `erp_workflow_get_allowed_transitions()`
- `erp_workflow_can_transition(string $entity, string $from_state, string $to_state)`
- `erp_workflow_require_transition(string $entity, string $from_state, string $to_state)`
- `erp_workflow_build_transition_result(string $entity, string $from_state, string $to_state)`

### Phase 1A helpers already used by Access Request pages
These are not in the primary inspection list but are the current runtime stack for create/list/detail:

**`includes/erp-auth-helper.php`**
- `erp_auth_start_session()`
- `erp_auth_session_keys()`
- `erp_auth_is_logged_in()`
- `erp_auth_require_login()`
- `erp_auth_current_user()`
- `erp_auth_logout_keys()`
- `erp_auth_touch_activity()`

**`includes/erp-permission-helper.php`**
- `erp_permission_user_roles()`
- `erp_permission_has_role(string $role)`
- `erp_permission_has_any_role(array $roles)`
- `erp_permission_is_system_owner()`
- `erp_permission_require_role(string $role)`
- `erp_permission_require_any_role(array $roles)`
- `erp_permission_access_denied()`

**`includes/erp-csrf-helper.php`**
- `erp_csrf_start()`
- `erp_csrf_normalize_purpose(string $purpose)`
- `erp_csrf_generate(string $purpose)`
- `erp_csrf_input(string $purpose)`
- `erp_csrf_validate(string $purpose, ?string $token)`
- `erp_csrf_require_valid(string $purpose, ?string $token)`
- `erp_csrf_clear(string $purpose)`
- `erp_csrf_access_denied()`

**`includes/erp-audit-helper.php`**
- `erp_audit_safe_actor()`
- `erp_audit_client_context()`
- `erp_audit_sanitize_string(?string $value, int $maxLength)`
- `erp_audit_safe_json(array $details)`
- `erp_audit_connection()`
- `erp_audit_write(array $event)` — writes to `dbo.core_audit_logs`
- `erp_audit_login_success(int $userId, string $username)`
- `erp_audit_login_failure(string $username)`
- `erp_audit_logout()`
- `erp_audit_access_denied(string $eventType)`

### Missing dedicated helper
- `core_access_change_history` insert helper: **Needs confirmation before implementation**

## 4. Current Access Request Flow

### Create page (`erp-access-request-create.php`)
- Request creation only.
- Protected by Phase 1A helper stack: config loader, auth login, role permission, CSRF on POST, audit on write.
- POST path: CSRF validation → field validation → ODBC transaction → insert into `core_access_requests` and `core_access_request_items` → `erp_audit_write()` → commit.
- Does not perform workflow state transition.
- Does not use Phase 2 workflow engine.

### List page (`erp-access-request-list.php`)
- Read-only GET list.
- Protected by config loader, auth login, role permission.
- SELECT-only query against `core_access_requests` and related tables.
- No CSRF, no workflow engine, no write path.

### Detail page (`erp-access-request-detail.php`)
- Read-only GET detail view by `request_id`.
- Protected by config loader, auth login, role permission.
- SELECT-only header and item queries.
- No CSRF, no workflow engine, no write path.

### Phase 2 transition prototype (`public_html/erp-access-request-transition.php`)
- Exists separately as a controlled preview-only page.
- Uses Phase 2 helper stack: `erp-auth-context`, `erp-csrf`, `erp-permission-check`, `erp-workflow-engine`.
- Validates `DRAFT -> SUBMITTED` preview only.
- No database connection, no state update, no audit/history insert.
- Must not be merged blindly into create/list/detail pages.

## 5. Confirmed SQL Column Map

### `core_access_requests`
- `request_id` bigint identity
- `request_number` nvarchar(60)
- `request_type` nvarchar(80)
- `request_state` nvarchar(60)
- `priority` nvarchar(40)
- `subject_user_id` int
- `requested_by_user_id` int
- `justification` nvarchar(max)
- `owner_acknowledged` bit
- `is_emergency` bit
- `migration_source` nvarchar(60) nullable
- `submitted_at` datetime2 nullable
- `decided_at` datetime2 nullable
- `applied_at` datetime2 nullable
- `applied_by_user_id` int nullable
- `cancelled_at` datetime2 nullable
- `cancelled_by_user_id` int nullable
- `created_at` datetime2
- `updated_at` datetime2 nullable
- `row_version` timestamp

### `core_access_approvals`
- `approval_id` bigint identity
- `request_id` bigint
- `approver_user_id` int
- `approver_capacity` nvarchar(80)
- `decision` nvarchar(40)
- `comment` nvarchar(max)
- `decided_at` datetime2
- `created_at` datetime2

### `core_access_change_history`
- `history_id` bigint identity
- `user_id` int
- `request_id` bigint
- `change_type` nvarchar(80)
- `entity_type` nvarchar(100)
- `entity_id` bigint nullable
- `before_json` nvarchar(max) nullable
- `after_json` nvarchar(max) nullable
- `changed_by_user_id` int nullable
- `changed_at` datetime2

### `core_access_request_items`
- `item_id` bigint identity
- `request_id` bigint
- `item_type` nvarchar(80)
- `role_id` int nullable
- `department_id` int nullable
- `position_id` int nullable
- `module_key` nvarchar(160) nullable
- `permission_key` nvarchar(240) nullable
- `scope_type` nvarchar(40) nullable
- `effective_from` datetime2
- `expires_at` datetime2 nullable
- `is_temporary` bit
- `item_decision` nvarchar(40)
- `sort_order` int
- `created_at` datetime2

### `core_access_suspensions`
- `suspension_id` bigint identity
- `user_id` int
- `request_id` bigint
- `scope_type` nvarchar(40)
- `module_key` nvarchar(160) nullable
- `reason` nvarchar(max)
- `starts_at` datetime2
- `ends_at` datetime2 nullable
- `lifted_at` datetime2 nullable
- `lifted_by_request_id` bigint nullable
- `created_at` datetime2

### `core_access_restrictions`
- `restriction_id` bigint identity
- `user_id` int
- `request_id` bigint
- `restriction_type` nvarchar(40)
- `module_key` nvarchar(160) nullable
- `permission_key` nvarchar(240) nullable
- `incident_note` nvarchar(max)
- `starts_at` datetime2
- `ends_at` datetime2 nullable
- `lifted_at` datetime2 nullable
- `lifted_by_request_id` bigint nullable
- `created_at` datetime2

## 6. Required Transition Safety Chain

```
Browser Form
→ CSRF Validation
→ Auth Check
→ Permission Check
→ Workflow Engine
→ Audit / History
→ State Update
```

## 7. Proposed First Transition

Propose only this safe transition:

**Access Request status transition only:**
- Entity: `access_request`
- From current `request_state`: `DRAFT`
- To controlled next `request_state`: `SUBMITTED`
- Permission concept: `access_request.submit`
- Candidate row from read-only inspection: `request_id = 4`

**Approved write scope for first implementation (when separately approved):**
- Update only `core_access_requests.request_state`
- Update `core_access_requests.updated_at`
- Set `core_access_requests.submitted_at` if approved by design review
- Re-check `core_access_requests.row_version` or current state inside transaction before update
- Insert audit/history row into `core_access_change_history`

**Forbidden in this first transition:**
- Direct role assignment
- Direct permission change
- User creation
- Tenant change
- Customer portal change
- Touching `core_user_roles` or equivalent assignment tables
- Updates to `core_access_request_items`
- Updates to `core_access_approvals`
- Updates to `core_access_suspensions`
- Updates to `core_access_restrictions`

## 8. Missing Information

Before implementation, confirm:

| Item | Status |
|------|--------|
| Exact existing `request_state` values in database | Partially confirmed: `APPLIED`, `DRAFT`; `SUBMITTED` not yet observed in data |
| Exact helper function names for CSRF validation on transition page | Phase 2: `erp_csrf_require_valid_token()`; Phase 1A: `erp_csrf_require_valid()` — stack choice needs approval |
| Exact helper function names for login/auth context | Phase 2: `erp_auth_require_current_user()`; Phase 1A: `erp_auth_require_login()` — stack choice needs approval |
| Exact helper function names for permission check | Phase 2: `erp_permission_require()` with `access_request.submit`; Phase 1A: `erp_permission_require_any_role()` — stack choice needs approval |
| Exact workflow engine transition function | Confirmed: `erp_workflow_require_transition()` and `erp_workflow_build_transition_result()` |
| Exact audit/history helper or approved insert path | `erp_audit_write()` writes to `core_audit_logs`; no dedicated `core_access_change_history` helper exists — **Needs confirmation before implementation** |
| Required `change_type` values for history rows | **Needs confirmation before implementation** |
| Required `before_json` / `after_json` shape for history rows | **Needs confirmation before implementation** |
| CHECK constraints on `request_state` | **Needs confirmation before implementation** |
| Whether `submitted_at` must be set on `DRAFT -> SUBMITTED` | **Needs confirmation before implementation** |
| Whether both `core_audit_logs` and `core_access_change_history` are required | **Needs confirmation before implementation** |

## 9. Sign-Off

No runtime behavior was changed.

This document is only an implementation inventory for the next controlled code step.

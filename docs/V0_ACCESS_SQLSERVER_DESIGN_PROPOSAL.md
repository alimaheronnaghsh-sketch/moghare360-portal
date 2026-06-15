<<<<<<< HEAD
# MOGHARE360 — Version 0 Access Lifecycle SQL Server Database Design Proposal

## Document metadata

| Field | Value |
|-------|-------|
| Target database | `moghare360_ERP` |
| Platform | Microsoft SQL Server |
| Scope | Version 0 Access Lifecycle Management for **internal staff only** |
| Status | Design proposal — no executable DDL in this document |

---

## Design conventions

| Convention | Choice |
|------------|--------|
| Schema | `dbo` (table names prefixed `core_`) |
| Text / Persian | `NVARCHAR` with database collation supporting Persian (e.g. `Persian_100_CI_AS` or UTF-8 on SQL Server 2019+) |
| Timestamps | `DATETIME2(3)`; application layer uses `Asia/Tehran` |
| Booleans | `BIT` |
| Surrogate keys | `INT IDENTITY` for master data; `BIGINT IDENTITY` for workflow and audit tables |
| JSON snapshots | `NVARCHAR(MAX)` with `ISJSON` check constraint where used |
| Soft delete | `is_active`, `revoked_at`, lifecycle state — no physical delete on audit/history |
| Legacy bridge | `legacy_staff_user_id`, `migration_source` columns for migration continuity |

**Explicitly excluded in Version 0:** `core_user_permissions` (no direct user → permission grants).

---

## Entity relationship summary

```
=======
MOGHARE360 — Version 0 Access Lifecycle SQL Server Database Design Proposal

Document metadata







Field



Value





Target database



moghare360_ERP





Platform



Microsoft SQL Server





Scope



Version 0 Access Lifecycle Management for internal staff only





Status



Design proposal — no executable DDL in this document



Design conventions







Convention



Choice





Schema



dbo (table names prefixed core_)





Text / Persian



NVARCHAR with database collation supporting Persian (e.g. Persian_100_CI_AS or UTF-8 on SQL Server 2019+)





Timestamps



DATETIME2(3); application layer uses Asia/Tehran





Booleans



BIT





Surrogate keys



INT IDENTITY for master data; BIGINT IDENTITY for workflow and audit tables





JSON snapshots



NVARCHAR(MAX) with ISJSON check constraint where used





Soft delete



is_active, revoked_at, lifecycle state — no physical delete on audit/history





Legacy bridge



legacy_staff_user_id, migration_source columns for migration continuity

Explicitly excluded in Version 0: core_user_permissions (no direct user → permission grants).



Entity relationship summary

>>>>>>> e32f46d2d6146d3db627c100c7b042e5da7c12bd
core_departments ──< core_positions
       │                    │
       └──────────┬─────────┘
                  ▼
core_users ──1:1── core_staff_profiles
    │    │
    │    ├──< core_access_requests (subject / requester)
    │    │         ├──< core_access_request_items
    │    │         └──< core_access_approvals
    │    │
    │    ├──< core_user_roles ──> core_roles ──< core_role_permissions >── core_permissions
    │    ├──< core_access_suspensions
    │    ├──< core_access_restrictions
    │    ├──< core_access_change_history
    │    └──< core_audit_logs
<<<<<<< HEAD
```

---

## Access resolution order

1. `core_users.lifecycle_state` ≠ `ACTIVE` → deny (except Owner policy, audited).
2. Active row in `core_access_suspensions`.
3. Active row in `core_access_restrictions` matching module/permission.
4. Owner flag (`is_system_owner`) — audited on sensitive actions.
5. Effective rows in `core_user_roles` joined to `core_role_permissions` → `core_permissions`.
6. Legacy fallback from portal `access_*` tables (logged in `core_audit_logs`; not stored in V0 core tables).
7. Deny.

---

## Table definitions

### 1. `core_users`

**Classification:** Master data (identity)

**Purpose:** Canonical staff identity. Lifecycle state controls login eligibility. Source of truth for staff authentication in `moghare360_ERP`.

| Column | Type | Null | Notes |
|--------|------|------|-------|
| `user_id` | `INT` | NO | Surrogate PK; preserve legacy `staff_users.id` on migration via `IDENTITY_INSERT` |
| `username` | `NVARCHAR(80)` | NO | Unique login name |
| `password_hash` | `NVARCHAR(255)` | NO | bcrypt/argon hash |
| `full_name` | `NVARCHAR(160)` | NO | Display name |
| `email` | `NVARCHAR(255)` | YES | |
| `mobile` | `NVARCHAR(30)` | YES | |
| `lifecycle_state` | `NVARCHAR(30)` | NO | `DRAFT`, `PENDING_ONBOARDING`, `ACTIVE`, `PROMOTED`, `ACCESS_CHANGED`, `SUSPENDED`, `RESTRICTED`, `OFFBOARDING`, `INACTIVE` |
| `is_system_owner` | `BIT` | NO | Default 0; Owner override policy |
| `is_login_enabled` | `BIT` | NO | Maintained on apply; false for DRAFT/INACTIVE/OFFBOARDING |
| `legacy_staff_user_id` | `INT` | YES | Bridge to portal `staff_users.id` during transition |
| `last_login_at` | `DATETIME2(3)` | YES | |
| `created_at` | `DATETIME2(3)` | NO | |
| `updated_at` | `DATETIME2(3)` | YES | |
| `created_by_user_id` | `INT` | YES | Self-FK |
| `updated_by_user_id` | `INT` | YES | Self-FK |
| `row_version` | `ROWVERSION` | NO | Optimistic concurrency |

**Primary key:** `user_id`

**Foreign keys:**
- `created_by_user_id` → `core_users(user_id)`
- `updated_by_user_id` → `core_users(user_id)`

**Indexes:**
- `UX_core_users_username` UNIQUE on `username`
- `IX_core_users_lifecycle_state` on `lifecycle_state`
- `IX_core_users_legacy_staff_user_id` UNIQUE WHERE `legacy_staff_user_id IS NOT NULL`
- `IX_core_users_is_login_enabled` on `is_login_enabled` INCLUDE `lifecycle_state`

**Business rules:**
- Login allowed only when `is_login_enabled = 1` AND `lifecycle_state = 'ACTIVE'` (Owner bypass in app layer only, always audited).
- `is_system_owner = 1` does not replace workflow; emergency actions still require audit.
- No direct permission columns on this table.
- Staff only — no customer accounts in this table in Version 0.

---

### 2. `core_departments`

**Classification:** Master data (organization)

**Purpose:** Organizational units. Scopes department-manager approval and positions catalog.

| Column | Type | Null | Notes |
|--------|------|------|-------|
| `department_id` | `INT IDENTITY(1,1)` | NO | PK |
| `dept_key` | `NVARCHAR(50)` | NO | Stable slug, e.g. `reception`, `inventory` |
| `dept_name` | `NVARCHAR(120)` | NO | Persian display name |
| `parent_department_id` | `INT` | YES | Self-FK for hierarchy |
| `manager_user_id` | `INT` | YES | FK → `core_users` |
| `is_active` | `BIT` | NO | Default 1 |
| `sort_order` | `INT` | NO | Default 100 |
| `created_at` | `DATETIME2(3)` | NO | |
| `updated_at` | `DATETIME2(3)` | YES | |

**Primary key:** `department_id`

**Foreign keys:**
- `parent_department_id` → `core_departments(department_id)`
- `manager_user_id` → `core_users(user_id)`

**Indexes:**
- `UX_core_departments_dept_key` UNIQUE on `dept_key`
- `IX_core_departments_parent` on `parent_department_id`
- `IX_core_departments_manager` on `manager_user_id`
- `IX_core_departments_active_sort` on `is_active`, `sort_order`

**Business rules:**
- Seed 14 internal departments per org plan.
- Deactivate, never hard-delete, if referenced.
- Manager should belong to same department (app validation in V0).

---

### 3. `core_positions`

**Classification:** Master data (organization)

**Purpose:** Job titles within a department. May suggest roles; does not auto-grant access.

| Column | Type | Null | Notes |
|--------|------|------|-------|
| `position_id` | `INT IDENTITY(1,1)` | NO | PK |
| `department_id` | `INT` | NO | FK |
| `position_key` | `NVARCHAR(50)` | NO | Unique within department |
| `position_name` | `NVARCHAR(120)` | NO | |
| `suggested_role_id` | `INT` | YES | FK → `core_roles`; onboarding hint only |
| `is_active` | `BIT` | NO | Default 1 |
| `sort_order` | `INT` | NO | Default 100 |
| `created_at` | `DATETIME2(3)` | NO | |
| `updated_at` | `DATETIME2(3)` | YES | |

**Primary key:** `position_id`

**Foreign keys:**
- `department_id` → `core_departments(department_id)`
- `suggested_role_id` → `core_roles(role_id)`

**Indexes:**
- `UX_core_positions_dept_key` UNIQUE on `department_id`, `position_key`
- `IX_core_positions_department_active` on `department_id`, `is_active`, `sort_order`

**Business rules:**
- Position change does not auto-update `core_user_roles`; requires access request.
- `suggested_role_id` is advisory for onboarding UI only.

---

### 4. `core_roles`

**Classification:** Master data (authorization)

**Purpose:** Named responsibility bundles and access levels. **Only path to permissions for staff users.**

| Column | Type | Null | Notes |
|--------|------|------|-------|
| `role_id` | `INT IDENTITY(1,1)` | NO | PK |
| `role_key` | `NVARCHAR(80)` | NO | Unique slug |
| `role_name` | `NVARCHAR(120)` | NO | |
| `access_level` | `NVARCHAR(30)` | NO | `OWNER`, `GENERAL_MANAGER`, `OPERATIONS_MANAGER`, `DEPARTMENT_MANAGER`, `STAFF`, `READ_ONLY` — see Customer Access Decision |
| `description` | `NVARCHAR(500)` | YES | |
| `is_active` | `BIT` | NO | Default 1 |
| `sort_order` | `INT` | NO | Default 100 |
| `created_at` | `DATETIME2(3)` | NO | |
| `updated_at` | `DATETIME2(3)` | YES | |

**Primary key:** `role_id`

**Foreign keys:** None

**Indexes:**
- `UX_core_roles_role_key` UNIQUE on `role_key`
- `IX_core_roles_access_level` on `access_level`, `is_active`

**Business rules:**
- Editing role definition does not change user access until a new request is applied.
- Deactivate role if assigned users exist; block delete.
- System roles (`owner`, `system_admin`, etc.) non-deletable in app layer.
- `CUSTOMER` access level reserved for future — not used in V0 seed data.

---

### 5. `core_permissions`

**Classification:** Master data (authorization catalog)

**Purpose:** Atomic capabilities (`module.action`). Assigned **only to roles**, never users.

| Column | Type | Null | Notes |
|--------|------|------|-------|
| `permission_id` | `INT IDENTITY(1,1)` | NO | PK |
| `permission_key` | `NVARCHAR(120)` | NO | e.g. `inventory.price`, `access.request.approve` |
| `module_key` | `NVARCHAR(80)` | NO | |
| `action_key` | `NVARCHAR(80)` | NO | |
| `permission_label` | `NVARCHAR(180)` | NO | Persian label |
| `sort_order` | `INT` | NO | Default 100 |
| `is_active` | `BIT` | NO | Default 1 |
| `created_at` | `DATETIME2(3)` | NO | |

**Primary key:** `permission_id`

**Foreign keys:** None

**Indexes:**
- `UX_core_permissions_permission_key` UNIQUE on `permission_key`
- `IX_core_permissions_module` on `module_key`, `sort_order`, `is_active`

**Business rules:**
- Permission keys immutable after seed.
- Migrate keys from legacy `access_permissions` unchanged where possible.
- Include meta-permissions for access module (`access.request.create`, `access.request.approve`, `access.audit.view`, etc.).
- No customer portal permissions in V0 seed.

---

### 6. `core_role_permissions`

**Classification:** Master data (authorization mapping)

**Purpose:** Maps permissions to roles (role × permission matrix).

| Column | Type | Null | Notes |
|--------|------|------|-------|
| `role_id` | `INT` | NO | FK |
| `permission_id` | `INT` | NO | FK |
| `granted_at` | `DATETIME2(3)` | NO | |
| `granted_by_user_id` | `INT` | YES | FK → `core_users` |

**Primary key:** `(role_id, permission_id)`

**Foreign keys:**
- `role_id` → `core_roles(role_id)`
- `permission_id` → `core_permissions(permission_id)`
- `granted_by_user_id` → `core_users(user_id)`

**Indexes:**
- `IX_core_role_permissions_permission` on `permission_id` INCLUDE `role_id`

**Business rules:**
- Matrix changes audited in `core_audit_logs`.
- No `user_id` column.
- Sensitive permissions (`admin.*`, `inventory.price`) require explicit mapping review.

---

### 7. `core_staff_profiles`

**Classification:** Master data (HR / org profile)

**Purpose:** 1:1 staff organizational profile. Department and position assignment target on request apply.

| Column | Type | Null | Notes |
|--------|------|------|-------|
| `profile_id` | `INT IDENTITY(1,1)` | NO | PK |
| `user_id` | `INT` | NO | FK, UNIQUE |
| `department_id` | `INT` | YES | FK; nullable during DRAFT |
| `position_id` | `INT` | YES | FK |
| `employee_code` | `NVARCHAR(40)` | YES | UNIQUE if present |
| `hire_date` | `DATE` | YES | |
| `exit_date` | `DATE` | YES | Set on offboarding |
| `profile_photo_path` | `NVARCHAR(500)` | YES | |
| `notes` | `NVARCHAR(MAX)` | YES | |
| `created_at` | `DATETIME2(3)` | NO | |
| `updated_at` | `DATETIME2(3)` | YES | |

**Primary key:** `profile_id`

**Foreign keys:**
- `user_id` → `core_users(user_id)` ON DELETE RESTRICT
- `department_id` → `core_departments(department_id)`
- `position_id` → `core_positions(position_id)`

**Indexes:**
- `UX_core_staff_profiles_user_id` UNIQUE on `user_id`
- `UX_core_staff_profiles_employee_code` UNIQUE WHERE `employee_code IS NOT NULL`
- `IX_core_staff_profiles_department` on `department_id` INCLUDE `user_id`, `position_id`

**Business rules:**
- Exactly one profile per staff user.
- Department/position changes via access request apply only (except DRAFT creation).
- `position_id` must belong to `department_id` (app/trigger validation).

---

### 8. `core_access_requests`

**Classification:** Transaction data (workflow header)

**Purpose:** Formal access/lifecycle intent. Central workflow record from submit through apply.

| Column | Type | Null | Notes |
|--------|------|------|-------|
| `request_id` | `BIGINT IDENTITY(1,1)` | NO | PK |
| `request_number` | `NVARCHAR(30)` | NO | Human-readable, UNIQUE |
| `request_type` | `NVARCHAR(40)` | NO | See business rules |
| `request_state` | `NVARCHAR(30)` | NO | `DRAFT`, `SUBMITTED`, `UNDER_REVIEW`, `APPROVED`, `PARTIALLY_APPROVED`, `REJECTED`, `APPLIED`, `CANCELLED` |
| `priority` | `NVARCHAR(20)` | NO | `NORMAL`, `URGENT` |
| `subject_user_id` | `INT` | NO | FK → user receiving change |
| `requested_by_user_id` | `INT` | NO | FK → submitter |
| `justification` | `NVARCHAR(MAX)` | NO | Required reason |
| `owner_acknowledged` | `BIT` | NO | Default 0; required if `priority = URGENT` |
| `is_emergency` | `BIT` | NO | Default 0; Owner-only |
| `migration_source` | `NVARCHAR(30)` | YES | e.g. `LEGACY`, `LEGACY_PROFILE` |
| `submitted_at` | `DATETIME2(3)` | YES | |
| `decided_at` | `DATETIME2(3)` | YES | |
| `applied_at` | `DATETIME2(3)` | YES | |
| `applied_by_user_id` | `INT` | YES | FK; NULL = system auto-apply |
| `cancelled_at` | `DATETIME2(3)` | YES | |
| `cancelled_by_user_id` | `INT` | YES | FK |
| `created_at` | `DATETIME2(3)` | NO | |
| `updated_at` | `DATETIME2(3)` | YES | |
| `row_version` | `ROWVERSION` | NO | |

**Request types:** `ONBOARDING`, `ROLE_GRANT`, `TEMPORARY_ROLE_GRANT`, `DEPARTMENT_ASSIGN`, `POSITION_ASSIGN`, `PROMOTION`, `ACCESS_UPGRADE`, `ACCESS_DOWNGRADE`, `SUSPENSION`, `ACCESS_RESTRICTION`, `OFFBOARDING`, `EMERGENCY`

**Primary key:** `request_id`

**Foreign keys:**
- `subject_user_id` → `core_users(user_id)`
- `requested_by_user_id` → `core_users(user_id)`
- `applied_by_user_id` → `core_users(user_id)`
- `cancelled_by_user_id` → `core_users(user_id)`

**Indexes:**
- `UX_core_access_requests_request_number` UNIQUE on `request_number`
- `IX_core_access_requests_state_type` on `request_state`, `request_type`, `submitted_at DESC`
- `IX_core_access_requests_subject` on `subject_user_id`, `created_at DESC`
- `IX_core_access_requests_requester` on `requested_by_user_id`, `created_at DESC`
- `IX_core_access_requests_pending` on `request_state` WHERE `request_state IN ('SUBMITTED','UNDER_REVIEW','APPROVED','PARTIALLY_APPROVED')`

**Business rules:**
- No row in `core_user_roles` without `granted_by_request_id` pointing to an `APPLIED` request (except migration-flagged).
- `TEMPORARY_ROLE_GRANT` requires item-level `expires_at`.
- `URGENT` requires `owner_acknowledged = 1`.
- `EMERGENCY` only if requester is Owner.
- State transitions append `core_audit_logs` on every change.
- Offboarding blocks new grant requests for same subject while `OFFBOARDING`.

---

### 9. `core_access_request_items`

**Classification:** Transaction data (workflow lines)

**Purpose:** Line-level proposed changes: role grant/revoke, department, position, suspension/restriction parameters.

| Column | Type | Null | Notes |
|--------|------|------|-------|
| `item_id` | `BIGINT IDENTITY(1,1)` | NO | PK |
| `request_id` | `BIGINT` | NO | FK |
| `item_type` | `NVARCHAR(40)` | NO | See business rules |
| `role_id` | `INT` | YES | FK when role item |
| `department_id` | `INT` | YES | FK |
| `position_id` | `INT` | YES | FK |
| `module_key` | `NVARCHAR(80)` | YES | For restriction/suspension scope |
| `permission_key` | `NVARCHAR(120)` | YES | For permission-level restriction |
| `scope_type` | `NVARCHAR(20)` | YES | `FULL`, `MODULE`, `LOGIN_ONLY` |
| `effective_from` | `DATETIME2(3)` | NO | Required for grants |
| `expires_at` | `DATETIME2(3)` | YES | Required for temporary grants |
| `is_temporary` | `BIT` | NO | Default 0 |
| `item_decision` | `NVARCHAR(20)` | NO | `PENDING`, `APPROVED`, `REJECTED` |
| `sort_order` | `INT` | NO | Default 1 |
| `created_at` | `DATETIME2(3)` | NO | |

**Item types:** `ROLE_GRANT`, `ROLE_REVOKE`, `DEPARTMENT_SET`, `POSITION_SET`, `SUSPENSION_CREATE`, `RESTRICTION_CREATE`, `LIFECYCLE_STATE_SET`

**Primary key:** `item_id`

**Foreign keys:**
- `request_id` → `core_access_requests(request_id)`
- `role_id` → `core_roles(role_id)`
- `department_id` → `core_departments(department_id)`
- `position_id` → `core_positions(position_id)`

**Indexes:**
- `IX_core_access_request_items_request` on `request_id`, `sort_order`
- `IX_core_access_request_items_role` on `role_id` WHERE `role_id IS NOT NULL`
- `IX_core_access_request_items_expiry` on `expires_at` WHERE `expires_at IS NOT NULL AND item_type = 'ROLE_GRANT'`

**Business rules:**
- `ROLE_GRANT` items must have `role_id` and `effective_from`.
- `TEMPORARY_ROLE_GRANT` → `is_temporary = 1` and `expires_at NOT NULL`.
- `PARTIALLY_APPROVED` requests apply only items with `item_decision = APPROVED`.
- No direct permission grant item type in V0.

---

### 10. `core_access_approvals`

**Classification:** Transaction data (approval decisions)

**Purpose:** Immutable approver decisions per request. Supports multi-approver matrix from policy.

| Column | Type | Null | Notes |
|--------|------|------|-------|
| `approval_id` | `BIGINT IDENTITY(1,1)` | NO | PK |
| `request_id` | `BIGINT` | NO | FK |
| `approver_user_id` | `INT` | NO | FK |
| `approver_capacity` | `NVARCHAR(40)` | NO | `DEPARTMENT_MANAGER`, `SYSTEM_ADMIN`, `OPERATIONS_MANAGER`, `OWNER` |
| `decision` | `NVARCHAR(20)` | NO | `APPROVED`, `REJECTED`, `PARTIAL` |
| `comment` | `NVARCHAR(MAX)` | NO | Required written explanation |
| `decided_at` | `DATETIME2(3)` | NO | |
| `created_at` | `DATETIME2(3)` | NO | |

**Primary key:** `approval_id`

**Foreign keys:**
- `request_id` → `core_access_requests(request_id)`
- `approver_user_id` → `core_users(user_id)`

**Indexes:**
- `IX_core_access_approvals_request` on `request_id`, `decided_at`
- `IX_core_access_approvals_approver_pending` on `approver_user_id`, `decision` INCLUDE `request_id`
- `UX_core_access_approvals_unique_capacity` UNIQUE on `request_id`, `approver_capacity`

**Business rules:**
- Approvals are append-only; no UPDATE/DELETE.
- Department manager approves only if subject’s department matches approver’s managed department.
- Minimum approvers per `request_type` enforced before state → `APPROVED`.
- Rejection sets request to `REJECTED`; no apply.

**Approval matrix (V0 static):**

| Request type | Minimum approvers |
|--------------|-------------------|
| `ONBOARDING` | Department Manager + System Admin |
| `ROLE_GRANT` / `TEMPORARY_ROLE_GRANT` | Department Manager |
| `ACCESS_UPGRADE` | Department Manager + System Admin |
| `ACCESS_DOWNGRADE` | Department Manager |
| `PROMOTION` | Department Manager + Operations Manager |
| `SUSPENSION` / `ACCESS_RESTRICTION` | Department Manager + System Admin |
| `OFFBOARDING` | HR/Administrative Manager or Department Manager + System Admin |
| `EMERGENCY` | Owner (self-approved + mandatory audit reason) |

---

### 11. `core_user_roles`

**Classification:** Transaction data (effective authorization assignments)

**Purpose:** **Only store of active staff role assignments.** Every grant traces to an applied request.

| Column | Type | Null | Notes |
|--------|------|------|-------|
| `user_role_id` | `BIGINT IDENTITY(1,1)` | NO | PK |
| `user_id` | `INT` | NO | FK |
| `role_id` | `INT` | NO | FK |
| `granted_by_request_id` | `BIGINT` | NO | FK → applied request |
| `effective_from` | `DATETIME2(3)` | NO | |
| `expires_at` | `DATETIME2(3)` | YES | Required if temporary |
| `revoked_at` | `DATETIME2(3)` | YES | Set on downgrade/offboarding |
| `revoked_by_request_id` | `BIGINT` | YES | FK |
| `is_temporary` | `BIT` | NO | Default 0 |
| `created_at` | `DATETIME2(3)` | NO | |

**Primary key:** `user_role_id`

**Foreign keys:**
- `user_id` → `core_users(user_id)`
- `role_id` → `core_roles(role_id)`
- `granted_by_request_id` → `core_access_requests(request_id)`
- `revoked_by_request_id` → `core_access_requests(request_id)`

**Indexes:**
- `IX_core_user_roles_user_active` on `user_id`, `effective_from`, `expires_at` WHERE `revoked_at IS NULL`
- `IX_core_user_roles_role` on `role_id` INCLUDE `user_id`
- `IX_core_user_roles_expiry` on `expires_at` WHERE `revoked_at IS NULL AND expires_at IS NOT NULL`
- `UX_core_user_roles_active_unique` UNIQUE on `user_id`, `role_id` WHERE `revoked_at IS NULL`

**Business rules:**
- Effective role: `revoked_at IS NULL` AND `effective_from <= now` AND (`expires_at IS NULL OR expires_at > now`) AND user `lifecycle_state = ACTIVE`.
- Inserts only from apply engine after request `APPLIED`.
- Revoke = set `revoked_at` + `revoked_by_request_id`; no physical delete.
- Expired temporary roles: batch job sets `revoked_at` and writes history + audit.

---

### 12. `core_access_suspensions`

**Classification:** Transaction data (access denial overlay)

**Purpose:** Temporary or open-ended suspension blocking access regardless of roles.

| Column | Type | Null | Notes |
|--------|------|------|-------|
| `suspension_id` | `BIGINT IDENTITY(1,1)` | NO | PK |
| `user_id` | `INT` | NO | FK |
| `request_id` | `BIGINT` | NO | FK |
| `scope_type` | `NVARCHAR(20)` | NO | `FULL`, `MODULE`, `LOGIN_ONLY` |
| `module_key` | `NVARCHAR(80)` | YES | Required when `scope_type = MODULE` |
| `reason` | `NVARCHAR(MAX)` | NO | |
| `starts_at` | `DATETIME2(3)` | NO | |
| `ends_at` | `DATETIME2(3)` | YES | NULL = indefinite until lifted |
| `lifted_at` | `DATETIME2(3)` | YES | |
| `lifted_by_request_id` | `BIGINT` | YES | FK |
| `created_at` | `DATETIME2(3)` | NO | |

**Primary key:** `suspension_id`

**Foreign keys:**
- `user_id` → `core_users(user_id)`
- `request_id` → `core_access_requests(request_id)`
- `lifted_by_request_id` → `core_access_requests(request_id)`

**Indexes:**
- `IX_core_access_suspensions_user_active` on `user_id`, `starts_at`, `ends_at` WHERE `lifted_at IS NULL`
- `IX_core_access_suspensions_scope` on `scope_type`, `module_key`

**Business rules:**
- Active suspension: `lifted_at IS NULL` AND `starts_at <= now` AND (`ends_at IS NULL OR ends_at > now`).
- Evaluated before role permissions in access resolution.
- Creation only via `SUSPENSION` or `EMERGENCY` applied request.
- Lifting requires new applied request.

---

### 13. `core_access_restrictions`

**Classification:** Transaction data (targeted denial overlay)

**Purpose:** Narrower than suspension — deny specific module or permission while other access remains.

| Column | Type | Null | Notes |
|--------|------|------|-------|
| `restriction_id` | `BIGINT IDENTITY(1,1)` | NO | PK |
| `user_id` | `INT` | NO | FK |
| `request_id` | `BIGINT` | NO | FK |
| `restriction_type` | `NVARCHAR(20)` | NO | `MODULE`, `PERMISSION` |
| `module_key` | `NVARCHAR(80)` | YES | Required if type = MODULE |
| `permission_key` | `NVARCHAR(120)` | YES | Required if type = PERMISSION |
| `incident_note` | `NVARCHAR(MAX)` | NO | Mistake/violation description |
| `starts_at` | `DATETIME2(3)` | NO | |
| `ends_at` | `DATETIME2(3)` | YES | |
| `lifted_at` | `DATETIME2(3)` | YES | |
| `lifted_by_request_id` | `BIGINT` | YES | FK |
| `created_at` | `DATETIME2(3)` | NO | |

**Primary key:** `restriction_id`

**Foreign keys:**
- `user_id` → `core_users(user_id)`
- `request_id` → `core_access_requests(request_id)`
- `lifted_by_request_id` → `core_access_requests(request_id)`

**Indexes:**
- `IX_core_access_restrictions_user_active` on `user_id` WHERE `lifted_at IS NULL`
- `IX_core_access_restrictions_module` on `module_key` WHERE `lifted_at IS NULL`
- `IX_core_access_restrictions_permission` on `permission_key` WHERE `lifted_at IS NULL`

**Business rules:**
- Created only via `ACCESS_RESTRICTION` applied request.
- Requires `incident_note`.
- User lifecycle may be `RESTRICTED` while restrictions active.
- Does not remove roles; overlays deny at evaluation time.

---

### 14. `core_access_change_history`

**Classification:** Transaction data (immutable ledger)

**Purpose:** Before/after snapshot of every applied access or lifecycle change.

| Column | Type | Null | Notes |
|--------|------|------|-------|
| `history_id` | `BIGINT IDENTITY(1,1)` | NO | PK |
| `user_id` | `INT` | NO | Subject user FK |
| `request_id` | `BIGINT` | NO | FK |
| `change_type` | `NVARCHAR(40)` | NO | e.g. `ROLE_GRANTED`, `ROLE_REVOKED`, `LIFECYCLE_CHANGED` |
| `entity_type` | `NVARCHAR(50)` | NO | `USER_ROLE`, `STAFF_PROFILE`, `SUSPENSION`, etc. |
| `entity_id` | `BIGINT` | YES | PK of affected row |
| `before_json` | `NVARCHAR(MAX)` | YES | `ISJSON` check when not null |
| `after_json` | `NVARCHAR(MAX)` | YES | `ISJSON` check when not null |
| `changed_by_user_id` | `INT` | YES | FK; NULL = system |
| `changed_at` | `DATETIME2(3)` | NO | |

**Primary key:** `history_id`

**Foreign keys:**
- `user_id` → `core_users(user_id)`
- `request_id` → `core_access_requests(request_id)`
- `changed_by_user_id` → `core_users(user_id)`

**Indexes:**
- `IX_core_access_change_history_user` on `user_id`, `changed_at DESC`
- `IX_core_access_change_history_request` on `request_id`
- `IX_core_access_change_history_type` on `change_type`, `changed_at DESC`

**Business rules:**
- INSERT only; no UPDATE/DELETE.
- One or more rows per applied request.
- Must capture lifecycle transitions and role grant/revoke snapshots.

---

### 15. `core_audit_logs`

**Classification:** Transaction data (security audit — append-only)

**Purpose:** Broader security event log: request state changes, approvals, login failures, denials, emergency overrides, legacy fallback usage.

| Column | Type | Null | Notes |
|--------|------|------|-------|
| `audit_id` | `BIGINT IDENTITY(1,1)` | NO | PK |
| `actor_user_id` | `INT` | YES | FK; NULL for anonymous/system |
| `action` | `NVARCHAR(80)` | NO | e.g. `REQUEST_SUBMITTED`, `ACCESS_DENIED`, `LOGIN_FAILED`, `EMERGENCY_OVERRIDE` |
| `entity_type` | `NVARCHAR(50)` | YES | |
| `entity_id` | `BIGINT` | YES | |
| `request_id` | `BIGINT` | YES | FK optional |
| `subject_user_id` | `INT` | YES | FK when action concerns another user |
| `details_json` | `NVARCHAR(MAX)` | YES | `ISJSON` check |
| `ip_address` | `NVARCHAR(45)` | YES | |
| `user_agent` | `NVARCHAR(500)` | YES | |
| `is_emergency` | `BIT` | NO | Default 0 |
| `created_at` | `DATETIME2(3)` | NO | |

**Primary key:** `audit_id`

**Foreign keys:**
- `actor_user_id` → `core_users(user_id)`
- `subject_user_id` → `core_users(user_id)`
- `request_id` → `core_access_requests(request_id)`

**Indexes:**
- `IX_core_audit_logs_created` on `created_at DESC`
- `IX_core_audit_logs_actor` on `actor_user_id`, `created_at DESC`
- `IX_core_audit_logs_action` on `action`, `created_at DESC`
- `IX_core_audit_logs_request` on `request_id`
- `IX_core_audit_logs_subject` on `subject_user_id`, `created_at DESC`

**Business rules:**
- INSERT only; no UPDATE/DELETE.
- Partitioning by month recommended when volume grows (post-V0).
- Every request state transition, approval, apply, suspension, expiry, and emergency action must log here.

---

## Migration order

| Step | Action |
|------|--------|
| 0 | Backup all source databases; complete audit sign-off per `DATABASE_CLEANUP_PLAN.md` |
| 1 | Create empty database `moghare360_ERP` with correct collation |
| 2 | Create master tables: `core_users` → `core_departments` → `core_roles` → `core_permissions` → `core_positions` → `core_role_permissions` → `core_staff_profiles` |
| 3 | Seed departments, positions, roles, permissions, role-permission matrix |
| 4 | Create workflow tables: `core_access_requests` → `core_access_request_items` → `core_access_approvals` |
| 5 | Create assignment/overlay tables: `core_user_roles`, `core_access_suspensions`, `core_access_restrictions` |
| 6 | Create immutable logs: `core_access_change_history`, `core_audit_logs` |
| 7 | Migrate identity from legacy `staff_users` → `core_users` + `core_staff_profiles` (preserve `user_id`, `lifecycle_state = ACTIVE`) |
| 8 | Synthesize `APPLIED` requests (`migration_source = LEGACY`) from `access_profiles` / `staff_user_access_profiles` → populate `core_user_roles` + history + audit |
| 9 | Seed Owner and System Admin users; verify approval matrix |
| 10 | Application cutover: portal reads/writes `moghare360_ERP` for staff access; legacy tables remain read-only in source DB |

**FK enforcement:** Add restrictive FKs after migration; use `NOCHECK` only during bulk load if needed, then re-enable.

---

## Seed data needed

### Organization
- 14 internal departments: Executive Management, Operations, Reception, CRM, Mechanical, Electrical and Options, Suspension and Undercarriage, Technical Management, Inventory, Purchase, Finance, HR, Marketing and Sales, System Administration
- Minimum 2 positions per department (Manager, Staff)

### Roles (Version 0 staff only)
- `owner`, `system_admin`, `general_manager`, `operations_manager`, `department_manager`, `reception_staff`, `inventory_staff`, `inventory_price_control`, `inbound_receipt`, `read_only`
- Do **not** seed any customer role

### Permissions
- Full staff catalog from legacy `access_permissions` (dashboard, customer module staff views, reception, inventory, purchase, sales_accounting, hr, admin, reports)
- Meta permissions: `access.request.create`, `access.request.approve`, `access.request.view_all`, `access.user.lifecycle`, `access.roles.manage`, `access.matrix.manage`, `access.audit.view`, `admin.users`

### Role-permission bundles
- Map legacy `access_profiles` to `core_roles` + `core_role_permissions` (documented mapping table external to DB)

### Bootstrap users
- At least one `is_system_owner = 1` user
- At least one `system_admin` role holder

### Approval rules
- Static matrix per `V0_ACCESS_LIFECYCLE_POLICY_FA.md` (implemented in application layer in V0)

### Synthetic migration data
- One `APPLIED` request per legacy user-profile assignment with `migration_source = LEGACY`

---

## Risks

| Risk | Mitigation |
|------|------------|
| Cross-database migration from MySQL portal legacy | ETL with ID preservation; validate row counts |
| Dual DB during bridge (portal MySQL + ERP SQL Server) | Clear read/write ownership; legacy fallback logged |
| `IDENTITY` collision on `core_users.user_id` | `IDENTITY_INSERT` with explicit IDs from `staff_users` |
| Audit table growth | `BIGINT` keys; monthly partitioning plan for `core_audit_logs` |
| Partial approval complexity | `item_decision` per line; apply only approved items |
| Expiry not enforced | Scheduled job on `core_user_roles.expires_at` + dashboard alerts |
| Persian collation issues | Test names/labels in staging |
| Accidental DDL on legacy DBs | All V0 DDL only in `moghare360_ERP` |
| Direct insert bypass | Restrict DB role permissions on `core_user_roles` to apply service account only |
| Customer access mixed into V0 | Enforce Customer Access Decision section below |

---

## Tables and databases that must not be touched

### Databases (no DROP, no destructive schema change)
- `moghare360` (legacy/archive)
- `moghare360_StockCenter`
- `moghare360D`
- Any production portal database holding customer/contract/inventory data

### Legacy tables (read-only for migration; no DROP/ALTER in V0)
- `staff_users`
- `access_profiles`
- `access_permissions`
- `access_profile_permissions`
- `staff_user_access_profiles`

### Portal / domain tables (out of V0 scope)
- `portal_customers_staging`, `portal_service_requests_staging`, `portal_contract_confirmations`
- `otp_verifications`, `vehicle_lookups`
- `portal_jobcards`, `portal_jobcard_status_history`
- `inventory_items_staging` and all StockCenter inventory tables

### Within `moghare360_ERP`
- No physical DELETE on `core_access_change_history` or `core_audit_logs`
- No `core_user_permissions` table
- No direct INSERT into `core_user_roles` outside apply/migration process

---

## Customer Access Decision
=======



Access resolution order





core_users.lifecycle_state ≠ ACTIVE → deny (except Owner policy, audited).



Active row in core_access_suspensions.



Active row in core_access_restrictions matching module/permission.



Owner flag (is_system_owner) — audited on sensitive actions.



Effective rows in core_user_roles joined to core_role_permissions → core_permissions.



Legacy fallback from portal access_* tables (logged in core_audit_logs; not stored in V0 core tables).



Deny.



Table definitions

1. core_users

Classification: Master data (identity)

Purpose: Canonical staff identity. Lifecycle state controls login eligibility. Source of truth for staff authentication in moghare360_ERP.







Column



Type



Null



Notes





user_id



INT



NO



Surrogate PK; preserve legacy staff_users.id on migration via IDENTITY_INSERT





username



NVARCHAR(80)



NO



Unique login name





password_hash



NVARCHAR(255)



NO



bcrypt/argon hash





full_name



NVARCHAR(160)



NO



Display name





email



NVARCHAR(255)



YES









mobile



NVARCHAR(30)



YES









lifecycle_state



NVARCHAR(30)



NO



DRAFT, PENDING_ONBOARDING, ACTIVE, PROMOTED, ACCESS_CHANGED, SUSPENDED, RESTRICTED, OFFBOARDING, INACTIVE





is_system_owner



BIT



NO



Default 0; Owner override policy





is_login_enabled



BIT



NO



Maintained on apply; false for DRAFT/INACTIVE/OFFBOARDING





legacy_staff_user_id



INT



YES



Bridge to portal staff_users.id during transition





last_login_at



DATETIME2(3)



YES









created_at



DATETIME2(3)



NO









updated_at



DATETIME2(3)



YES









created_by_user_id



INT



YES



Self-FK





updated_by_user_id



INT



YES



Self-FK





row_version



ROWVERSION



NO



Optimistic concurrency

Primary key: user_id

Foreign keys:





created_by_user_id → core_users(user_id)



updated_by_user_id → core_users(user_id)

Indexes:





UX_core_users_username UNIQUE on username



IX_core_users_lifecycle_state on lifecycle_state



IX_core_users_legacy_staff_user_id UNIQUE WHERE legacy_staff_user_id IS NOT NULL



IX_core_users_is_login_enabled on is_login_enabled INCLUDE lifecycle_state

Business rules:





Login allowed only when is_login_enabled = 1 AND lifecycle_state = 'ACTIVE' (Owner bypass in app layer only, always audited).



is_system_owner = 1 does not replace workflow; emergency actions still require audit.



No direct permission columns on this table.



Staff only — no customer accounts in this table in Version 0.



2. core_departments

Classification: Master data (organization)

Purpose: Organizational units. Scopes department-manager approval and positions catalog.







Column



Type



Null



Notes





department_id



INT IDENTITY(1,1)



NO



PK





dept_key



NVARCHAR(50)



NO



Stable slug, e.g. reception, inventory





dept_name



NVARCHAR(120)



NO



Persian display name





parent_department_id



INT



YES



Self-FK for hierarchy





manager_user_id



INT



YES



FK → core_users





is_active



BIT



NO



Default 1





sort_order



INT



NO



Default 100





created_at



DATETIME2(3)



NO









updated_at



DATETIME2(3)



YES





Primary key: department_id

Foreign keys:





parent_department_id → core_departments(department_id)



manager_user_id → core_users(user_id)

Indexes:





UX_core_departments_dept_key UNIQUE on dept_key



IX_core_departments_parent on parent_department_id



IX_core_departments_manager on manager_user_id



IX_core_departments_active_sort on is_active, sort_order

Business rules:





Seed 14 internal departments per org plan.



Deactivate, never hard-delete, if referenced.



Manager should belong to same department (app validation in V0).



3. core_positions

Classification: Master data (organization)

Purpose: Job titles within a department. May suggest roles; does not auto-grant access.







Column



Type



Null



Notes





position_id



INT IDENTITY(1,1)



NO



PK





department_id



INT



NO



FK





position_key



NVARCHAR(50)



NO



Unique within department





position_name



NVARCHAR(120)



NO









suggested_role_id



INT



YES



FK → core_roles; onboarding hint only





is_active



BIT



NO



Default 1





sort_order



INT



NO



Default 100





created_at



DATETIME2(3)



NO









updated_at



DATETIME2(3)



YES





Primary key: position_id

Foreign keys:





department_id → core_departments(department_id)



suggested_role_id → core_roles(role_id)

Indexes:





UX_core_positions_dept_key UNIQUE on department_id, position_key



IX_core_positions_department_active on department_id, is_active, sort_order

Business rules:





Position change does not auto-update core_user_roles; requires access request.



suggested_role_id is advisory for onboarding UI only.



4. core_roles

Classification: Master data (authorization)

Purpose: Named responsibility bundles and access levels. Only path to permissions for staff users.







Column



Type



Null



Notes





role_id



INT IDENTITY(1,1)



NO



PK





role_key



NVARCHAR(80)



NO



Unique slug





role_name



NVARCHAR(120)



NO









access_level



NVARCHAR(30)



NO



OWNER, GENERAL_MANAGER, OPERATIONS_MANAGER, DEPARTMENT_MANAGER, STAFF, READ_ONLY — see Customer Access Decision





description



NVARCHAR(500)



YES









is_active



BIT



NO



Default 1





sort_order



INT



NO



Default 100





created_at



DATETIME2(3)



NO









updated_at



DATETIME2(3)



YES





Primary key: role_id

Foreign keys: None

Indexes:





UX_core_roles_role_key UNIQUE on role_key



IX_core_roles_access_level on access_level, is_active

Business rules:





Editing role definition does not change user access until a new request is applied.



Deactivate role if assigned users exist; block delete.



System roles (owner, system_admin, etc.) non-deletable in app layer.



CUSTOMER access level reserved for future — not used in V0 seed data.



5. core_permissions

Classification: Master data (authorization catalog)

Purpose: Atomic capabilities (module.action). Assigned only to roles, never users.







Column



Type



Null



Notes





permission_id



INT IDENTITY(1,1)



NO



PK





permission_key



NVARCHAR(120)



NO



e.g. inventory.price, access.request.approve





module_key



NVARCHAR(80)



NO









action_key



NVARCHAR(80)



NO









permission_label



NVARCHAR(180)



NO



Persian label





sort_order



INT



NO



Default 100





is_active



BIT



NO



Default 1





created_at



DATETIME2(3)



NO





Primary key: permission_id

Foreign keys: None

Indexes:





UX_core_permissions_permission_key UNIQUE on permission_key



IX_core_permissions_module on module_key, sort_order, is_active

Business rules:





Permission keys immutable after seed.



Migrate keys from legacy access_permissions unchanged where possible.



Include meta-permissions for access module (access.request.create, access.request.approve, access.audit.view, etc.).



No customer portal permissions in V0 seed.



6. core_role_permissions

Classification: Master data (authorization mapping)

Purpose: Maps permissions to roles (role × permission matrix).







Column



Type



Null



Notes





role_id



INT



NO



FK





permission_id



INT



NO



FK





granted_at



DATETIME2(3)



NO









granted_by_user_id



INT



YES



FK → core_users

Primary key: (role_id, permission_id)

Foreign keys:





role_id → core_roles(role_id)



permission_id → core_permissions(permission_id)



granted_by_user_id → core_users(user_id)

Indexes:





IX_core_role_permissions_permission on permission_id INCLUDE role_id

Business rules:





Matrix changes audited in core_audit_logs.



No user_id column.



Sensitive permissions (admin.*, inventory.price) require explicit mapping review.



7. core_staff_profiles

Classification: Master data (HR / org profile)

Purpose: 1:1 staff organizational profile. Department and position assignment target on request apply.







Column



Type



Null



Notes





profile_id



INT IDENTITY(1,1)



NO



PK





user_id



INT



NO



FK, UNIQUE





department_id



INT



YES



FK; nullable during DRAFT





position_id



INT



YES



FK





employee_code



NVARCHAR(40)



YES



UNIQUE if present





hire_date



DATE



YES









exit_date



DATE



YES



Set on offboarding





profile_photo_path



NVARCHAR(500)



YES









notes



NVARCHAR(MAX)



YES









created_at



DATETIME2(3)



NO









updated_at



DATETIME2(3)



YES





Primary key: profile_id

Foreign keys:





user_id → core_users(user_id) ON DELETE RESTRICT



department_id → core_departments(department_id)



position_id → core_positions(position_id)

Indexes:





UX_core_staff_profiles_user_id UNIQUE on user_id



UX_core_staff_profiles_employee_code UNIQUE WHERE employee_code IS NOT NULL



IX_core_staff_profiles_department on department_id INCLUDE user_id, position_id

Business rules:





Exactly one profile per staff user.



Department/position changes via access request apply only (except DRAFT creation).



position_id must belong to department_id (app/trigger validation).



8. core_access_requests

Classification: Transaction data (workflow header)

Purpose: Formal access/lifecycle intent. Central workflow record from submit through apply.







Column



Type



Null



Notes





request_id



BIGINT IDENTITY(1,1)



NO



PK





request_number



NVARCHAR(30)



NO



Human-readable, UNIQUE





request_type



NVARCHAR(40)



NO



See business rules





request_state



NVARCHAR(30)



NO



DRAFT, SUBMITTED, UNDER_REVIEW, APPROVED, PARTIALLY_APPROVED, REJECTED, APPLIED, CANCELLED





priority



NVARCHAR(20)



NO



NORMAL, URGENT





subject_user_id



INT



NO



FK → user receiving change





requested_by_user_id



INT



NO



FK → submitter





justification



NVARCHAR(MAX)



NO



Required reason





owner_acknowledged



BIT



NO



Default 0; required if priority = URGENT





is_emergency



BIT



NO



Default 0; Owner-only





migration_source



NVARCHAR(30)



YES



e.g. LEGACY, LEGACY_PROFILE





submitted_at



DATETIME2(3)



YES









decided_at



DATETIME2(3)



YES









applied_at



DATETIME2(3)



YES









applied_by_user_id



INT



YES



FK; NULL = system auto-apply





cancelled_at



DATETIME2(3)



YES









cancelled_by_user_id



INT



YES



FK





created_at



DATETIME2(3)



NO









updated_at



DATETIME2(3)



YES









row_version



ROWVERSION



NO





Request types: ONBOARDING, ROLE_GRANT, TEMPORARY_ROLE_GRANT, DEPARTMENT_ASSIGN, POSITION_ASSIGN, PROMOTION, ACCESS_UPGRADE, ACCESS_DOWNGRADE, SUSPENSION, ACCESS_RESTRICTION, OFFBOARDING, EMERGENCY

Primary key: request_id

Foreign keys:





subject_user_id → core_users(user_id)



requested_by_user_id → core_users(user_id)



applied_by_user_id → core_users(user_id)



cancelled_by_user_id → core_users(user_id)

Indexes:





UX_core_access_requests_request_number UNIQUE on request_number



IX_core_access_requests_state_type on request_state, request_type, submitted_at DESC



IX_core_access_requests_subject on subject_user_id, created_at DESC



IX_core_access_requests_requester on requested_by_user_id, created_at DESC



IX_core_access_requests_pending on request_state WHERE request_state IN ('SUBMITTED','UNDER_REVIEW','APPROVED','PARTIALLY_APPROVED')

Business rules:





No row in core_user_roles without granted_by_request_id pointing to an APPLIED request (except migration-flagged).



TEMPORARY_ROLE_GRANT requires item-level expires_at.



URGENT requires owner_acknowledged = 1.



EMERGENCY only if requester is Owner.



State transitions append core_audit_logs on every change.



Offboarding blocks new grant requests for same subject while OFFBOARDING.



9. core_access_request_items

Classification: Transaction data (workflow lines)

Purpose: Line-level proposed changes: role grant/revoke, department, position, suspension/restriction parameters.







Column



Type



Null



Notes





item_id



BIGINT IDENTITY(1,1)



NO



PK





request_id



BIGINT



NO



FK





item_type



NVARCHAR(40)



NO



See business rules





role_id



INT



YES



FK when role item





department_id



INT



YES



FK





position_id



INT



YES



FK





module_key



NVARCHAR(80)



YES



For restriction/suspension scope





permission_key



NVARCHAR(120)



YES



For permission-level restriction





scope_type



NVARCHAR(20)



YES



FULL, MODULE, LOGIN_ONLY





effective_from



DATETIME2(3)



NO



Required for grants





expires_at



DATETIME2(3)



YES



Required for temporary grants





is_temporary



BIT



NO



Default 0





item_decision



NVARCHAR(20)



NO



PENDING, APPROVED, REJECTED





sort_order



INT



NO



Default 1





created_at



DATETIME2(3)



NO





Item types: ROLE_GRANT, ROLE_REVOKE, DEPARTMENT_SET, POSITION_SET, SUSPENSION_CREATE, RESTRICTION_CREATE, LIFECYCLE_STATE_SET

Primary key: item_id

Foreign keys:





request_id → core_access_requests(request_id)



role_id → core_roles(role_id)



department_id → core_departments(department_id)



position_id → core_positions(position_id)

Indexes:





IX_core_access_request_items_request on request_id, sort_order



IX_core_access_request_items_role on role_id WHERE role_id IS NOT NULL



IX_core_access_request_items_expiry on expires_at WHERE expires_at IS NOT NULL AND item_type = 'ROLE_GRANT'

Business rules:





ROLE_GRANT items must have role_id and effective_from.



TEMPORARY_ROLE_GRANT → is_temporary = 1 and expires_at NOT NULL.



PARTIALLY_APPROVED requests apply only items with item_decision = APPROVED.



No direct permission grant item type in V0.



10. core_access_approvals

Classification: Transaction data (approval decisions)

Purpose: Immutable approver decisions per request. Supports multi-approver matrix from policy.







Column



Type



Null



Notes





approval_id



BIGINT IDENTITY(1,1)



NO



PK





request_id



BIGINT



NO



FK





approver_user_id



INT



NO



FK





approver_capacity



NVARCHAR(40)



NO



DEPARTMENT_MANAGER, SYSTEM_ADMIN, OPERATIONS_MANAGER, OWNER





decision



NVARCHAR(20)



NO



APPROVED, REJECTED, PARTIAL





comment



NVARCHAR(MAX)



NO



Required written explanation





decided_at



DATETIME2(3)



NO









created_at



DATETIME2(3)



NO





Primary key: approval_id

Foreign keys:





request_id → core_access_requests(request_id)



approver_user_id → core_users(user_id)

Indexes:





IX_core_access_approvals_request on request_id, decided_at



IX_core_access_approvals_approver_pending on approver_user_id, decision INCLUDE request_id



UX_core_access_approvals_unique_capacity UNIQUE on request_id, approver_capacity

Business rules:





Approvals are append-only; no UPDATE/DELETE.



Department manager approves only if subject’s department matches approver’s managed department.



Minimum approvers per request_type enforced before state → APPROVED.



Rejection sets request to REJECTED; no apply.

Approval matrix (V0 static):







Request type



Minimum approvers





ONBOARDING



Department Manager + System Admin





ROLE_GRANT / TEMPORARY_ROLE_GRANT



Department Manager





ACCESS_UPGRADE



Department Manager + System Admin





ACCESS_DOWNGRADE



Department Manager





PROMOTION



Department Manager + Operations Manager





SUSPENSION / ACCESS_RESTRICTION



Department Manager + System Admin





OFFBOARDING



HR/Administrative Manager or Department Manager + System Admin





EMERGENCY



Owner (self-approved + mandatory audit reason)



11. core_user_roles

Classification: Transaction data (effective authorization assignments)

Purpose: Only store of active staff role assignments. Every grant traces to an applied request.







Column



Type



Null



Notes





user_role_id



BIGINT IDENTITY(1,1)



NO



PK





user_id



INT



NO



FK





role_id



INT



NO



FK





granted_by_request_id



BIGINT



NO



FK → applied request





effective_from



DATETIME2(3)



NO









expires_at



DATETIME2(3)



YES



Required if temporary





revoked_at



DATETIME2(3)



YES



Set on downgrade/offboarding





revoked_by_request_id



BIGINT



YES



FK





is_temporary



BIT



NO



Default 0





created_at



DATETIME2(3)



NO





Primary key: user_role_id

Foreign keys:





user_id → core_users(user_id)



role_id → core_roles(role_id)



granted_by_request_id → core_access_requests(request_id)



revoked_by_request_id → core_access_requests(request_id)

Indexes:





IX_core_user_roles_user_active on user_id, effective_from, expires_at WHERE revoked_at IS NULL



IX_core_user_roles_role on role_id INCLUDE user_id



IX_core_user_roles_expiry on expires_at WHERE revoked_at IS NULL AND expires_at IS NOT NULL



UX_core_user_roles_active_unique UNIQUE on user_id, role_id WHERE revoked_at IS NULL

Business rules:





Effective role: revoked_at IS NULL AND effective_from <= now AND (expires_at IS NULL OR expires_at > now) AND user lifecycle_state = ACTIVE.



Inserts only from apply engine after request APPLIED.



Revoke = set revoked_at + revoked_by_request_id; no physical delete.



Expired temporary roles: batch job sets revoked_at and writes history + audit.



12. core_access_suspensions

Classification: Transaction data (access denial overlay)

Purpose: Temporary or open-ended suspension blocking access regardless of roles.







Column



Type



Null



Notes





suspension_id



BIGINT IDENTITY(1,1)



NO



PK





user_id



INT



NO



FK





request_id



BIGINT



NO



FK





scope_type



NVARCHAR(20)



NO



FULL, MODULE, LOGIN_ONLY





module_key



NVARCHAR(80)



YES



Required when scope_type = MODULE





reason



NVARCHAR(MAX)



NO









starts_at



DATETIME2(3)



NO









ends_at



DATETIME2(3)



YES



NULL = indefinite until lifted





lifted_at



DATETIME2(3)



YES









lifted_by_request_id



BIGINT



YES



FK





created_at



DATETIME2(3)



NO





Primary key: suspension_id

Foreign keys:





user_id → core_users(user_id)



request_id → core_access_requests(request_id)



lifted_by_request_id → core_access_requests(request_id)

Indexes:





IX_core_access_suspensions_user_active on user_id, starts_at, ends_at WHERE lifted_at IS NULL



IX_core_access_suspensions_scope on scope_type, module_key

Business rules:





Active suspension: lifted_at IS NULL AND starts_at <= now AND (ends_at IS NULL OR ends_at > now).



Evaluated before role permissions in access resolution.



Creation only via SUSPENSION or EMERGENCY applied request.



Lifting requires new applied request.



13. core_access_restrictions

Classification: Transaction data (targeted denial overlay)

Purpose: Narrower than suspension — deny specific module or permission while other access remains.







Column



Type



Null



Notes





restriction_id



BIGINT IDENTITY(1,1)



NO



PK





user_id



INT



NO



FK





request_id



BIGINT



NO



FK





restriction_type



NVARCHAR(20)



NO



MODULE, PERMISSION





module_key



NVARCHAR(80)



YES



Required if type = MODULE





permission_key



NVARCHAR(120)



YES



Required if type = PERMISSION





incident_note



NVARCHAR(MAX)



NO



Mistake/violation description





starts_at



DATETIME2(3)



NO









ends_at



DATETIME2(3)



YES









lifted_at



DATETIME2(3)



YES









lifted_by_request_id



BIGINT



YES



FK





created_at



DATETIME2(3)



NO





Primary key: restriction_id

Foreign keys:





user_id → core_users(user_id)



request_id → core_access_requests(request_id)



lifted_by_request_id → core_access_requests(request_id)

Indexes:





IX_core_access_restrictions_user_active on user_id WHERE lifted_at IS NULL



IX_core_access_restrictions_module on module_key WHERE lifted_at IS NULL



IX_core_access_restrictions_permission on permission_key WHERE lifted_at IS NULL

Business rules:





Created only via ACCESS_RESTRICTION applied request.



Requires incident_note.



User lifecycle may be RESTRICTED while restrictions active.



Does not remove roles; overlays deny at evaluation time.



14. core_access_change_history

Classification: Transaction data (immutable ledger)

Purpose: Before/after snapshot of every applied access or lifecycle change.







Column



Type



Null



Notes





history_id



BIGINT IDENTITY(1,1)



NO



PK





user_id



INT



NO



Subject user FK





request_id



BIGINT



NO



FK





change_type



NVARCHAR(40)



NO



e.g. ROLE_GRANTED, ROLE_REVOKED, LIFECYCLE_CHANGED





entity_type



NVARCHAR(50)



NO



USER_ROLE, STAFF_PROFILE, SUSPENSION, etc.





entity_id



BIGINT



YES



PK of affected row





before_json



NVARCHAR(MAX)



YES



ISJSON check when not null





after_json



NVARCHAR(MAX)



YES



ISJSON check when not null





changed_by_user_id



INT



YES



FK; NULL = system





changed_at



DATETIME2(3)



NO





Primary key: history_id

Foreign keys:





user_id → core_users(user_id)



request_id → core_access_requests(request_id)



changed_by_user_id → core_users(user_id)

Indexes:





IX_core_access_change_history_user on user_id, changed_at DESC



IX_core_access_change_history_request on request_id



IX_core_access_change_history_type on change_type, changed_at DESC

Business rules:





INSERT only; no UPDATE/DELETE.



One or more rows per applied request.



Must capture lifecycle transitions and role grant/revoke snapshots.



15. core_audit_logs

Classification: Transaction data (security audit — append-only)

Purpose: Broader security event log: request state changes, approvals, login failures, denials, emergency overrides, legacy fallback usage.







Column



Type



Null



Notes





audit_id



BIGINT IDENTITY(1,1)



NO



PK





actor_user_id



INT



YES



FK; NULL for anonymous/system





action



NVARCHAR(80)



NO



e.g. REQUEST_SUBMITTED, ACCESS_DENIED, LOGIN_FAILED, EMERGENCY_OVERRIDE





entity_type



NVARCHAR(50)



YES









entity_id



BIGINT



YES









request_id



BIGINT



YES



FK optional





subject_user_id



INT



YES



FK when action concerns another user





details_json



NVARCHAR(MAX)



YES



ISJSON check





ip_address



NVARCHAR(45)



YES









user_agent



NVARCHAR(500)



YES









is_emergency



BIT



NO



Default 0





created_at



DATETIME2(3)



NO





Primary key: audit_id

Foreign keys:





actor_user_id → core_users(user_id)



subject_user_id → core_users(user_id)



request_id → core_access_requests(request_id)

Indexes:





IX_core_audit_logs_created on created_at DESC



IX_core_audit_logs_actor on actor_user_id, created_at DESC



IX_core_audit_logs_action on action, created_at DESC



IX_core_audit_logs_request on request_id



IX_core_audit_logs_subject on subject_user_id, created_at DESC

Business rules:





INSERT only; no UPDATE/DELETE.



Partitioning by month recommended when volume grows (post-V0).



Every request state transition, approval, apply, suspension, expiry, and emergency action must log here.



Migration order







Step



Action





0



Backup all source databases; complete audit sign-off per DATABASE_CLEANUP_PLAN.md





1



Create empty database moghare360_ERP with correct collation





2



Create master tables: core_users → core_departments → core_roles → core_permissions → core_positions → core_role_permissions → core_staff_profiles





3



Seed departments, positions, roles, permissions, role-permission matrix





4



Create workflow tables: core_access_requests → core_access_request_items → core_access_approvals





5



Create assignment/overlay tables: core_user_roles, core_access_suspensions, core_access_restrictions





6



Create immutable logs: core_access_change_history, core_audit_logs





7



Migrate identity from legacy staff_users → core_users + core_staff_profiles (preserve user_id, lifecycle_state = ACTIVE)





8



Synthesize APPLIED requests (migration_source = LEGACY) from access_profiles / staff_user_access_profiles → populate core_user_roles + history + audit





9



Seed Owner and System Admin users; verify approval matrix





10



Application cutover: portal reads/writes moghare360_ERP for staff access; legacy tables remain read-only in source DB

FK enforcement: Add restrictive FKs after migration; use NOCHECK only during bulk load if needed, then re-enable.



Seed data needed

Organization





14 internal departments: Executive Management, Operations, Reception, CRM, Mechanical, Electrical and Options, Suspension and Undercarriage, Technical Management, Inventory, Purchase, Finance, HR, Marketing and Sales, System Administration



Minimum 2 positions per department (Manager, Staff)

Roles (Version 0 staff only)





owner, system_admin, general_manager, operations_manager, department_manager, reception_staff, inventory_staff, inventory_price_control, inbound_receipt, read_only



Do not seed any customer role

Permissions





Full staff catalog from legacy access_permissions (dashboard, customer module staff views, reception, inventory, purchase, sales_accounting, hr, admin, reports)



Meta permissions: access.request.create, access.request.approve, access.request.view_all, access.user.lifecycle, access.roles.manage, access.matrix.manage, access.audit.view, admin.users

Role-permission bundles





Map legacy access_profiles to core_roles + core_role_permissions (documented mapping table external to DB)

Bootstrap users





At least one is_system_owner = 1 user



At least one system_admin role holder

Approval rules





Static matrix per V0_ACCESS_LIFECYCLE_POLICY_FA.md (implemented in application layer in V0)

Synthetic migration data





One APPLIED request per legacy user-profile assignment with migration_source = LEGACY



Risks







Risk



Mitigation





Cross-database migration from MySQL portal legacy



ETL with ID preservation; validate row counts





Dual DB during bridge (portal MySQL + ERP SQL Server)



Clear read/write ownership; legacy fallback logged





IDENTITY collision on core_users.user_id



IDENTITY_INSERT with explicit IDs from staff_users





Audit table growth



BIGINT keys; monthly partitioning plan for core_audit_logs





Partial approval complexity



item_decision per line; apply only approved items





Expiry not enforced



Scheduled job on core_user_roles.expires_at + dashboard alerts





Persian collation issues



Test names/labels in staging





Accidental DDL on legacy DBs



All V0 DDL only in moghare360_ERP





Direct insert bypass



Restrict DB role permissions on core_user_roles to apply service account only





Customer access mixed into V0



Enforce Customer Access Decision section below



Tables and databases that must not be touched

Databases (no DROP, no destructive schema change)





moghare360 (legacy/archive)



moghare360_StockCenter



moghare360D



Any production portal database holding customer/contract/inventory data

Legacy tables (read-only for migration; no DROP/ALTER in V0)





staff_users



access_profiles



access_permissions



access_profile_permissions



staff_user_access_profiles

Portal / domain tables (out of V0 scope)





portal_customers_staging, portal_service_requests_staging, portal_contract_confirmations



otp_verifications, vehicle_lookups



portal_jobcards, portal_jobcard_status_history



inventory_items_staging and all StockCenter inventory tables

Within moghare360_ERP





No physical DELETE on core_access_change_history or core_audit_logs



No core_user_permissions table



No direct INSERT into core_user_roles outside apply/migration process



Customer Access Decision
>>>>>>> e32f46d2d6146d3db627c100c7b042e5da7c12bd

Customer access is out of scope for Version 0.

Version 0 Access Lifecycle is only for internal staff users.

Customer access must be designed later as a separate Case-Based Customer Portal Access module.

Customer access is not role-based like staff access. It must be calculated based on:

<<<<<<< HEAD
- customer identity
- mobile OTP
- customer_id
- vehicle ownership or authorized contact
- service_request_id
- contract_id
- jobcard_id
- customer approval status
=======




customer identity



mobile OTP



customer_id



vehicle ownership or authorized contact



service_request_id



contract_id



jobcard_id



customer approval status
>>>>>>> e32f46d2d6146d3db627c100c7b042e5da7c12bd

No customer role, customer permission, or customer access workflow should be seeded or implemented in Version 0.

If CUSTOMER appears as an access level in the design, it is reserved for future use only and must not be used in Version 0 seed data.

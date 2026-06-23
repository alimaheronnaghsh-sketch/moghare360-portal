# MOGHARE360 ERP Core Foundation — Version 0 Revised Plan  
## Access Management & User Lifecycle Module

---

## 1. Strategic shift

**Previous framing (superseded):** Add normalized `core_*` tables and migrate legacy profiles with admin CRUD.

**Revised framing:** Version 0 establishes a **permanent, workflow-driven Access Management and User Lifecycle Management module**. Access is never a one-time configuration event. Every grant, change, restriction, or removal flows through defined lifecycle stages, requests, approvals, effective dates, and audit records.

**Core principle:** Users do **not** receive ad-hoc or direct permission assignments. They receive **roles** (and thus permissions) only through **controlled access requests** that are **reviewed and approved** by authorized actors. Direct admin overrides exist only for Owner/System Administration under explicit emergency policy, and are themselves audited.

**Alignment with architecture docs:**
- Role-based system (`ERP_MASTER_ARCHITECTURE.md` requirement #1)
- Anti-island, process-driven design (requirements #2, #5)
- Org structure from `ORG_ROLES_ACCESS_PLAN.md` (14 departments, 7 access levels)
- Database discipline from `DATABASE_CLEANUP_PLAN.md` (audit before prod schema; additive only; no drops)
- Deployment discipline from `PROJECT_STATUS.md` (staged rollout; no multi-core-file blast radius)

---

## 2. Version 0 scope boundary

### In scope
- Identity foundation (users)
- Organization foundation (departments, positions)
- Authorization model (roles, permissions, role-permission mapping)
- **Access request workflow** (request → review → approve/reject)
- **Lifecycle events** (onboard, promote, upgrade, downgrade, suspend, restrict, offboard)
- **Temporary and expiring access**
- **Access change history** and **audit logs**
- Admin UI for lifecycle and access operations
- Bridge from legacy `staff_users` / `access_*` without breaking existing portal pages

### Out of scope (later versions)
- JobCard / intake workflow permissions tied to case status
- Customer portal access model
- Inventory, finance, HR payroll modules
- Automated provisioning from external HR/attendance systems
- AI-driven access recommendations
- Removing legacy tables

---

## 3. Conceptual model

### 3.1 Permanent entities (master data)

| Concept | Purpose |
|---------|---------|
| **User** | Authenticated staff identity; lifecycle state drives what access they may hold |
| **Department** | Organizational unit; scopes managers and default position catalog |
| **Position** | Job title within a department; may imply suggested roles, not automatic grants |
| **Role** | Named bundle of responsibilities tied to an access level; **the only path to permissions** |
| **Permission** | Atomic capability (`module.action`); assigned **only to roles**, never directly to users |

### 3.2 Transactional / workflow entities (lifecycle)

| Concept | Purpose |
|---------|---------|
| **Access Request** | Formal intent to grant, change, extend, restrict, or revoke access |
| **Access Approval** | Decision record (approve / reject / partial) by an authorized approver |
| **Access Change History** | Immutable ledger of what changed, when, why, and under which request |
| **Access Suspension** | Active block on some or all access without deleting identity |
| **Access Expiry** | Time-bound end date on a role assignment or suspension lift |
| **Audit Log** | System-wide record of security-relevant actions (broader than access history) |

### 3.3 User lifecycle states

```
DRAFT → PENDING_ONBOARDING → ACTIVE → [PROMOTED | ACCESS_CHANGED | SUSPENDED | RESTRICTED] → OFFBOARDING → INACTIVE
```

| State | Meaning | Access behavior |
|-------|---------|-----------------|
| `DRAFT` | User record created, not yet submitted | No login |
| `PENDING_ONBOARDING` | Onboarding request in flight | No login until approved |
| `ACTIVE` | Normal employment | Effective approved roles only |
| `SUSPENDED` | Temporary full or partial lock | Overrides deny regardless of roles |
| `RESTRICTED` | Violation/mistake-based limitation | Scoped denial via restriction record |
| `OFFBOARDING` | Exit in progress | No new grants; revocations queued |
| `INACTIVE` | Employment ended | All roles revoked; login disabled |

---

## 4. Lifecycle workflows (Version 0 must implement)

Each workflow produces an **Access Request** (except automatic system actions like expiry), follows **approval rules**, writes **Access Change History**, and emits **Audit Log** entries.

### 4.1 New employee onboarding
1. HR or System Admin creates user in `DRAFT` with department, position, proposed start date.
2. Submitter opens **Access Request** type `ONBOARDING` with proposed role(s) (from position suggestions or manual selection within policy).
3. Approver chain: Department Manager → Operations Manager or System Admin (configurable per department in V0 as static rules).
4. On final approval: user → `ACTIVE`, roles effective from `effective_from`, login enabled, onboarding history recorded.

### 4.2 Role assignment (initial or additional)
- Request type: `ROLE_GRANT`
- Cannot assign permissions directly; only role IDs.
- Requires approval unless requester is Owner (still audited).
- Multiple roles allowed; conflicting roles flagged at review (V0: warning, not auto-block).

### 4.3 Department assignment
- Request type: `DEPARTMENT_ASSIGN` or bundled in `ONBOARDING` / `TRANSFER`
- Updates `core_staff_profiles.department_id` on approval.
- Does **not** auto-change roles; may trigger recommended follow-up request.

### 4.4 Position assignment
- Request type: `POSITION_ASSIGN`
- Updates position; may suggest role template for approver review.

### 4.5 Promotion
- Request type: `PROMOTION`
- Bundles: new position, optional department change, role upgrade (grant new + optional revoke old).
- Higher approval tier (e.g. Operations Manager or General Manager).

### 4.6 Access upgrade
- Request type: `ACCESS_UPGRADE`
- Adds role(s) with higher permission footprint; requires manager + admin reviewer for sensitive modules (`admin.*`, `inventory.price`, etc.).

### 4.7 Access downgrade
- Request type: `ACCESS_DOWNGRADE`
- Revokes role(s); may be initiated by manager, HR, or System Admin.
- Faster approval path; effective immediately or scheduled.

### 4.8 Temporary access
- Request type: `TEMPORARY_ROLE_GRANT`
- Mandatory `expires_at` on assignment.
- Auto-revoke job (V0: documented cron/manual procedure; scheduler optional stub).
- Visible in UI as “temporary” badge.

### 4.9 Suspension
- Request type: `SUSPENSION` (or emergency action by Owner with retroactive request)
- Creates **Access Suspension** record: scope (`FULL` | `MODULE` | `LOGIN_ONLY`), reason, `starts_at`, optional `ends_at`.
- While active: `accessHas()` returns false for scoped areas regardless of roles.

### 4.10 Employee exit / offboarding
- Request type: `OFFBOARDING`
- User → `OFFBOARDING` then `INACTIVE`; all role assignments end; sessions invalidated.
- Optional cooling period where account exists but login disabled.

### 4.11 Mistake or violation-based restriction
- Request type: `ACCESS_RESTRICTION`
- Narrower than suspension: specific permissions/modules denied via restriction overlay.
- Linked to incident note; requires Department Manager + System Admin approval.

### 4.12 Full audit trail
- Every request state change, approval, role effective date change, suspension, expiry, login deny, and emergency override → `core_audit_logs`.
- Every approved outcome → `core_access_change_history` row (before/after snapshot).

---

## 5. Access request workflow engine (Version 0)

### 5.1 Request states
```
DRAFT → SUBMITTED → UNDER_REVIEW → APPROVED | PARTIALLY_APPROVED | REJECTED → APPLIED | CANCELLED
```

### 5.2 Request payload (logical)
- `request_type` (enum of lifecycle types above)
- `subject_user_id`
- `requested_by_user_id`
- `proposed_roles[]` with `effective_from`, `expires_at` (nullable)
- `proposed_department_id`, `proposed_position_id` (when applicable)
- `justification` (required text)
- `priority` (normal / urgent — urgent requires Owner acknowledgment in V0)

### 5.3 Approval rules (V0 static matrix)

| Request type | Minimum approvers |
|--------------|-------------------|
| `ONBOARDING` | Dept Manager + System Admin |
| `ROLE_GRANT` / `TEMPORARY_ROLE_GRANT` | Dept Manager |
| `ACCESS_UPGRADE` | Dept Manager + System Admin |
| `ACCESS_DOWNGRADE` | Dept Manager |
| `PROMOTION` | Dept Manager + Operations Manager |
| `SUSPENSION` / `ACCESS_RESTRICTION` | Dept Manager + System Admin |
| `OFFBOARDING` | HR or Dept Manager + System Admin |
| Emergency (Owner only) | Self-approved + mandatory audit reason |

### 5.4 Application step
On `APPROVED` / `PARTIALLY_APPROVED`:
1. Write `core_user_roles` rows (or end-date existing for revocations).
2. Update staff profile (department, position, lifecycle state).
3. Create suspension/restriction records if applicable.
4. Append **Access Change History**.
5. Mark request `APPLIED` with `applied_at` and `applied_by` (system or admin).

**No direct SQL or UI path to insert into `core_user_roles` without an applied request** (except documented migration bridge and Owner emergency).

---

## 6. Data model (Version 0 tables)

### 6.1 Master data
| Table | Notes |
|-------|-------|
| `core_users` | Identity + `lifecycle_state`, `is_system_owner`, login fields |
| `core_departments` | 14 seeded departments |
| `core_positions` | FK to department |
| `core_roles` | `role_key`, `access_level`, active flag |
| `core_permissions` | Atomic keys; migrate from `access_permissions` |
| `core_role_permissions` | Role → permission only |
| `core_staff_profiles` | `user_id`, `department_id`, `position_id`, `employee_code`, hire/exit dates |

### 6.2 Workflow & lifecycle (new vs prior plan)
| Table | Notes |
|-------|-------|
| `core_access_requests` | Header: type, state, subject, requester, justification, timestamps |
| `core_access_request_items` | Line items: role grant/revoke, dept, position, effective/expiry dates |
| `core_access_approvals` | `request_id`, `approver_user_id`, `decision`, `comment`, `decided_at` |
| `core_user_roles` | **Effective assignments only**; columns: `granted_by_request_id`, `effective_from`, `expires_at`, `revoked_at`, `is_temporary` |
| `core_access_suspensions` | Active/historical suspensions with scope and reason |
| `core_access_restrictions` | Module/permission-level denials |
| `core_access_change_history` | Immutable: user, change_type, before_json, after_json, request_id |
| `core_audit_logs` | All security events |

### 6.3 Rules
- **No** `core_user_permissions` table in V0.
- **No** direct user → permission assignment in UI or migration (legacy read-only fallback only during bridge).

---

## 7. Access evaluation logic (runtime)

`accessHas($permissionKey)` resolution order:

1. User `lifecycle_state` not `ACTIVE` → deny (except Owner policy).
2. Active **suspension** covering scope → deny.
3. Active **restriction** for module/permission → deny.
4. Owner → allow (audited on sensitive actions).
5. Collect **effective** `core_user_roles` where `effective_from <= now` and (`expires_at` is null or `expires_at > now`) and `revoked_at` is null.
6. Union permissions from `core_role_permissions`.
7. If no core match, **legacy fallback** (temporary bridge from `access_profile_*`) — log fallback usage to audit.
8. Deny → optional audit entry for repeated denies (configurable).

Session carries: `user_id`, `lifecycle_state`, cached role keys (refreshed on apply or login).

---

## 8. Required SQL files (revised)

| Order | File | Purpose |
|-------|------|---------|
| 1 | `core_v0_01_master_tables.sql` | users, departments, positions, roles, permissions, role_permissions, staff_profiles |
| 2 | `core_v0_02_workflow_tables.sql` | access_requests, request_items, approvals, user_roles, suspensions, restrictions |
| 3 | `core_v0_03_history_audit_tables.sql` | access_change_history, audit_logs |
| 4 | `core_v0_04_seed_org.sql` | departments, positions |
| 5 | `core_v0_05_seed_roles_permissions.sql` | roles, permissions (from legacy keys), default role-permission bundles |
| 6 | `core_v0_06_seed_approval_rules.sql` | Static approver role requirements per request type |
| 7 | `core_v0_07_migrate_identity.sql` | staff_users → core_users + profiles (lifecycle = ACTIVE) |
| 8 | `core_v0_08_migrate_access_as_requests.sql` | Synthesize **closed/APPLIED** historical requests for legacy profile mappings → user_roles |
| 9 | `core_v0_09_run_all.sql` | Orchestrator |

**Migration note for #8:** Legacy assignments become archived `APPLIED` requests with `migration_source = legacy` so history is continuous, not a silent cutover.

---

## 9. Required PHP admin module (revised pages)

### 9.1 User Lifecycle Management
| Page | Function |
|------|----------|
| `staff-users.php` (enhance) | User list with lifecycle state; create draft user; no direct role checkboxes |
| `staff-user-onboard.php` | Onboarding wizard → creates `ONBOARDING` request |
| `staff-user-lifecycle.php` | Single-user timeline: state, requests, history, suspensions |
| `staff-user-offboard.php` | Offboarding request flow |
| `staff-user-save.php` (enhance) | Profile fields only; access changes via requests |

### 9.2 Access Management
| Page | Function |
|------|----------|
| `staff-access-requests.php` | Inbox: my requests, pending approvals, all (admin) |
| `staff-access-request.php` | Create/view request by type |
| `staff-access-request-save.php` | Submit, cancel, approve, reject, apply |
| `staff-access-approvals.php` | Approver queue with filters |
| `staff-roles.php` | Role CRUD (master data; changes do not affect users until new requests) |
| `staff-permissions.php` | Permission catalog; assign to roles only |
| `staff-access-matrix.php` | Role × permission matrix (admin) |
| `staff-departments.php` | Department CRUD |
| `staff-positions.php` | Position CRUD |
| `staff-access-suspensions.php` | Active suspensions/restrictions; lift via request |
| `staff-audit-logs.php` | Searchable audit viewer |
| `staff-access-history.php` | Per-user or per-request change history |

### 9.3 Shared module file
| File | Function |
|------|----------|
| `core-lifecycle-helpers.php` | State transitions, request validation, apply logic |
| `core-access-helpers.php` | Permission resolution, suspension/restriction checks |
| `access-control.php` (enhance) | Delegate to helpers; legacy fallback + audit |

### 9.4 Navigation
- `staff-dashboard.php`: sections **“درخواست‌های دسترسی”**, **“تأیید دسترسی”**, **“چرخه عمر پرسنل”** (gated by role).

---

## 10. Permissions for the access module itself (meta)

Seed permissions (assigned to System Admin / Owner roles only in V0):

| Key | Action |
|-----|--------|
| `access.request.create` | Submit requests for self or others (scoped) |
| `access.request.approve` | Approve/reject as delegated approver |
| `access.request.view_all` | See all requests |
| `access.user.lifecycle` | Onboard/offboard/suspend |
| `access.roles.manage` | Edit role definitions |
| `access.matrix.manage` | Edit role-permission matrix |
| `access.audit.view` | View audit and history |
| `admin.users` | User profile management (existing) |

Managers receive `access.request.approve` for their department scope only (V0: department_id match on subject user).

---

## 11. Migration and rollout order (revised)

### Phase A — Governance (no production writes)
1. Database audit sign-off (`DATABASE_CLEANUP_PLAN.md`).
2. Document lifecycle policies and approver matrix (Persian + internal English).
3. Staging DB + backups.

### Phase B — Schema (additive)
4. Run SQL 01 → 03 (master + workflow + audit).
5. Run seeds 04 → 06.

### Phase C — Historical continuity
6. Run identity migration (07).
7. Run legacy access migration as **synthetic applied requests** (08) — preserves “no random permissions” rule retroactively.

### Phase D — Application
8. Deploy helpers + `access-control.php` bridge.
9. Deploy workflow pages (requests, approvals, lifecycle).
10. Deploy master-data pages (roles, departments, positions, matrix).
11. Update `staff-auth.php`: refuse login for non-ACTIVE users; respect suspension.
12. Deprecate `staff-access-profiles.php` UI (redirect to requests/history); keep legacy tables read-only.

### Phase E — Validation
13. Scenario tests for all 12 lifecycle cases.
14. Verify no UI path grants `core_user_roles` without request.
15. Regression: customer contract + inventory unchanged.
16. Production upload only after manual approval.

---

## 12. Version 0 acceptance criteria

1. **Workflow mandatory:** Every new role grant/revoke in V0 UI goes through request → approval → apply.
2. **No direct permissions:** Users hold roles only; permissions resolved via roles.
3. **Lifecycle coverage:** Onboarding, promotion, upgrade, downgrade, temporary access, suspension, restriction, offboarding each have a request type and history.
4. **Expiry:** Temporary roles store `expires_at`; manual or scheduled revoke documented.
5. **Audit:** `core_audit_logs` and `core_access_change_history` populated for all apply operations.
6. **Legacy bridge:** Existing staff retain equivalent access via migrated applied requests; runtime fallback logged until removed.
7. **Continuous management:** Admin can open pending approvals and user lifecycle timeline without database access.

---

## 13. Risks (revised)

| Risk | Mitigation |
|------|------------|
| Workflow friction slows operations | Urgent path + downgrade fast-track; temporary access self-service with manager approval |
| Approver unavailable | Escalation rule to System Admin after N days (V0: manual escalation UI) |
| Dual legacy + workflow systems | Synthetic migration requests; single `accessHas()`; sunset plan documented |
| Emergency lockout needs | Owner emergency suspend with mandatory audit reason |
| Expiry not enforced | Daily check procedure + dashboard widget “expiring in 7 days” |
| Over-engineering V0 UI | Implement all 12 flows as request **types** sharing one engine, not 12 separate modules |
| ID preservation | Keep `staff_users.id` = `core_users.id` for inventory FKs |

---

## 14. Files that must not be touched (unchanged from prior plan)

**Customer / contract:** `index.php`, `customer-contract.php`, `customer-profile.php`, `customer-service-request.php`, `send-otp.php`, `verify-otp.php`, `assets/app.js`, `assets/style.css`, contract SQL patches, `docs/contract-flow-reference/*`

**Workflow domain (future):** `erp_jobcard_workflow_v1.sql`, portal/customer SQL patches, `vehicle_lookups.sql`, `otp_verifications.sql`

**Inventory module:** all `staff-inventory*.php`, inventory helpers, inventory SQL patches

**Secrets / runtime:** `config.php`, logs, `.bak` backups

**Legacy tables:** no DROP/ALTER on `staff_users`, `access_*` in V0

**Documentation:** read-only under `docs/`

---

## 15. Work packages (revised sequence)

| WP | Deliverable |
|----|-------------|
| WP1 | Policy doc: lifecycle states, request types, approver matrix |
| WP2 | SQL master + workflow + audit tables |
| WP3 | Seeds: org, roles, permissions, approval rules |
| WP4 | Identity + synthetic legacy request migration |
| WP5 | `core-lifecycle-helpers.php` + request apply engine |
| WP6 | `core-access-helpers.php` + `access-control.php` evaluation chain |
| WP7 | Access request + approval UI |
| WP8 | User lifecycle UI (onboard, offboard, suspend, history) |
| WP9 | Master data UI (roles, matrix, departments, positions) |
| WP10 | Auth gate + dashboard integration |
| WP11 | End-to-end scenario QA + production gate |

---

## 16. Summary

Version 0 is not an access **setup** phase. It is the foundation of a **continuous Access Management and User Lifecycle Management module** where organizational identity (user, department, position) and authorization (role, permission) stay linked through **access requests, approvals, effective dates, suspensions, expiries, and immutable history**. Random or direct permission grants are excluded by design; legacy access is absorbed as documented historical requests so the audit trail starts complete on day one.

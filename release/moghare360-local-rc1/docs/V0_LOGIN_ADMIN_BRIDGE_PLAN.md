# V0 Login / Admin Bridge Plan
# طرح پل ورود و ادمین — نسخه ۰ MOGHARE360 ERP

**Document type:** Implementation planning (no code changes in this phase)  
**Status:** Proposed — awaiting approval before any PHP implementation  
**Scope:** Bridge between existing cPanel portal login and `moghare360_ERP` core access tables

**Related documents:**
- `docs/V0_SQL_EXECUTION_STATUS.md`
- `docs/V0_BOOTSTRAP_EXECUTION_STATUS.md`
- `docs/PRODUCT_ARCHITECTURE_DECISION.md`
- `docs/V0_ACCESS_SQLSERVER_DESIGN_PROPOSAL.md`
- `docs/V0_ACCESS_LIFECYCLE_POLICY_FA.md`

---

## Executive summary / خلاصه

SQL Server foundation and Platform Owner bootstrap are **complete** in Development/Staging (`moghare360_ERP`, `user_id = 10001`).  
The live portal PHP stack still authenticates staff against **legacy MySQL-style tables** (`staff_users`, `access_*`), not `core_*`.

**Recommendation:** **Option A** — keep current portal login untouched; add a **read-only ERP diagnostic/validation page** first; then build a **separate ERP admin login path**; only after testing, consider migrating current staff login.

**Final rule:** No login replacement until a read-only bridge diagnostic is approved and tested.

---

## 1) Current state analysis / تحلیل وضعیت فعلی

### 1.1 PHP files related to staff login and admin access

| File | Exists in repo | Role |
|------|----------------|------|
| `public_html/config.php` | **No** (not in repository) | Server-local runtime config; expected to define `getPdo()`, session helpers, `requireStaffLogin()`, `isMasterAdmin()`, etc. |
| `public_html/config.example.php` | **Yes** | Template with `$db_host`, `$db_name`, `$db_user`, `$db_pass` — typical **MySQL/cPanel PDO** placeholders. |
| `public_html/staff-login.php` | **Yes** | Staff login form → POST to `staff-auth.php`. |
| `public_html/staff-auth.php` | **Yes** | **Primary staff authentication** — queries `staff_users`, `password_verify()`, sets `$_SESSION['staff_user']`. |
| `public_html/staff-dashboard.php` | **Yes** | Post-login dashboard; `requireStaffLogin()`; module gating via `meeting-helpers.php` (`role_name` text, `is_master_admin`). |
| `public_html/staff-users.php` | **Yes** | Master-admin user CRUD on `staff_users` (add/update/toggle). |
| `public_html/access-control.php` | **Yes** | Permission checks via legacy `staff_user_access_profiles` → `access_profiles` → `access_permissions`. |
| `public_html/staff-logout.php` | **Yes** | Clears `$_SESSION['staff_user']`. |

**Closely related (not in inspection list, but part of current access model):**

| File | Role |
|------|------|
| `public_html/staff-user-save.php` | INSERT/UPDATE `staff_users` (direct user creation — bypasses ERP workflow). |
| `public_html/staff-access-profiles.php` | UI for legacy access profiles. |
| `public_html/staff-access-save.php` | Saves legacy profile/permission assignments. |
| `public_html/meeting-helpers.php` | `meetingCanAccessStaffModule()` — **hardcoded role-name / username rules**, not `core_permissions`. |
| `public_html/admin-login.php` | Separate **config-file password** admin (`$adminPassword`), not ERP users. |

### 1.2 Does current portal login use legacy tables or ERP config?

**Confirmed from repository code:**

| Area | Evidence | Conclusion |
|------|----------|------------|
| Staff login | `staff-auth.php` SELECT from `staff_users` | **Legacy portal table** |
| Staff permissions | `access-control.php` joins `staff_user_access_profiles`, `access_profiles`, `access_permissions` | **Legacy access model** |
| Staff admin | `staff-users.php` / `staff-user-save.php` CRUD on `staff_users` | **Legacy portal table** |
| Password check | `password_verify()` on `staff_users.password_hash` | **PHP bcrypt/argon style** (typical MySQL portal) |
| ERP core | No PHP references to `core_users`, `core_user_roles`, `moghare360_ERP`, or `sqlsrv` | **No ERP bridge in PHP yet** |
| Config template | `config.example.php` uses generic MySQL-style `$db_*` variables | **Portal DB assumed MySQL/cPanel** |

**ERP SQL Server state (separate from portal PHP today):**

| Item | Status |
|------|--------|
| Database `moghare360_ERP` | Created and seeded (V0 foundation) |
| Bootstrap user `user_id = 10001` | Created with roles `owner`, `system_admin` |
| Portal PHP connection to `moghare360_ERP` | **Not implemented** |

### 1.3 Uncertainties (must be validated before implementation)

| # | Uncertainty | Why it matters |
|---|-------------|----------------|
| U1 | **Actual `config.php` on server** — DB host, driver, database name | Bridge needs a second connection block or confirmed network path to SQL Server. |
| U2 | **PHP SQL Server driver** (`sqlsrv` / `pdo_sqlsrv`) on cPanel vs local SQLEXPRESS | Without driver, ERP login cannot run on current hosting. |
| U3 | **Network reachability** — can cPanel PHP reach `SQLEXPRESS` on dev/staging host? | May require VPN, firewall rule, or on-prem bridge only. |
| U4 | **Password hash algorithm** in `core_users.password_hash` vs portal `staff_users` | Must confirm `password_verify()` compatibility (bootstrap likely uses PHP `password_hash()` — **assumed but not verified in PHP**). |
| U5 | **Whether production portal and ERP share any DB today** | Code suggests **two separate data stores** (MySQL portal + SQL Server ERP). |
| U6 | **`requireStaffLogin()` / `getPdo()` implementation** | Defined only in server-local `config.php`, not visible in repo. |

Mark all bridge work as **blocked on U1–U3** until the read-only diagnostic page is run in the target environment.

---

## 2) Target state / وضعیت هدف

Future staff/admin authentication and authorization for MOGHARE360 ERP Version 0 must use **`moghare360_ERP`** as the identity and access source of truth.

### 2.1 Authentication source

| Requirement | Table / column |
|-------------|----------------|
| Identity | `core_users` |
| Password | `core_users.password_hash` — verify with `password_verify()` (after hash-format confirmation) |
| Login eligibility | `lifecycle_state = 'ACTIVE'` **and** `is_login_enabled = 1` |
| Platform Owner | `user_id = 10001` (`mahin.paradigm.owner`) must be **visible and usable** after bridge |

### 2.2 Authorization source (role-based only)

Per `docs/V0_ACCESS_SQLSERVER_DESIGN_PROPOSAL.md` and `docs/V0_ACCESS_LIFECYCLE_POLICY_FA.md`:

```
core_user_roles (active rows)
    → core_roles
        → core_role_permissions
            → core_permissions
```

- **No** `core_user_permissions` in V0.
- **No** direct role assignment from PHP for normal users — only via applied access requests (bootstrap exception already recorded as `BOOTSTRAP-10001`).

### 2.3 Overrides (later phase, after basic login works)

Access resolution order from design proposal:

1. `lifecycle_state ≠ ACTIVE` → deny (Owner policy exceptions audited in app layer).
2. Active `core_access_suspensions` → deny or scope deny.
3. Active `core_access_restrictions` → deny module/permission.
4. Effective `core_user_roles` → permissions.
5. Legacy portal fallback (if ever needed) — **logged in `core_audit_logs`**, not stored in core.

Suspensions and restrictions are **not required for first bridge diagnostic** but must be wired before production ERP admin login.

### 2.4 V0 exclusions

| Excluded | Reason |
|----------|--------|
| Customer portal login (`customer-login.php`, OTP flow) | Out of V0; case-based future module |
| `CUSTOMER` role / customer permissions | `customer_role_count = 0` in seeds |
| Multi-tenant tables | Single-tenant execution per architecture decision |
| Moghareh Tenant Owner as DB entity | Conceptually separate from Platform Owner |

### 2.5 Conceptual separation (architecture)

| Concept | V0 representation |
|---------|-------------------|
| **Platform Owner** | `core_users.user_id = 10001`, role `owner` |
| **Moghareh Tenant Owner** | Future tenant-scoped responsibility — **not** merged into bootstrap user model |
| Same person, two hats | Allowed in pilot; **must not** collapse into one DB role without explicit policy |

---

## 3) Bridge options / گزینه‌های پل

### Option A — Keep current login + add read-only ERP validation (recommended first)

| Pros | Cons |
|------|------|
| Zero risk to existing portal staff workflow | ERP owner cannot use new permissions in old portal yet |
| Proves SQL Server connectivity and data | Two parallel auth systems temporarily |
| Validates bootstrap counts before any login change | Extra page to maintain short-term |

**Flow:** `staff-login.php` / `staff-auth.php` unchanged → new `erp-bootstrap-status.php` (read-only) confirms ERP state.

---

### Option B — New separate ERP admin login path

| Pros | Cons |
|------|------|
| Clean separation: portal vs ERP admin | Two login URLs, two sessions |
| Can target `core_users` only | Users may confuse which login to use |
| Safe pilot for Platform Owner (`10001`) | Requires new auth session namespace |

**Flow:** `erp-admin-login.php` → `erp-auth.php` → `erp-admin-dashboard.php` using `core_*` only.

---

### Option C — Replace current staff login with ERP core login

| Pros | Cons |
|------|------|
| Single login eventually | **Highest risk** — breaks portal if ERP/legacy mismatch |
| One permission model long-term | All `staff_users` must be migrated first |
| Aligns with policy (no direct role assignment) | `meeting-helpers.php` role-name logic must be rewritten |

**Not recommended until Options A + B are proven.**

---

## 4) Recommended approach for now / رویکرد پیشنهادی فعلی

**Recommend Option A first**, then Option B, then consider Option C only after explicit approval.

### Phase sequence

```
Phase 0 (now)     → This plan document only — no PHP changes
Phase 1 (next)    → Read-only erp-bootstrap-status.php (after approval)
Phase 2           → Separate ERP admin login (erp-admin-login.php / erp-auth.php)
Phase 3           → ERP access-control + admin UI (users read-only at first)
Phase 4 (later)   → Migrate staff-auth.php to core_users (only after migration strategy approved)
```

### Rationale (Persian/English)

- **پرتال فعلی را نشکنید** — Do not break current portal; operational staff may still depend on `staff_users`.
- **ابتدا اعتبارسنجی فقط-خواندنی** — Add read-only validation/admin status page to prove connection and bootstrap integrity.
- **ورود ERP جدا بسازید** — Build ERP admin login as a **separate path** for Platform Owner and future system admin.
- **مهاجرت بعد از تست** — Only after testing, plan migration of `staff-auth.php` to `core_users` with synthetic APPLIED requests per lifecycle policy.

---

## 5) Required future PHP files (proposed only — not created now)

These files are **planned names and responsibilities**. None exist yet; **do not create until Phase 1/2 approval.**

| Proposed file | Purpose |
|---------------|---------|
| `erp-admin-login.php` | Login form for ERP core users (separate from `staff-login.php`). |
| `erp-admin-dashboard.php` | Post-login landing for Platform Owner / system admin; read-only V0. |
| `erp-auth.php` | POST handler: authenticate against `core_users`, set ERP session (e.g. `$_SESSION['erp_user']`). |
| `erp-access-control.php` | `erpHas($permissionKey)`, `erpRequire()`, role/permission resolution from `core_*`. |
| `erp-admin-users.php` | List/search `core_users` and role assignments — **read-only in V0**; no create/update until workflow UI exists. |
| `erp-bootstrap-status.php` | **Phase 1 priority** — read-only diagnostics (connection, user 10001, roles, no customer roles). |

**Config extension (proposed, not in repo):**  
Add ERP SQL Server settings to a future `config.erp.example.php` or guarded section in `config.example.php` — e.g. `$erp_db_host`, `$erp_db_name = 'moghare360_ERP'`, `$erp_db_user`, `$erp_db_pass` — **never commit real credentials**.

---

## 6) Required future SQL / PHP behavior / رفتار مورد نیاز

When ERP login is implemented (Phase 2+), each authentication attempt must:

| Step | Rule |
|------|------|
| 1 | Load user from `core_users` by `username` (or approved identifier). |
| 2 | Verify `password_hash` with `password_verify($password, $hash)` — **after confirming hash format matches bootstrap**. |
| 3 | Reject if `lifecycle_state <> 'ACTIVE'`. |
| 4 | Reject if `is_login_enabled <> 1`. |
| 5 | Load **active** roles from `core_user_roles` (`revoked_at IS NULL`, effective date window per design). |
| 6 | Resolve permissions via `core_roles` → `core_role_permissions` → `core_permissions`. |
| 7 | **Deny CUSTOMER** access in V0 — no customer role in seeds; app layer must refuse customer-only permission keys if ever added. |
| 8 | (Phase 2b) Check `core_access_suspensions` and `core_access_restrictions` before granting access. |
| 9 | (Phase 3) Log login success/failure to `core_audit_logs` — append-only; no password or hash in log payload. |

**Session payload (proposed):** `user_id`, `username`, `full_name`, `is_system_owner`, role codes array, permission keys cache (short TTL) — **never** `password_hash`.

**Staff profile:** Join `core_staff_profiles` for display fields when needed; not required for first login test.

---

## 7) Security rules / قوانین امنیتی

| Rule | Detail |
|------|--------|
| Never commit real password or hash | Same as bootstrap execution status — repo placeholders only. |
| Do not expose `password_hash` in UI | Diagnostics may show user_id, username, roles — **not** hash column. |
| Do not bypass workflow for normal users | No INSERT into `core_user_roles` from PHP except documented bootstrap exception already in SQL. |
| Do not create staff users from PHP until approved | `staff-user-save.php` pattern must **not** be copied to ERP without access-request workflow. |
| Do not touch customer portal login in V0 bridge | `customer-login.php`, OTP, contract flows remain unchanged. |
| Separate sessions | ERP session (`erp_user`) vs portal session (`staff_user`) until unified migration is approved. |
| CSRF on all ERP POST forms | Match existing portal pattern (`csrfField()`, `checkCsrf()`). |
| Rate-limit / lockout | Plan for failed login audit (future); not blocking Phase 1 diagnostic. |
| Master-admin bypass | Legacy `isMasterAdmin()` full bypass must **not** be copied blindly — ERP Owner policy must be explicit and audited per design proposal. |

---

## 8) Risk list / فهرست ریسک‌ها

| ID | Risk | Impact | Mitigation |
|----|------|--------|------------|
| R1 | **Breaking current portal login** | Staff cannot work | Option A: no changes to `staff-auth.php` until Phase 4 approval |
| R2 | **Mixing MySQL portal with SQL Server ERP** | Wrong DB, split brain users | Two connection helpers: `getPdo()` (portal) vs `getErpPdo()` / `sqlsrv` (ERP); clear naming |
| R3 | **Password hash incompatibility** | Owner cannot log in | Verify bootstrap hash algorithm in SSMS/test script before ERP login |
| R4 | **Missing SQL Server PHP driver on hosting** | ERP bridge impossible on cPanel | Run diagnostic on target host; fallback: ERP admin only on dev VPN machine |
| R5 | **Confusing Platform Owner with Moghareh Tenant Owner** | Wrong governance model | Document UI labels; separate ERP admin from tenant ops modules |
| R6 | **Duplicated user management** | `staff-users.php` vs `erp-admin-users.php` conflict | ERP users read-only until workflow; communicate single source of truth migration plan |
| R7 | **Legacy `meeting-helpers.php` role strings** | Module access ignores `core_permissions` | Keep portal and ERP paths separate until Phase 4 rewrite |
| R8 | **Exposing diagnostic page publicly** | Information disclosure | Protect `erp-bootstrap-status.php` with IP allowlist, basic auth, or staff-only gate |
| R9 | **Audit gap** | No trace of login attempts | Add `core_audit_logs` writes in Phase 3 before production ERP login |

---

## 9) Next safe implementation step / گام امن بعدی

**Do not implement yet.** When approved, the **first** code change should be:

### Proposed: `erp-bootstrap-status.php` (read-only diagnostic)

**Purpose:** Confirm bridge prerequisites without changing any login behavior.

**Checks to display (no writes):**

| Check | Expected |
|-------|----------|
| Connection to `moghare360_ERP` | Success / error message (no credentials in output) |
| `core_users` where `user_id = 10001` | Row exists; show username, full_name, lifecycle_state, is_login_enabled, is_system_owner — **not** password_hash |
| Active roles for user 10001 | `owner`, `system_admin` (count = 2) |
| Customer roles in `core_roles` | None / count = 0 |
| Optional counts | `user_count = 1`, `assigned_role_count = 2`, `bootstrap_request_count = 1` (match bootstrap execution status) |

**Access control for diagnostic page:** Restrict to Platform Owner network or temporary shared secret — **not** public internet.

**Exit criteria before Phase 2:**

- [ ] Diagnostic runs on same machine/environment intended for ERP admin login  
- [ ] SQL Server driver confirmed  
- [ ] User 10001 and roles visible  
- [ ] No customer roles  
- [ ] Product owner approves proceeding to `erp-admin-login.php`

---

## 10) Final rule / قانون نهایی

> **No login replacement until a read-only bridge diagnostic is approved and tested.**  
> **تا زمانی که صفحه تشخیصی فقط-خواندنی پل تأیید و تست نشده، هیچ جایگزینی برای ورود فعلی انجام نشود.**

This means:

- Do **not** modify `staff-auth.php`, `staff-login.php`, or `access-control.php` in Phase 1.  
- Do **not** point `getPdo()` at `moghare360_ERP` without a dedicated ERP connection and migration plan.  
- Do **not** create users or assign roles from PHP in V0 bridge work.  
- Platform Owner validates ERP via diagnostic → separate ERP login → later migration.

---

## Appendix A — Current vs target mapping

| Concern | Current (portal) | Target (ERP V0) |
|---------|------------------|-----------------|
| User table | `staff_users` | `core_users` |
| User PK | `id` (auto) | `user_id` (manual / reserved) |
| Role model | `role_name` text + `is_master_admin` | `core_user_roles` → `core_roles` |
| Permissions | `access_profiles` / `access_permissions` | `core_role_permissions` → `core_permissions` |
| Active flag | `is_active` | `lifecycle_state`, `is_login_enabled` |
| User creation | `staff-user-save.php` direct INSERT | Access request workflow → APPLIED |
| Session key | `$_SESSION['staff_user']` | `$_SESSION['erp_user']` (proposed) |
| DB engine | MySQL (inferred) | SQL Server `moghare360_ERP` |

---

## Appendix B — Bootstrap reference (post-bridge target user)

From `docs/V0_BOOTSTRAP_EXECUTION_STATUS.md`:

| Field | Value |
|-------|-------|
| user_id | 10001 |
| username | mahin.paradigm.owner |
| lifecycle_state | ACTIVE |
| is_system_owner | 1 |
| is_login_enabled | 1 |
| Roles | owner, system_admin |
| Bootstrap request | BOOTSTRAP-10001 (EMERGENCY / APPLIED) |

This user must be the **first successful ERP admin login** after Phase 2.

---

*End of V0 Login / Admin Bridge Plan.*

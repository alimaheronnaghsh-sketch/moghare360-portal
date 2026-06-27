# MOGHARE360 V1 — Production User Access Plan

## Scope

Defines how **real staff users** enter V1 after database lock. This is operational preparation only — no new product modules or UI redesign.

## Identity model (V1)

| Layer | Table | Purpose |
|-------|-------|---------|
| Login identity | `dbo.core_users` | username, `password_hash` (bcrypt), lifecycle |
| Tenant binding | `dbo.erp_company_users` | `company_id`, `role_code`, active flag |
| Permission foundation | `dbo.core_roles` | seeded role keys (reference for mapping) |

Staff API login: `POST /api/auth/staff-login` joins `core_users` + `erp_company_users` for the resolved tenant.

## Roles covered in production template

| `role_code` (private JSON) | `erp_company_users.role_code` | `core_roles.role_key` (verify mapping) | Typical use |
|----------------------------|-------------------------------|----------------------------------------|-------------|
| `OWNER` | OWNER | `owner` | Company owner / emergency oversight |
| `SYSTEM_ADMIN` | SYSTEM_ADMIN | `system_admin` | Technical admin |
| `RECEPTION` | RECEPTION | `reception_staff` | Front desk intake |
| `TECHNICIAN` | TECHNICIAN | `mechanical_staff` | Workshop operations |
| `INVENTORY` | INVENTORY | `inventory_staff` | Parts / stock |
| `FINANCE` | FINANCE | `finance_staff` | Payments / finance views |
| `QC` | QC | `technical_manager` | QC / technical sign-off |
| `CRM` | CRM | `crm_staff` | Customer follow-up |
| `COMPANY_OWNER_VIEWER` | COMPANY_OWNER_VIEWER | `read_only` | Owner read-only dashboard |

> Platform bootstrap user `user_id = 10001` (V0 script) is separate from production tenant users (`20001+` range in template).

## Private user file structure

Template: `private/templates/production-users.template.json`  
Runtime: `private/production-users.json` (**gitignored**)

Per user:

| Field | Required | Notes |
|-------|----------|-------|
| `user_id` | yes | Use `20001+` for production staff; do not collide with `10001` |
| `username` | yes | Unique login |
| `display_name` | yes | Shown in UI |
| `mobile_optional` | no | Store only in private file |
| `role_code` | yes | One of allowed codes above |
| `company_code` | yes | Default `MOGHAREH_MAIN` |
| `is_login_enabled` | yes | `false` until ready |
| `temporary_password_or_hash_placeholder` | yes | bcrypt hash **or** one-time plain password in private file only |

## Import procedure

```powershell
# On production host only
Copy-Item private\templates\production-users.template.json private\production-users.json
# Edit private\production-users.json (never commit)

.\tools\production\CREATE_PRODUCTION_USERS_FROM_PRIVATE_JSON.ps1
```

### Import behaviour

- **Idempotent upsert** on `username` / `user_id`
- Updates `core_users` + `erp_company_users`
- Verifies `company_code` and `core_roles` mapping
- **Never logs** plain passwords
- Writes summary to `runtime/PRODUCTION_USERS_IMPORT_REPORT.md`

### Password rules

1. Prefer generating bcrypt on server:
   ```powershell
   C:\xampp\php\php.exe -r "echo password_hash('ONE_TIME_PASSWORD', PASSWORD_BCRYPT);"
   ```
   Paste hash into private JSON only.
2. Alternatively put a one-time plain value in private JSON; script hashes via PHP stdin (not echoed).
3. Rotate password after first login when operational policy requires.

## Access request path (self-service staff)

Public staff without pre-provisioned accounts may use:

- Mirror / master: access request flow → `erp_user_access_requests`
- Admin review: existing ERP access request workflow

Pre-provisioned production users from JSON bypass pending access request for first login.

## Security boundaries

- Templates: placeholder usernames only (`*.placeholder`)
- No real mobile/national ID in Git
- No password/hash in import report
- No MySQL `staff_users` table for V1 SaaS login

## Verification

```powershell
C:\xampp\php\php.exe tools\test-v1-real-run-readiness.php
.\tools\production\VERIFY_PRODUCTION_RUNTIME_CONFIG.ps1
```

Test staff login (after import):

```http
POST /api/auth/staff-login
Content-Type: application/json

{"username":"<from-private-file>","password":"<one-time-password>"}
```

## Rollback

- Disable login: set `is_login_enabled: false` in private JSON and re-import, or `UPDATE core_users SET is_login_enabled = 0`.
- No DROP of user tables.

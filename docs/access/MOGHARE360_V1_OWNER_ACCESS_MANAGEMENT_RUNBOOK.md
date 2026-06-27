# MOGHARE360 V1 — Owner Access Management Runbook

## Before one-day live run

1. Log in as platform owner (`owner-login.php` → API owner session).
2. Open **`erp-access-management.php`**.
3. Review **One-Day Run readiness** (PASS / WARNING / BLOCKED).
4. Create staff users for each operational role (RECEPTION, TECHNICIAN, etc.).
5. Enable login only when ready (`is_login_enabled` + `lifecycle_state=ACTIVE`).
6. Use **permission preview** per user before go-live.
7. Reset temporary passwords once; share verbally — never commit passwords.

## Create staff (UI)

1. `erp-access-user-create.php`
2. Fill username, display name, department, position, role_code, temporary password (≥8 chars).
3. Submit — creates `core_users`, `core_user_roles`, `erp_company_users`, `core_staff_profiles`, audit/history.

## Fallback import (not primary)

```powershell
Copy-Item private\templates\production-users.template.json private\production-users.json
# edit private file only
.\tools\production\CREATE_PRODUCTION_USERS_FROM_PRIVATE_JSON.ps1
```

Use when bulk bootstrap is faster; **UI remains canonical** for ongoing operations.

## Owner shared-login risk

If readiness shows **WARNING** on owner shared login: no login-enabled staff exist — stop using owner login for daily staff work.

## Security

- No password hashes in UI or Git
- No permission matrix edits
- No legacy MySQL staff path for V1 SaaS

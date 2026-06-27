# MOGHARE360 V1 — Access Management UI Plan

## Purpose

P11.4 delivers an **owner/admin UI** for professional staff access on the existing SQL Server identity model. JSON import (`private/production-users.json`) remains **bootstrap / bulk / emergency fallback only**.

## Primary path

| Action | UI page |
|--------|---------|
| Staff list & readiness | `erp-access-management.php` |
| Create staff | `erp-access-user-create.php` |
| Edit profile / login flags | `erp-access-user-edit.php` |
| Assign / revoke roles | `erp-access-role-assign.php` |
| Reset temporary password | `erp-access-password-reset.php` |
| Permission preview | `erp-access-permission-preview.php` |
| Access history | `erp-access-change-history.php` |

## Reused architecture (no new Auth)

- `includes/erp-auth-context.php` — session + DB permission resolution
- `includes/erp-permission-guard.php` — action guard reference
- `dbo.core_users`, `dbo.core_user_roles`, `dbo.core_roles`, `dbo.core_permissions`, `dbo.core_role_permissions`
- `dbo.core_staff_profiles`, `dbo.erp_company_users`
- `dbo.core_access_requests`, `dbo.core_access_change_history`, `dbo.core_audit_logs`

## Forbidden

- Changes to `staff-login.php`, `owner-login.php`, `access-control.php`, API auth endpoints
- Permission seed edits from UI
- Legacy MySQL `staff_users`
- Raw SQL editor
- New Auth/Login stack

## Role code mapping (UI → core_roles.role_key)

| UI role_code | core role_key |
|--------------|---------------|
| OWNER | owner |
| SYSTEM_ADMIN | system_admin |
| RECEPTION | reception_staff |
| SERVICE_MANAGER | operations_manager |
| TECHNICIAN | mechanical_staff |
| PARTS | inventory_staff |
| FINANCE | finance_staff |
| QC | technical_manager |

## Gaps documented

- `force-change-password` column not in schema — recommend manual rotation after first login
- Navigation registry has no per-route `permission_key` — preview uses heuristic + warnings

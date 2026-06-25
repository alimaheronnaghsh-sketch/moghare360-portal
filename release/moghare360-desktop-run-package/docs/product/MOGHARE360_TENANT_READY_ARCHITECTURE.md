# MOGHARE360 Tenant-Ready Architecture

## Design Principles

- Single-database Soft Run today; tenant_id column strategy documented for future
- Auth/permission files remain unchanged until approved migration project
- Commercial tables (`erp_commercial_*`) are metadata only — not tenant runtime

## Why Tenant Is Not Active Now

- Soft Run validates single-workshop operations first
- Auth and permission models must not be rewritten without governance
- Real SaaS requires billing, provisioning, and security review

## Do NOT Change Yet

- `staff-auth.php`, `access-control.php`, `staff-login.php`
- `config.php`, `private/*`
- Core permission seed tables without migration plan
- Legacy customer portal files

## Future Approval Gates

1. Architecture sign-off
2. Security review
3. Pilot single tenant
4. Multi-tenant schema migration (non-destructive)
5. Billing integration (separate project)

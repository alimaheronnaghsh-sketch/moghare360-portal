# MOGHARE360 V1 — SaaS Activation Plan

## Goal
Activate SaaS foundation for MOGHARE360 V1 Production Release on Master Server.

## Components
1. `erp_companies` tenant registry
2. `erp_company_domains` domain mapping
3. `erp_company_users` user-to-tenant mapping
4. API request logging and mirror intake tables
5. Hosted storage path via `moghare360-saas-storage-adapter.php`
6. Master API endpoints under `public_html/api/`

## Activation Steps
1. Run `public_html/sql/sqlserver/v1_saas_activation_foundation.sql` on hosted DB
2. Generate `private/erp-config.php` with `CREATE_LOCAL_CONFIG.ps1`
3. Set `saas.enabled = true` and `storage_root` on server
4. Deploy mirror package to moghareh360.ir with `MASTER_SERVER_BASE_URL`
5. Verify `saas-health.php` and `/api/mirror/health.php`

## Security
- No credentials in ZIP/GitHub
- Secrets only in server private config / environment
- Tenant isolation via `company_id` on all SaaS tables

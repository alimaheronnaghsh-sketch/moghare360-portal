# MOGHARE360 V1 — SaaS Security Boundary

## Allowed on SaaS Server
- Hosted SQL Server database
- Hosted file storage under configured `storage_root`
- Private `erp-config.php` with credentials
- API sessions at runtime (not in ZIP)

## Forbidden in ZIP / GitHub
- credentials
- private config
- real customer data
- vehicle photos / diagnostic PDFs / HR documents
- database backups
- logs with personal data

## Tenant Isolation
- Every API request resolves `company_id`
- Cross-tenant read/write blocked in application layer
- Default company `MOGHAREH_MAIN` for single-tenant V1

## Mirror Site
- No database on mirror host
- No business data cache in service worker
- No sensitive tokens in localStorage
- Static assets only in PWA cache

## CORS
- Mirror origins documented in `saas.mirror_allowed_origins`
- Write routes support optional CSRF token

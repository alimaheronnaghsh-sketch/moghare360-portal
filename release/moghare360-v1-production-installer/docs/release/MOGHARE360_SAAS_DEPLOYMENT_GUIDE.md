# MOGHARE360 V1 — SaaS Deployment Guide

## Master Server (Production Backend)
1. Install with `tools/production/INSTALL_MOGHARE360_V1.ps1`
2. Or full auto deploy: `tools/production/AUTO_DEPLOY_MOGHARE360_V1.ps1`
3. Create config: `tools/desktop-run-templates/CREATE_LOCAL_CONFIG.ps1`
4. Apply SQL: `v1_saas_activation_foundation.sql`
5. Verify:
   - `http://localhost:8080/moghare360/saas-health.php`
   - `http://localhost:8080/moghare360/api/mirror/health.php`

## Mirror Website (moghareh360.ir)
1. Build: `tools/package-moghare360-mirror-site.ps1`
2. Upload `release/moghare360-mirror-site-package/public_html/`
3. Copy `mirror-config.example.php` → `mirror-config.php`
4. Set `MASTER_SERVER_BASE_URL` to production Master API base
5. Verify `mirror-health.php` and PWA install

## Packages
| Package | Script |
|---------|--------|
| Production Installer | `package-moghare360-v1-production-installer.ps1` |
| Auto Deploy | `package-moghare360-v1-auto-deploy.ps1` |
| SaaS Deploy | `package-moghare360-v1-saas-deploy.ps1` |
| Final Delivery | `package-moghare360-v1-production-final-delivery.ps1` |

## External Prerequisites
- Production DB credentials on server
- SSL on production domain
- Storage path writable on server
- Environment-specific private config

# MOGHARE360 V1 — Production Package Index

**Status:** `OWNER_GATE_PRODUCTION_DEPLOYMENT_REQUIRED`  
**Host target:** Windows VPS + IIS + SQL Server (not XAMPP)  
**Database:** single writable `moghare360_ERP`

## Documents

| Doc | Path |
|-----|------|
| Runbook | `docs/release/v1/DEPLOYMENT_RUNBOOK_FA.md` |
| Prerequisites | `docs/release/v1/PRODUCTION_PREREQUISITES_FA.md` |
| Rollback | `docs/release/v1/PRODUCTION_ROLLBACK_FA.md` |
| Health check | `docs/release/v1/PRODUCTION_HEALTH_CHECK_FA.md` |
| Backup checklist | `docs/release/v1/PRODUCTION_BACKUP_CHECKLIST_FA.md` |
| Security review | `docs/release/v1/PRODUCTION_SECURITY_REVIEW_FA.md` |
| Deployment manifest template | `docs/release/v1/DEPLOYMENT_MANIFEST_TEMPLATE.md` |

## Scripts / templates (placeholders only)

| Item | Path |
|------|------|
| IIS notes | `deployment/iis/README.md` |
| IIS security sample | `deployment/iis/web.config.security.example.xml` |
| SQL migration order | `deployment/sql/MIGRATION_ORDER.md` |
| DB backup | `tools/production/backup-db.template.ps1` |
| DB restore | `tools/production/restore-db.template.ps1` |
| Health check | `tools/production/health-check.template.ps1` |
| Post-deploy smoke | `tools/production/post-deploy-smoke.template.ps1` |
| SHA256 release manifest | `tools/production/sha256-release-manifest.template.ps1` |
| Config example | `private/erp-config.example.php` |
| Asset links example | `public_html/.well-known/assetlinks.json.example` |

## Owner inputs still required (do not paste secrets in chat)

1. Production domain/hostname  
2. Windows VPS access method  
3. IIS availability confirmation  
4. SQL Server host + `moghare360_ERP` readiness  
5. HTTPS certificate status  
6. Maintenance window  
7. Offsite backup destination  
8. Rollback authority  
9. Deployment notification recipients  

Credentials must be entered only in the secure deployment environment.

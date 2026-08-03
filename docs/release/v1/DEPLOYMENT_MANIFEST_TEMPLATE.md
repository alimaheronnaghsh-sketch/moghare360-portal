# Deployment Manifest Template — MOGHARE360 V1 (Phase 7)

**Status:** TEMPLATE — copy per release; fill PLACEHOLDER_* only (no real secrets).  
**Related:** `DEPLOYMENT_RUNBOOK_FA.md`, `tools/production/sha256-release-manifest.template.ps1`

---

## Release identity

| Field | Value |
|-------|--------|
| Product | MOGHARE360 V1 |
| Phase gate | Phase 7 — Production Preparation |
| Release tag | PLACEHOLDER_RELEASE_TAG |
| Git commit SHA | PLACEHOLDER_GIT_COMMIT_SHA |
| Built at (UTC) | PLACEHOLDER_BUILD_UTC |
| Built by | PLACEHOLDER_BUILD_OPERATOR |
| Target FQDN | PLACEHOLDER_PROD_FQDN |
| IIS site | PLACEHOLDER_IIS_SITE_NAME |
| Database | `moghare360_ERP` @ PLACEHOLDER_SQL_INSTANCE |
| Schema version / migration watermark | PLACEHOLDER_SCHEMA_VERSION |

---

## Architecture confirmation

| Check | Confirmed |
|-------|-----------|
| Host = Windows VPS + IIS (not XAMPP Prod) | ☐ |
| Single writable DB = `moghare360_ERP` | ☐ |
| HTTPS only for public ERP | ☐ |
| Private config outside web root | ☐ |
| Offsite backup destination defined | ☐ |
| No dual-writable DB topology | ☐ |

---

## Artifact inventory

List relative paths included in this release package. Compute SHA256 with the template script.

| Relative path | Bytes | SHA256 |
|---------------|-------|--------|
| PLACEHOLDER_ARTIFACT_PATH_1 | PLACEHOLDER_BYTES_1 | PLACEHOLDER_SHA256_1 |
| PLACEHOLDER_ARTIFACT_PATH_2 | PLACEHOLDER_BYTES_2 | PLACEHOLDER_SHA256_2 |
| PLACEHOLDER_ARTIFACT_PATH_3 | PLACEHOLDER_BYTES_3 | PLACEHOLDER_SHA256_3 |

**Manifest aggregate hash (optional):** PLACEHOLDER_MANIFEST_AGGREGATE_SHA256

```powershell
pwsh -File PLACEHOLDER_TOOLS_PATH\sha256-release-manifest.ps1 `
  -ReleaseRoot 'PLACEHOLDER_RELEASE_ROOT' `
  -OutFile 'PLACEHOLDER_MANIFEST_OUT_PATH'
```

---

## Explicit exclusions (must NOT be in web root / public artifact)

- [ ] Real `erp-config.php` / secrets
- [ ] Database `.bak` / dumps
- [ ] Signed APK / AAB / keystore
- [ ] Local XAMPP runtime trees
- [ ] Quarantine contents as public URLs
- [ ] `tools/` executable via anonymous HTTP

---

## Pre-deploy backups

| Type | Backup ID | Location token | Verified |
|------|-----------|----------------|----------|
| DB `moghare360_ERP` | PLACEHOLDER_DB_BACKUP_ID | PLACEHOLDER_BACKUP_LOCAL_PATH / OFFSITE | ☐ |
| Files `public_html` | PLACEHOLDER_FILE_BACKUP_ID | PLACEHOLDER_FILE_BACKUP_PATH | ☐ |
| Private config | PLACEHOLDER_CONFIG_BACKUP_ID | PLACEHOLDER_CONFIG_BACKUP_PATH | ☐ |

---

## Migration notes

- Order document: `deployment/sql/MIGRATION_ORDER.md`
- Scripts applied this release: PLACEHOLDER_MIGRATION_SCRIPT_LIST
- Operator: PLACEHOLDER_DBA_OPERATOR
- Result: ☐ OK · ☐ SKIPPED (no schema change) · ☐ FAILED → rollback

---

## Post-deploy verification

| Gate | Result | Ref |
|------|--------|-----|
| Health check | ☐ PASS · ☐ FAIL | `PRODUCTION_HEALTH_CHECK_FA.md` |
| Smoke | ☐ PASS · ☐ FAIL | `post-deploy-smoke.template.ps1` |
| Security review | ☐ PASS · ☐ BLOCKED | `PRODUCTION_SECURITY_REVIEW_FA.md` |

---

## Sign-off

| Role | Name token | UTC | Signature / initials |
|------|------------|-----|----------------------|
| Deploy operator | PLACEHOLDER_DEPLOY_OPERATOR | PLACEHOLDER_DEPLOY_UTC | |
| Technical lead | PLACEHOLDER_TECH_LEAD | | |
| Owner | PLACEHOLDER_OWNER_APPROVER | | |

**Final disposition:** ☐ GO · ☐ NO-GO · ☐ ROLLED BACK (`PLACEHOLDER_INCIDENT_ID`)

---

## Change summary (short)

PLACEHOLDER_CHANGE_SUMMARY_FA_OR_EN

## Known warnings / accepted risks

PLACEHOLDER_KNOWN_WARNINGS

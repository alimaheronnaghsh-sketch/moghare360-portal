# MOGHARE360 Local Release Candidate 1 — Release Notes

## Version

**MOGHARE360 Local Release Candidate 1**

## Scope

Controlled local release / demo / pilot review — not production deployment.

## Included Layers

- Core ERP pages (public_html safe subset)
- Soft Run pages
- Pilot pages
- Branding / localization pages
- Security audit pages (Phase 13)
- Deployment plan pages (Phase 14)
- Release package pages (Phase 15)

## Known Limitations

| Limitation | Status |
|------------|--------|
| Not production | Active boundary |
| Not SaaS | NOT ACTIVE |
| Not public customer portal | NOT ACTIVE |
| Not official accounting | NOT ACTIVE |
| No payment gateway | NOT ACTIVE |
| No official tax invoice | NOT ACTIVE |

## Setup Notes

1. Extract `moghare360-local-rc1.zip` to a clean directory
2. Copy `public_html/` contents to web server document root
3. Create `private/erp-config.php` from example (not included in package)
4. Run SQL migration scripts in order (see `docs/deployment/MOGHARE360_DATABASE_MIGRATION_PLAN.md`)
5. Run phase test tools to verify

## Test URLs (Local)

- http://localhost:8080/moghare360/erp-business-command-center.php
- http://localhost:8080/moghare360/erp-release-package-dashboard.php
- http://localhost:8080/moghare360/moghare360-release-download.php

## Package Paths

- Folder: `release/moghare360-local-rc1/`
- ZIP: `release/moghare360-local-rc1.zip`
- Script: `tools/package-moghare360-local-release.ps1`

## Security Exclusions

No private config, credentials, real customer data, logs, or DB backups in package.

Filenames containing `.bak` (including patterns like `*.php.bak_*`) are explicitly excluded from Local RC1 ZIP via safe copy and post-build ZIP inspection.

## Warning

**This is NOT a production installer.** Deployment requires Phase 14 checklist approval and owner sign-off.

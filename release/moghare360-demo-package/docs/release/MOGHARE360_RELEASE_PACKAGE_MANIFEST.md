# MOGHARE360 Release Package Manifest

## Package Types

1. **Demo Package** — `release/moghare360-demo-package.zip`
2. **Local Release Package** — `release/moghare360-local-rc1.zip`

## Included (Local Release)

- public_html safe files (exclusions applied)
- docs/ (product, release, deployment, missions summaries as copied)
- public_html/sql/sqlserver migration scripts
- tools test scripts (test-phase-*.php)
- release manifest docs

## Included (Demo Package)

- Commercial demo pages
- Brand/localization/asset pages
- Release package pages
- Required CSS and brand assets
- docs/product, docs/release, docs/deployment

## Excluded (Both Packages)

| Item | Reason |
|------|--------|
| private/ | Private config boundary |
| config.php, config.example.php | Config boundary |
| private/erp-config.php, private/erp-config.example.php | Secrets boundary |
| credentials / secrets | Security |
| real customer data | Privacy |
| logs/ | No logs in package |
| backups/ | No backup files |
| .git/ | Not distributable |
| uploads/ with real data | Privacy |
| node_modules/, vendor/ | Dependencies |
| Codex ZIP archive | Forbidden |
| *.bak, *.log, *.tmp | Temporary/backup files — any filename containing `.bak` is excluded |
| source *.zip files | Avoid nested archives |

## ZIP Verification

Local release packaging uses per-file safe copy (not robocopy wildcards alone) and post-build `tar -tf` inspection. Test tool validates ZIP entry names before PASS.

## Package Is NOT

- Production installer
- Auto deploy script
- SaaS activation
- Customer portal package

## Phase 15 Note

PHASE 15 has no database write foundation; it is a controlled release packaging layer.

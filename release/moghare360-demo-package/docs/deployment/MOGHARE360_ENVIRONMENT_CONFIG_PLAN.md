# MOGHARE360 Environment Config Plan

## Environments

| Environment | Purpose | Status |
|-------------|---------|--------|
| Local | Development and internal test (XAMPP) | READY |
| Pilot | Controlled workshop pilot | READY FOR CONTROLLED USE |
| Production | Operational deployment | NOT DEPLOYED |

## Database Connection Boundary

- Connection strings live in **private config** only — never in repo
- SQL Server instance configured per environment
- No credentials in `public_html/` or committed files

## Private Config Policy

- `private/erp-config.php` — outside version control
- `private/erp-config.example.php` — template only, no real secrets
- Content of private config is never displayed in Phase 14 pages

## .gitignore Policy

- Private config files must be gitignored
- Runtime-generated files excluded
- No backup dumps in repo

## Secrets Policy

- **No credentials in repo**
- API keys, DB passwords, SMTP — environment-only
- Rotation on production go-live

## public_html Boundary

- Deploy target: sync `public_html/` to runtime (e.g. `C:\xampp\htdocs\moghare360`)
- Exclude `.md` from runtime sync
- No `private/` under public web root

## Runtime Path Boundary

- Repo path ≠ runtime path
- Production URL and path require separate approval

## Deployment Approval

Production deployment requires explicit owner/technical approval. Phase 14 does not execute deployment.

## Phase 14 Note

PHASE 14 has no database write foundation; it is a read-only production deployment planning layer.

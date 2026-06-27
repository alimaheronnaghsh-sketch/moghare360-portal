# MOGHARE360 V1 — Local Demo Package Manifest

## Package Script

`tools/package-moghare360-v1-local-demo.ps1`

Usage:

```powershell
powershell -ExecutionPolicy Bypass -File tools\package-moghare360-v1-local-demo.ps1 -DryRun
powershell -ExecutionPolicy Bypass -File tools\package-moghare360-v1-local-demo.ps1
```

## Output Paths

- Directory: `dist/moghare360-v1-local-demo-rc/`
- Zip: `dist/moghare360-v1-local-demo-rc.zip`
- Manifest: `dist/moghare360-v1-local-demo-rc/MANIFEST.txt`
- Hash: `dist/moghare360-v1-local-demo-rc/PACKAGE_SHA256.txt`

## Included Files

- `public_html/` product PHP, includes, APIs, m360 assets
- `database/migrations/P*.sql`
- `docs/release/*`
- `docs/demo/*`
- `docs/missions/**/*` (if present)
- `tools/test-p*.php`
- Package manifest doc (this file copy)

## Excluded Files (mandatory)

- `private/`
- `.env`, `.env.*`
- Real `config.php`, `mirror-config.php`, `erp-config.php`
- Files containing password / api_key / token / secret
- `*.bak`, `*.backup`, `*.tmp`, `*.log`, prior `*.zip`
- Database dumps
- Real upload files / customer documents / photos
- Real mobile/plate data without DEMO marker
- `node_modules/`, `vendor/` (optional heavy dirs)
- `.git/`, `.github/` secrets
- `cache/`, `temp/`, `session/` files

## Security Scan Rules

Before zip, script scans for:

- API key patterns
- Password assignments
- Secret / bearer / token literals
- Real-like mobile (09xxxxxxxxx) without DEMO
- Real-like plate without DEMO

**Fail-on-suspect:** package build aborts with non-zero exit code.

## No Real Credentials Guarantee

- PHP UI does **not** build zip
- Only PowerShell script builds package locally
- Private config never copied
- Credential scan blocks suspicious content

## UI Reference

`public_html/erp-local-demo-package.php` — read-only status and manifest display.

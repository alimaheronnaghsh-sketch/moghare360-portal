# IIS Deployment Notes — MOGHARE360 (Phase 7)

**Audience:** Ops / deploy operators  
**Target:** Windows VPS + IIS + PHP + SQL Server  
**Not supported as Production host:** XAMPP (Apache/MySQL stack from XAMPP)

All hostnames, paths, and identities below use `PLACEHOLDER_*` only.

---

## 1. Recommended topology

```text
Internet
   │
   ▼
IIS (HTTPS :443)  ──►  public_html  (site physical path)
   │
   ├── PHP FastCGI
   ├── private config OUTSIDE web root  (PLACEHOLDER_PRIVATE_CONFIG_PATH)
   ├── private storage / quarantine OUTSIDE public URL space
   └── SQL Server  (PLACEHOLDER_SQL_INSTANCE)  DB = moghare360_ERP only
```

| Item | Placeholder |
|------|-------------|
| Site name | PLACEHOLDER_IIS_SITE_NAME |
| App pool | PLACEHOLDER_IIS_APPPOOL_NAME |
| App pool identity | PLACEHOLDER_IIS_APPPOOL_IDENTITY |
| Site path | PLACEHOLDER_IIS_SITE_PATH |
| Public FQDN | PLACEHOLDER_PROD_FQDN |
| PHP version | PLACEHOLDER_PHP_VERSION |
| PHP cgi path | PLACEHOLDER_PHP_CGI_PATH |

---

## 2. IIS site baseline

1. Create Application Pool:
   - No Managed Code (for pure PHP sites), or as required by optional .NET apps
   - Identity: PLACEHOLDER_IIS_APPPOOL_IDENTITY
   - Start Mode: AlwaysRunning (optional)
2. Create Website:
   - Binding: HTTPS, certificate for PLACEHOLDER_PROD_FQDN
   - Physical path: `PLACEHOLDER_IIS_SITE_PATH\public_html`
3. Enable HTTP→HTTPS redirect (URL Rewrite or separate HTTP site redirect).
4. Disable directory browsing for the site.
5. Request Filtering: deny risky extensions if policy requires (`.bak`, `.sql` public download, etc.).

### Block non-public trees from HTTP

Ensure these are **not** publicly executable (deny rules, move outside site, or equivalent):

- `tools/` (repo tools; also see `tools/.htaccess` pattern — on IIS use Request Filtering / `<denyUrlSequences>` / separate non-site path)
- Migration / one-off PHP CLI scripts
- `private/` config
- Backup folders
- Quarantine: PLACEHOLDER_QUARANTINE_PATH

---

## 3. PHP on IIS (FastCGI)

1. Install PHP PLACEHOLDER_PHP_VERSION for Windows (Non-Thread-Safe or Thread-Safe per IIS FastCGI guidance you standardize on).
2. Register FastCGI handler mapping for `*.php` → PLACEHOLDER_PHP_CGI_PATH.
3. Required extensions (typical for this app):
   - `odbc` / `sqlsrv` + `pdo_sqlsrv` (as used by your build)
   - `openssl`, `mbstring`, `fileinfo`, `gd` (if used), `curl` (if used)
4. Production `php.ini` highlights:
   - `display_errors = Off`
   - `log_errors = On`
   - `error_log = PLACEHOLDER_PHP_ERROR_LOG`
   - `session.cookie_secure = 1`
   - `session.cookie_httponly = 1`
   - upload limits aligned with product policy: PLACEHOLDER_UPLOAD_MAX_FILESIZE

Point the app at private config via env or loader paths:

- `MOGHARE360_ERP_CONFIG_PATH` = `PLACEHOLDER_PRIVATE_CONFIG_PATH\erp-config.php`

Example file (placeholders only): `private/erp-config.example.php`

---

## 4. Optional: ASP.NET Core Hosting Bundle

The core MOGHARE360 ERP PHP portal does **not** require the ASP.NET Core Hosting Bundle.

Install **ASP.NET Core Hosting Bundle** (PLACEHOLDER_DOTNET_HOSTING_BUNDLE_VERSION) only if you also host:

- a separate ASP.NET Core app/site on the same IIS, or
- an out-of-process .NET module approved for this VPS

Notes:

- Hosting Bundle installs shared runtime + IIS ANCM (AspNetCoreModuleV2).
- After install, reboot or restart IIS (`iisreset` in maintenance window).
- Do not confuse Hosting Bundle with SDK; production VPS usually needs **Runtime/Hosting Bundle**, not full SDK.
- Keep PHP site and .NET site in separate app pools when both exist.

---

## 5. SQL Server connectivity

| Setting | Value |
|---------|--------|
| Database name | `moghare360_ERP` (only writable app DB) |
| Instance | PLACEHOLDER_SQL_INSTANCE |
| App login | PLACEHOLDER_SQL_APP_LOGIN |
| Auth mode | PLACEHOLDER_SQL_AUTH_MODE (`Windows` or `SQL`) |

Firewall: do not expose SQL to the public Internet. Prefer localhost/private network / VPN: PLACEHOLDER_ADMIN_CIDR.

Migrations: `deployment/sql/MIGRATION_ORDER.md` — run from admin workstation/CLI, not via anonymous HTTP.

---

## 6. ACL sketch

| Path | App pool | Deploy ops |
|------|----------|------------|
| `public_html` (most files) | Read & execute | Modify during deploy |
| PLACEHOLDER_PRIVATE_STORAGE_PATH | Modify | Modify |
| PLACEHOLDER_LOG_PATH | Modify | Read |
| PLACEHOLDER_PRIVATE_CONFIG_PATH | Read | Modify (controlled) |
| PLACEHOLDER_BACKUP_LOCAL_PATH | None (preferred) | Modify (backup operator) |
| PLACEHOLDER_QUARANTINE_PATH | Modify (if app uses it) | Modify — **not** anonymous IIS browse |

---

## 7. Deploy / recycle

```powershell
Import-Module WebAdministration
# After files + config ready:
Restart-WebAppPool -Name 'PLACEHOLDER_IIS_APPPOOL_NAME'
```

Then:

```powershell
pwsh -File PLACEHOLDER_TOOLS_PATH\health-check.ps1
pwsh -File PLACEHOLDER_TOOLS_PATH\post-deploy-smoke.ps1
```

Full procedure: `docs/release/v1/DEPLOYMENT_RUNBOOK_FA.md`

---

## 8. Digital Asset Links (Android TWA)

If publishing TWA:

- Place production `assetlinks.json` under `public_html/.well-known/` on HTTPS origin only after filling placeholders from `assetlinks.json.example`.
- Do not commit production fingerprints with real secrets; SHA256 cert fingerprint is public by nature but still treat package naming carefully.
- See `android-twa/twa-manifest.example.json` and `android-twa/README.md`.

---

## 9. Forbidden on Production IIS

- Running the live ERP from XAMPP
- Dual-writable databases
- Serving backups, APKs, keystores, or real config from the site root
- Leaving `display_errors` on
- Public quarantine or tools endpoints

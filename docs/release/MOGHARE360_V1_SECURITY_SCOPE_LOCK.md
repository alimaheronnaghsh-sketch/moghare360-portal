# MOGHARE360 V1 Security Scope Lock

**Phase:** P10 — Release Hardening  
**Policy:** P10 is navigation and release readiness only. No auth changes, no workflow mutation, no destructive SQL.

## In Scope (Allowed)

- Read-only route registry (`m360-navigation-registry.php`)
- File existence audits (`m360_nav_file_exists`)
- Release readiness scoring and category reports
- Staff-gated RC UI pages (`m360_release_hardening_require_staff`)
- Documentation and manifest generation
- PHP lint validation via test suites

## Out of Scope (Forbidden)

| Category | Forbidden patterns |
|----------|-------------------|
| Auth / config | Rewriting `staff-login.php`, `owner-login.php`, `access-control.php` |
| Credentials | Hardcoded passwords, connection strings with secrets |
| Workflow mutation | `INSERT/UPDATE dbo.erp_jobcards`, `erp_final_invoices`, `erp_payments` from P10 |
| Gate bypass | `skip gate`, `bypass gate`, `gate override` |
| Destructive SQL | `DROP`, `DELETE`, `TRUNCATE` in P10 migration |
| Payment / accounting | Payment gateways, ledger posting, journal vouchers |
| Package build | `ZipArchive` or unsafe shell exec from P10 web context |
| HTTP audit | `curl_*` or `file_get_contents('http...')` for route validation |

## P10 Files Under Scan

- `erp-product-home.php`
- `erp-demo-package-rc.php`
- `erp-release-readiness.php`
- `erp-route-map.php`
- `erp-link-audit.php`
- `includes/m360-*release*.php`
- `includes/m360-navigation-registry.php`

## Staff Gate

All P10 RC pages require authenticated staff session. Unauthenticated users redirect to `staff-login.php` via `m360_nav_require_staff()`.

## POST Policy

P10 RC pages are **GET-only**. No forms that mutate operational state. POST actions remain in P1–P7 workflow pages only.

## Verification

Run `tools/test-p10-security-scope-control.php` before each RC review.

## Related Documents

- `docs/release/MOGHARE360_V1_RC_MANIFEST.md`
- `docs/release/MOGHARE360_V1_RELEASE_READINESS_REPORT.md`
- `docs/release/MOGHARE360_SAAS_SECURITY_BOUNDARY.md`

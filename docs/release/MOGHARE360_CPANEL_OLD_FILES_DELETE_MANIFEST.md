# MOGHARE360 — cPanel Old Files Delete Manifest (Full Replace v2)

Use this list when replacing the public cPanel site with `moghare360-cpanel-public-full-replace-v2.zip`.

**Goal:** Remove leftover files from older public packages so only the v2 OTP-first site remains.

## DO NOT DELETE (unless you know exactly why)

| Item | Reason |
|------|--------|
| `mirror-config.php` | Live production config — **must be preserved** |
| `.htaccess` | Host routing / security — review before any change |
| SSL certificates, `cgi-bin`, host panel files | System / hosting |
| User uploads outside this app scope | Business data |

## DELETE — Old deploy artifacts

- `moghare360-cpanel-public-final-clean.zip`
- `moghare360-cpanel-public-full-replace-v2.zip` (after extract)
- Any other `*.zip` deploy archives in `public_html`
- `moghare360_public_html_sanitized_backup.zip.zip` (if present)

## DELETE — Wrong layout / debug

- `public_html/public_html/` (entire nested tree if it exists)
- `api/sync/` (entire directory)
- `debug-pending.php`

## DELETE — Forbidden app config (public tree)

- `config.php`
- `erp-config.php`
- `mirror-config.php` **only if** you are about to restore from backup — otherwise **KEEP**

## DELETE — Non-public runtime folders (if exposed under public_html)

- `private/`
- `runtime/`
- `logs/`
- `uploads/` (only if this folder was mistakenly deployed to public_html — verify first)
- `docs/` (release docs should not be web-accessible)

## DELETE — Obsolete public backups (if web-accessible)

- `customer-request.php.bak`
- `customer-request-old.php`
- `customer-form-old.js`
- Any `*.bak`, `*.old`, `*.tmp` in public paths

## DELETE — Old OTP / form files superseded by v2

If duplicate or backup copies exist alongside live files (not the current v2 files):

- Old standalone OTP test scripts in `public_html`
- Duplicate `customer-request.php` backups in public paths

## KEEP — v2 package files (do not delete after deploy)

- `index.php`
- `customer-request.php`
- `staff-login.php`, `owner-login.php`
- `includes/m360-otp-helper.php`
- `api/customer/send-otp.php`, `verify-otp.php`, `profile-status.php`, `request.php`
- `assets/js/customer-form.js`
- `assets/css/mirror.css`, `moghare360-v1-luxury-ui.css`
- `service-worker.js`, `manifest.webmanifest`
- `mirror-config.example.php` (example only — safe)

## After cleanup

1. Extract `moghare360-cpanel-public-full-replace-v2.zip` to flat `public_html` root.
2. Follow `MOGHARE360_CPANEL_FULL_REPLACE_DEPLOY_CHECKLIST.md`.
3. Unregister Service Worker and clear site data on client browsers.

# MOGHARE360 — cPanel Full Replace Deploy Checklist (Public Site v2)

Use with package: `release/moghare360-cpanel-public-full-replace-v2.zip`  
Target document root: `/home2/moghareh/public_html`

## Before you start

1. **Backup** the live `mirror-config.php` from the server (download a copy). Do not deploy without this backup.
2. Confirm SMS provider credentials are configured only in the live `mirror-config.php` on the host (never inside the ZIP).
3. If an SMS API key was ever exposed in chat, email, or an old commit, **rotate it in the provider panel** before go-live.

## Step 1 — Remove old public package files

Follow `MOGHARE360_CPANEL_OLD_FILES_DELETE_MANIFEST.md` and remove obsolete public files **except**:

- `mirror-config.php` (live config — **keep**)
- `.htaccess` (review before any change — **do not delete blindly**)
- SSL / host / system files outside this public app

## Step 2 — Upload new ZIP

1. Upload `moghare360-cpanel-public-full-replace-v2.zip` to `/home2/moghareh/public_html` (or cPanel File Manager).
2. **Extract** into `/home2/moghareh/public_html` so files land at the **flat root** (`index.php`, `customer-request.php`, `assets/`, `api/`, `includes/`).
3. Confirm there is **no** `public_html/public_html` nested folder after extract.
4. **Delete the ZIP** from the server after successful extract.

## Step 3 — Restore / verify live config

1. Ensure `mirror-config.php` still exists and was **not** overwritten by the package.
2. If missing, restore from backup or copy from `mirror-config.example.php` and set production values manually.
3. Never commit or upload real `mirror-config.php` into Git or the ZIP.

## Step 4 — Clear browser cache / Service Worker

Old cached JS/CSS can show the previous customer form.

1. Open the site in Chrome/Edge.
2. DevTools → Application → **Service Workers** → **Unregister**.
3. Application → **Storage** → **Clear site data**.
4. Hard refresh: **Ctrl+F5** (or Cmd+Shift+R on Mac).
5. If issues persist, test in **Incognito** / private window.

## Step 5 — Smoke test (production)

1. Open `https://moghareh360.ir/customer-request.php` (or your public URL).
2. Confirm **no** localhost / DEV / `123456` / technical debug text is visible.
3. Enter mobile → send OTP → verify (real SMS when provider is configured).
4. Returning customer: short request form only. New customer: full profile + vehicle + request.
5. Check `staff-login.php` and `owner-login.php` still load (HTTP 200).

## Step 6 — Post-deploy cleanup

- Remove old deploy ZIPs from `public_html` if any remain.
- Remove `api/sync/` or `debug-pending.php` if still present from older packages.

## Rollback

1. Restore previous files from cPanel backup (or re-upload prior known-good ZIP).
2. Restore `mirror-config.php` from Step 1 backup if it was changed.
3. Clear Service Worker + site data again after rollback.

---

**OTP security:** DEV code `123456` is **localhost-only** in source. Production must use real SMS only; fake OTP is hard-blocked on `moghareh360.ir`.

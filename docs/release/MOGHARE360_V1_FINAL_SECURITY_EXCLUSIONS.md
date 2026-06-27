# MOGHARE360 V1 — Final Security Exclusions

## Forbidden Files in Package

- `private/` and all contents
- `.env`, `.env.*`
- Production `config.php`, `mirror-config.php`, `erp-config.php`
- `*.bak`, `*.backup`, `*.tmp`, `*.log`
- Prior release zips, database dumps
- Real customer uploads and documents

## Forbidden Secrets

- Passwords in source or package
- SMS API keys
- Bearer tokens / raw secrets
- Production credentials of any kind

## Forbidden Data

- Real customer names tied to production
- Real mobile numbers without DEMO marker
- Real license plates without DEMO marker
- Confidential business documents

## Forbidden SQL

- `DROP`, `DELETE`, `TRUNCATE` in release migrations (P11 is comment-only)
- Operational `INSERT`/`UPDATE` from P8–P11 read-only pages
- Schema changes in P11

## Forbidden Upload

- `move_uploaded_file` in release/demo read-only pages
- Unrestricted upload endpoints
- Upload bypass around P1.5–P10 gates

## Forbidden OTP Behavior

- Fake OTP enabled on production hosts (moghareh360.ir)
- Hardcoded production OTP bypass
- API keys / pattern codes in committed repo files
- Pattern variable key `%OTP%` in payload (use `OTP`)

## OTP Provider Config (P11.1)

- Real credentials: `private/m360-otp-config.php` (gitignored) or environment variables
- Example only: `private/m360-otp-config.example.php`
- `useFakeOtp` allowed only on localhost when explicitly enabled
- IPPanel pattern variable name: `OTP` (provider template uses `%OTP%`)

## Auth / Config Lock

Do not modify:

- Login/Auth core
- `staff-login.php`
- `owner-login.php`
- `access-control.php`
- Real `config.php` / `mirror-config.php`

## Workflow Lock

- No new approve / override / release / close / payment actions in P8–P11
- No gate bypass for P1.5–P10
- No payment gateway, ledger, bank, tax, purchase write, inventory write

## Enforcement

- `m360_release_lock_security_scan()` on P11 files
- `tools/package-moghare360-v1-local-demo.ps1` credential scan
- `tools/test-p11-security-final-scan.php`

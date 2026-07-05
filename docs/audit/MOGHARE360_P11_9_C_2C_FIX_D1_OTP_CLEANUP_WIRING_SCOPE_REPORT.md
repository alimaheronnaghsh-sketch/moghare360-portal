# MOGHARE360 P11.9-C-2C-FIX-D1 — OTP Cleanup Wiring Scope Gate

**Phase:** P11.9-C-2C-FIX-D1  
**Date:** 2026-07-04  
**Prior audit:** FIX-D0 (`MOGHARE360_P11_9_C_2C_FIX_D0_OTP_ARCHITECTURE_CLEANUP_AUDIT.md`)

---

## Scope Gate Checklist

| # | Item | Result |
|---|------|--------|
| 1 | D0 findings confirmed | **PASS** — dual stack; reception send-only; gate reads payload |
| 2 | Canonical OTP helper files exist | **PASS** — `m360-otp-config-loader.php`, `m360-otp-helper.php`, `api/customer/send-otp.php`, `api/customer/verify-otp.php` |
| 3 | Reception has `send_customer_otp` | **PASS** — in `m360_rw_intake_allowed_actions()` and intake UI |
| 4 | `verify_customer_otp` missing | **PASS (gap confirmed)** — not in allowed actions; no UI |
| 5 | `m360_otp_send()` session storage | **PASS** — `otp_hash`, `otp_phone`, `otp_expires_at` in PHP session |
| 6 | `m360_otp_verify()` validation | **PASS** — `password_verify`, expiry, attempts; sets `otp_verified_*` session on success |
| 7 | Gate reads `otp_verified` | **PASS** — `m360_online_req_payload_otp_verified()` on payload JSON / DB column |
| 8 | Files to modify | See below |
| 9 | No private/secret modification | **PASS** — no edits to `private/m360-otp-config.php` |
| 10 | No DB/Auth/permission/schema/JobCard change | **PASS** — payload JSON + optional existing column only |

**Scope gate:** **PROCEED** — no STOP conditions triggered.

---

## Files to Modify (D1)

| File | Change |
|------|--------|
| `public_html/includes/m360-otp-config-loader.php` | Safe deploy-root detection for flat XAMPP (`htdocs/private/`) |
| `public_html/includes/m360-reception-workbench-helper.php` | Send persist OTP meta; add `verify_customer_otp`; mobile reset; config status message |
| `public_html/erp-reception-intake-file.php` | Verify UI, OTP status labels |
| `public_html/send-otp.php` | Deprecation stub |
| `public_html/verify-otp.php` | Deprecation stub |
| `public_html/check-otp.php` | Deprecation stub |
| `public_html/send-contract-otp.php` | Deprecation stub (legacy; V1 uses `api/customer/contract-send-otp.php`) |
| `public_html/verify-contract-otp.php` | Deprecation stub |
| `tools/test-p11-9-c-2c-fix-d1-*.php` | Six new test files |

**Not modified:** `staff-auth.php`, `access-control.php`, Auth/Login, roles, DB schema, private config, erp-reception-intake-save.php (routing unchanged except via helper).

---

## Legacy Route Decision

| Route | V1 active usage | D1 action |
|-------|-----------------|-----------|
| `send-otp.php`, `verify-otp.php`, `check-otp.php` | Legacy `customer-login.php` only (broken) | **Deprecation stub** |
| `send-contract-otp.php`, `verify-contract-otp.php` | Legacy `customer-contract.php` links; V1 uses API routes | **Deprecation stub** + note: `customer-contract.php` needs future redirect (out of D1) |

---

## Reception OTP Session Model

Staff browser session holds pending OTP after Send; staff enters customer-received code on Verify in same session. No parallel OTP helper created.

---

**Gate status:** READY FOR IMPLEMENTATION

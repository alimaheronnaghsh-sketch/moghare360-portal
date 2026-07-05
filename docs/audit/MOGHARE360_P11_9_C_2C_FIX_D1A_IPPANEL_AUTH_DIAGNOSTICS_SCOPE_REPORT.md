# MOGHARE360 P11.9-C-2C-FIX-D1A — IPPanel Auth Diagnostics Scope Gate

**Phase:** P11.9-C-2C-FIX-D1A  
**Date:** 2026-07-04  
**Blocker:** HTTP 401 `Invalid token` from IPPanel Edge despite config PASS

---

## Scope Gate Checklist

| # | Item | Finding |
|---|------|---------|
| 1 | IPPanel endpoint used by `m360_otp_send_sms()` | `POST https://edge.ippanel.com/v1/api/send` |
| 2 | Headers currently sent (pre-fix) | `Content-Type: application/json`, `Authorization: AccessKey {token}` |
| 3 | Authorization header used? | **Yes** |
| 4 | `apikey` separate header used? | **No** |
| 5 | Token format (pre-fix) | **`AccessKey` prefix + raw key** (not Edge canonical) |
| 6 | Matches IPPanel Edge docs? | **No** — Edge docs: `Authorization: your-token-or-apikey-here` (raw, no prefix) |
| 7 | `check_token` diagnostic exists? | **No (pre-fix)** — added in D1A |
| 8 | 401 root cause | **Primary: wrong token format (`AccessKey` prefix)**; secondary: invalid/expired token value possible |
| 9 | No secrets printed | **Confirmed** — diagnostics mask only length/status |
| 10 | DB/Auth/schema changes required? | **No** |

**Gate:** PROCEED — minimal auth header fix + diagnostics only.

---

## Planned Changes

| File | Change |
|------|--------|
| `m360-otp-helper.php` | Default auth = raw token; optional `accesskey` mode; `check_token`; 401 Persian message |
| `tools/ippanel-auth-diagnostics.php` | New CLI tool |
| `m360-reception-workbench-helper.php` | Propagate provider error message to flash |
| Tests + audit docs | D1A suite |

**Not changed:** Gate, payload OTP truth, mobile correction, Auth/Login, DB schema, private config file contents.

---

**Status:** READY FOR IMPLEMENTATION

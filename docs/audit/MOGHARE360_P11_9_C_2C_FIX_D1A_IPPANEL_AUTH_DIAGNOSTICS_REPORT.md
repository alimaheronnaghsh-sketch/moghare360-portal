# MOGHARE360 P11.9-C-2C-FIX-D1A — IPPanel Auth Diagnostics Report

**Phase:** P11.9-C-2C-FIX-D1A  
**Date:** 2026-07-04  
**Prior:** FIX-D1 reception OTP wiring

---

## 1. Scope Gate Result

**PROCEED** — See [`MOGHARE360_P11_9_C_2C_FIX_D1A_IPPANEL_AUTH_DIAGNOSTICS_SCOPE_REPORT.md`](MOGHARE360_P11_9_C_2C_FIX_D1A_IPPANEL_AUTH_DIAGNOSTICS_SCOPE_REPORT.md).

Minimal auth compatibility fix only. No Gate/payload/mobile/Auth/DB changes.

---

## 2. Network Status

Owner-reported (confirmed in scope):

| Check | Result |
|-------|--------|
| `Test-NetConnection edge.ippanel.com -Port 443` | PASS |
| DNS resolve `edge.ippanel.com` | PASS |
| `curl -I https://edge.ippanel.com` | HTTP 404 (ArvanCloud — host reachable) |

Network is **not** the blocker.

---

## 3. Config Status

`tools/otp-config-diagnostics.php` (masked):

| Field | Status |
|-------|--------|
| Private OTP config | Found |
| Provider | `ippanel` |
| API key | Present (length only in diagnostics) |
| Sender | Present |
| Pattern code | Present |
| Pattern variable | `OTP` |
| OTP config diagnostics | **PASS** |

Config detection is **not** the blocker.

---

## 4. Current Provider Response (Pre-Fix)

Live send returned:

```
HTTP 401
meta.message: Invalid token
```

With pre-fix header: `Authorization: AccessKey {token}`

---

## 5. Auth Header Decision

**Root cause:** Wrong token format in `Authorization` header.

| Item | Pre-D1A | Post-D1A (canonical Edge) |
|------|---------|---------------------------|
| Endpoint | `https://edge.ippanel.com/v1/api/send` | Unchanged |
| Header name | `Authorization` | Unchanged |
| Header value | `AccessKey {token}` | **`{token}` raw** |
| Separate `apikey` header | No | No |
| Edge docs alignment | **No** | **Yes** |

IPPanel Edge documentation: `Authorization: your-token-or-apikey-here` ([Edge API docs](https://ippanelcom.github.io/Edge-Document/docs/)).

**Implementation:**
- Default mode: `authorization` (raw token)
- Optional config: `ippanelAuthHeaderMode` = `authorization` | `accesskey` | `apikey`
- Legacy `accesskey` mode retains old `AccessKey` prefix for rollback only
- No owner config edit required when default applies

---

## 6. Diagnostic Tool Result

**Tool:** `tools/ippanel-auth-diagnostics.php`

Post-fix run (masked, no secrets):

```
IPPANEL AUTH DIAGNOSTICS
provider: ippanel
config_found: yes
token_present: yes
token_length: 92
auth_header_mode: authorization
check_token_http_status: 200
check_token_provider_message: انجام شد
token_valid: yes
result: PASS
```

Uses `POST https://edge.ippanel.com/v1/api/acl/auth/check_token` — **no SMS**, **no OTP generation**.

If token invalid (401), recommendation:

> توکن iPPanel نامعتبر است یا برای Edge API فعال نیست. توکن جدید از پنل دریافت و در فایل private جایگزین شود.

---

## 7. Error Message Improvement

When provider returns 401 / `Invalid token`:

- Helper constant: `M360_OTP_MSG_IPPANEL_INVALID_TOKEN`
- User message: **توکن iPPanel نامعتبر است یا دسترسی API فعال نیست.**
- Reception intake flash propagates `$result['message']` from helper (not generic-only)
- No raw JSON in browser

---

## 8. Tests Passed

| Test | Result |
|------|--------|
| `test-p11-9-c-2c-fix-d1a-ippanel-auth-diagnostics.php` | 12/12 PASS |
| `test-p11-9-c-2c-fix-d1a-no-secret-leak.php` | 8/8 PASS |
| `test-p11-9-c-2c-fix-d1a-scope-security.php` | 11/11 PASS |
| `test-p11-9-c-2c-fix-d1-otp-canonical-wiring.php` | PASS |
| `test-p11-9-c-2c-fix-d1-reception-verify-bridge.php` | PASS |
| `test-v1-production-signoff.php` | PASS |

---

## 9. Browser Validation Status

**PENDING_OPERATOR**

After deploying updated `m360-otp-helper.php` to XAMPP:

1. `http://localhost:8080/moghare360/erp-reception-intake-file.php?online_request_id=18#section-mobile-otp`
2. Send OTP → expect SMS (token now validates via `check_token` 200)
3. Verify OTP → Gate OTP item passes
4. No automatic JobCard
5. Repeat request 20

CLI `ippanel-auth-diagnostics.php` **PASS** indicates provider auth should succeed in browser after deploy.

---

## 10. What Was Not Changed

- Reception Gate logic
- Payload OTP truth / verify bridge
- Mobile correction reset
- Auth/Login, `staff-auth.php`, `access-control.php`
- Roles / permissions
- DB schema / SQL migrations
- OTP fake / bypass
- Automatic JobCard
- Private config file contents (not modified, not committed)
- P12 / C-2D scope

---

## 11. Commit Eligibility

**NOT_ELIGIBLE_UNTIL_PROVIDER_AUTH_PASS_AND_BROWSER_OTP_PASS**

Provider auth CLI: **PASS** after D1A fix.  
Browser OTP UAT: **PENDING_OPERATOR**.

---

## 12. Owner Action Required

1. **Deploy** updated files to XAMPP (`m360-otp-helper.php`, reception helper if not already).
2. **Run** `php tools/ippanel-auth-diagnostics.php` on host — expect `result: PASS`.
3. **Browser UAT** Send + Verify OTP on intake requests 18 and 20.
4. **Token regeneration:** Not required if `check_token` returns PASS. If FAIL with 401 after deploy, regenerate Edge API key from iPPanel panel (Developers > Access Keys) and replace in `private/m360-otp-config.php` only on host.
5. **Do not** set `ippanelAuthHeaderMode` to `accesskey` unless legacy rollback needed — default `authorization` is correct for Edge API.

---

P11.9-C-2C-FIX-D1A diagnoses the remaining IPPanel OTP blocker as provider authentication/token failure, adds a safe masked token diagnostic, aligns the helper with the canonical Edge auth header when needed, improves controlled Persian error handling for Invalid token, and preserves OTP integrity, secret safety, Auth/Login, permissions, database schema, workflow, and the no-automatic-JobCard boundary.

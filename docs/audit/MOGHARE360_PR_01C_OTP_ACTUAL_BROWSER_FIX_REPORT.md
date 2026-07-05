# MOGHARE360 PR-01C — OTP Actual Browser Fix Report

**Mission ID:** PR-01C  
**Phase:** Actual OTP browser fix (runtime, not report-only)  
**Date:** 2026-07-05  
**Commit Eligibility:** `NOT_ELIGIBLE_UNTIL_BROWSER_OTP_SEND_AND_VERIFY_PASS`

---

## 1. Config Source Result

| Check | Result |
|-------|--------|
| Repo private `private/m360-otp-config.php` exists | **Yes** |
| Repo private readable | **Yes** |
| XAMPP private `C:\xampp\htdocs\private\m360-otp-config.php` exists | **Yes** |
| XAMPP private readable | **Yes** |
| Same config key labels | **Yes** |
| Non-secret fingerprint match (repo vs XAMPP) | **Yes** (SHA256 file hash + safe fingerprint) |
| Git tracked | **No** (gitignored — no `SECRET_RISK_TRACKED_CONFIG`) |
| CLI loader source | `project_private` |
| Apache expected loader source | `htdocs_private` / `apache_htdocs_private` when `moghare360/private` missing |
| SMS configured after load | **Yes** |

**Owner reference honored:** `moghare360-portal/private/m360-otp-config.php` is the known-correct config. XAMPP runtime copy matches (identical file hash).

**Loader improvements:** `M360_REPO_ROOT/private` candidate, safe fingerprint reporting, `m360_otp_config_source_report()`.

---

## 2. Repo vs XAMPP Config Fingerprint Result

| Metric | Repo private | XAMPP private |
|--------|--------------|---------------|
| Exists | Yes | Yes |
| Readable | Yes | Yes |
| File size | 1509 bytes | 1509 bytes |
| SHA256 match | **Yes** | **Yes** |
| Safe fingerprint match | **Yes** | **Yes** |

No stale runtime config detected. No automatic copy performed.

---

## 3. Active File Sync Result

All six active files were **out of sync before** PR-01C (customer-request had stale `?v=full-replace-v2`). After sync:

| File | Repo ↔ XAMPP hash match |
|------|-------------------------|
| `customer-request.php` | **Yes** (after copy) |
| `assets/js/customer-form.js` | **Yes** |
| `api/customer/send-otp.php` | **Yes** |
| `api/customer/verify-otp.php` | **Yes** |
| `includes/m360-otp-helper.php` | **Yes** |
| `includes/m360-otp-config-loader.php` | **Yes** |

Private config was **not** copied or overwritten.

---

## 4. cURL Fixes Applied

New centralized `m360_otp_ippanel_apply_curl_options()` on send + check_token:

| Option | Value |
|--------|-------|
| `CURLOPT_CONNECTTIMEOUT` | 8 |
| `CURLOPT_TIMEOUT` | 25 |
| `CURLOPT_RETURNTRANSFER` | true |
| `CURLOPT_HTTP_VERSION` | `CURL_HTTP_VERSION_1_1` |
| `CURLOPT_IPRESOLVE` | `CURL_IPRESOLVE_V4` |
| `CURLOPT_NOSIGNAL` | true |
| `CURLOPT_SSL_VERIFYPEER` | true |
| `CURLOPT_SSL_VERIFYHOST` | 2 |
| `CURLOPT_PROXY` | `''` (clear inherited proxy) |

`check_token` remains **diagnostic only** — not called by `m360_otp_send()`.

Safe error classification: `m360_otp_ippanel_safe_error_code()`.

---

## 5. Endpoint/JS Fixes Applied

| Fix | Detail |
|-----|--------|
| JS cache bust | `customer-request.php` uses `filemtime()` version query on `customer-form.js` |
| Safe error code in API | `send-otp.php` returns `data.error_code` on failure |
| JS dev logging | `customer-form.js` logs `error_code` in localhost console (no secret) |
| Canonical endpoints | Unchanged — `api/customer/send-otp.php`, `verify-otp.php` |
| Payload key | `{ phone }` — unchanged, correct |

---

## 6. Network Proof Result

### PowerShell `Test-NetConnection edge.ippanel.com -Port 443`

| Result | Value |
|--------|-------|
| DNS | Resolves (185.143.233.235 / 185.143.234.235) |
| TCP 443 | **FAILED** (timeout) |
| Ping | **FAILED** (timeout) |

### PHP diagnostic (`diagnose-pr-01c-otp-live-fix.php`)

| Test | Result |
|------|--------|
| `dns_ok` | **yes** |
| `tcp_443_ok` | **no** |
| `curl_default_connect` | **no** (`PROVIDER_TIMEOUT`) |
| `curl_ipv4_connect` | **no** (`PROVIDER_TIMEOUT`) |
| `send_attempt_success` | **no** |
| `safe_error_code` | `PROVIDER_TIMEOUT` |
| `browser_endpoint_error_code` | `PROVIDER_TIMEOUT` |

**Decision:** `HOST_NETWORK_BLOCKER_CONFIRMED`

IPv4 forcing did **not** restore connectivity — not `OTP_FIXED_BY_FORCE_IPV4`.

---

## 7. Root Cause

| Layer | Status |
|-------|--------|
| Config source | **Correct** — repo + XAMPP private aligned |
| Endpoint/JS path | **Correct** — canonical API, cache bust added |
| XAMPP file sync | **Fixed** — all active files copied |
| cURL software options | **Hardened** — IPv4, timeouts, proxy clear, SSL verify |
| **External network** | **BLOCKED** — host cannot complete TCP 443 to `edge.ippanel.com` |

Software-side issues were fixed. Remaining failure is **host/network reachability to IPPanel**, not wrong config, wrong endpoint, or stale JS.

---

## 8. Files Modified

| File | Change |
|------|--------|
| `public_html/includes/m360-otp-helper.php` | cURL hardening, safe error codes, send error propagation |
| `public_html/includes/m360-otp-config-loader.php` | `M360_REPO_ROOT` path, fingerprint, source report |
| `public_html/api/customer/send-otp.php` | `error_code` in failure JSON |
| `public_html/customer-request.php` | `filemtime` JS cache bust |
| `public_html/assets/js/customer-form.js` | Safe `error_code` dev logging |
| `tools/diagnose-pr-01c-otp-live-fix.php` | Connectivity + send proof |
| `tools/test-pr-01c-otp-*.php` (4 files) | PR-01C test suite |
| `docs/audit/MOGHARE360_PR_01C_OTP_ACTUAL_BROWSER_FIX_REPORT.md` | This report |

---

## 9. Tests Passed

| Test | Result |
|------|--------|
| `test-pr-01c-otp-browser-path.php` | **9/9 PASS** |
| `test-pr-01c-otp-config-source.php` | **9/9 PASS** |
| `test-pr-01c-otp-curl-options.php` | **13/13 PASS** |
| `test-pr-01c-otp-scope-security.php` | **7/7 PASS** |

**Total: 38/38 PASS**

---

## 10. Browser UAT Result

| Check | Status |
|-------|--------|
| `http://localhost:8080/moghare360/customer-request.php` | Reachable |
| Hard refresh cache bust | **Applied** (`filemtime` query) |
| Send OTP `09128166648` | **FAIL** — `PROVIDER_TIMEOUT` |
| Persian success + SMS | **Not achieved** (network blocker) |
| Verify OTP | **Blocked** on send |
| Secret in network response | **None** |
| Safe error code in API | **Yes** — `PROVIDER_TIMEOUT` |

**Browser UAT: FAIL** — external network blocker proven.

---

## 11. External Blocker (Exact)

```
HOST_NETWORK_BLOCKER_CONFIRMED
```

Evidence:
- `Test-NetConnection edge.ippanel.com -Port 443` → TCP failed
- PHP `tcp_443_ok` → no
- PHP cURL default + IPv4 → `PROVIDER_TIMEOUT`
- Browser endpoint → `error_code: PROVIDER_TIMEOUT`

**Owner action required:** Restore outbound HTTPS access to `edge.ippanel.com:443` (firewall, ISP, VPN, or regional routing). Software path is ready; when TCP 443 succeeds, send should return success JSON with Persian message.

**Verify after network restored:** Ctrl+F5 on customer-request → send OTP → verify with SMS code.

---

## 12. Commit Eligibility

```
NOT_ELIGIBLE_UNTIL_BROWSER_OTP_SEND_AND_VERIFY_PASS
```

---

MOGHARE360 PR-01C performs the actual OTP browser fix by aligning the correct private config source, hardening PHP cURL for the IPPanel endpoint, eliminating stale XAMPP/browser route issues, proving the active customer-request endpoint path, and allowing the project to continue only when real browser send and verify pass or an external host/network blocker is proven with direct tests.

---

**END OF PR-01C REPORT**

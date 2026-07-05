# MOGHARE360 PR-01B — OTP Reference Reconciliation Report

**Mission ID:** PR-01B  
**Phase:** Known-Working Private Reference Reconciliation  
**Date:** 2026-07-05  
**Commit Eligibility:** `NOT_ELIGIBLE_UNTIL_BROWSER_OTP_SEND_AND_VERIFY_PASS`

---

## 1. Canonical Docs Read

| # | Document |
|---|----------|
| 1–6 | PR-00 canonical pack |
| 7 | `MOGHARE360_PR_01_OTP_CANONICAL_RECOVERY_SCOPE_REPORT.md` |
| 8 | `MOGHARE360_PR_01_OTP_CANONICAL_RECOVERY_REPORT.md` |

---

## 2. Known-Working Private Reference Detection

| Candidate | In repo | Real values | Send function | curl | Host | Auth style | Endpoint | Modified |
|-----------|---------|-------------|---------------|------|------|------------|----------|----------|
| `C:\xampp\htdocs\private\m360-otp-config.php` | **No** | **Yes** | **No** (config only) | Via helper | `edge.ippanel.com` | `authorization_raw` (default) | `/v1/api/send` pattern | 2026-07-04 |
| `private/m360-otp-config.php` (repo) | Yes | Yes | No | Via helper | Same | Same | Same | 2026-07-04 (synced copy) |
| `private/m360-otp-config.example.php` | Yes | No (placeholders) | No | — | — | — | — | — |
| `private/logs/ippanel-debug.log` | Yes | N/A (trace only) | No | Yes | `edge.ippanel.com` | **accesskey_prefix** (failed 401) | `/v1/api/send` | 2026-06-27 |

**Result:** Known-working reference **FOUND**.

**Primary reference:** `C:\xampp\htdocs\private\m360-otp-config.php` — owner-placed runtime private config with real provider fields. No separate sender PHP file exists; sending is performed by the canonical helper using this config.

**Historical note:** `ippanel-debug.log` (2026-06-27) shows pre-D1A `AccessKey` auth failing with HTTP 401 Invalid token. D1A (2026-07-04) aligned helper to `authorization_raw` and reported `check_token` PASS with the same private config generation date.

---

## 3. Secret Safety Result

| Check | Result |
|-------|--------|
| Secrets printed in tools/reports | **None** |
| API keys/tokens in diagnostic output | **None** |
| Real private config copied to repo | **No** |
| Real private config overwritten on XAMPP | **No** |
| Example config contains placeholders only | **Yes** |

---

## 4. Current Helper vs Reference Comparison

| # | Item | Reference (private + D1A) | Current canonical helper | Match |
|---|------|---------------------------|--------------------------|-------|
| 1 | Host | `edge.ippanel.com` | `edge.ippanel.com` | **Yes** |
| 2 | Endpoint | `/v1/api/send` | `/v1/api/send` | **Yes** |
| 3 | Auth header | `Authorization: {raw_token}` | `authorization` mode (raw token) | **Yes** |
| 4 | Content-Type | `application/json` | `application/json` | **Yes** |
| 5 | Payload shape | pattern: `code`, `recipients`, `params.OTP` | `m360_otp_ippanel_pattern_payload()` | **Yes** |
| 6 | Mobile normalization | `+98` E.164 | `m360_otp_normalize_phone()` / `+98` | **Yes** |
| 7 | Pattern mode | pattern (pattern code present) | pattern when `pattern_id` set | **Yes** |
| 8 | Timeout | 20s (old) | 25s send / 10s connect (PR-01B) | Improved |
| 9 | SSL verify | default | default | **Yes** |
| 10 | HTTP method | POST | POST | **Yes** |
| 11 | cURL options | POST, RETURNTRANSFER, TIMEOUT | + CONNECTTIMEOUT | Compatible |
| 12 | Error handling | Persian failure messages | `M360_OTP_MSG_*` constants | **Yes** |
| 13 | Config keys | `ippanelApiKey`, `ippanelSender`, `ippanelPatternCode`, aliases | Loader normalizes all aliases | **Yes** |
| 14 | Config source (XAMPP) | `htdocs/private` | Loader merges `htdocs_private` + `apache_htdocs_private` | **Aligned (PR-01B)** |
| 15 | Apache vs CLI same config | Same file content (synced 2026-07-04) | Both load real values when readable | **Yes** |
| 16 | check_token endpoint | `/v1/api/acl/auth/check_token` | Same (diagnostic only) | **Yes** |
| 17 | check_token mandatory for send | **No** | Send does not gate on check_token | **Yes** |
| 18 | Send can work if check_token times out | **Yes** | Independent code paths | **Yes** |

**Conclusion:** Canonical helper **already matches** the known-working private reference behavior (post-D1A). No auth/payload/host realignment required.

---

## 5. Root Cause Classification

| Cause | Applies | Notes |
|-------|---------|-------|
| OTP_REF_CAUSE_1 | No | Host correct |
| OTP_REF_CAUSE_2 | No | Auth matches reference (`authorization_raw`) |
| OTP_REF_CAUSE_3 | No | Endpoint correct |
| OTP_REF_CAUSE_4 | No | Payload shape matches |
| OTP_REF_CAUSE_5 | **Partial (fixed)** | Loader now includes Apache `DOCUMENT_ROOT` parent private path |
| OTP_REF_CAUSE_6 | **Partial** | XAMPP has no `moghare360/private/m360-otp-config.php`; `htdocs/private` is the runtime source |
| OTP_REF_CAUSE_7 | **Yes** | `check_token` timeout is diagnostic-only and **misleading** for send failure diagnosis |
| OTP_REF_CAUSE_8 | No | curl options aligned; connect timeout added |
| **OTP_REF_CAUSE_9** | **Primary** | Probe A and Probe B **both fail** with connect timeout to `edge.ippanel.com:443` |
| OTP_REF_CAUSE_10 | No | — |

**Decision:** `OTP_PROVIDER_OR_NETWORK_BLOCKER_CONFIRMED` from agent CLI probes. **Not** a helper/reference mismatch.

---

## 6. Probe A Result (Canonical Helper)

| Field | Value |
|-------|-------|
| Config source | `merged_runtime` |
| Host | `edge.ippanel.com` |
| Endpoint | `/v1/api/send` |
| Auth format | `authorization_raw` |
| Request mode | `pattern` |
| HTTP status | 0 |
| curl error | `provider_or_network` (timeout) |
| Success | **no** |
| Message | ارسال کد تأیید انجام نشد... |

---

## 7. Probe B Result (Reference Private Config)

| Field | Value |
|-------|-------|
| Config source | `xampp_htdocs_private` |
| Host | `edge.ippanel.com` |
| Endpoint | `/v1/api/send` |
| Auth format | `authorization_raw` |
| Request mode | `pattern` |
| HTTP status | 0 |
| curl error | `timeout` |
| Success | **no** |

**Both probes fail identically** → no canonical vs reference divergence.

---

## 8. Files Modified

| File | Change |
|------|--------|
| `public_html/includes/m360-otp-config-loader.php` | Apache `DOCUMENT_ROOT` private path; cache reset; readable diagnostics |
| `public_html/includes/m360-otp-helper.php` | `CURLOPT_CONNECTTIMEOUT` on send + check_token |
| `tools/diagnose-pr-01b-otp-reference-reconcile.php` | Probe A/B diagnostic |
| `tools/test-pr-01b-otp-reference-alignment.php` | Alignment tests |
| `tools/test-pr-01b-otp-scope-security.php` | Scope security tests |
| `docs/audit/MOGHARE360_PR_01B_OTP_REFERENCE_RECONCILIATION_REPORT.md` | This report |

---

## 9. Files Not Modified

- `customer-request.php`, `customer-form.js`, `send-otp.php`, `verify-otp.php` (already canonical)
- Reception, cartable, JobCard, Auth/Login, DB/SQL
- Private real config files (repo + XAMPP)

---

## 10. Tests Passed

| Test | Result |
|------|--------|
| `test-pr-01b-otp-reference-alignment.php` | **14/14 PASS** |
| `test-pr-01b-otp-scope-security.php` | **9/9 PASS** |

---

## 11. Browser UAT Result

| Check | Status |
|-------|--------|
| XAMPP HTTP POST send-otp (after copy) | Valid JSON failure (provider/network) — not empty fatal |
| SMS received on `09128166648` | **Not verified** (agent environment cannot reach IPPanel) |
| OTP verify + session persist | **Blocked** on send |
| No secrets in response | **PASS** |

**Owner browser test still required** on host network where IPPanel was previously working.

---

## 12. Remaining Blockers

1. **IPPanel connectivity** from runtime host to `edge.ippanel.com:443` (both probes timeout).
2. **Owner browser confirmation** — send + verify on `customer-request.php`.
3. Optional: place symlink/copy of `htdocs/private/m360-otp-config.php` → `moghare360/private/` if Apache `open_basedir` blocks parent directory reads (only if Apache diagnostics show unreadable `htdocs_private`).

---

## 13. Commit Eligibility

```
NOT_ELIGIBLE_UNTIL_BROWSER_OTP_SEND_AND_VERIFY_PASS
```

---

## XAMPP Files Copied

- `includes/m360-otp-helper.php`
- `includes/m360-otp-config-loader.php`

`C:\xampp\htdocs\private\m360-otp-config.php` was **not** overwritten.

---

MOGHARE360 PR-01B reconciles the current canonical OTP path with the owner’s known-working private OTP reference, preserves secret safety, prevents duplicate OTP systems, proves or disproves provider/network failure with safe probes, and allows the project to proceed only after browser send and verify pass.

---

**END OF PR-01B REPORT**

# MOGHARE360 PR-01 — OTP Canonical Recovery Scope Report

**Mission ID:** PR-01  
**Phase:** GLOBAL_FIX_OTP_FIRST  
**Date:** 2026-07-05  
**Status:** Pre-fix investigation (scope gate)

---

## 1. Canonical Docs Read

| # | Document | Read |
|---|----------|------|
| 1 | `docs/00_CANONICAL/MOGHARE360_MASTER_PRODUCT_BLUEPRINT.md` | Yes |
| 2 | `docs/00_CANONICAL/MOGHARE360_DATABASE_GAP_MATRIX.md` | Yes |
| 3 | `docs/00_CANONICAL/MOGHARE360_RUNTIME_CLEANUP_PLAN.md` | Yes |
| 4 | `docs/00_CANONICAL/MOGHARE360_EXECUTION_ROADMAP.md` | Yes |
| 5 | `docs/00_CANONICAL/MOGHARE360_CURSOR_EXECUTION_RULES.md` | Yes |
| 6 | `docs/audit/MOGHARE360_PR_00_PROGRAM_RESET_CONTROL_PACK_REPORT.md` | Yes |

---

## 2. Modified OTP Files (Working Tree)

Per global audit freeze and open-tree inventory, the following OTP-related files are **modified or untracked** relative to last stable baseline:

| File | Status | Notes |
|------|--------|-------|
| `public_html/includes/m360-otp-helper.php` | **Modified** | D1A: IPPanel auth header mode, check_token, failure messages |
| `public_html/includes/m360-otp-config-loader.php` | **Modified** | Config merge, env map, diagnostics |
| `public_html/api/customer/send-otp.php` | Likely modified | References undefined `M360_OTP_TTL_SECONDS` |
| `public_html/api/customer/verify-otp.php` | Stable canonical | Calls `m360_otp_verify()` |
| `public_html/includes/m360-legacy-otp-deprecation-stub.php` | Present | 410 stubs for legacy routes |
| Root `send-otp.php`, `verify-otp.php`, etc. | Stubbed | Via deprecation stub |

**Not modified in this investigation:** `customer-request.php`, `customer-form.js` (canonical wiring intact).

---

## 3. customer-request.php OTP Route

| Item | Value |
|------|-------|
| **Page** | `public_html/customer-request.php` |
| **JS included** | `assets/js/customer-form.js` |
| **Send endpoint (JS)** | `api/customer/send-otp.php` |
| **Verify endpoint (JS)** | `api/customer/verify-otp.php` |
| **Payload key** | `{ phone: "09xxxxxxxxx" }` |
| **Legacy root routes** | **Not called** by customer-form.js |

**Conclusion:** customer-request uses **canonical API path** — not OTP_CAUSE_2.

---

## 4. JS OTP Request Path

| Step | Detail |
|------|--------|
| File | `public_html/assets/js/customer-form.js` |
| Function | `sendOtp()` → `fetchJson('api/customer/send-otp.php', { phone: phone })` |
| Verify | `verifyOtp()` → `fetchJson('api/customer/verify-otp.php', { phone, otp: code })` |
| Method | POST JSON, `credentials: 'same-origin` |
| Error fallback | Generic Persian: «ارسال کد تأیید انجام نشد...» on empty/non-JSON response |

**Conclusion:** JS payload matches endpoint — not OTP_CAUSE_5.

---

## 5. Send Endpoint → Helper Chain

```
api/customer/send-otp.php
  → require m360-otp-helper.php
  → m360_otp_send($phone)
    → m360_otp_sms_configured()
    → m360_otp_send_sms()
      → m360_otp_ippanel_send()
```

**Conclusion:** Endpoint uses canonical helper — not OTP_CAUSE_3.

---

## 6. Private Config Path

| Candidate | Purpose |
|-----------|---------|
| `C:\xampp\htdocs\private\m360-otp-config.php` | **Owner-expected XAMPP runtime** |
| `C:\xampp\htdocs\moghare360\private\m360-otp-config.php` | Project-relative private |
| `{repo}/private/m360-otp-config.php` | Repo private (gitignored) |
| `{repo}/private/m360-otp-config.example.php` | Example only (placeholders) |

**Loader current behavior (`m360-otp-config-loader.php`):**
- Resolves repo root as parent of `public_html`
- Loads `{root}/private/m360-otp-config.php`
- Does **not** check `htdocs/private/` (parent of project folder)

**Risk:** If runtime config exists only at `C:\xampp\htdocs\private\`, loader may miss it when app runs from `htdocs/moghare360/`.

D1A audit reported config PASS via CLI from repo context — browser/XAMPP path may differ.

---

## 7. XAMPP Runtime Config Existence

| Path | Expected |
|------|----------|
| `C:\xampp\htdocs\private\m360-otp-config.php` | Owner runtime candidate — **verify on host** |
| `C:\xampp\htdocs\moghare360\private\m360-otp-config.php` | Alternate — **verify on host** |

**Rule:** Do not print config values. Diagnostic tool reports `config loaded yes/no` and source label only.

---

## 8. Legacy Root OTP Routes

| File | Status |
|------|--------|
| `public_html/send-otp.php` | **STUBBED** — `m360_legacy_otp_route_deprecated()` HTTP 410 |
| `public_html/verify-otp.php` | **STUBBED** |
| `public_html/check-otp.php` | **STUBBED** |
| `public_html/send-contract-otp.php` | **STUBBED** |
| `public_html/verify-contract-otp.php` | **STUBBED** |

**Conclusion:** Legacy routes inactive — not OTP_CAUSE_7 for customer-request (JS does not call them).

---

## 9. Contract OTP Routes

| Route | Used by customer-request? |
|-------|----------------------------|
| `send-contract-otp.php` | **No** — stubbed |
| `verify-contract-otp.php` | **No** — stubbed |
| Contract API under `api/` | **No** — customer-form.js uses `api/customer/*` only |

**Conclusion:** Contract OTP not involved in customer online request flow.

---

## 10. Root Cause Classification

### Primary causes (confirmed in code review)

| Cause | Applies? | Evidence |
|-------|----------|----------|
| **OTP_CAUSE_1** | **PARTIAL** | Loader may not resolve `htdocs/private/` runtime path |
| OTP_CAUSE_2 | No | JS calls `api/customer/send-otp.php` |
| OTP_CAUSE_3 | No | Endpoint calls `m360_otp_send()` |
| **OTP_CAUSE_4** | **POSSIBLE** | If config not loaded → SMS inactive; if loaded but API fails → Persian failure message |
| OTP_CAUSE_5 | No | Payload `{ phone }` matches endpoint |
| **OTP_CAUSE_6** | **POSSIBLE** | Undefined constant crash → empty response → JS generic error |
| OTP_CAUSE_7 | No | Legacy not called by JS |
| OTP_CAUSE_8 | No | Canonical API active, not deprecated |
| **OTP_CAUSE_9** | **POSSIBLE** | Live IPPanel failure if config OK but provider rejects |
| OTP_CAUSE_10 | — | Fallback if multi-factor |

### Critical code defect (confirmed)

**`public_html/api/customer/send-otp.php` line 24** references `M360_OTP_TTL_SECONDS` constant which is **not defined** in current `m360-otp-helper.php` (replaced by `m360_otp_ttl_seconds()` function).

**Effect:** If `m360_otp_send()` succeeds, PHP fatal error on undefined constant → empty/non-JSON HTTP response → browser shows generic «ارسال کد تأیید انجام نشد» even when SMS was sent.

This explains **fixture vs browser divergence**: helper unit path may pass; full endpoint response path fatals.

### Combined root cause statement

**Primary:** Undefined `M360_OTP_TTL_SECONDS` in send endpoint success path (**OTP_CAUSE_6** variant — response contract break).

**Secondary:** Config loader may not prefer XAMPP `htdocs/private/` path (**OTP_CAUSE_1**).

**Tertiary:** If config loads but IPPanel rejects, provider failure (**OTP_CAUSE_4/9**) — D1A auth header fix already applied (raw token in Authorization).

---

## 11. Scope Gate Decision

**PROCEED** with controlled fix:

1. Fix `send-otp.php` to use `m360_otp_ttl_seconds()` (no undefined constant).
2. Extend config loader to merge `htdocs`-level private config when present.
3. Add PR-01 diagnostic + tests.
4. Copy to XAMPP and run browser/API verification.

**STOP conditions:** If after fixes, config still missing at all candidate paths → report exact path owner must populate (no secrets).

---

## 12. Files Allowed To Modify (This Phase)

Per PR-01 mission — OTP scope only.

---

## 13. Commit Eligibility

`NOT_ELIGIBLE_UNTIL_BROWSER_OTP_SEND_AND_VERIFY_PASS`

---

**END OF SCOPE REPORT**

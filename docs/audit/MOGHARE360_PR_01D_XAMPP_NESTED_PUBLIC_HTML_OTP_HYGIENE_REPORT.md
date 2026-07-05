# MOGHARE360 PR-01D — XAMPP Nested public_html OTP Hygiene Report

**Mission ID:** PR-01D  
**Phase:** Controlled runtime hygiene (Option B)  
**Date:** 2026-07-05  
**Commit Eligibility:** `NOT_ELIGIBLE_RUNTIME_HYGIENE_ONLY`

---

## 1. Owner Approval Summary

| Item | Status |
|------|--------|
| Canonical source `moghare360-portal/public_html/` | **Approved** |
| Private config repo + XAMPP `htdocs/private/` | **Approved** |
| Runtime mirror `C:\xampp\htdocs\moghare360\` | **Approved** |
| Option A (browser OTP with Iran route, VPN off) | **Approved — owner observed working** |
| Option B (nested `public_html/` legacy OTP stub only) | **Approved — implemented** |

---

## 2. Problem Statement

Apache document root for owner UAT is **`C:\xampp\htdocs\moghare360\`** (root). A secondary nested tree at **`C:\xampp\htdocs\moghare360\public_html\`** still contained **live legacy MySQL OTP routes** (`config.php` stack). Accidental access to URLs such as:

`http://localhost:8080/moghare360/public_html/send-otp.php`

could invoke the old stack (missing `config.php`, plaintext DB OTP) instead of the canonical deprecation behavior.

Root-level legacy routes were already stubbed (HTTP 410). Nested copies were **not**.

---

## 3. Action Taken (Runtime Only)

Replaced **five** nested XAMPP files with controlled deprecation stubs pointing to the **parent** includes tree:

| File | Path |
|------|------|
| `send-otp.php` | `C:\xampp\htdocs\moghare360\public_html\send-otp.php` |
| `verify-otp.php` | `C:\xampp\htdocs\moghare360\public_html\verify-otp.php` |
| `check-otp.php` | `C:\xampp\htdocs\moghare360\public_html\check-otp.php` |
| `send-contract-otp.php` | `C:\xampp\htdocs\moghare360\public_html\send-contract-otp.php` |
| `verify-contract-otp.php` | `C:\xampp\htdocs\moghare360\public_html\verify-contract-otp.php` |

**Stub pattern (nested — one level below moghare360 root):**

```php
<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'm360-legacy-otp-deprecation-stub.php';
m360_legacy_otp_route_deprecated();
```

**Behavior:** HTTP **410 Gone** with JSON body when `Accept: application/json`; HTML deprecation page otherwise. Message: «این مسیر قدیمی OTP غیرفعال شده است. از مسیر جدید سامانه استفاده کنید.»

---

## 4. Files Explicitly NOT Modified

| Category | Files |
|----------|-------|
| OTP engine | `includes/m360-otp-helper.php` |
| Config loader | `includes/m360-otp-config-loader.php` |
| Private secrets | `private/m360-otp-config.php`, `C:\xampp\htdocs\private\m360-otp-config.php` |
| Customer intake | `customer-request.php`, `assets/js/customer-form.js` |
| Canonical APIs | `api/customer/send-otp.php`, `api/customer/verify-otp.php` |
| Reception / cartable / JobCard | `erp-reception-intake-*`, `m360-reception-workbench-helper.php` |
| Auth / login | `staff-login.php`, `staff-auth.php`, `access-control.php` |
| Database | No SQL or schema changes |
| Git | No commit, no push |

---

## 5. Canonical Path Verification (Unchanged)

```
customer-request.php
  → assets/js/customer-form.js
  → api/customer/send-otp.php
  → api/customer/verify-otp.php
  → includes/m360-otp-helper.php
  → includes/m360-otp-config-loader.php
  → private/m360-otp-config.php (via loader)
```

| File | Repo SHA256 | XAMPP root SHA256 | Match |
|------|-------------|-------------------|-------|
| `customer-request.php` | `37909b33…28d0d` | `37909b33…28d0d` | ✅ |
| `assets/js/customer-form.js` | `4d6e4472…5461a` | `4d6e4472…5461a` | ✅ |
| `api/customer/send-otp.php` | `6736f894…7a675` | `6736f894…7a675` | ✅ |
| `api/customer/verify-otp.php` | `709f41ba…2492b` | `709f41ba…2492b` | ✅ |
| `includes/m360-otp-helper.php` | `3f3c714f…87c1e` | `3f3c714f…87c1e` | ✅ |
| `includes/m360-otp-config-loader.php` | `3e886878…b12e8` | `3e886878…b12e8` | ✅ |
| `private/m360-otp-config.php` | `46325346…a6be0` | `46325346…a6be0` | ✅ |

---

## 6. HTTP 410 Live Verification

POST with `Accept: application/json` to nested routes:

| URL | Status | JSON `ok` | Deprecation message |
|-----|--------|-----------|---------------------|
| `…/moghare360/public_html/send-otp.php` | **410** | `false` | ✅ |
| `…/moghare360/public_html/verify-otp.php` | **410** | `false` | ✅ |
| `…/moghare360/public_html/check-otp.php` | **410** | `false` | ✅ |
| `…/moghare360/public_html/send-contract-otp.php` | **410** | `false` | ✅ |
| `…/moghare360/public_html/verify-contract-otp.php` | **410** | `false` | ✅ |

Canonical customer intake URL remains:

`http://localhost:8080/moghare360/customer-request.php`

---

## 7. Test Results

**Tool:** `tools/test-pr-01d-xampp-nested-public-html-otp-hygiene.php`

```
Total: 45 | PASS: 45 | FAIL: 0
```

**Coverage:**

- Nested files exist, use deprecation stub, require parent `includes/`, no `config.php`
- Repo canonical hashes unchanged (baseline from PR-01C)
- XAMPP root canonical files match repo
- Private config hash untouched
- `customer-form.js` still targets `api/customer/*`
- Forbidden scope files present in repo (reception, staff-login, intake) — not modified by this phase
- Live HTTP 410 on all five nested routes via `localhost:8080`

**Run command:**

```bat
C:\xampp\php\php.exe tools\test-pr-01d-xampp-nested-public-html-otp-hygiene.php
```

---

## 8. Repo Artifacts Added (Non-Runtime)

| File | Purpose |
|------|---------|
| `tools/test-pr-01d-xampp-nested-public-html-otp-hygiene.php` | Regression guard for Option B |
| `docs/audit/MOGHARE360_PR_01D_XAMPP_NESTED_PUBLIC_HTML_OTP_HYGIENE_REPORT.md` | This report |

No repo `public_html/` OTP runtime files were changed in PR-01D.

---

## 9. Residual Notes

1. **`customer-login.php`** and **`customer-contract.php`** in repo still *form-post* to root legacy route names (`send-otp.php`, `send-contract-otp.php`). Root stubs already return 410. Rewiring to canonical APIs is **Option C** — out of scope for PR-01D.
2. Nested `public_html/` may contain other legacy mirror files beyond these five OTP routes; only OTP hygiene was authorized.
3. Option A browser OTP success (Iran route, VPN off) is owner-confirmed; software path unchanged by PR-01D.

---

## 10. Commit Eligibility

| Gate | Result |
|------|--------|
| Browser OTP send + verify (Option A) | **Owner confirmed PASS** |
| Nested legacy OTP accidental access | **PASS (410)** |
| Canonical files unchanged | **PASS** |
| Private config untouched | **PASS** |
| Forbidden scope untouched | **PASS** |
| Git commit / push | **NOT performed** |

**Commit eligibility:** `NOT_ELIGIBLE_RUNTIME_HYGIENE_ONLY` — XAMPP nested stubs are runtime-only; repo change is test + audit doc only. Owner may later include PR-01D test in a broader commit after separate eligibility review.

---

## 11. Sign-Off Checklist

- [x] Five nested XAMPP OTP files stubbed
- [x] HTTP 410 verified live
- [x] Canonical OTP chain hash-verified unchanged
- [x] Private config untouched
- [x] No reception / cartable / JobCard / Auth / DB changes
- [x] No commit / push
- [x] Test suite 45/45 PASS

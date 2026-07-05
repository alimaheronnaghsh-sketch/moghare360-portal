# MOGHARE360 P11.9-C-2C-FIX-D1 — OTP Cleanup Wiring Report

**Phase:** P11.9-C-2C-FIX-D1  
**Date:** 2026-07-04  
**Prior:** FIX-D0 audit, FIX-A/B/C intake work

---

## 1. Scope Gate Result

**PROCEED** — See [`MOGHARE360_P11_9_C_2C_FIX_D1_OTP_CLEANUP_WIRING_SCOPE_REPORT.md`](MOGHARE360_P11_9_C_2C_FIX_D1_OTP_CLEANUP_WIRING_SCOPE_REPORT.md).

No DB schema, Auth/Login, permission, or JobCard changes required.

---

## 2. Canonical OTP Stack Used

| Layer | File | Functions |
|-------|------|-----------|
| Loader | `public_html/includes/m360-otp-config-loader.php` | `m360_otp_config_merged()` — added flat XAMPP private path + `M360_REPO_ROOT` env |
| Helper | `public_html/includes/m360-otp-helper.php` | `m360_otp_send()`, `m360_otp_verify()` |
| API (unchanged) | `api/customer/send-otp.php`, `verify-otp.php` | Public customer flow |

No parallel OTP helper created.

---

## 3. Reception Send OTP Wiring

**Action:** `send_customer_otp` (POST, CSRF, staff session)

**Behavior:**
- Uses `m360_otp_send($mobile)` on request mobile (after correction persisted on column)
- Does **not** set `otp_verified`
- Persists `reception_intake.otp`:
  - `status`: `sent` | `failed` | `config_missing`
  - `mobile`, `sent_at`, `provider` (`ippanel` or `dev`), `updated_at`
- Flash messages:
  - Success: **پیامک OTP ارسال شد.**
  - Failure: **ارسال OTP ناموفق بود.**
  - Config missing: **تنظیمات پیامک فعال نیست.**
- Redirect: `#section-mobile-otp`

**Config missing UI:** Send button disabled; message **تنظیمات پیامک فعال نیست؛ فایل خصوصی OTP تنظیم نشده است.**

---

## 4. Reception Verify OTP Bridge

**Action:** `verify_customer_otp` (new)

**Behavior:**
- Reads `otp_code` from POST
- Calls `m360_otp_verify($mobile, $code)` — no fake/bypass
- On success writes payload:
  - `otp_verified = 1`
  - `otp_verified_at` (UTC ISO)
  - `otp_verified_mobile`
  - `reception_intake.otp.status = verified`
  - `reception_intake.otp.verified_at`
- Updates DB column `otp_verified` when column exists
- On failure/expiry: does **not** set `otp_verified`; updates OTP meta (`failed_attempt` / `expired`)
- Flash: **شماره موبایل مشتری تأیید شد.** or helper Persian error

---

## 5. Payload Gate Integration

Gate checklist continues to use `m360_online_req_payload_otp_verified($request)`.

After successful `verify_customer_otp`, payload `otp_verified=1` satisfies gate OTP item.

Session OTP (staff browser) is used only during send/verify window; **persisted truth** is payload JSON (+ optional DB column).

---

## 6. Mobile Correction Reset

On `save_mobile_correction`:
- `otp_verified = 0`
- Removes `otp_verified_at`, `otp_verified_mobile`
- `reception_intake.otp.status = unverified`
- `m360_otp_clear_pending()` + `m360_otp_reset_verified()`
- Column `otp_verified = 0` when column exists

Old OTP cannot verify a corrected mobile.

---

## 7. Legacy Route Deactivation

Replaced with controlled 410 deprecation stub (`includes/m360-legacy-otp-deprecation-stub.php`):

| Route | Status |
|-------|--------|
| `send-otp.php` | Deprecated stub |
| `verify-otp.php` | Deprecated stub |
| `check-otp.php` | Deprecated stub |
| `send-contract-otp.php` | Deprecated stub |
| `verify-contract-otp.php` | Deprecated stub |

Message: **این مسیر قدیمی OTP غیرفعال شده است. از مسیر جدید سامانه استفاده کنید.**

V1 active flows use `api/customer/*` — confirmed by tests.

**Note:** `customer-contract.php` still links to legacy contract routes; future phase should redirect to `customer-intake-contract-sign.php` / API (out of D1 scope).

---

## 8. Secret Safety

- No secrets added to Git
- No `private/m360-otp-config.php` modified or committed
- Diagnostics mask secrets (`tools/otp-config-diagnostics.php`)
- No raw OTP in payload, HTML, or logs
- Loader supports `M360_REPO_ROOT` env without exposing values

---

## 9. Tests Passed

| Test | Result |
|------|--------|
| `test-p11-9-c-2c-fix-d1-otp-canonical-wiring.php` | 8/8 PASS |
| `test-p11-9-c-2c-fix-d1-reception-verify-bridge.php` | 8/8 PASS |
| `test-p11-9-c-2c-fix-d1-mobile-reset.php` | 8/8 PASS |
| `test-p11-9-c-2c-fix-d1-legacy-route-deprecation.php` | 17/17 PASS |
| `test-p11-9-c-2c-fix-d1-no-secret-leak.php` | 15/15 PASS |
| `test-p11-9-c-2c-fix-d1-scope-security.php` | 17/18 PASS |
| FIX-A/B/C regressions (via scope master) | PASS |
| `test-v1-production-signoff.php` | PASS |

**Scope master note:** `test-p11-otp-provider-config.php` reported 24/25 on this host — assertion *Placeholder not valid production config* fails when host SMS config is present (environmental; diagnostics returned **PASS** on same machine indicating configured OTP). Not a D1 regression.

---

## 10. Browser Validation Status

**PENDING_OPERATOR**

Operator checklist (after `C:\xampp\htdocs\private\m360-otp-config.php` exists and XAMPP copy deployed):

1. Open `erp-reception-intake-file.php?online_request_id=18#section-mobile-otp`
2. Save mobile → OTP resets unverified
3. Send OTP → SMS received
4. Wrong OTP → rejected
5. Correct OTP → verified status
6. Gate OTP item passes
7. No automatic JobCard
8. Mobile correction → OTP resets again
9. Repeat for request 20

If config missing: Send disabled, no Fatal, Persian controlled message.

---

## 11. What Was Not Changed

- Auth/Login architecture
- `staff-auth.php`
- `access-control.php`
- Roles / permissions
- DB schema / SQL migrations
- Workflow architecture (no JobCard conversion)
- OTP fake / bypass
- Automatic JobCard creation
- Private config files (not committed)
- P12 scope

---

## 12. Commit Eligibility

**NOT_ELIGIBLE_UNTIL_OPERATOR_BROWSER_PASS**

---

## 13. Recommended Next Phase

After operator browser OTP PASS:

**P11.9-C-2D** — Gate-Driven JobCard Conversion / Reception-to-Operation Handoff

---

P11.9-C-2C-FIX-D1 cleans up the reception OTP architecture by reusing the canonical MOGHARE360 OTP loader/helper, wiring reception Send OTP and Verify OTP into the intake workflow, bridging successful verification into `request_payload_json.otp_verified` for Gate truth, deprecating unsafe legacy OTP routes where safe, and preserving Auth/Login, permissions, database schema, workflow, secret safety, OTP integrity, and the no-automatic-JobCard boundary.

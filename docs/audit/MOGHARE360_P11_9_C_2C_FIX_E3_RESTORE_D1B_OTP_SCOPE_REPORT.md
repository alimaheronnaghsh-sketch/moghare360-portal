# MOGHARE360 P11.9-C-2C-FIX-E3 — Restore D1B OTP Scope Report

**Phase:** P11.9-C-2C-FIX-E3  
**Date:** 2026-07-05  
**Status:** Scope gate PASSED — E2 UI/helper OTP regression confirmed; fix without architecture change

---

## 1. D1B / D1 Stable OTP Guarantees

| Guarantee | D1/D1B intent |
|-----------|----------------|
| Send OTP visible | Button on mobile_otp step when not verified |
| Verify input visible | OTP code field when valid mobile exists |
| mobile_otp complete only when verified | Gate uses `m360_online_req_payload_otp_verified()` |
| Canonical send/verify | `m360_otp_send()` / `m360_otp_verify()` only |
| Payload truth | `otp_verified=1` in `request_payload_json` |

D1B (browser-stable tranche after D1/D1A) additionally required: success flash must not show stale `failed` badge; single mobile resolver; no edit-click to reveal verify form.

---

## 2. E2 Regression Differences Found

| Area | E2 behavior (broken vs D1B) |
|------|-----------------------------|
| Mobile resolver | DB row mobile checked **before** `reception_intake.mobile_correction` |
| Verify handler | `$existing` used **before** parse (latent PHP error) |
| OTP UI | Verify form nested behind separate `$otpShowVerify`; split forms in wizard case |
| UI status | Flash success only via `otp_sent=1` GET param, not success message text |
| Failed persistence | `sent_at` written on failed sends (misleading metadata) |
| Lock ordering | OTP actions passed through general lock guard (same outcome when unlocked, confusing path) |

---

## 3. Duplicate / Split Path Analysis

| Check | Result |
|-------|--------|
| Duplicate OTP status writer | Single `reception_intake.otp` writer in send/verify |
| Duplicate send wrapper | Single `m360_rw_intake_process_send_otp()` |
| Duplicate status resolver | Consolidated `m360_rw_intake_otp_ui_status()` |
| Duplicate forms | E2 wizard had inline duplicate markup — fixed via `m360_rw_intake_render_otp_wizard_block()` |
| Stale failed overriding success | Possible when payload `failed` + missing `otp_sent` flash — fixed with flash message honor |
| Lock guard blocking send early | Only when intake locked — correct; reorder for clarity |
| Wrong mobile resolution | **Confirmed regression** — priority inverted |
| Missing CSRF/fields in wizard OTP | Present — not root cause |

---

## 4. Does `send_customer_otp` Reach `m360_otp_send()`?

**Yes.** `m360_rw_intake_process_send_otp()` line calls `m360_otp_send($mobile)` after config/mobile validation.

Browser message «ارسال کد تأیید انجام نشد...» is `M360_OTP_MSG_SMS_FAILED` from **canonical helper** when IPPanel HTTP/meta fails — not a reception-local fake. E3 fixes UI/state regressions so successful sends display correctly; real provider failures still surface honestly with `category` diagnostic.

---

## 5. Result Misread vs Real Failure

- **Real provider failure:** IPPanel returns non-2xx or `meta.status=false` → `M360_OTP_MSG_SMS_FAILED`
- **UI false failure:** Payload `otp.status=failed` shown while flash indicates success → **fixed** via flash message + `otp_sent` honor
- **Invalid token:** Separate Persian message from D1A mapping

---

## 6. Provider Category (Diagnostic)

`m360_rw_intake_classify_otp_send_failure()` categories:

- `config_missing`
- `invalid_token`
- `rate_limited`
- `provider_fail`

No raw body/token/OTP in logs returned to UI.

---

## 7. Files Modified (E3)

- `public_html/includes/m360-reception-workbench-helper.php`
- `public_html/erp-reception-intake-file.php`

No change to `m360-otp-helper.php` auth header (D1A preserved).

---

## 8. Confirmations

No OTP architecture, Auth/Login, schema, permission, or JobCard change required.

**Scope gate:** PROCEED with targeted E3 OTP restoration inside E2 wizard.

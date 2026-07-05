# MOGHARE360 P11.9-C-2C-FIX-E1 — Stepper Browser Regression Scope Report

**Phase:** P11.9-C-2C-FIX-E1  
**Date:** 2026-07-04  
**Status:** SCOPE_APPROVED — Browser UX regression fix only

---

## 1. Why Send OTP Became «ارسال ناموفق» After FIX-E

**Primary cause (UI mislabeling):** FIX-E displayed `reception_intake.otp.status=failed` from payload in the status badge even when the latest redirect flash indicated success (`ok=1`, `otp_sent=1`). A prior failed attempt or stale payload state could show «ارسال ناموفق» while canonical `m360_otp_send()` had succeeded on retry.

**Secondary causes addressed:**
- OTP status renderer did not honor `otp_sent=1&ok=1` query hints after successful redirect.
- Send form used `return_section_hidden('mobile_otp')` instead of explicit `return_step_hidden('otp')`.
- Mobile resolution used only `$request['mobile']`, not corrected mobile in payload.

**Not an OTP architecture break:** `send_customer_otp` still routes to `m360_rw_intake_process_send_otp()` → `m360_otp_send()`. D1A auth header unchanged.

---

## 2. Send OTP Form POST Fields (Post-FIX-E Audit)

| Field | Present |
|---|---|
| `action_type=send_customer_otp` | ✅ |
| `online_request_id` | ✅ |
| CSRF (`erp_csrf_token`) | ✅ |
| `return_active_step=otp` | ✅ via `m360_rw_intake_return_step_hidden('otp')` |
| `return_section` | ✅ (section anchor for fail-stay) |

---

## 3. Mobile Value After Stepper Refactor

- Send/verify now use `m360_rw_intake_resolve_mobile_for_otp($request, $payload)`.
- Reads `request.mobile`, then `payload.mobile_corrected` / `reception_intake.mobile.corrected`.

---

## 4. Canonical m360_otp_send() — Still Used

`m360_rw_intake_process_send_otp()` calls `m360_otp_send($mobile)` after `m360_rw_intake_load_otp_helper()`. No alternate/bypass path introduced.

---

## 5. Provider Error vs UI Mislabeling

| Scenario | Message |
|---|---|
| Real provider fail | Provider message or «ارسال OTP ناموفق بود.» |
| Invalid token (D1A) | «توکن iPPanel نامعتبر است...» |
| Config missing | «تنظیمات پیامک فعال نیست.» |
| Success | «پیامک OTP ارسال شد.» + payload `status=sent` |
| Stale UI | Fixed: `otp_sent&ok` overrides status badge |

---

## 6. FIX-E OTP Status Rendering Issue

`m360_rw_intake_otp_ui_status()` mapped payload `failed` → «ارسال ناموفق» without considering successful redirect flash. Fixed in E1.

---

## 7. Why Inactive Steps Were Still Visible (Tomar)

**Root cause:** FIX-E rendered **all step full forms in HTML** for incomplete sections because `m360_rw_intake_stepper_section_ui_state()` only forced `show_form=true` on the active step but left `show_form=true` for all other incomplete sections.

CSS `display:none` on non-active panels was fragile (not server-side truth) and still produced a giant DOM.

---

## 8. CSS Loaded but Selectors Mismatched?

CSS was present (`.m360-rw-step-panel.is-active`) but relied on hiding non-active panels. When panels rendered full forms, any CSS failure or specificity issue exposed stacked forms.

---

## 9. JS Dependency for Hiding?

FIX-E depended on CSS-only hiding. E1 uses **server-side** `if ($activeStep === 'step')` guards — JS is scroll enhancement only.

---

## 10. Exact Files to Modify

| File | Change |
|---|---|
| `public_html/erp-reception-intake-file.php` | Server-side single active full form; OTP block fix |
| `public_html/includes/m360-reception-workbench-helper.php` | OTP mobile resolve, status flash, compact summaries |
| `public_html/assets/css/moghare360-v1-luxury-ui.css` | Compact inactive cards, active card emphasis |
| `tools/test-p11-9-c-2c-fix-e1-*.php` | Five regression tests |
| `docs/audit/MOGHARE360_P11_9_C_2C_FIX_E1_*.md` | Scope + final reports |

---

## 11. Scope Gate Confirmation

No OTP architecture, IPPanel auth, DB schema, Auth/Login, permissions, or automatic JobCard changes required.

**SCOPE_GATE: PASS**

# MOGHARE360 P11.9-C-2C-FIX-E7A-DIAG — Documents Contract CSRF Diagnostic Report

## 1. Scope

Diagnostic-only static inspection of the staff documents-step CSRF failure observed in browser UAT:

- URL: `erp-reception-intake-file.php?online_request_id=18&active_step=documents#step-documents`
- Symptom: Submitting **ایجاد لینک مطالعه و امضای قرارداد مشتری** or **ذخیره مستندات و ادامه** lands on `erp-reception-intake-save.php` with message **اعتبار امنیتی فرم منقضی شده است**
- No code, CSRF, OTP, wizard, contract, DB, or Auth changes were made in this phase

## 2. Files Inspected

| File | Role |
|------|------|
| `public_html/erp-reception-intake-file.php` | Documents step form rendering |
| `public_html/includes/m360-reception-workbench-helper.php` | Contract staff block form, redirect hidden fields, blocked legacy contract actions |
| `public_html/erp-reception-intake-save.php` | POST save endpoint + CSRF gate |
| `public_html/includes/m360-reception-helper.php` | `m360_reception_csrf_input_html()`, `m360_reception_csrf_is_valid()`, intake CSRF error page |
| `includes/erp-csrf.php` | Session token create/validate primitives (`erp_csrf_create_token`, `erp_csrf_validate_token`) |
| `public_html/includes/erp-customer-core-helper.php` | Loads `erp-csrf.php`, defines `erp_csrf_input()` wrapper |
| `includes/erp-auth-context.php` | Session start via `erp_auth_context_start()` (read-only chain inspection) |
| `public_html/assets/js/m360-reception-intake.js` | Checked for form/CSRF interception (none found) |
| XAMPP deploy mirror `C:\xampp\htdocs\moghare360\` | Confirmed deployed intake/save/helper files match repo E7 structure |

Not modified: OTP helpers, staff-auth, access-control, DB schema, contract customer page.

## 3. Documents Step Form Inventory

All forms render only when `$canShowStepForm` is true (`$canAct && !$isLocked`).  
Global CSRF HTML is built once at page top:

```php
$csrfInputHtml = $canAct ? m360_reception_csrf_input_html() : '';
$saveUrl = 'erp-reception-intake-save.php';
```

There is **no** `active_step` or `return_step` hidden field name. Redirect context uses **`return_active_step`** and **`return_section`** via `m360_rw_intake_return_step_hidden()`.

### Form 1 — Diagnostic PDF upload

| # | Attribute | Value |
|---|-----------|-------|
| 1 | Purpose | Upload diagnostic PDF |
| 2 | Method | `POST` |
| 3 | Action | `erp-reception-intake-save.php` |
| 4 | `action_type` | `save_diagnostic_pdf` |
| 5 | `online_request_id` | **Yes** |
| 6 | `active_step` hidden | **No** (uses `return_active_step=documents`) |
| 7 | `return_step` hidden | **No** |
| 8 | `return_section` hidden | **Yes** (`section-diagnostic-pdf` via `m360_rw_intake_return_step_hidden('documents')`) |
| 9 | CSRF hidden | **Yes** (`<?= $csrfInputHtml ?>`) |
| 10 | CSRF field name | `erp_csrf_token` |
| 11 | CSRF helper | `m360_reception_csrf_input_html()` → `erp_csrf_input('online_request_reception')` |
| 12 | Accidental GET | **No** (`method="post"`) |
| 13 | Nested inside another form | **No** |
| 14 | Duplicate `<form>` tags | **No** (single form, closed before contract block) |
| Extra | `enctype` | `multipart/form-data` (CSRF hidden still inside form) |

Source: `erp-reception-intake-file.php` lines 331–338.

### Form 2 — Prepare customer contract review link

| # | Attribute | Value |
|---|-----------|-------|
| 1 | Purpose | Generate hashed customer contract review token; show one-time link |
| 2 | Method | `POST` |
| 3 | Action | `erp-reception-intake-save.php` |
| 4 | `action_type` | `prepare_customer_contract_review` |
| 5 | `online_request_id` | **Yes** |
| 6 | `active_step` hidden | **No** (uses `return_active_step=documents`, duplicated once by section helper) |
| 7 | `return_step` hidden | **No** |
| 8 | `return_section` hidden | **Yes** (`section-diagnostic-pdf` — primary section of `documents` step, not `section-contract`) |
| 9 | CSRF hidden | **Yes** (`echo $csrfInputHtml`) |
| 10 | CSRF field name | `erp_csrf_token` |
| 11 | CSRF helper | Same shared `$csrfInputHtml` passed from intake file |
| 12 | Accidental GET | **No** |
| 13 | Nested inside another form | **No** (rendered after Form 1 closes) |
| 14 | Duplicate `<form>` tags | **No** |

Source: `m360_rw_intake_render_documents_contract_staff_block()` lines 2870–2877.

Shown only when contract not yet `customer_accepted`.

### Form 3 — Save documents and cost

| # | Attribute | Value |
|---|-----------|-------|
| 1 | Purpose | Save cost agreement; advance only if documents complete |
| 2 | Method | `POST` |
| 3 | Action | `erp-reception-intake-save.php` |
| 4 | `action_type` | `save_documents_and_cost` |
| 5 | `online_request_id` | **Yes** |
| 6 | `active_step` hidden | **No** (`return_active_step=documents`) |
| 7 | `return_step` hidden | **No** |
| 8 | `return_section` hidden | **Yes** |
| 9 | CSRF hidden | **Yes** |
| 10 | CSRF field name | `erp_csrf_token` |
| 11 | CSRF helper | Same shared `$csrfInputHtml` |
| 12 | Accidental GET | **No** |
| 13 | Nested inside another form | **No** |
| 14 | Duplicate `<form>` tags | **No** |

Source: `erp-reception-intake-file.php` lines 349–357.

### Legacy forms — NOT present in UI

| Action | Rendered in documents UI |
|--------|--------------------------|
| `run_intake_contract` | **No** |
| `approve_intake_contract` | **No** |

Grep of `erp-reception-intake-file.php` confirms no **اجرای قرارداد پذیرش** button remains.

## 4. CSRF Token Rendering Findings

1. **Single generation per page load:** `m360_reception_csrf_input_html()` is called **once** at line 34 of `erp-reception-intake-file.php`. The returned HTML string is reused in all three documents forms.
2. **Purpose key:** `M360_RECEPTION_CSRF_PURPOSE = 'online_request_reception'`.
3. **Field name:** `erp_csrf_token` (from `erp_csrf_input()` in `erp-customer-core-helper.php`).
4. **Token creation side effect:** Every call to `erp_csrf_input()` invokes `erp_csrf_create_token()`, which **overwrites** `$_SESSION['erp_csrf_tokens']['online_request_reception']` with a new random value (`includes/erp-csrf.php` lines 40–44).
5. **Multiple forms share one token value on the page:** Because `$csrfInputHtml` is cached once, all three forms embed the **same** token string. This is correct for same-page multi-form use.
6. **No second CSRF generation on documents page:** Wizard progress, footer nav, and `m360-reception-intake.js` do not call CSRF helpers. `$csrfConvertHtml` is assigned but not rendered on this page.
7. **Empty CSRF case:** If `$canAct` is false, `$csrfInputHtml = ''` and `$canShowStepForm` is also false, so action forms should not render.
8. **prepare vs save_documents:** Both use identical CSRF context and the same cached `$csrfInputHtml`.

## 5. Save Endpoint CSRF Validation Findings

| # | Finding |
|---|---------|
| 1 | Validation function: `m360_reception_csrf_is_valid($csrfToken)` |
| 2 | Expected POST field: `erp_csrf_token` |
| 3 | Session required: **Yes** — `erp_csrf_validate_token()` reads `$_SESSION['erp_csrf_tokens'][$form_key]` after `session_start()` |
| 4 | One-time vs reusable: **Reusable** — validation uses `hash_equals()` only; token is **not** unset/consumed on success |
| 5 | Consumed/rotated after submit: **Not on validate** — rotation happens only when a new page calls `erp_csrf_create_token()` again |
| 6 | Multiple forms sharing token: **Supported** on same page (same cached HTML) |
| 7 | Documents page token count: **One session token; one HTML blob echoed into three forms** |
| 8 | prepare vs save_documents CSRF context: **Identical** purpose key and helper chain |
| 9 | File upload form: `enctype="multipart/form-data"`; CSRF hidden field is first child inside form — still posted |
| 10 | CSRF failure recovery: `online_request_id` read from POST **before** CSRF check (`erp-reception-intake-save.php` lines 20–28). Error page uses `context='intake'` with hardcoded back link to `active_step=documents#step-documents`. **`active_step` is not read from POST on error path** (hardcoded in error content). |

Validation chain:

```
erp-reception-intake-save.php
  → m360_reception_csrf_is_valid()
    → erp_csrf_validate_token('online_request_reception', POST token)
      → includes/erp-csrf.php (session compare)
```

If `erp_csrf_validate_token` is missing, `m360_reception_csrf_is_valid()` returns **false** (fail closed).

## 6. Browser Failure Cause Classification

| Code | Verdict | Evidence |
|------|---------|----------|
| **CAUSE_A** — CSRF missing from prepare form | **RULED OUT** | `echo $csrfInputHtml` present in contract staff block |
| **CAUSE_B** — Field name mismatch | **RULED OUT** | Form posts `erp_csrf_token`; save reads `erp_csrf_token` |
| **CAUSE_C** — One-time token invalidated by sibling form | **UNLIKELY** | Token not consumed on validate; same token in all forms on one render |
| **CAUSE_D** — Stale/cached token after redirect/back/multi-tab | **LIKELY** | `erp_csrf_create_token()` regenerates on every page GET; older tab or bfcache can hold HTML token ≠ current session token |
| **CAUSE_E** — Nested forms | **RULED OUT** | Forms are sequential siblings, properly closed |
| **CAUSE_F** — Button outside form | **RULED OUT** | Submit buttons inside their respective `<form>` elements |
| **CAUSE_G** — prepare as GET/wrong POST | **RULED OUT** | `method="post"`, correct `action_type` |
| **CAUSE_H** — Save loses request id on CSRF fail | **PARTIAL / RULED OUT for id** | `online_request_id` recovered from POST; `active_step` not from POST but hardcoded in back link |
| **CAUSE_I** — Different CSRF helper on render vs validate | **RULED OUT** | Both use `M360_RECEPTION_CSRF_PURPOSE` + `erp-csrf.php` |
| **CAUSE_J** — Unknown / needs runtime debug | **LIKELY (secondary)** | If PHP session cookie is not sent on POST (session path, host mismatch, expired session, cookie blocked), validation fails even with visible CSRF field |

### Primary diagnostic conclusion

Static code shows documents forms **are correctly wired** with CSRF fields and matching validation. The failure is **not** explained by missing hidden inputs or wrong action types in FIX-E7 source.

The most probable explanations are **runtime session/token drift**:

1. **CAUSE_D** — Token in rendered HTML no longer matches session because another page load/tab regenerated `online_request_reception` token, or browser served a stale documents page.
2. **CAUSE_J** — POST request arrives without the same PHP session that created the token (session cookie issue).

Architectural amplifier: `erp_csrf_input()` always calls `erp_csrf_create_token()` (overwrite), never reuses an existing valid session token. Any extra page load before submit invalidates prior HTML.

## 7. Contract Action Routing Findings

| Action | UI rendered | Backend handled | Blocked | Conflicts with prepare |
|--------|-------------|-----------------|---------|------------------------|
| `run_intake_contract` | **No** | **Yes** (case in `apply_action`) | **Yes** — returns error «تأیید قرارداد فقط از طریق صفحه مطالعه مشتری…» | **No** UI path |
| `approve_intake_contract` | **No** | **Yes** (same blocked case) | **Yes** | **No** UI path |
| `prepare_customer_contract_review` | **Yes** | **Yes** | **No** | N/A |

Legacy actions remain in helper switch for explicit rejection only; they do not appear in documents UI and cannot be clicked in browser.

Minor redirect inconsistency (not CSRF): prepare form uses `m360_rw_intake_return_step_hidden('documents')` which sets `return_section=section-diagnostic-pdf`, while action anchor map maps prepare → `section-contract`.

## 8. Risk Assessment

| Risk | Level | Note |
|------|-------|------|
| Contract acceptance bypass via CSRF fail | Low | Fail closed — no write occurs |
| Staff blocked on documents step | **High (operational)** | Cannot prepare customer link or save documents until CSRF/session aligned |
| Data corruption / JobCard | None | CSRF gate stops before `m360_rw_intake_process_save()` |
| OTP / Auth regression | None | Not touched in this diagnostic |
| Multi-form token overwrite bug on same render | Low | Verified single `$csrfInputHtml` generation |

## 9. Recommended Fix Plan

**Next fix phase only — not implemented here.**

1. **Runtime confirm (browser DevTools):** On failing POST to `erp-reception-intake-save.php`, verify `erp_csrf_token` and `online_request_id` are present in Form Data; verify session cookie is sent.
2. **Reuse session token on render:** Change `m360_reception_csrf_input_html()` or `erp_csrf_create_token()` to return existing `online_request_reception` token if still present instead of always regenerating — reduces stale-tab failures.
3. **Hard refresh protocol for UAT:** Full reload documents URL before first submit after any navigation from another intake step or CSRF error page.
4. **Optional:** After successful CSRF validation on intake save, rotate token explicitly (if security policy requires) — currently not rotated.
5. **Optional redirect polish:** Use contract-specific `return_section` (`section-contract`) on prepare form for anchor consistency (routing only, not CSRF root cause).
6. **If CAUSE_J confirmed:** Inspect PHP `session.save_path`, cookie params, and localhost host consistency (`localhost` vs `127.0.0.1`).

## 10. Files That Must Be Changed In Next Fix

| File | Likely change |
|------|----------------|
| `public_html/includes/m360-reception-helper.php` | Token reuse in `m360_reception_csrf_input_html()` |
| and/or `includes/erp-csrf.php` | Add get-or-create token helper; avoid blind overwrite |
| `public_html/includes/m360-reception-workbench-helper.php` | Optional: prepare form `return_section` → `section-contract` |
| `public_html/erp-reception-intake-file.php` | Only if render order or CSRF generation point moves |

Runtime debug (temporary logging of session id + token presence) would be a separate controlled fix sub-phase if CAUSE_J persists.

## 11. Files That Must Not Be Changed

- `m360-otp-helper.php`, `m360-otp-config-loader.php`, OTP UI, IPPanel config
- `send_customer_otp` / `verify_customer_otp` flows
- `staff-auth.php`, `access-control.php`, roles/permissions
- DB schema / SQL migrations
- Customer contract review page logic (FIX-E7 contract flow)
- JobCard conversion paths
- Wizard routing logic (E6A) except incidental redirect anchor polish

## 12. Final Diagnostic Conclusion

Documents-step forms in FIX-E7 source **include CSRF correctly** for all three POST actions. The browser CSRF-expired page is produced by the **intentional fail-closed gate** in `erp-reception-intake-save.php`, meaning the posted `erp_csrf_token` did not match the session value for purpose `online_request_reception`.

Static analysis does **not** support missing-field or wrong-endpoint causes. The highest-probability root causes are **stale HTML token vs regenerated session token (CAUSE_D)** and **session cookie not accompanying POST (CAUSE_J)**. A controlled next fix should target token reuse and runtime session verification before altering contract or wizard behavior.

**Commit Eligibility:** `NOT_ELIGIBLE_DIAGNOSTIC_ONLY`

---

P11.9-C-2C-FIX-E7A-DIAG performs a no-code diagnostic of the documents-step CSRF failure, identifies the exact form/token/validation mismatch causing the browser CSRF expired page, and prepares a controlled fix plan without changing OTP, Wizard, Contract flow, Auth/Login, database schema, private config, or JobCard behavior.

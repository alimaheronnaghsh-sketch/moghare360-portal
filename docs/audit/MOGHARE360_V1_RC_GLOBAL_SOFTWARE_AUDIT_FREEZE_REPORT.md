# MOGHARE360 V1 RC — Global Software Audit Freeze Report

**Phase:** REPORT ONLY — NO CODE CHANGE  
**Date:** 2026-07-05  
**Commit Eligibility:** `NOT_ELIGIBLE_AUDIT_FREEZE`

---

## 1. Executive Summary

MOGHARE360 V1 RC is in an **audit freeze** with **114 open working-tree entries** (16 modified, 98 untracked). The reception intake wizard, customer contract/cartable bridge (E7D), and OTP stack have diverged from stable browser reality.

**Critical findings:**

| Area | Status | Risk |
|------|--------|------|
| OTP customer-request send | **Browser failing** for `09128166648` | HIGH — blocks entire customer entry |
| OTP freeze | **Violated in working tree** — 8 OTP-related files modified | HIGH |
| Legacy OTP routes | Root `send-otp.php` / `verify-otp.php` / etc. **stubbed to HTTP 410** | MEDIUM — pages still pointing at legacy URLs break |
| Customer cartable (E7D) | Code + tests PASS; **browser UAT incomplete** | MEDIUM |
| Live request 18 | **Truly incomplete** (vehicle/photos/docs) — not a routing bug | INFO |
| Fixture tests vs live DB | **Mismatch** — tests can pass while browser fails | HIGH |

**Primary OTP regression classification:** **OTP_CAUSE_4** (IPPanel auth/header/config regression in modified `m360-otp-helper.php`) with **OTP_CAUSE_9** (runtime API send failure) as co-primary when private config is present and SMS path is active on localhost.

**Recommended next phase:** **GLOBAL_FIX_OTP_FIRST** — isolate and stabilize OTP before continuing cartable browser UAT or any commit.

---

## 2. Git Working Tree Status

Command: `git status --short`

**Total changed entries: 114** (16 modified `M`, 98 untracked `??`)

### Modified (16)

| Path | Status |
|------|--------|
| `includes/erp-csrf.php` | M |
| `private/m360-otp-config.example.php` | M |
| `public_html/assets/css/moghare360-v1-luxury-ui.css` | M |
| `public_html/assets/js/m360-reception-intake.js` | M |
| `public_html/check-otp.php` | M |
| `public_html/erp-reception-intake-file.php` | M |
| `public_html/erp-reception-intake-save.php` | M |
| `public_html/includes/m360-otp-config-loader.php` | M |
| `public_html/includes/m360-otp-helper.php` | M |
| `public_html/includes/m360-reception-helper.php` | M |
| `public_html/includes/m360-reception-workbench-helper.php` | M |
| `public_html/send-contract-otp.php` | M |
| `public_html/send-otp.php` | M |
| `public_html/verify-contract-otp.php` | M |
| `public_html/verify-otp.php` | M |
| `tools/test-p11-9-c-2c-fix-c-photo-six.php` | M |
| `tools/test-p11-9-c-2c-fix-c-scroll-anchor.php` | M |

*(Note: git lists 16 `M` lines; total status lines = 114 including untracked.)*

### Untracked (98)

Includes: 28 audit docs, `customer-intake-contract-review.php`, `m360-legacy-otp-deprecation-stub.php`, 2 diagnose tools, `tools/fixtures/`, ~65 new test tools, and related artifacts.

**Commit / push:** Forbidden until owner review of this report.

---

## 3. Changed Files Classification

| Category | Count (approx.) | Examples |
|----------|-----------------|----------|
| **Runtime PHP** | 11 | `erp-reception-intake-file.php`, `erp-reception-intake-save.php`, `customer-intake-contract-review.php` (untracked) |
| **Includes/helpers** | 5 | `m360-reception-workbench-helper.php`, `m360-reception-helper.php`, `erp-csrf.php` |
| **OTP-related** | 9 | `m360-otp-helper.php`, `m360-otp-config-loader.php`, `send-otp.php`, `verify-otp.php`, `check-otp.php`, `send-contract-otp.php`, `verify-contract-otp.php`, `m360-legacy-otp-deprecation-stub.php`, `private/m360-otp-config.example.php` |
| **Customer/cartable** | 1 | `customer-intake-contract-review.php` |
| **Wizard/reception** | 4 | intake file/save, workbench helper, reception helper |
| **CSS/JS** | 2 | `moghare360-v1-luxury-ui.css`, `m360-reception-intake.js` |
| **Tools/tests** | ~67 | P11.9 FIX-D1 through E7D test + diagnose files |
| **Docs/audit** | 28 | Untracked phase reports (E1–E7D, D1, D1A, etc.) |
| **Private/config risk** | 1 modified | `private/m360-otp-config.example.php` (placeholders only) |
| **Unknown/unexpected** | 0 | All entries map to known P11.9-C-2C work |

---

## 4. OTP Architecture Findings

### 4.1 Current canonical OTP flow

```
Customer public entry:
  customer-request.php
    → assets/js/customer-form.js
    → POST api/customer/send-otp.php  (JSON { phone })
    → m360_otp_send($phone)
    → m360_otp_send_sms() → m360_otp_ippanel_send()  [if m360_otp_sms_configured()]
    → or dev/fake path [if NOT configured AND localhost dev mode]

Verify:
  customer-form.js → POST api/customer/verify-otp.php → m360_otp_verify()

Reception intake:
  erp-reception-intake-file.php (form)
    → POST erp-reception-intake-save.php
    → action_type=send_customer_otp | verify_customer_otp
    → m360_rw_intake_process_send_otp() / process_verify_otp()
    → m360_otp_send() / m360_otp_verify()  [same helper]
```

Config load order (`m360_otp_config_merged()`):

1. `public_html/mirror-config.php` (gitignored, optional)
2. `private/m360-otp-config.php` (gitignored, primary)
3. Environment variables (`M360_SMS_API_KEY`, etc.)
4. **Example file is NOT merged at runtime** — `m360-otp-config.example.php` is docs-only

Repo root resolution: `m360_otp_config_repo_root()` checks env `M360_REPO_ROOT`, then repo `private/m360-otp-config.php`, then flat XAMPP-style layout.

### 4.2 Which endpoint customer-request.php calls

**Canonical (correct):** `api/customer/send-otp.php` via `customer-form.js` `fetchJson('api/customer/send-otp.php', { phone })`.

`customer-request.php` itself does not POST OTP; JS drives the flow. `customer-request.php` is **not** in the modified file list (stable).

### 4.3 Which endpoint reception intake calls

**Server-side form POST** to `erp-reception-intake-save.php` with `action_type=send_customer_otp` — **not** the public API routes. Same underlying `m360_otp_send()` helper.

### 4.4 Contract/cartable OTP

**E7D customer cartable** (`customer-intake-contract-review.php`):

- Does **not** send OTP itself
- Gates acceptance on `m360_online_req_payload_otp_verified($request)` from intake row/payload
- OTP must be completed in reception intake (or earlier customer-request flow) first

**Legacy contract OTP API** still exists for other pages:

- `customer-intake-contract-sign.php` → `api/customer/contract-send-otp.php` (not stubbed)
- Root `send-contract-otp.php` / `verify-contract-otp.php` → **deprecated stub (410)**

### 4.5 Old contract OTP routes

| Route | State |
|-------|-------|
| `public_html/send-contract-otp.php` | **Stubbed** → 410 deprecation |
| `public_html/verify-contract-otp.php` | **Stubbed** → 410 deprecation |
| `public_html/api/customer/contract-send-otp.php` | **Active** (used by contract-sign page) |
| Root `send-otp.php`, `verify-otp.php`, `check-otp.php` | **Stubbed** → 410 deprecation |

### 4.6 Legacy OTP routes: stubbed or active

**Stubbed (410):** All root-level legacy mirror OTP files now require `m360-legacy-otp-deprecation-stub.php` and call `m360_legacy_otp_route_deprecated()`.

**Active canonical API:** `public_html/api/customer/send-otp.php`, `verify-otp.php` — unchanged in git status (committed baseline).

### 4.7 IPPanel auth/config path

Modified `m360-otp-helper.php` (D1A work) adds:

- `m360_otp_ippanel_auth_header_mode()` — default `authorization` (raw token in `Authorization` header)
- Previous behavior always prefixed `AccessKey `
- Endpoint: `https://edge.ippanel.com/v1/api/send`
- Token check: `https://edge.ippanel.com/v1/api/acl/auth/check_token`

**Risk:** If live private config token was issued for `AccessKey` mode but runtime now defaults to raw `Authorization`, sends fail → user sees generic SMS failure message.

### 4.8 Loader source priority

| Source | Used at runtime? | Gitignored? |
|--------|------------------|-------------|
| Repo `private/m360-otp-config.php` | Yes, if file exists | Yes |
| XAMPP flat `../private/m360-otp-config.php` | Yes, if detected as repo root | Yes |
| `public_html/mirror-config.php` | Yes, merged first | Yes |
| `private/m360-otp-config.example.php` | **No** (diagnostics/docs only) | No (committed example) |
| Env vars | Yes, overlay | N/A |

**Observation:** `private/m360-otp-config.php` **exists on disk** (repo and XAMPP paths confirmed present; gitignored). Therefore localhost attempts **real SMS**, not dev fake OTP, unless `useFakeOtp` is set.

---

## 5. OTP Browser Failure Root Cause Classification

### 5.1 Observed browser message

`ارسال کد تأیید انجام نشد. لطفاً دوباره تلاش کنید.`

This exact string is:

- `M360_OTP_MSG_SMS_FAILED` in `m360-otp-helper.php` (returned when IPPanel send fails)
- Hard-coded fallback in `customer-form.js` on empty/non-JSON response or fetch catch

It is **not** the dev-inactive message (`M360_OTP_MSG_SMS_INACTIVE`) nor the rate-limit message.

### 5.2 Failure mode analysis

| Hypothesis | Likelihood | Evidence |
|------------|------------|----------|
| Config missing | LOW | `private/m360-otp-config.php` exists on disk |
| customer-request wrong endpoint | LOW | JS correctly targets `api/customer/send-otp.php` |
| Send endpoint wrong helper | LOW | API file calls `m360_otp_send()` correctly |
| IPPanel auth/header regression | **HIGH** | `m360-otp-helper.php` modified: auth header mode change (+161 lines) |
| JS wrong payload | LOW | Posts `{ phone: "09..." }` matching API |
| CSRF/session failure | LOW | API route is JSON POST, no CSRF gate on send-otp API |
| Disabled route | LOW for customer-request | API route active; root legacy routes stubbed |
| Legacy route conflict | MEDIUM for **customer-login.php** | Still posts HTML form to root `send-otp.php` (410) |
| Runtime API failure | **HIGH** | Private config present → real SMS path; failure returns SMS_FAILED |
| XAMPP deploy stale | MEDIUM | Only 2 files copied to htdocs in last E7D session; OTP helper changes may not be deployed consistently |

### 5.3 Root cause codes (ranked)

| Code | Verdict |
|------|---------|
| **OTP_CAUSE_4** | **PRIMARY** — IPPanel auth/header/config regression in modified helper |
| **OTP_CAUSE_9** | **CO-PRIMARY** — runtime send/check_token API failure with existing private config |
| OTP_CAUSE_1 | Secondary — only if private config has placeholder values despite file existing |
| OTP_CAUSE_7 | Secondary — legacy root routes stubbed; affects customer-login, not customer-request JS path |
| OTP_CAUSE_8 | Secondary — customer-login still targets deprecated root route |
| OTP_CAUSE_2, 5, 6 | Unlikely for customer-request canonical path |
| OTP_CAUSE_10 | Fallback if live network/token state differs from repo inspection |

**No fix applied in this phase.**

---

## 6. OTP-Related Changed Files — Detailed Review

| File | M/New | Why changed (diff summary) | Violates OTP freeze? | Secrets? | Recommendation |
|------|-------|---------------------------|----------------------|----------|----------------|
| `public_html/includes/m360-otp-helper.php` | M | D1A: IPPanel auth header mode, check_token, failure messages, debug logging (+161/−few) | **YES** | No literals; reads private config | **Manual review** — do not commit until OTP browser pass |
| `public_html/includes/m360-otp-config-loader.php` | M | Config normalization, diagnostics report, placeholder detection (+37) | **YES** | Mask helpers only | **Manual review** with helper |
| `public_html/send-otp.php` | M | Replaced legacy DB OTP with 5-line deprecation stub | **YES** (intentional D1) | No | Review — breaks customer-login form POST |
| `public_html/verify-otp.php` | M | Same deprecation stub | **YES** | No | Review |
| `public_html/check-otp.php` | M | Same deprecation stub | **YES** | No | Review |
| `public_html/send-contract-otp.php` | M | Large legacy code removed → stub | **YES** | No | Review |
| `public_html/verify-contract-otp.php` | M | Legacy removed → stub | **YES** | No | Review |
| `public_html/includes/m360-legacy-otp-deprecation-stub.php` | **NEW** | Central 410 deprecation handler | Borderline | No | Safe after OTP routing audit |
| `private/m360-otp-config.example.php` | M | Example key docs (+3 lines) | No | **Placeholders only** | Safe to commit |

**api/customer/send-otp.php** — NOT in working tree (unchanged committed file). This is the path customer-request actually uses.

---

## 7. Reception Wizard Findings

### 7.1 Current wizard steps (8 + locked)

From `m360_rw_intake_stepper_definition()`:

1. `otp` — موبایل و OTP  
2. `vehicle` — خودرو و پلاک  
3. `condition` — وضعیت خودرو  
4. `service` — خدمات و مسیر عیب  
5. `referral` — ارجاع تیم  
6. `photos` — عکس‌های پذیرش  
7. `documents` — دیاگ / قرارداد / توافق هزینه  
8. `signature` — امضای مشتری و تأیید نهایی  
9. `locked_summary` — when intake locked  

### 7.2 Resolver function

`m360_rw_intake_resolve_active_step($query, $request, $payload, $formValues)`

### 7.3 What determines `active_step`

1. If locked → `locked_summary`  
2. **E7D rule:** if `active_step=documents` in query (and not `wizard_edit=1`) → **always `documents`**  
3. Else: compare requested step vs `first_incomplete` from wizard state; prior-step completion gates  

### 7.4 What determines `first_incomplete_step`

`m360_rw_intake_get_wizard_step_state()` walks operational keys `[otp, vehicle, condition, service, referral, photos, documents, signature]` and returns first step where `complete=false`.

### 7.5 Can `active_step=documents` be overridden?

**After E7D:** Explicit `active_step=documents` is **not** overridden to vehicle/photos (blocker UI stays on documents).

Without explicit query param, resolver still returns `first_incomplete` (e.g. `vehicle` for live request 18).

### 7.6 Request 18 live data

Runtime diagnostic (E7D tool, request 18):

| Field | Live value |
|-------|------------|
| `otp_verified` | yes |
| `vehicle_complete` | **no** |
| `photos_complete` | **no** |
| `documents_cost_ready` | **no** |
| `prerequisites_ready` | **no** |
| `first_blocker_step` | **vehicle** |
| `access_token_hash_exists` | **no** |

**Conclusion:** Live request 18 is **truly incomplete**, not falsely flagged by fixtures.

### 7.7 Vehicle complete fields

`m360_rw_intake_vehicle_step_complete()` requires canonical: `plate`, `brand`, `model`, `mileage`, `fuel_level` all non-empty.

### 7.8 Photos complete fields

`m360_rw_intake_photos_complete()` — canonical photos `is_complete`, `completed_count >= 6`, no missing slot labels.

### 7.9 Documents complete fields

`m360_rw_intake_documents_step_state()` requires:

- diagnostic present (`diagnostic_status` or `diagnostic_pdf`)
- `cost_agreement` present
- **customer contract accepted** (`m360_rw_intake_contract_customer_accepted()`)

### 7.10 Cartable ready fields

`m360_rw_intake_contract_cartable_prerequisites()` — OTP + vehicle + condition + service + referral + photos 6/6 + diagnostic + cost (does **not** require prior customer acceptance).

### 7.11 `section_status` and routing

`section_status` is **written** by `m360_rw_intake_mark_section_saved()` but **not read** by `resolve_active_step()` or `get_wizard_step_state()`. **No routing impact** from stale `section_status`.

E6A fixture has contradictory `section_status` (vehicle/camera marked incomplete) while canonical fields are complete — confirms section_status is ignored.

### 7.12 Routing risk classification (request 18)

| Risk | Applies to live 18? |
|------|---------------------|
| Vehicle false incomplete | **No** — brand/model/mileage/fuel actually missing live |
| Photos false incomplete | **No** — photos actually incomplete live |
| Documents false incomplete | **Expected** — no customer acceptance yet |
| active_step override | **Mitigated by E7D** for explicit documents URL |
| Stale section_status | **No** — not used in resolver |
| Payload path mismatch | **No** |
| Live data truly incomplete | **YES — primary** |

---

## 8. Contract / Customer Cartable Findings

### 8.1 Current contract model (E7D hybrid bridge)

- Staff assigns **customer cartable contract task** via `prepare_customer_contract_review`
- Payload: `reception_intake.customer_cartable.contract_task`
- Compatibility mirrors: `reception_intake.contract.*`, `reception_intake.documents.contract_status`
- Statuses: `pending_customer_review` / `customer_accepted` / `customer_pending_review` (documents mirror)

### 8.2 Customer cartable page

`customer-intake-contract-review.php` — titled **کارتابل مشتری**; token param `t` (alias `token`); validates hash; OTP gate for acceptance.

### 8.3 Raw token storage

**Never persisted in payload.** Transient `_contract_review_token_once` stripped before persist; one-time display via staff PHP session.

### 8.4 Hash persistence

Written to both `customer_cartable.contract_task.access_token_hash` and `contract.review_token_hash` on successful prepare; self-validated before showing URL.

### 8.5 Temporary URL display

Only after prepare succeeds and session one-time token consumed on staff documents page. Label: temporary V1 RC local access.

### 8.6 Customer acceptance OTP requirement

Yes — `m360_online_req_payload_otp_verified($requestRow)` required in `apply_customer_contract_acceptance()`.

### 8.7 Acceptance mirror writes

Updates cartable task, `contract.status`, `documents.contract_status`, acceptance audit fields.

### 8.8 Staff routing after acceptance

`save_documents_and_cost` redirects to `signature` when documents step complete (includes customer accepted).

### 8.9 Temporary bridge vs real portal

**Confirmed temporary bridge only** — no customer auth/login, no DB schema, no real cartable portal.

### 8.10 Owner blueprint gap analysis

| Blueprint step | Implementation | Gap |
|----------------|----------------|-----|
| Staff completes intake | Wizard exists | Live data incomplete on request 18 |
| System sends task to customer cartable | Prepare action + payload | Blocked until prerequisites complete |
| Customer accepts in cartable | Token page + OTP gate | Browser UAT not complete |
| File returns to staff | Status mirrors + staff UI states | Not browser-verified |
| Staff continues to signature | Redirect when accepted | Code present; UAT pending |
| No receptionist contract approval | Blocked actions | Implemented |
| No automatic JobCard | Not implemented | OK |
| Real customer portal/cartable | Deferred | By design |

---

## 9. Fixture Tests vs Live Browser Reality

| Dimension | Fixture/tests | Live browser (req 18) |
|-----------|---------------|------------------------|
| Vehicle/photos | E6A fixture complete | Actually incomplete |
| OTP send | Static/helper tests pass | customer-request send fails |
| Cartable token | Generated in memory tests | No hash persisted live |
| CSRF | E7B stable token tests pass | Was a prior browser issue (fixed in code, not fully UAT'd) |
| Wizard resolve | Tests use explicit `active_step=documents` | Matches E7D fix |
| IPPanel SMS | Diagnostics tests pass (static) | Live API may fail |

**Conclusion:** Current "all PASS" on automated suites is **partially misleading** — tests prove code paths with fixtures, not live OTP API or live DB completeness.

---

## 10. Tools and Test Inventory

| Metric | Value |
|--------|-------|
| Total `tools/*.php` files | ~588 (repo-wide; includes legacy/historical) |
| New/untracked P11.9 test tools | ~65 (FIX-D1 through E7D) |
| New diagnose tools | 2 (`diagnose-p11-9-c-2c-wizard-state.php`, `diagnose-p11-9-c-2c-fix-e7d-customer-cartable.php`) |
| Modified existing tests | 2 (`test-p11-9-c-2c-fix-c-photo-six.php`, `test-p11-9-c-2c-fix-c-scroll-anchor.php`) |

### DB vs fixture usage

| Type | Examples |
|------|----------|
| **Fixture-only** | Most E7/E7D/E6A tests using `e6a_request18_payload()` |
| **Real DB read** | `diagnose-p11-9-c-2c-*` tools (read-only) |
| **Real DB write** | None in untracked tests (apply_action in memory only) |

### OTP-related tests (untracked/modified)

`test-p11-9-c-2c-fix-d1-*`, `d1a-*`, `e1-otp-send-regression`, `e3-restore-d1b-otp-flow`, `e4-otp-freeze-regression`, `e5-otp-service-freeze-regression`, etc.

### Safe to commit (after stabilization)

- Diagnostic tools (read-only, no secrets)
- Fixture-based tests with clear scope labels
- Audit docs (this report + phase reports)

### Diagnostic-only (not production gates)

- `diagnose-p11-9-c-2c-wizard-state.php`
- `diagnose-p11-9-c-2c-fix-e7d-customer-cartable.php`
- `tools/ippanel-auth-diagnostics.php`

### Tests that can pass while browser fails

- All fixture-based wizard/cartable tests
- OTP static wiring tests (do not call live IPPanel send)
- V1 production signoff (23 checks — not intake/OTP browser)

---

## 11. Docs Inventory

### New untracked audit reports (28)

| Phase | Reports |
|-------|---------|
| FIX-E stepper | E scope, E report |
| FIX-E1 | scope, browser regression |
| FIX-E2 | scope, true wizard lock |
| FIX-E3 | scope, restore D1B OTP |
| FIX-E4 | scope, service diagnostic gate |
| FIX-E5 | scope, photo completion |
| FIX-E6A | scope, SQL truth wizard |
| FIX-E7 | scope, customer contract |
| FIX-E7A | CSRF diagnostic (no code) |
| FIX-E7B | scope, stable CSRF |
| FIX-E7C | contract flow diagnostic (no code) |
| FIX-E7D | scope, customer cartable bridge |
| FIX-D1 | scope, OTP cleanup wiring |
| FIX-D1A | scope, IPPanel auth diagnostics |

### Docs vs code reality

| Report | Still accurate? |
|--------|-----------------|
| E7D bridge report ("tests PASS") | Accurate for fixtures; **browser UAT incomplete** |
| E7C diagnostic | Accurate for request 18 token/resolver issues |
| E3 "OTP restored" | **Superseded by current browser OTP send failure** |
| D1A IPPanel auth | Accurate for code intent; **live send not verified** |
| E7B CSRF | Code accurate; browser partially verified |

### Safe to commit after stabilization

Scope reports + final phase reports + this global freeze report.

### Superseded by browser failures

- Any report claiming OTP "solved/frozen" (E3, E4 freeze regression signoff)
- E7D commit eligibility implying browser cartable pass

---

## 12. Secret / Config Risk

### Files with sensitive-looking keys

| File | Values | Commit? |
|------|--------|---------|
| `private/m360-otp-config.example.php` | **Placeholders** (`YOUR_REAL_*`) | Yes — example only |
| `private/m360-otp-config.php` | Real (on disk) | **NEVER** — gitignored |
| `public_html/mirror-config.php` | Unknown (gitignored) | **NEVER** |
| `public_html/includes/m360-otp-helper.php` | Key **names** only, no literals | Review before commit |
| `public_html/includes/m360-otp-config-loader.php` | Mask/diagnostics only | Review before commit |

### .gitignore protection

```
private/m360-otp-config.php
public_html/mirror-config.php
*.secret.php
.env
```

**Adequate** for standard private config paths.

### public_html real secrets

Static inspection: **no hard-coded API keys** in committed public PHP. Runtime reads gitignored private files.

**Do not print secret values** — confirmed not done in this report.

---

## 13. Commit / Push Eligibility

| Question | Answer |
|----------|--------|
| Code commit eligible? | **NO** |
| Docs-only commit eligible? | **NO** (freeze — owner review first) |
| Tools-only commit eligible? | **NO** |
| Push eligible? | **NO** |

**Commit Eligibility:** `NOT_ELIGIBLE_AUDIT_FREEZE`

---

## 14. Files Safe To Commit Later

*(After OTP browser pass + cartable UAT + owner signoff)*

- `public_html/customer-intake-contract-review.php`
- `public_html/includes/m360-legacy-otp-deprecation-stub.php` (after legacy caller audit)
- `includes/erp-csrf.php`, reception helpers (non-OTP)
- `tools/fixtures/`, E7D tests, diagnose tools
- `docs/audit/*` phase reports
- `private/m360-otp-config.example.php` (placeholders)

---

## 15. Files Requiring Manual Review

- `public_html/includes/m360-otp-helper.php` — **OTP freeze violation; browser regression**
- `public_html/includes/m360-otp-config-loader.php`
- All stubbed legacy OTP root files + callers (`customer-login.php` form action)
- `public_html/includes/m360-reception-workbench-helper.php` — large surface (wizard + cartable)
- XAMPP deploy parity (htdocs vs repo)

---

## 16. Files That Must Not Be Committed Now

- **Entire working tree** until freeze lifted
- Specifically: any OTP runtime changes before browser OTP pass
- `private/m360-otp-config.php` (must never be committed)
- `public_html/mirror-config.php` if present locally

---

## 17. Recommended Recovery Plan

### Phase order

1. **GLOBAL_FIX_OTP_FIRST**
   - Run IPPanel auth diagnostics on target host (CLI, masked)
   - Verify `auth_header_mode` matches token type in private config
   - Confirm XAMPP htdocs has matching `m360-otp-helper.php` + API routes
   - Fix customer-login legacy POST target OR restore controlled bridge
   - Browser pass: customer-request OTP for `09128166648`

2. **LIVE_DATA_REPAIR** (operational, not code)
   - Complete request 18 vehicle/photos/docs in reception UI
   - Re-run E7D diagnostic

3. **CUSTOMER_CARTABLE_CONTINUE**
   - Browser UAT Case A/B from E7D spec
   - Only after OTP stable

### Not recommended now

- **GLOBAL_REVERT_OTP_FILES** — only if owner chooses full rollback to pre-D1; would lose intentional cleanup
- **HARD_FREEZE_AND_REPLAN** — unless OTP fix fails twice
- **C-2D / JobCard** — out of scope

### OTP before cartable?

**YES** — cartable acceptance requires OTP verified on intake row; customer entry OTP is currently broken in browser.

---

## 18. STOP / GO Decision

| Decision | Status |
|----------|--------|
| Commit | **STOP** |
| Push | **STOP** |
| Code fixes | **STOP** (audit freeze) |
| OTP changes | **STOP** until dedicated fix phase approved |
| Cartable browser UAT | **STOP** until OTP pass |
| Production-ready claim | **STOP** |
| Docs audit (this report) | **GO** (complete) |

---

## 19. Final Conclusion

MOGHARE360 V1 RC global audit freeze confirms the current software state, changed file risk, OTP regression cause, live wizard/cartable mismatch, secret/config exposure risk, and commit eligibility before any further fix, commit, push, C-2D, JobCard conversion, or production-ready claim.

**Summary judgment:** The codebase contains substantial P11.9-C-2C progress (wizard, CSRF, cartable bridge) trapped in an **114-file uncommitted working tree** with **OTP freeze violated** and **browser OTP send failing** on the canonical customer-request path. Automated tests pass on fixtures but do not validate live IPPanel SMS or live request 18 completeness. **Do not commit or push.** Next approved work: **GLOBAL_FIX_OTP_FIRST**, then cartable browser UAT on live data.

---

*End of MOGHARE360 V1 RC Global Software Audit Freeze Report*

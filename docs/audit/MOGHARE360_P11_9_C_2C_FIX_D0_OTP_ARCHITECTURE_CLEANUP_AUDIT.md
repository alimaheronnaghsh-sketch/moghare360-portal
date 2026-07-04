# MOGHARE360 P11.9-C-2C-FIX-D0 — OTP Architecture Cleanup Audit

**Phase:** P11.9-C-2C-FIX-D0 (report-only)  
**Product:** MOGHARE360 V1 RC  
**Date:** 2026-07-04  
**Repo root:** `C:\Users\User\Documents\GitHub\alimaheronnaghsh-sketch\moghare360-portal`  
**XAMPP web root (observed):** `C:\xampp\htdocs\moghare360`  

Machine-readable inventory: [`MOGHARE360_P11_9_C_2C_FIX_D0_OTP_FILE_INVENTORY.csv`](MOGHARE360_P11_9_C_2C_FIX_D0_OTP_FILE_INVENTORY.csv)

---

## Executive Summary

MOGHARE360 has **two incompatible OTP stacks**:

1. **Canonical V1 stack** — `m360-otp-config-loader.php` + `m360-otp-helper.php` + `api/customer/send-otp.php` / `verify-otp.php`. Session-backed hash storage, IPPanel Edge pattern API, no fake pass on production.
2. **Legacy mirror stack** — `send-otp.php`, `verify-otp.php`, `check-otp.php`, `send-contract-otp.php`, `verify-contract-otp.php` requiring **`config.php` (absent from repo)** and MySQL `otp_verifications` with **plaintext OTP codes**.

Reception intake uses the **canonical send path** but the **gate reads persisted `request_payload_json.otp_verified`**, not PHP session verification. **No reception verify action exists** to bridge session OTP → payload truth. Even with SMS configured, reception gate OTP would remain blocked until D1 wiring.

**Diagnostics (repo CLI):** `tools/otp-config-diagnostics.php` → **FAIL** — `private/m360-otp-config.php` absent; mirror absent in repo; API key missing.  
**Diagnostics (XAMPP runtime):** mirror-config **found** but keys **empty**; private resolves to `C:\xampp\htdocs\private\m360-otp-config.php` — **absent**; SMS not configured.

**Primary root cause:** Architecture disconnect — reception send stores OTP in staff/browser-unrelated session; gate requires `payload['otp_verified']=1` which intake never sets after verify.

**Secondary blockers:** Missing real IPPanel config; XAMPP flat-deploy private path mismatch vs full repo layout.

---

## A) Full OTP Inventory

### A.1 Public routes — canonical (active)

| Path | Method | Function |
|------|--------|----------|
| `public_html/api/customer/send-otp.php` | POST JSON | `m360_otp_send($phone)` |
| `public_html/api/customer/verify-otp.php` | POST JSON | `m360_otp_verify($phone, $otp)` |
| `public_html/api/customer/request.php` | POST JSON | Requires `m360_otp_is_verified()`; writes `otp_verified: 1` into payload on create |
| `public_html/api/customer/contract-send-otp.php` | POST | `m360_contract_send_otp()` |
| `public_html/api/customer/contract-sign.php` | POST | Contract OTP verify + sign |
| `public_html/api/customer/estimate-send-otp.php` | POST | Estimate OTP send |
| `public_html/api/customer/estimate-approve.php` | POST | Estimate OTP verify |
| `public_html/api/customer/delivery-send-otp.php` | POST | Delivery OTP send |
| `public_html/api/customer/delivery-confirm.php` | POST | Delivery OTP verify |

### A.2 Public routes — legacy (orphaned / broken)

| Path | Depends on | Status |
|------|------------|--------|
| `public_html/send-otp.php` | `config.php`, MySQL `otp_verifications`, `sendIppanelOtp()` | **Broken** — `config.php` not in repo |
| `public_html/verify-otp.php` | `config.php` | **Broken** |
| `public_html/check-otp.php` | `config.php` | **Broken** |
| `public_html/send-contract-otp.php` | `config.php`, MySQL portal tables | **Broken** |
| `public_html/verify-contract-otp.php` | `config.php` | **Broken** |

Legacy send stores **raw 5-digit OTP** in MySQL (`otp_verifications.otp_code`). V1 helper uses **6-digit** codes and **password_hash in session**.

### A.3 Helpers / loaders

| File | Role |
|------|------|
| `public_html/includes/m360-otp-config-loader.php` | Merge `mirror-config.php` + `private/m360-otp-config.php` + env; normalize keys |
| `public_html/includes/m360-otp-helper.php` | Send, verify, IPPanel, session state |
| `includes/erp-config-loader.php` | ERP DB only — **no OTP keys** |
| `public_html/includes/m360-reception-workbench-helper.php` | `send_customer_otp` → `m360_otp_send()`; gate → `m360_online_req_payload_otp_verified()` |
| `public_html/includes/m360-online-request-helper.php` | `m360_online_req_payload_otp_verified()`; public create sets `otp_verified=1` |
| `public_html/includes/m360-contract-signature-helper.php` | Per-contract session `m360_contract_otp_{id}` |
| `public_html/includes/m360-estimate-approval-helper.php` | Estimate OTP session namespace |
| `public_html/includes/m360-customer-delivery-helper.php` | Delivery OTP session namespace |
| `public_html/includes/m360-online-intake-bridge-helper.php` | Bridge template defaults `otp_verified: 0` |

Key functions in `m360-otp-helper.php`:

- Config: `m360_otp_load_config()`, `m360_otp_sms_settings()`, `m360_otp_sms_configured()`
- Send/verify: `m360_otp_send()`, `m360_otp_verify()`, `m360_otp_is_verified()`, `m360_otp_verified_token()`
- IPPanel: `m360_otp_ippanel_send()`, `m360_otp_ippanel_pattern_payload()` → `https://edge.ippanel.com/v1/api/send`
- Session pending: `otp_phone`, `otp_hash`, `otp_expires_at`, `otp_attempts`
- Session verified: `otp_verified_phone`, `otp_verified_at`, `otp_verified_token`

### A.4 Private config

| File | In repo | In XAMPP runtime | OTP content |
|------|---------|------------------|-------------|
| `private/m360-otp-config.example.php` | Yes (placeholders) | N/A | Safe example |
| `private/m360-otp-config.php` | **No** (gitignored) | **No** at `C:\xampp\htdocs\private\` | Owner must create |
| `private/erp-config.php` | **Yes** (gitignored, present) | Unknown | **No OTP keys** |

### A.5 Public config

| File | In repo | Notes |
|------|---------|-------|
| `public_html/mirror-config.example.php` | Yes | Empty SMS keys; documents localhost test mode |
| `public_html/mirror-config.php` | **No** (gitignored) | **Present on XAMPP** at `htdocs/moghare360/mirror-config.php` but SMS keys empty at audit |
| `public_html/config.php` | **Absent** | Required by all legacy OTP routes |

No `public_html/config.example.php` found. Legacy stack referenced a general `config.php` pattern (historical mirror site).

### A.6 Tools / tests

| Tool | Safe? | Audit result |
|------|-------|--------------|
| `tools/otp-config-diagnostics.php` | Yes — masked | FAIL: API key missing |
| `tools/test-p11-otp-provider-config.php` | Yes | **25/25 PASS** |
| `tools/test-p11-4-5-ippanel-config-loader-compatibility.php` | Yes | **15/15 PASS** |
| `tools/test-p11-4-5-ippanel-pattern-payload.php` | Yes | Not re-run (static) |
| `tools/test-ippanel-request-response-diagnostic.php` | CLI-only; masked trace | Not run live (no live SMS per phase rules) |
| `tools/test-p11-9-c-2c-fix-b-otp-mobile.php` | Yes | Covers send + mobile reset; not verify-to-payload |

### A.7 Documentation (OTP / IPPanel / SMS)

**Accurate / current:**

- `docs/audit/MOGHARE360_GITHUB_COMPLETION_AUDIT_REPORT.md` — canonical `private/m360-otp-config.php`
- `docs/release/MOGHARE360_V1_FINAL_SECURITY_EXCLUSIONS.md`
- `docs/dry-run/P11_9_A_OTP_DEFERRAL_PROTOCOL.md`
- `docs/audit/MOGHARE360_P11_9_0_ONE_DAY_RUN_DRY_RUN_READINESS_DISCOVERY_REPORT.md`

**Partially outdated / caution:**

- `docs/audit/MOGHARE360_P11_9_B_FIX_C_PUBLIC_ENTRY_ROUTER_SCOPE_REPORT.md` — correctly flags legacy `customer-login.php` / `config.php` but operators may still follow old paths
- Mission reports under `docs/missions/p1_*` describe flows correctly for **public** intake; do not document reception verify gap
- `dist/` and `release/` trees duplicate OTP files — **not canonical runtime**

### A.8 Reception intake OTP (current behavior)

**Send OTP button** (`erp-reception-intake-file.php`):

- Action: `send_customer_otp` → `m360_rw_intake_process_send_otp()` → `m360_otp_send($mobile)`
- Availability: `m360_rw_intake_otp_send_available()` — true if SMS configured OR localhost dev code allowed
- Success message: *"مشتری باید کد را از طریق مسیر تأیید مشتری وارد کند"* — **no staff-side verify UI**

**Verify OTP:** **None** in reception intake. Grep confirms no `verify_customer_otp` action in `m360-reception-workbench-helper.php`.

**Payload structure:**

```json
{
  "otp_verified": 0,
  "mobile": "09xxxxxxxxx",
  "reception_intake": {
    "mobile_correction": { "mobile": "...", "corrected_at": "..." },
    "section_status": { "mobile_otp": { "completed": true } }
  }
}
```

- `save_mobile_correction` sets `otp_verified = 0` (correct invalidation)
- `send_customer_otp` does **not** update payload OTP fields
- **Nothing sets `otp_verified = 1`** in reception save path

**Gate checklist** (`m360_rw_intake_gate_checklist`):

- Uses `m360_online_req_payload_otp_verified($request)` — reads `request_payload_json.otp_verified` or DB column `otp_verified`
- **Does not** call `m360_otp_is_verified()`

**Section completion** for `mobile_otp`: only checks mobile non-empty — **not** OTP verified.

### A.9 Customer / contract OTP

| Flow | OTP engine | Truth store | Conflicts with reception? |
|------|------------|-------------|---------------------------|
| Public online intake (`customer-request.php`) | Global session via `api/customer/*` | Session → payload on `request.php` create | No — separate entry path |
| Reception intake send | Global session `m360_otp_send` | Session only | **Yes** — gate expects payload |
| Contract sign (V1 API) | `m360_contract_otp_{contractId}` session | Contract row `otp_verified` | No namespace collision |
| Estimate / delivery | Domain session keys | Domain tables | No collision |
| Legacy contract OTP routes | MySQL + config.php | DB plaintext | **Deprecated** — parallel stack |

Contract/customer V1 OTP **shares** `m360_otp_send_sms()` / IPPanel config but uses **isolated session keys** — no conflict if canonical config is loaded once.

---

## B) Runtime Path Diagnosis

Loader logic (`m360_otp_config_loader.php`):

```php
$root = dirname(__DIR__, 2);  // from public_html/includes
$mirror = dirname(__DIR__) . '/mirror-config.php';
$private = $root . '/private/m360-otp-config.php';
```

### B.1 Per-loader resolution table

| Context | `repo_root` computed | Mirror path | Private OTP path | Mirror exists | Private exists |
|---------|-------------------|-------------|------------------|---------------|----------------|
| **Repo dev** (`.../moghare360-portal/public_html/includes`) | `.../moghare360-portal` | `.../public_html/mirror-config.php` | `.../private/m360-otp-config.php` | No | No |
| **XAMPP flat** (`C:/xampp/htdocs/moghare360/includes`) | `C:/xampp/htdocs` | `C:/xampp/htdocs/moghare360/mirror-config.php` | `C:/xampp/htdocs/private/m360-otp-config.php` | **Yes** (empty SMS keys) | No |
| **Production placeholder** | `{deploy_root}` where `public_html` is one level below | `{deploy_root}/public_html/mirror-config.php` | `{deploy_root}/private/m360-otp-config.php` | Host-specific | Host-specific |

### B.2 Loader behavior

| Question | Answer |
|----------|--------|
| Silent fail? | **No** — `m360_otp_sms_configured()` returns false; user message `امکان ارسال پیامک در حال حاضر فعال نیست.` |
| Controlled disabled? | **Yes** — send blocked unless localhost dev fallback |
| Prints sensitive errors? | **No** in helper; logs `[MOGHARE360 OTP] sms_not_configured` without secrets |
| Secrets committable? | **Mitigated** — `.gitignore` covers `private/m360-otp-config.php`, `mirror-config.php` |
| Public_html secret risk | **Risk if operator puts real keys in `mirror-config.php`** — gitignored but web-adjacent |

### B.3 ERP config loader (separate)

`includes/erp-config-loader.php` → `{repo}/private/erp-config.php` only. No OTP merge.

---

## C) Security Risk Assessment

| Item | Classification | Why | Used? | Safe? | Conflicts? | Final arch? |
|------|----------------|-----|-------|-------|------------|-------------|
| `m360-otp-helper.php` | **KEEP** | Canonical V1 engine | Yes | Yes — hash in session | Legacy only | **Yes** |
| `m360-otp-config-loader.php` | **KEEP** | Single config merge | Yes | Yes | None | **Yes** |
| `private/m360-otp-config.php` | **KEEP_BUT_FIX** | Required secrets | No (absent) | N/A until created | None | **Yes** |
| `mirror-config.php` (runtime) | **KEEP_BUT_FIX** | Optional overlay | XAMPP empty | OK if empty; **DANGEROUS if real keys** | Duplicates private | Optional overlay only |
| `api/customer/send-otp.php` | **KEEP** | Public API | Yes | Yes | None | **Yes** |
| `api/customer/verify-otp.php` | **KEEP** | Public API | Yes | Yes | None | **Yes** |
| Legacy `send-otp.php` | **DEPRECATE** | Plaintext DB OTP | No (broken) | **No** | Parallel truth | **No** |
| Legacy `verify/check-otp.php` | **DEPRECATE** | Old stack | No | **No** | Yes | **No** |
| Legacy contract OTP routes | **DEPRECATE** | config.php + MySQL | No | **No** | Yes | **No** |
| `sql/otp_verifications.sql` | **REMOVE_LATER** | Plaintext schema | Legacy only | **No** | Yes | **No** |
| Reception send without verify bridge | **KEEP_BUT_FIX** | Half-wired | Yes | Misleading UX | **Truth split** | Fix in D1 |
| `customer-request.php` dev code display | **KEEP** | Localhost only | Dev | OK on localhost | None | Yes with guards |
| `dist/` / `release/` OTP copies | **DEPRECATE** | Archive duplicates | No | Confusion | Doc drift | **No** |
| Missing `config.php` | **UNKNOWN_NEEDS_OWNER_DECISION** | Delete legacy routes or restore? | N/A | N/A | Yes | Deprecate routes |

**Danger flags checked:**

- No raw OTP in committed V1 code paths ✓
- Legacy stack **would** store raw OTP in MySQL if enabled — **DEPRECATE**
- V1 does not set `otp_verified` without verify ✓ (reception gap is opposite — never sets when it should)
- No fake OTP on production host in helper ✓ (tests confirm)
- Dev code on localhost only when `m360_otp_can_use_dev_code()` ✓

No **SECRET_PRESENT_REDACTED** in repo files audited. XAMPP `mirror-config.php` exists but diagnostics report empty/placeholder keys only.

---

## D) Current Failure Root Cause

Evidence-based answers:

| # | Question | Answer |
|---|----------|--------|
| 1 | Missing private config? | **Yes** — `private/m360-otp-config.php` absent repo + XAMPP private path |
| 2 | Wrong loader path? | **Partially on XAMPP flat deploy** — private resolves to `htdocs/private/` not `moghare360-portal/private/` |
| 3 | Reception not using helper? | **Send uses helper; verify does not exist** |
| 4 | Old send-otp not connected? | **Correct** — reception uses new helper; legacy routes orphaned |
| 5 | Duplicate implementations? | **Yes** — legacy MySQL stack + V1 session stack |
| 6 | IPPanel contract wrong? | **No evidence** — Edge pattern payload implemented; blocked by missing credentials |
| 7 | Provider credentials absent? | **Yes** — diagnostics FAIL |
| 8 | Fake/dev mode confusion? | **Minor** — localhost dev may allow send with test code but **still no payload verify** |
| 9 | OTP status in too many places? | **Yes** — session, payload JSON, DB column, domain session keys, legacy MySQL |
| 10 | **Single primary root cause** | **Reception gate reads `request_payload_json.otp_verified` but intake has no verify action to set it after `m360_otp_verify()` — send-only wiring** |

SMS misconfiguration is a **co-equal blocker** for real SMS send on non-localhost hosts.

---

## E) Canonical OTP Architecture Proposal

### E.1 Final private config path

| Environment | Path |
|-------------|------|
| Repo | `moghare360-portal/private/m360-otp-config.php` |
| XAMPP (full repo layout) | Same relative to repo root |
| XAMPP (flat `htdocs/moghare360` only) | **Either** place file at `C:\xampp\htdocs\private\m360-otp-config.php` **or** fix loader in D1 to support deploy manifest |
| Production | `{DEPLOY_ROOT}/private/m360-otp-config.php` (outside web-served tree) |

Copy from `private/m360-otp-config.example.php`. Never commit real values.

### E.2 Final loader

- **File:** `public_html/includes/m360-otp-config-loader.php`
- **Functions:** `m360_otp_config_merged()`, `m360_otp_config_diagnostics_report()`
- **Refactor (D1):** Optional — explicit deploy-root override env `M360_REPO_ROOT` for flat XAMPP; document operator placement

### E.3 Final helper / provider

- **File:** `public_html/includes/m360-otp-helper.php`
- **Send:** `m360_otp_send(string $phone)`
- **Verify:** `m360_otp_verify(string $phone, string $otp)`
- **SMS:** `m360_otp_send_sms()` → `m360_otp_ippanel_send()` pattern mode
- **Pattern:** `M360_SMS_PATTERN_CODE` + param key `OTP` (not `%OTP%`)
- **Domain wrappers:** Keep contract/estimate/delivery helpers; they already call central SMS

### E.4 Final storage model

| Concern | Canonical store |
|---------|-----------------|
| Pending OTP hash | PHP session (`otp_hash`, `otp_phone`, `otp_expires_at`) during verify window |
| Verified (public customer flow) | Session `otp_verified_*` + token; persisted to `request_payload_json.otp_verified=1` on request create |
| Verified (reception intake) | **`request_payload_json.otp_verified=1`** (+ optional DB column sync) after staff/customer verify action |
| Expiry | Session `otp_expires_at`; TTL from `otpExpireMinutes` (default 5) |
| Mobile correction | Reset `otp_verified=0` in payload (already implemented) + clear session pending |

Reception D1 should add **`verify_customer_otp`** action: call `m360_otp_verify()`, on success write payload + column.

### E.5 Final UI integration

| Surface | Send | Verify |
|---------|------|--------|
| Public intake | `customer-form.js` → `api/customer/send-otp.php` | → `verify-otp.php` → `request.php` | **Already complete** |
| Reception intake | `send_customer_otp` form | **Add:** code field + `verify_customer_otp` OR link customer to public verify with staff confirmation |
| Contract / estimate / delivery | Existing API routes | Existing verify in sign/approve/confirm | **Keep** |

### E.6 Final security rules

- No raw OTP in HTML (except guarded localhost dev banner on `customer-request.php`)
- No raw OTP in logs — helper uses hash + masked diagnostics
- No OTP secrets in `public_html` committed files
- No OTP secrets in Git — use `private/m360-otp-config.php` or env vars
- No fake OTP on production
- No bypass — gate must require real verify before `otp_verified=1`
- Single truth for reception gate: **`m360_online_req_payload_otp_verified()`**

### E.7 Deprecation plan

| Keep active | Deprecate (Stage 2 message) | Remove later (owner approval) |
|-------------|----------------------------|-------------------------------|
| `m360-otp-*`, `api/customer/*` OTP | Legacy `send-otp.php`, `verify-otp.php`, `check-otp.php` | Same + `send/verify-contract-otp.php` |
| Reception send (until verify added) | Legacy routes return controlled "deprecated" JSON/HTML | `sql/otp_verifications.sql`, `config.php` pattern docs |
| Domain OTP helpers | | `dist/`/`release/` duplicate OTP PHP |

Update docs: reception OTP verify gap, XAMPP private path, deprecate legacy mirror OTP routes.

---

## F) Cleanup Plan

### Stage 1 — FIX-D0 (this phase)

Report-only. No code, file, or config changes. **Complete.**

### Stage 2 — Safe deactivation

- Add controlled deprecation responses on legacy routes (no deletion)
- Log/route map update
- No iPPanel wiring yet

### Stage 3 — Canonical wiring (FIX-D1)

- Owner creates `private/m360-otp-config.php` (or env vars)
- Fix loader deploy-root if needed for XAMPP
- Add reception `verify_customer_otp` → payload `otp_verified=1`
- Align gate section completion with verified state

### Stage 4 — Browser validation

- Real iPPanel send on staging
- Wrong OTP rejected, expired rejected
- Gate passes only after verify

### Stage 5 — Commit eligibility

- No secrets in Git
- OTP send + verify pass operator browser UAT
- Gate checklist OTP green only after real verification

---

## G) Deliverables

| Deliverable | Path | Status |
|-------------|------|--------|
| Audit report | `docs/audit/MOGHARE360_P11_9_C_2C_FIX_D0_OTP_ARCHITECTURE_CLEANUP_AUDIT.md` | Created |
| CSV inventory | `docs/audit/MOGHARE360_P11_9_C_2C_FIX_D0_OTP_FILE_INVENTORY.csv` | Created |

---

## H) Evidence Commands Run

```powershell
# Config existence (repo)
Test-Path moghare360-portal/private/m360-otp-config.php  # False
Test-Path moghare360-portal/private/erp-config.php       # True
Test-Path moghare360-portal/public_html/mirror-config.php # False

# OTP diagnostics (repo) — masked, safe
C:\xampp\php\php.exe tools/otp-config-diagnostics.php
# RESULT: FAIL — API key missing or placeholder

# Static tests — safe
C:\xampp\php\php.exe tools/test-p11-otp-provider-config.php           # 25/25 PASS
C:\xampp\php\php.exe tools/test-p11-4-5-ippanel-config-loader-compatibility.php  # 15/15 PASS

# XAMPP runtime diagnostics (read-only, masked)
# repo_root=C:\xampp\htdocs; mirror=yes; private=no; sms_configured=no
```

No live SMS sent. No private config modified. No secrets printed.

---

## I) Final Decision

### 1. Recommended canonical OTP path

```
private/m360-otp-config.php
    ↓ merged by
public_html/includes/m360-otp-config-loader.php
    ↓ used by
public_html/includes/m360-otp-helper.php
    ↓ exposed via
public_html/api/customer/send-otp.php + verify-otp.php
    ↓ reception bridge (D1)
erp-reception-intake-save.php → verify_customer_otp → request_payload_json.otp_verified=1
    ↓ gate
m360_online_req_payload_otp_verified()
```

### 2. Files to keep

- `m360-otp-config-loader.php`, `m360-otp-helper.php`
- `private/m360-otp-config.example.php`
- `api/customer/send-otp.php`, `verify-otp.php`, `request.php`
- Domain helpers (contract, estimate, delivery)
- `tools/otp-config-diagnostics.php`, P11 OTP tests

### 3. Files to fix

- `m360-reception-workbench-helper.php` — add verify → payload bridge
- `erp-reception-intake-file.php` — verify UI
- `m360-otp-config-loader.php` — optional deploy-root override for flat XAMPP
- Operator host — create `private/m360-otp-config.php`

### 4. Files to deprecate

- `send-otp.php`, `verify-otp.php`, `check-otp.php`
- `send-contract-otp.php`, `verify-contract-otp.php`
- Archive copies under `dist/`, `release/`

### 5. Files to remove later (owner approval)

- Legacy routes above after deprecation period
- `public_html/sql/otp_verifications.sql`
- Stale release stage OTP duplicates

### 6. Exact next Cursor phase name

**`P11.9-C-2C-FIX-D1`** — Canonical OTP wiring: private config placement guide, reception verify action, payload truth bridge, optional loader deploy-root fix, Stage 2 deprecation stubs.

### 7. Exact owner action required

1. Create **`private/m360-otp-config.php`** on each runtime host from example with real IPPanel pattern credentials (or set env vars `M360_SMS_API_KEY`, etc.).
2. Confirm deploy layout: full repo vs flat XAMPP — place private file where loader resolves it.
3. Approve FIX-D1 implementation scope (reception verify UI + payload write; no Auth/DB schema changes).
4. Run `php tools/otp-config-diagnostics.php` until **PASS** before live SMS UAT.

### 8. Safe to proceed to implementation?

**Conditional yes** — architecture is clear and canonical code exists; proceed to **FIX-D1** after owner confirms config placement and approves reception verify design. Do **not** wire iPPanel until private config exists and diagnostics PASS.

### Final status

**`BLOCKED_BY_ARCHITECTURE_CONFLICT`**

(Reception session-vs-payload OTP truth split is the primary functional blocker; co-blocked by **`BLOCKED_BY_MISSING_CONFIG`** for production SMS send.)

---

P11.9-C-2C-FIX-D0 performs a report-only OTP architecture cleanup audit and canonical path decision for MOGHARE360, identifying every OTP route, helper, config, test, and document, classifying duplicate or unsafe paths, and preparing a safe cleanup and iPPanel wiring plan without changing code, moving files, deleting files, exposing secrets, faking OTP, bypassing OTP, changing Auth/Login, changing permissions, changing database schema, running SQL migrations, or creating automatic JobCards.

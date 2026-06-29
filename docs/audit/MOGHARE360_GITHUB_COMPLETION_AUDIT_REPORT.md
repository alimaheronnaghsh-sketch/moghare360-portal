# MOGHARE360 V1 RC — GitHub / Repository Completion Audit Report

**Report ID:** P11.6-0  
**Date:** 2026-06-26  
**Scope:** Read-only audit of `moghare360-portal` repository state  
**Remote:** `https://github.com/alimaheronnaghsh-sketch/moghare360-portal.git`  
**Audit type:** REPORT ONLY — no application, database, config, or Auth/Login changes performed for this report.

---

## 1. Executive Summary

MOGHARE360 V1 RC is a **356-commit** PHP/SQL Server ERP repository with substantial committed work through **P11.4.5-A**. The working tree is **clean**, branch **`main`** is **up to date with `origin/main`**, and there are **no uncommitted or staged changes** at audit time.

**What is clearly complete in Git:**

- Operational workflow phases **P1–P10** (intake → delivery → management → soft run → release hardening)
- **P11** RC lock, demo package, security scan, and production signoff scaffolding
- **P11.1–P11.3** OTP provider config, IPPanel diagnostic tooling, and secure online intake bridge
- **P11.4** owner access management console and full P11.4.x bugfix chain (Persian encoding, position UX, role grant fix, staff/owner login redirect, IPPanel config compatibility)

**What is not complete (cannot be declared done from repository evidence alone):**

- **One-Day Run** — requires live staff users, real passwords, and end-to-end operational validation
- **External network path** — static IP / port 8080 / firewall / DNS for `erp.moghareh360.ir` not proven externally working from this audit
- **Real OTP SMS send** — code and diagnostics are committed; production send depends on host-only `private/m360-otp-config.php` and live IPPanel credentials
- **Position seed cleanup** — documented backlog only (`P11.4.3` position model); not implemented
- **P12** — not started as committed business scope

**Repository health:** Strong test coverage for recent P11.4.x work (25 dedicated test files). **199** CLI test scripts exist under `tools/`. Security exclusions are documented and `.gitignore` protects private config paths.

---

## 2. Git Repository Status

| Item | Value |
|------|-------|
| Current branch | `main` |
| HEAD commit | `8d44784` — *Fix IPPanel OTP config compatibility* |
| Tracking | `origin/main` |
| Ahead of `origin/main` | **0** |
| Behind `origin/main` | **0** |
| Uncommitted changes | **None** (working tree clean) |
| Staged files | **None** |
| Untracked files | **None** |
| Total commits | **356** |

**Interpretation:** All P11.4.x work through P11.4.5-A is **committed and pushed**. No local-only drift detected.

---

## 3. Commit History Summary

### Last 80 commits — grouped by phase

#### P11.4.x (Access Management + Bugfixes) — **8 commits**

| Commit | Date | Summary |
|--------|------|---------|
| `8d44784` | 2026-06-28 | **P11.4.5-A** — IPPanel OTP config compatibility + diagnostics |
| `b866c3b` | 2026-06-28 | **P11.4.4-C** — Owner login redirect + session sync |
| `5473bad` | 2026-06-28 | **P11.4.4-B** — Staff login redirect + role-aware landing |
| `ea5c445` | 2026-06-28 | **P11.4.3-A** — Access role grant / request ID ODBC fix |
| `cac55c3` | 2026-06-28 | **P11.4.2** — Position dropdown filtering + validation |
| `d0372cb` | 2026-06-28 | **P11.4.1** — Persian encoding + dept/position schema alignment |
| `436c4ee` | 2026-06-28 | **P11.4** — Owner access management console |

#### P11.1–P11.3 (OTP + Online Bridge) — **4 commits**

| Commit | Date | Summary |
|--------|------|---------|
| `28229eb` | 2026-06-27 | **P11.3** — Secure online intake bridge (HMAC) |
| `df6af4b` | 2026-06-27 | **P11.2** — IPPanel OTP diagnostic CLI tool |
| `f6fd42f` | 2026-06-27 | Minor IPPanel example config update |
| `9c49328` | 2026-06-27 | **P11.1** — OTP provider configuration for IPPanel |

#### P11 RC Lock / Demo — **2 commits**

| Commit | Summary |
|--------|---------|
| `514fea7` | Lock V1 RC audit and local demo package |
| `1f83140` | Release hardening navigation and demo package RC |

#### P9–P10 (Soft Run + Release Hardening) — **4 commits**

| Commit | Summary |
|--------|---------|
| `45f2597` | End-to-end soft run and demo scenario |
| `083c1f5` | Management dashboard and owner KPI control |
| (+ earlier waves in history) | Soft run control room, findings, executive readiness |

#### P1–P8 (Operational Workflow) — **visible in last 80**

| Commit | Phase | Summary |
|--------|-------|---------|
| `a870cca` | P7 | Final invoice settlement + customer delivery |
| `2219724` | P6 | QC final inspection + delivery readiness |
| `6d7b45e` | P5 | Work execution + parts consumption |
| `8d7cf7d` | P4 | Estimate approval parts + finance gate |
| `e154c3d` | P3 | Technical operation board |
| `b02bd54` | P2 | Reception JobCard + contract gate |
| `eae034b` | P1.5 | Intake contract OTP signature gate |
| `94b11fb` | P1 | Online request intake + reception dashboard |

#### Earlier foundation (also in last 80)

- V1 production signoff (`c50389f`), SaaS release packages, Apex domain model, Wave 4–9 soft-run/executive modules, critical forms v2 activation, media/camera foundations.

#### Not found in Git history

| Phase | Status |
|-------|--------|
| **P11.4.2-A** (Username/User ID policy report) | **No commit** — report-only deliverable, not persisted as code |
| **P12** | **No committed business scope** — only exclusion references in docs/tests |

---

## 4. Phase Completion Matrix

| Phase | Goal | Key files | Tests | Commit found? | Status | Evidence | Remaining risk |
|-------|------|-----------|-------|---------------|--------|----------|----------------|
| **P11.4** | Owner access management console | `erp-access-management.php`, `m360-access-*-helper.php` (5 helpers), 7 UI pages | 7 test files | `436c4ee` | **DONE** | Commit message cites 69/69 tests; RC lock updated | Live staff users not created on production DB from Git alone |
| **P11.4.1** | Persian encoding + schema alignment | `m360-access-management-helper.php`, audit/permission helpers | `test-p11-4-persian-access-ui-schema-encoding.php` | `d0372cb` | **DONE** | 34/34 encoding test; no SQL migration | ODBC encoding edge cases on other Windows hosts |
| **P11.4.2** | Department-dependent position dropdown | `m360-access-position-filter.js`, user create/edit pages | 3 test files | `cac55c3` | **DONE** | Server + client validation | 43 seed positions still duplicate labels (UX mitigated only) |
| **P11.4.2-A** | Username vs user_id policy report | — | — | **No** | **REPORT ONLY** | Conversation/report deliverable | Policy not codified in repo |
| **P11.4.3-A** | Role grant request ID ODBC fix | `m360-access-audit-helper.php`, role/user helpers | 4 test files | `ea5c445` | **DONE** | SCOPE_IDENTITY fallback + ARM request numbers | Position seed cleanup still backlog |
| **P11.4.4-B** | Staff login redirect + role landing | `staff-login.php`, `erp-staff-home.php`, `m360-staff-home-helper.php` | 4 test files | `5473bad` | **DONE** | Session sync + redirect_url in API | Browser E2E on production host not proven here |
| **P11.4.4-C** | Owner login redirect | `owner-login.php`, `api/auth/owner-login.php` | 3 test files | `b866c3b` | **DONE** | Owner session sync + fallback paths | Same — needs live owner login test |
| **P11.4.5-A** | IPPanel OTP config compatibility | `m360-otp-config-loader.php`, `m360-otp-helper.php`, diagnostics | 3 test files + dry-run | `8d44784` | **DONE** (code) / **BLOCKED** (live send) | 15+12+20 tests pass; canonical key normalization | Real credentials on host only; live SMS not verified in audit |
| **P11.3** | Online test gate / HMAC bridge | `api/online-intake-secure-receive.php`, bridge helpers | 6 test files | `28229eb` | **PARTIAL** | 67/67 P11.3 tests | Port forward + cPanel forwarder not proven external |
| **P11.2** | IPPanel diagnostic CLI | `test-ippanel-request-response-diagnostic.php` | Existing OTP tests | `df6af4b` | **DONE** | CLI-only, masked trace | Requires configured host for real HTTP 200 |
| **P11.1** | OTP provider config patch | `m360-otp-config-loader.php`, example config | `test-p11-otp-provider-config.php` | `9c49328` | **DONE** | 25/25 provider config test | Host must create `private/m360-otp-config.php` |
| **P11 RC** | Lock, demo, security scan | `docs/release/MOGHARE360_V1_RC_FINAL_LOCK.md` | Multiple P11 tests | `514fea7`+ | **DONE** | RC lock doc + signoff tests | Operational acceptance still pending |
| **P1–P10** | Full V1 workflow | 179+ `erp-*.php`, SQL Server canonical | Wave/phase tests throughout | Many | **DONE** (in repo) | Soft run + signoff docs | Production E2E not re-run in this audit |
| **P12** | Future scope | — | — | **No** | **NOT STARTED** | Excluded in RC lock | N/A |
| **One-Day Run** | First real operational day | Runbook docs | `test-p11-4-one-day-run-access-readiness.php` | Doc only | **NOT STARTED** | Checklist in docs | Requires live users + flows |

---

## 5. Files Added / Modified by Phase

### P11.4 (`436c4ee`)

**Added**

- UI: `erp-access-management.php`, user create/edit, role assign, password reset, permission preview, change history
- Helpers: `m360-access-management-helper.php`, `m360-access-user-helper.php`, `m360-access-role-helper.php`, `m360-access-audit-helper.php`, `m360-access-permission-preview-helper.php`
- CSS: `assets/css/m360-access-management.css`
- Docs: 4 files under `docs/access/`
- Tests: 7 files (`test-p11-4-access-*`, `test-p11-4-one-day-run-access-readiness.php`)

**Modified:** `docs/release/MOGHARE360_V1_RC_FINAL_LOCK.md`

**Security preserved:** No Auth core rewrite; no permission seed mutation; bcrypt password hashing; audit trail.

---

### P11.4.1 (`d0372cb`)

**Modified:** Access helpers (ODBC UTF-8 normalization, schema column alignment)

**Added:** `tools/test-p11-4-persian-access-ui-schema-encoding.php`

**Security preserved:** No SQL migration; no seed changes; no Auth/Login changes.

---

### P11.4.2 (`cac55c3`)

**Added**

- `assets/js/m360-access-position-filter.js`
- Docs: position UX report + seed cleanup backlog
- Tests: 3 files

**Modified:** `erp-access-user-create.php`, `erp-access-user-edit.php`, `m360-access-user-helper.php`

**Security preserved:** No position seed mutation; no schema ALTER.

---

### P11.4.3-A (`ea5c445`)

**Modified:** Audit, role, user, management helpers (SCOPE_IDENTITY fallback, transactions, ARM request numbers)

**Added:** 4 test files

**Security preserved:** `granted_by_request_id` still required; no audit bypass.

---

### P11.4.4-B (`5473bad`)

**Added:** `erp-staff-home.php`, `m360-staff-home-helper.php`, `m360-staff-home.css`, `staff-logout.php`, 4 tests

**Modified:** `staff-login.php` (session sync + redirect only), `api/auth/staff-login.php` (`redirect_url`)

**Security preserved:** Password verification unchanged; role matrix restricts UI links; target pages retain own guards.

---

### P11.4.4-C (`b866c3b`)

**Modified:** `owner-login.php`, `api/auth/owner-login.php`, `m360-staff-home-helper.php` (owner helpers)

**Added:** 3 owner redirect tests

**Security preserved:** `is_system_owner`, `is_login_enabled`, `lifecycle_state=ACTIVE` unchanged.

---

### P11.4.5-A (`8d44784`)

**Modified:** `m360-otp-config-loader.php`, `m360-otp-helper.php`, `otp-config-diagnostics.php`, `test-ippanel-request-response-diagnostic.php`, `private/m360-otp-config.example.php`

**Added:** 3 P11.4.5 test files

**Security preserved:** No secrets committed; fake OTP blocked on production; Auth/Login untouched.

---

### P11.1–P11.3 (summary)

| Phase | Primary additions |
|-------|-------------------|
| P11.1 | OTP config loader, example config, provider test |
| P11.2 | IPPanel diagnostic CLI + masked debug helpers |
| P11.3 | HMAC online intake API, bridge config example, cPanel templates, 6 tests |

---

## 6. Test Coverage Matrix

### Access management (P11.4)

| Test file | Focus |
|-----------|-------|
| `test-p11-4-access-management-ui.php` | Console UI structure |
| `test-p11-4-access-user-create.php` | Staff user creation |
| `test-p11-4-role-assignment-ui.php` | Role assign/revoke |
| `test-p11-4-permission-preview-ui.php` | Read-only permission preview |
| `test-p11-4-password-reset-safety.php` | Temp password safety |
| `test-p11-4-access-scope-security.php` | Scope / no P12 / frozen auth files |
| `test-p11-4-one-day-run-access-readiness.php` | PASS/WARNING/BLOCKED readiness |

### Persian encoding (P11.4.1)

| Test file | Focus |
|-----------|-------|
| `test-p11-4-persian-access-ui-schema-encoding.php` | ODBC UTF-8, schema columns, escaping |

### Position dropdown filtering (P11.4.2)

| Test file | Focus |
|-----------|-------|
| `test-p11-4-2-position-dependent-dropdown.php` | Client filter behavior |
| `test-p11-4-2-position-validation.php` | Server-side dept/position pair |
| `test-p11-4-2-scope-security.php` | No seed/schema/auth changes |

### Role grant (P11.4.3-A)

| Test file | Focus |
|-----------|-------|
| `test-p11-4-3-access-request-schema-compatibility.php` | Request schema + insert ID fallback |
| `test-p11-4-3-role-grant-flow.php` | Grant flow |
| `test-p11-4-3-partial-user-retry.php` | Transaction rollback on failure |
| `test-p11-4-3-scope-security.php` | Frozen auth + no schema change |

### Staff login redirect (P11.4.4-B)

| Test file | Focus |
|-----------|-------|
| `test-p11-4-4-staff-login-redirect.php` | Redirect + session sync |
| `test-p11-4-4-staff-home-role-matrix.php` | Role → route cards |
| `test-p11-4-4-staff-home-authorization.php` | Role visibility rules |
| `test-p11-4-4-staff-home-scope-security.php` | Scope lock |

### Owner login redirect (P11.4.4-C)

| Test file | Focus |
|-----------|-------|
| `test-p11-4-4-owner-login-redirect.php` | Redirect resolution |
| `test-p11-4-4-owner-session-sync.php` | Session keys |
| `test-p11-4-4-owner-scope-security.php` | No bypass / no P12 |

### IPPanel OTP config (P11.1 + P11.4.5-A)

| Test file | Focus |
|-----------|-------|
| `test-p11-otp-provider-config.php` | Central OTP provider (25 assertions) |
| `test-p11-4-5-ippanel-config-loader-compatibility.php` | Canonical + alias keys |
| `test-p11-4-5-ippanel-pattern-payload.php` | IPPanel Edge payload shape |
| `test-p11-4-5-otp-security-scope.php` | Secrets + scope |
| `test-ippanel-request-response-diagnostic.php` | CLI trace (+ `--dry-run`) |
| `otp-config-diagnostics.php` | Masked config report (CLI) |

### Production signoff

| Test file | Focus |
|-----------|-------|
| `test-v1-production-signoff.php` | Signoff pages, SQL, DB read (23 assertions) |
| `test-v1-production-run-smoke.php` | Smoke checklist (24 assertions per prior signoff) |
| `test-p11-production-signoff-final.php` | P11 RC signoff integration |
| `test-p11-security-final-scan.php` | Security scan |

**Total `tools/test-*.php` files:** 199 (includes historical wave tests and dist copies).

---

## 7. Completed Items

Safe to describe as **completed in the repository**:

1. **V1 RC workflow codebase** — P1 through P10 operational modules committed with extensive wave/phase tests
2. **P11 RC lock** — documentation, demo package controls, security exclusions
3. **OTP architecture** — central `m360_otp_send_sms()`, private config loader, placeholder rejection, production fake-OTP block
4. **P11.4.5-A IPPanel compatibility** — canonical keys, alias normalization, wrong-case warnings, masked diagnostics
5. **Access management UI** — professional staff provisioning over SQL Server identity model
6. **P11.4.1 Persian rendering fix** — ODBC-safe UTF-8 path for access UI
7. **P11.4.2 position UX filter** — department-scoped dropdown + validation (not seed cleanup)
8. **P11.4.3-A role grant fix** — ODBC SCOPE_IDENTITY fallback for access requests
9. **P11.4.4-B/C login landing** — staff and owner post-login redirect + session sync
10. **P11.3 online bridge code** — HMAC secure intake API + cPanel templates (placeholders only in Git)
11. **Test automation** — 25 P11.4-specific tests + production signoff regression suite
12. **Security gitignore** — private OTP, ERP, bridge configs excluded from Git

---

## 8. Partial / Blocked Items

### Must not be considered complete yet

| Item | Why partial/blocked | Evidence |
|------|---------------------|----------|
| **Static IP / public ERP access** | No external proof of port 8080 reachable through firewall/DNAT | Docs use `LAPTOP_HOST_PLACEHOLDER`; runbook requires manual port forward |
| **DNS `erp.moghareh360.ir`** | Depends on external port 8080 responding; not validated in Git | User requirement; no passing external curl evidence in repo |
| **Real OTP SMS send** | Code ready; host needs `private/m360-otp-config.php` with real IPPanel credentials | Dry-run reports FAIL without private config |
| **One-Day Run** | Checklist exists; no commit proves first real user flow passed | `MOGHARE360_V1_ONE_DAY_RUN_ACCESS_SETUP.md` still unchecked |
| **Online intake bridge (live)** | Templates committed; cPanel forwarder + laptop bridge config are host-only | `private/m360-online-bridge-config.php` gitignored |
| **Position seed cleanup** | Backlog doc only — 43 positions, duplicate labels remain | `MOGHARE360_V1_POSITION_SEED_CLEANUP_BACKLOG.md` |
| **P11.4.2-A policy report** | Delivered as analysis only — not in Git | No matching commit |
| **Production customer E2E** | Soft run / demo complete in repo; first real customer run not signed off | `MOGHARE360_V1_FIRST_REAL_CUSTOMER_RUN.md` exists as guide |

---

## 9. Not Started Items

| Item | Notes |
|------|-------|
| **P12 operational taxonomy** | Explicitly excluded in RC lock; tests assert no P12 scope in P11.4.x changes |
| **Position seed/model cleanup (P11.4.3 backlog)** | Deferred; requires owner sign-off before seed migration |
| **Accounting / payment gateway / SaaS** | Locked out of V1 RC |
| **Production deploy from demo package** | RC lock forbids treating demo package as production deploy |
| **Username/user_id policy enforcement in code** | P11.4.2-A was report-only |

---

## 10. Security and Secret Exposure Review

| Control | Status | Evidence |
|---------|--------|----------|
| `private/m360-otp-config.php` not committed | **PROTECTED** | Listed in `.gitignore`; `git ls-files` returns empty |
| `public_html/mirror-config.php` not committed | **PROTECTED** | Gitignored; only `mirror-config.example.php` in repo (empty SMS keys) |
| `private/erp-config.php` not committed | **PROTECTED** | Gitignored |
| `private/production-users.json` not committed | **PROTECTED** | Gitignored |
| `private/m360-online-bridge-config.php` not committed | **PROTECTED** | Gitignored |
| Example configs contain placeholders only | **PASS** | `YOUR_REAL_*`, empty strings in examples |
| Tools not web-exposed | **PASS** | `tools/` is outside `public_html/` |
| `public_html/.htaccess` blocks sensitive paths | **PARTIAL** | Denies `config.php`, `sql/`, `*.sql`, `*.log`, `*.ini`; `docs/`, `private/`, `dist/` not under web root by default |
| No real staff passwords committed | **PASS** (audit scan) | Passwords hashed at runtime only; no plaintext in committed PHP |
| No API keys committed | **PASS** (audit scan) | Example + test values only; P11 security tests scan for keys |
| Fake OTP in production | **BLOCKED BY CODE** | `m360_otp_can_use_dev_code()` blocks production host |
| OTP displayed publicly in production | **BLOCKED BY CODE** | Production message does not expose code (tested) |

**Note:** Commit `f6fd42f` (*update Ippanel info*) touched example config placeholders only. If any real key was ever pushed historically, rotation is recommended per P11.2 commit message — not re-verified against full Git history in this audit.

---

## 11. Environment / Network Readiness

```
Customer → moghareh360.ir (cPanel)
        → forward-lead.php (HMAC signed)
        → LAPTOP:8080/api/online-intake-secure-receive.php
        → erp_customer_online_requests
        → erp-reception-online-requests.php
```

| Requirement | Repo state | Operational state |
|-------------|------------|-------------------|
| Apache/XAMPP on `:8080` | Documented | **Not verified externally** |
| Port forwarding / static IP | Placeholders in templates | **BLOCKED until network proven** |
| cPanel forwarder deployed | Example only in Git | **Host action required** |
| Bridge secret configured both sides | Example only | **Host action required** |
| `erp.moghareh360.ir` DNS → laptop:8080 | Not in committed config | **BLOCKED until 8080 responds externally** |
| Staff-gated readiness page | `erp-online-test-readiness.php` committed | Requires staff login + live config |

**Conclusion:** Network path is **designed and coded** but **not operationally complete** from repository evidence alone.

---

## 12. OTP Readiness

| Layer | Status |
|-------|--------|
| Code — config loader | **DONE** — returned-array support, canonical + alias keys, normalization |
| Code — IPPanel payload | **DONE** — `sending_type=pattern`, `params.OTP`, not `%OTP%` |
| Code — diagnostics | **DONE** — CLI masked report + `--dry-run` |
| Host — private config | **NOT IN GIT** — operator must create `private/m360-otp-config.php` |
| Live SMS send | **BLOCKED** — requires real IPPanel API key, sender, pattern code on host |
| Production fake OTP | **FORBIDDEN** — enforced in helper |

**Operator commands (on configured host):**

```powershell
C:\xampp\php\php.exe tools\otp-config-diagnostics.php
C:\xampp\php\php.exe tools\test-ippanel-request-response-diagnostic.php --dry-run
# After config present:
C:\xampp\php\php.exe tools\test-ippanel-request-response-diagnostic.php --mobile=09XXXXXXXXX --otp=123456
```

---

## 13. Login and Access Readiness

| Capability | Code | Live validation |
|------------|------|-----------------|
| Owner access management console | **DONE** | Needs owner login + SQL Server |
| Staff user create / edit | **DONE** | Needs owner/admin session |
| Role grant with audit trail | **DONE** (P11.4.3-A fix) | Needs live ODBC test |
| Staff login redirect → `erp-staff-home.php` | **DONE** | Needs browser test per role |
| Owner login redirect → product home | **DONE** | Needs browser test |
| Role-aware landing matrix | **DONE** | Missing route files show disabled cards |
| Dedicated staff logins for One-Day Run | **NOT DONE** | Checklist items unchecked in runbook |

---

## 14. One-Day Run Readiness

From `docs/access/MOGHARE360_V1_ONE_DAY_RUN_ACCESS_SETUP.md`:

- [ ] Access management readiness not BLOCKED
- [ ] Staff users `user_id >= 20001` created
- [ ] RECEPTION + TECHNICIAN (or required units) with roles
- [ ] `is_login_enabled=1`, `lifecycle_state=ACTIVE`
- [ ] Temporary passwords shared securely (not in Git)
- [ ] Permission preview reviewed
- [ ] Owner login reserved for oversight

**Automated readiness test exists:** `test-p11-4-one-day-run-access-readiness.php` — reports PASS/WARNING/BLOCKED against DB state when run on configured host.

**Verdict:** One-Day Run is **NOT COMPLETE** until the above checklist passes on the live environment with real users and at least one full operational flow (intake → jobcard → work → QC → delivery or agreed subset).

---

## 15. Recommended Next Step

### 1. Safe to say is completed

- All P11.4.x **code, tests, and documentation** committed to `main`
- V1 RC **feature scope P1–P11.4.5-A** is in the repository
- Security exclusions and gitignore model are in place

### 2. Not completed

- External network / DNS / port 8080 path
- Live OTP SMS verification
- Live online intake bridge (cPanel ↔ laptop)
- One-Day Run with real staff
- Position seed cleanup
- P12

### 3. Must be tested before One-Day Run

1. Create `private/m360-otp-config.php` on host; run `otp-config-diagnostics.php` → **PASS**
2. Send one diagnostic OTP to a test mobile via IPPanel CLI tool
3. Prove port 8080 reachable externally (or document VPN-only access model)
4. Deploy cPanel forwarder; submit DEMO lead; verify reception dashboard
5. Create run staff users via `erp-access-management.php`
6. Browser login test: owner → product home; each run role → `erp-staff-home.php`
7. Execute one end-to-end workflow on SQL Server with audit trail review
8. Run `test-p11-4-one-day-run-access-readiness.php` → PASS
9. Run `test-v1-production-signoff.php` and `test-v1-production-run-smoke.php` on host

### 4. Recommended immediate action

**P11.6-1 — Environment + One-Day Run prep (operational, not new features):**

1. Configure host-only secrets (`private/m360-otp-config.php`, bridge config)
2. Validate network path and OTP send on staging/test mobile
3. Provision One-Day Run staff users and execute controlled dry run
4. Record results in Fix Register / signoff pages — do not expand to P12

---

MOGHARE360 V1 RC repository audit separates committed work from tested work and identifies remaining environment, OTP, network, access and operational gaps before One-Day Run.

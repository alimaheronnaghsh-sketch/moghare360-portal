# MOGHARE360 — Local File Disposition Audit

**Date:** 2026-07-06  
**Repo:** `moghare360-portal`  
**Git snapshot:** `git status --porcelain=v1 -uall` (working tree)  
**Commit Eligibility:** `NOT_ELIGIBLE_DISPOSITION_AUDIT_ONLY`  
**Type:** Classification only — no runtime, tool, or code changes in this pass

---

## Classification Rules Applied

| Rule | Application |
|------|-------------|
| `public_html/*` | `HOLD_RUNTIME_UAT` until PR-02A / P11.9 browser UAT passes |
| `tools/test-pr-02a-*` | `HOLD_RUNTIME_UAT` until PR-02A runtime UAT passes |
| `docs/audit/*` governance | `COMMIT_NOW_DOCS` |
| `private/*` secrets | `FORBIDDEN_DO_NOT_COMMIT` (none detected in working tree) |
| Scope-exception runtime | `HOLD_SCOPE_EXCEPTION` for registered helper |
| temp/cache/dist | `REVERT_CANDIDATE` / `FORBIDDEN` (none detected) |

**Note:** PR-02A scope-exception docs (`MOGHARE360_PR_02A_*`) are already committed (`ffbc00b`) and are **not** in the working tree; they are excluded from this audit.

---

## Summary

| Metric | Count |
|--------|------:|
| **Total files classified** | **130** |
| `COMMIT_NOW_DOCS` | 29 |
| `COMMIT_NOW_TEST` | 0 |
| `HOLD_RUNTIME_UAT` | 100 |
| `HOLD_SCOPE_EXCEPTION` | 1 |
| `REVERT_CANDIDATE` | 0 |
| `FORBIDDEN_DO_NOT_COMMIT` | 0 |

| Git working-tree files | 129 |
| This disposition report (created by audit) | 1 |

---

## Next Safe Commit Recommendation

**Safe now (docs-only batch):** Commit **only** `docs/audit/MOGHARE360_LOCAL_FILE_DISPOSITION_AUDIT.md` together with the **28 untracked P11.9 / V1 RC audit reports** (`COMMIT_NOW_DOCS`). No `public_html`, `tools`, `includes`, or `private` files.

**Not safe now:** Any runtime (`public_html`, `includes/erp-csrf.php`), OTP wiring, reception/customer intake, calendar helper, diagnostics, fixtures, or tests — all remain held until:

1. PR-02A manual browser UAT (plate, vehicle, 30-day calendar, hall-manager send)
2. Owner approval of `m360-calendar-1405-helper.php` scope exception (registered in `ffbc00b` docs; runtime still uncommitted)
3. P11.9 C-2C fix phases (D1→E7D) reconciled and UAT-passed per phase reports

**Push:** Not allowed for any held runtime/test batch until owner signs off post-UAT.

---

## Table A — `COMMIT_NOW_DOCS` (29)

| File path | File type | PR/phase | Created/modified | Category | Reason | Commit allowed now | Push allowed now | Owner approval required | Related runtime dependency | Recommended action |
|-----------|-----------|----------|------------------|----------|--------|-------------------|------------------|-------------------------|---------------------------|-------------------|
| `docs/audit/MOGHARE360_LOCAL_FILE_DISPOSITION_AUDIT.md` | MD | Disposition audit | Created | COMMIT_NOW_DOCS | Governance classification report; no runtime coupling | yes | yes | no | None | Commit with other audit docs batch |
| `docs/audit/MOGHARE360_P11_9_C_2C_FIX_D1A_IPPANEL_AUTH_DIAGNOSTICS_REPORT.md` | MD | P11.9 D1A | Created | COMMIT_NOW_DOCS | Phase audit/governance doc | yes | yes | no | OTP / ippanel diagnostics (held runtime) | Commit docs-only batch |
| `docs/audit/MOGHARE360_P11_9_C_2C_FIX_D1A_IPPANEL_AUTH_DIAGNOSTICS_SCOPE_REPORT.md` | MD | P11.9 D1A | Created | COMMIT_NOW_DOCS | Scope/governance doc | yes | yes | no | OTP diagnostics scope | Commit docs-only batch |
| `docs/audit/MOGHARE360_P11_9_C_2C_FIX_D1_OTP_CLEANUP_WIRING_REPORT.md` | MD | P11.9 D1 | Created | COMMIT_NOW_DOCS | Phase audit report | yes | yes | no | OTP canonical wiring (held) | Commit docs-only batch |
| `docs/audit/MOGHARE360_P11_9_C_2C_FIX_D1_OTP_CLEANUP_WIRING_SCOPE_REPORT.md` | MD | P11.9 D1 | Created | COMMIT_NOW_DOCS | Scope report | yes | yes | no | OTP wiring scope | Commit docs-only batch |
| `docs/audit/MOGHARE360_P11_9_C_2C_FIX_E1_STEPPER_BROWSER_REGRESSION_REPORT.md` | MD | P11.9 E1 | Created | COMMIT_NOW_DOCS | Phase audit report | yes | yes | no | Reception stepper (held) | Commit docs-only batch |
| `docs/audit/MOGHARE360_P11_9_C_2C_FIX_E1_STEPPER_BROWSER_REGRESSION_SCOPE_REPORT.md` | MD | P11.9 E1 | Created | COMMIT_NOW_DOCS | Scope report | yes | yes | no | Stepper scope | Commit docs-only batch |
| `docs/audit/MOGHARE360_P11_9_C_2C_FIX_E2_TRUE_WIZARD_LOCK_REPORT.md` | MD | P11.9 E2 | Created | COMMIT_NOW_DOCS | Phase audit report | yes | yes | no | Wizard lock (held) | Commit docs-only batch |
| `docs/audit/MOGHARE360_P11_9_C_2C_FIX_E2_TRUE_WIZARD_LOCK_SCOPE_REPORT.md` | MD | P11.9 E2 | Created | COMMIT_NOW_DOCS | Scope report | yes | yes | no | Wizard lock scope | Commit docs-only batch |
| `docs/audit/MOGHARE360_P11_9_C_2C_FIX_E3_RESTORE_D1B_OTP_REPORT.md` | MD | P11.9 E3 | Created | COMMIT_NOW_DOCS | Phase audit report | yes | yes | no | OTP restore (held) | Commit docs-only batch |
| `docs/audit/MOGHARE360_P11_9_C_2C_FIX_E3_RESTORE_D1B_OTP_SCOPE_REPORT.md` | MD | P11.9 E3 | Created | COMMIT_NOW_DOCS | Scope report | yes | yes | no | OTP restore scope | Commit docs-only batch |
| `docs/audit/MOGHARE360_P11_9_C_2C_FIX_E4_SERVICE_DIAGNOSTIC_GATE_REPORT.md` | MD | P11.9 E4 | Created | COMMIT_NOW_DOCS | Phase audit report | yes | yes | no | Service gate (held) | Commit docs-only batch |
| `docs/audit/MOGHARE360_P11_9_C_2C_FIX_E4_SERVICE_DIAGNOSTIC_GATE_SCOPE_REPORT.md` | MD | P11.9 E4 | Created | COMMIT_NOW_DOCS | Scope report | yes | yes | no | Service gate scope | Commit docs-only batch |
| `docs/audit/MOGHARE360_P11_9_C_2C_FIX_E5_PHOTO_COMPLETION_REPORT.md` | MD | P11.9 E5 | Created | COMMIT_NOW_DOCS | Phase audit report | yes | yes | no | Photo wizard (held) | Commit docs-only batch |
| `docs/audit/MOGHARE360_P11_9_C_2C_FIX_E5_PHOTO_COMPLETION_SCOPE_REPORT.md` | MD | P11.9 E5 | Created | COMMIT_NOW_DOCS | Scope report | yes | yes | no | Photo scope | Commit docs-only batch |
| `docs/audit/MOGHARE360_P11_9_C_2C_FIX_E6A_SQL_TRUTH_WIZARD_ROUTING_REPORT.md` | MD | P11.9 E6A | Created | COMMIT_NOW_DOCS | Phase audit report | yes | yes | no | SQL-truth routing (held) | Commit docs-only batch |
| `docs/audit/MOGHARE360_P11_9_C_2C_FIX_E6A_SQL_TRUTH_WIZARD_SCOPE_REPORT.md` | MD | P11.9 E6A | Created | COMMIT_NOW_DOCS | Scope report | yes | yes | no | E6A scope | Commit docs-only batch |
| `docs/audit/MOGHARE360_P11_9_C_2C_FIX_E7A_DIAG_DOCUMENTS_CSRF_REPORT.md` | MD | P11.9 E7A | Created | COMMIT_NOW_DOCS | Diagnostic audit report | yes | yes | no | Documents/CSRF diag (held) | Commit docs-only batch |
| `docs/audit/MOGHARE360_P11_9_C_2C_FIX_E7B_STABLE_CSRF_REPORT.md` | MD | P11.9 E7B | Created | COMMIT_NOW_DOCS | Phase audit report | yes | yes | no | Stable CSRF (held) | Commit docs-only batch |
| `docs/audit/MOGHARE360_P11_9_C_2C_FIX_E7B_STABLE_CSRF_SCOPE_REPORT.md` | MD | P11.9 E7B | Created | COMMIT_NOW_DOCS | Scope report | yes | yes | no | CSRF scope | Commit docs-only batch |
| `docs/audit/MOGHARE360_P11_9_C_2C_FIX_E7C_DIAG_CONTRACT_FLOW_BLUEPRINT_REPORT.md` | MD | P11.9 E7C | Created | COMMIT_NOW_DOCS | Blueprint/diagnostic report | yes | yes | no | Contract flow (held) | Commit docs-only batch |
| `docs/audit/MOGHARE360_P11_9_C_2C_FIX_E7D_CUSTOMER_CARTABLE_BRIDGE_REPORT.md` | MD | P11.9 E7D | Created | COMMIT_NOW_DOCS | Phase audit report | yes | yes | no | Customer cartable (held) | Commit docs-only batch |
| `docs/audit/MOGHARE360_P11_9_C_2C_FIX_E7D_CUSTOMER_CARTABLE_BRIDGE_SCOPE_REPORT.md` | MD | P11.9 E7D | Created | COMMIT_NOW_DOCS | Scope report | yes | yes | no | E7D scope | Commit docs-only batch |
| `docs/audit/MOGHARE360_P11_9_C_2C_FIX_E7_CUSTOMER_CONTRACT_REPORT.md` | MD | P11.9 E7 | Created | COMMIT_NOW_DOCS | Phase audit report | yes | yes | no | Customer contract (held) | Commit docs-only batch |
| `docs/audit/MOGHARE360_P11_9_C_2C_FIX_E7_CUSTOMER_CONTRACT_SCOPE_REPORT.md` | MD | P11.9 E7 | Created | COMMIT_NOW_DOCS | Scope report | yes | yes | no | E7 scope | Commit docs-only batch |
| `docs/audit/MOGHARE360_P11_9_C_2C_FIX_E_STEPPER_UX_REPORT.md` | MD | P11.9 E | Created | COMMIT_NOW_DOCS | Phase audit report | yes | yes | no | Stepper UX (held) | Commit docs-only batch |
| `docs/audit/MOGHARE360_P11_9_C_2C_FIX_E_STEPPER_UX_SCOPE_REPORT.md` | MD | P11.9 E | Created | COMMIT_NOW_DOCS | Scope report | yes | yes | no | Stepper UX scope | Commit docs-only batch |
| `docs/audit/MOGHARE360_V1_RC_DOCS_BLUEPRINT_INVENTORY_REPORT.md` | MD | V1 RC | Created | COMMIT_NOW_DOCS | Blueprint inventory governance | yes | yes | no | None | Commit docs-only batch |
| `docs/audit/MOGHARE360_V1_RC_GLOBAL_SOFTWARE_AUDIT_FREEZE_REPORT.md` | MD | V1 RC | Created | COMMIT_NOW_DOCS | Global audit freeze governance | yes | yes | no | None | Commit docs-only batch |

---

## Table B — `HOLD_SCOPE_EXCEPTION` (1)

| File path | File type | PR/phase | Created/modified | Category | Reason | Commit allowed now | Push allowed now | Owner approval required | Related runtime dependency | Recommended action |
|-----------|-----------|----------|------------------|----------|--------|-------------------|------------------|-------------------------|---------------------------|-------------------|
| `public_html/includes/m360-calendar-1405-helper.php` | PHP | PR-02A | Created | HOLD_SCOPE_EXCEPTION | Registered scope exception (`CALENDAR_HELPER_SCOPE_EXCEPTION=yes` in committed docs); runtime uncommitted; shared customer+reception calendar only | no | no | yes | `customer-request.php`, `m360-reception-workbench-helper.php`, `mirror.css`, `customer-form.js`, `m360-reception-intake.js` | Hold until owner approves exception **and** PR-02A browser UAT passes; commit only with PR-02A runtime batch |

---

## Table C — `HOLD_RUNTIME_UAT` — `public_html` (20)

| File path | File type | PR/phase | Created/modified | Category | Reason | Commit allowed now | Push allowed now | Owner approval required | Related runtime dependency | Recommended action |
|-----------|-----------|----------|------------------|----------|--------|-------------------|------------------|-------------------------|---------------------------|-------------------|
| `public_html/api/customer/send-otp.php` | PHP | P11.9 D1 | Modified | HOLD_RUNTIME_UAT | OTP runtime; browser/UAT not signed off | no | no | yes | `m360-otp-helper.php`, ippanel config | Hold with OTP batch after D1 UAT |
| `public_html/assets/css/mirror.css` | CSS | PR-02A | Modified | HOLD_RUNTIME_UAT | Calendar disabled-day styles; UAT pending | no | no | yes | `m360-calendar-1405-helper.php`, customer+reception pages | Hold until PR-02A UAT |
| `public_html/assets/css/moghare360-v1-luxury-ui.css` | CSS | PR-02A | Modified | HOLD_RUNTIME_UAT | Reception vehicle/calendar UI; UAT pending | no | no | yes | `erp-reception-intake-file.php` | Hold until PR-02A UAT |
| `public_html/assets/js/customer-form.js` | JS | PR-02A | Modified | HOLD_RUNTIME_UAT | Customer calendar click guard; UAT pending | no | no | yes | `customer-request.php`, calendar helper | Hold until PR-02A UAT |
| `public_html/assets/js/m360-reception-intake.js` | JS | PR-02A | Modified | HOLD_RUNTIME_UAT | Reception plate/vehicle/calendar; UAT pending | no | no | yes | `erp-reception-intake-file.php` | Hold until PR-02A UAT |
| `public_html/assets/js/vehicle-brand-classes.js` | JS | PR-02A | Modified | HOLD_RUNTIME_UAT | Brand/model/year selectors; UAT pending | no | no | yes | Reception + customer vehicle UI | Hold until PR-02A UAT |
| `public_html/check-otp.php` | PHP | P11.9 D1 | Modified | HOLD_RUNTIME_UAT | OTP verification route; UAT pending | no | no | yes | OTP helper stack | Hold with OTP batch |
| `public_html/customer-request.php` | PHP | PR-02A | Modified | HOLD_RUNTIME_UAT | Customer visit calendar alignment; UAT pending | no | no | yes | `m360-calendar-1405-helper.php` | Hold until PR-02A UAT |
| `public_html/erp-reception-intake-file.php` | PHP | PR-02A | Modified | HOLD_RUNTIME_UAT | Reception wizard UI; UAT pending | no | no | yes | Workbench helper, JS/CSS assets | Hold until PR-02A UAT |
| `public_html/erp-reception-intake-save.php` | PHP | PR-02A | Modified | HOLD_RUNTIME_UAT | Reception save path; UAT pending | no | no | yes | Workbench helper validation | Hold until PR-02A UAT |
| `public_html/includes/m360-otp-config-loader.php` | PHP | P11.9 D1 | Modified | HOLD_RUNTIME_UAT | OTP config loader; UAT pending | no | no | yes | `private/m360-otp-config.php` (not in tree) | Hold with OTP batch |
| `public_html/includes/m360-otp-helper.php` | PHP | P11.9 D1 | Modified | HOLD_RUNTIME_UAT | Core OTP helper; UAT pending | no | no | yes | All OTP endpoints | Hold with OTP batch |
| `public_html/includes/m360-reception-helper.php` | PHP | P11.9 E | Modified | HOLD_RUNTIME_UAT | Reception helper changes; wizard UAT pending | no | no | yes | Intake file/save | Hold with P11.9 E batch |
| `public_html/includes/m360-reception-workbench-helper.php` | PHP | PR-02A | Modified | HOLD_RUNTIME_UAT | Plate/vehicle/calendar/hall-manager; UAT pending | no | no | yes | Calendar helper, intake save | Hold until PR-02A UAT |
| `public_html/send-contract-otp.php` | PHP | P11.9 D1/E7 | Modified | HOLD_RUNTIME_UAT | Contract OTP send; UAT pending | no | no | yes | OTP helper | Hold with OTP/contract batch |
| `public_html/send-otp.php` | PHP | P11.9 D1 | Modified | HOLD_RUNTIME_UAT | OTP send route; UAT pending | no | no | yes | OTP helper | Hold with OTP batch |
| `public_html/verify-contract-otp.php` | PHP | P11.9 E7 | Modified | HOLD_RUNTIME_UAT | Contract OTP verify; UAT pending | no | no | yes | Customer contract flow | Hold with E7 UAT |
| `public_html/verify-otp.php` | PHP | P11.9 D1 | Modified | HOLD_RUNTIME_UAT | OTP verify route; UAT pending | no | no | yes | OTP helper | Hold with OTP batch |
| `public_html/customer-intake-contract-review.php` | PHP | P11.9 E7/E7D | Created | HOLD_RUNTIME_UAT | New customer contract/cartable page; UAT pending | no | no | yes | E7/E7D tests, CSRF | Hold until E7D UAT |
| `public_html/includes/m360-legacy-otp-deprecation-stub.php` | PHP | P11.9 D1 | Created | HOLD_RUNTIME_UAT | Legacy OTP deprecation stub; UAT pending | no | no | yes | D1 canonical OTP routes | Hold with D1 batch |

---

## Table D — `HOLD_RUNTIME_UAT` — non-`public_html` runtime (2)

| File path | File type | PR/phase | Created/modified | Category | Reason | Commit allowed now | Push allowed now | Owner approval required | Related runtime dependency | Recommended action |
|-----------|-----------|----------|------------------|----------|--------|-------------------|------------------|-------------------------|---------------------------|-------------------|
| `includes/erp-csrf.php` | PHP | P11.9 E7B | Modified | HOLD_RUNTIME_UAT | CSRF token stability; needs browser multitab UAT | no | no | yes | Staff/customer forms, E7B tests | Hold until E7B UAT passes |
| `private/m360-otp-config.example.php` | PHP | P11.9 D1 | Modified | HOLD_RUNTIME_UAT | Example template only (not live secret); commit with OTP phase after review | no | no | yes | OTP loader (live config excluded) | Hold; verify diff contains no real secrets before any commit |

---

## Table E — `HOLD_RUNTIME_UAT` — `tools/test-pr-02a-*` (6)

| File path | File type | PR/phase | Created/modified | Category | Reason | Commit allowed now | Push allowed now | Owner approval required | Related runtime dependency | Recommended action |
|-----------|-----------|----------|------------------|----------|--------|-------------------|------------------|-------------------------|---------------------------|-------------------|
| `tools/test-pr-02a-hall-manager-gate.php` | PHP | PR-02A | Created | HOLD_RUNTIME_UAT | PR-02A test; rule: hold until PR-02A runtime UAT passes | no | no | yes | `m360-reception-workbench-helper.php` | Hold; commit with PR-02A runtime batch post-UAT |
| `tools/test-pr-02a-jalali-working-calendar.php` | PHP | PR-02A | Created | HOLD_RUNTIME_UAT | PR-02A calendar test; runtime UAT pending | no | no | yes | `m360-calendar-1405-helper.php` | Hold; commit with PR-02A batch |
| `tools/test-pr-02a-other-brand-manager-exception.php` | PHP | PR-02A | Created | HOLD_RUNTIME_UAT | PR-02A vehicle سایر test; UAT pending | no | no | yes | `vehicle-brand-classes.js` | Hold; commit with PR-02A batch |
| `tools/test-pr-02a-plate-standard-alignment.php` | PHP | PR-02A | Created | HOLD_RUNTIME_UAT | PR-02A plate test; UAT pending | no | no | yes | Reception intake plate widget | Hold; commit with PR-02A batch |
| `tools/test-pr-02a-scope-security.php` | PHP | PR-02A | Created | HOLD_RUNTIME_UAT | PR-02A scope hash gate; UAT pending | no | no | yes | All PR-02A runtime files | Hold; commit with PR-02A batch |
| `tools/test-pr-02a-vehicle-selector-standard.php` | PHP | PR-02A | Created | HOLD_RUNTIME_UAT | PR-02A vehicle selector test; UAT pending | no | no | yes | Brand/model JS + workbench | Hold; commit with PR-02A batch |

---

## Table F — `HOLD_RUNTIME_UAT` — `tools` P11.9 / diagnostics / fixtures (72)

All files below: **Created/modified** per git (`??` = created, `M` = modified). **Category** `HOLD_RUNTIME_UAT`. **Commit/Push** no/no. **Owner approval** yes. **Recommended action:** Hold until corresponding P11.9 phase browser UAT passes; commit with that phase's runtime batch.

| File path | File type | PR/phase | Related runtime dependency |
|-----------|-----------|----------|---------------------------|
| `tools/diagnose-p11-9-c-2c-fix-e7d-customer-cartable.php` | PHP | P11.9 E7D | `customer-intake-contract-review.php`, cartable bridge |
| `tools/diagnose-p11-9-c-2c-wizard-state.php` | PHP | P11.9 E6A | Reception wizard routing |
| `tools/fixtures/e6a-request18-payload.php` | PHP | P11.9 E6A | Request #18 wizard fixtures |
| `tools/fixtures/e7d-test-request-post.php` | PHP | P11.9 E7D | E7D POST fixtures |
| `tools/ippanel-auth-diagnostics.php` | PHP | P11.9 D1A | ippanel auth / OTP config |
| `tools/test-p11-9-c-2c-fix-c-photo-six.php` | PHP | P11.9 C | Photo step (modified) |
| `tools/test-p11-9-c-2c-fix-c-scroll-anchor.php` | PHP | P11.9 C | Scroll anchor (modified) |
| `tools/test-p11-9-c-2c-fix-d1-legacy-route-deprecation.php` | PHP | P11.9 D1 | Legacy OTP stub |
| `tools/test-p11-9-c-2c-fix-d1-mobile-reset.php` | PHP | P11.9 D1 | OTP mobile reset |
| `tools/test-p11-9-c-2c-fix-d1-no-secret-leak.php` | PHP | P11.9 D1 | OTP secret hygiene |
| `tools/test-p11-9-c-2c-fix-d1-otp-canonical-wiring.php` | PHP | P11.9 D1 | Canonical OTP routes |
| `tools/test-p11-9-c-2c-fix-d1-reception-verify-bridge.php` | PHP | P11.9 D1 | Reception verify bridge |
| `tools/test-p11-9-c-2c-fix-d1-scope-security.php` | PHP | P11.9 D1 | D1 scope hashes |
| `tools/test-p11-9-c-2c-fix-d1a-ippanel-auth-diagnostics.php` | PHP | P11.9 D1A | ippanel diagnostics |
| `tools/test-p11-9-c-2c-fix-d1a-no-secret-leak.php` | PHP | P11.9 D1A | Secret leak guard |
| `tools/test-p11-9-c-2c-fix-d1a-scope-security.php` | PHP | P11.9 D1A | D1A scope |
| `tools/test-p11-9-c-2c-fix-e-active-step-redirects.php` | PHP | P11.9 E | Step redirects |
| `tools/test-p11-9-c-2c-fix-e-long-form-collapse.php` | PHP | P11.9 E | Long-form UX |
| `tools/test-p11-9-c-2c-fix-e-otp-step-visibility.php` | PHP | P11.9 E | OTP step visibility |
| `tools/test-p11-9-c-2c-fix-e-scope-security.php` | PHP | P11.9 E | E scope |
| `tools/test-p11-9-c-2c-fix-e-stepper-ux.php` | PHP | P11.9 E | Stepper UX |
| `tools/test-p11-9-c-2c-fix-e1-no-tomar.php` | PHP | P11.9 E1 | Single-step render |
| `tools/test-p11-9-c-2c-fix-e1-otp-send-regression.php` | PHP | P11.9 E1 | OTP send regression |
| `tools/test-p11-9-c-2c-fix-e1-scope-security.php` | PHP | P11.9 E1 | E1 scope |
| `tools/test-p11-9-c-2c-fix-e1-step-routing.php` | PHP | P11.9 E1 | Step routing |
| `tools/test-p11-9-c-2c-fix-e1-true-single-step-render.php` | PHP | P11.9 E1 | Single-step render |
| `tools/test-p11-9-c-2c-fix-e2-navigation-rules.php` | PHP | P11.9 E2 | Navigation rules |
| `tools/test-p11-9-c-2c-fix-e2-no-tomar.php` | PHP | P11.9 E2 | Wizard lock |
| `tools/test-p11-9-c-2c-fix-e2-post-signature-lock.php` | PHP | P11.9 E2 | Post-signature lock |
| `tools/test-p11-9-c-2c-fix-e2-save-guard.php` | PHP | P11.9 E2 | Save guard |
| `tools/test-p11-9-c-2c-fix-e2-scope-security.php` | PHP | P11.9 E2 | E2 scope |
| `tools/test-p11-9-c-2c-fix-e2-true-wizard.php` | PHP | P11.9 E2 | True wizard |
| `tools/test-p11-9-c-2c-fix-e3-no-duplicate-otp-path.php` | PHP | P11.9 E3 | OTP dedup |
| `tools/test-p11-9-c-2c-fix-e3-otp-ui-state.php` | PHP | P11.9 E3 | OTP UI state |
| `tools/test-p11-9-c-2c-fix-e3-restore-d1b-otp-flow.php` | PHP | P11.9 E3 | D1B OTP restore |
| `tools/test-p11-9-c-2c-fix-e3-scope-security.php` | PHP | P11.9 E3 | E3 scope |
| `tools/test-p11-9-c-2c-fix-e3-wizard-lock-regression.php` | PHP | P11.9 E3 | Wizard lock regression |
| `tools/test-p11-9-c-2c-fix-e4-otp-freeze-regression.php` | PHP | P11.9 E4 | OTP freeze |
| `tools/test-p11-9-c-2c-fix-e4-scope-security.php` | PHP | P11.9 E4 | E4 scope |
| `tools/test-p11-9-c-2c-fix-e4-service-diagnostic-gate.php` | PHP | P11.9 E4 | Service gate |
| `tools/test-p11-9-c-2c-fix-e4-service-payload-contract.php` | PHP | P11.9 E4 | Service payload |
| `tools/test-p11-9-c-2c-fix-e4-service-wizard-routing.php` | PHP | P11.9 E4 | Service routing |
| `tools/test-p11-9-c-2c-fix-e5-otp-service-freeze-regression.php` | PHP | P11.9 E5 | OTP+service freeze |
| `tools/test-p11-9-c-2c-fix-e5-photo-loop-regression.php` | PHP | P11.9 E5 | Photo loop |
| `tools/test-p11-9-c-2c-fix-e5-photo-payload-contract.php` | PHP | P11.9 E5 | Photo payload |
| `tools/test-p11-9-c-2c-fix-e5-photo-save-slots.php` | PHP | P11.9 E5 | Photo slots |
| `tools/test-p11-9-c-2c-fix-e5-photo-wizard-completion.php` | PHP | P11.9 E5 | Photo completion |
| `tools/test-p11-9-c-2c-fix-e5-scope-security.php` | PHP | P11.9 E5 | E5 scope |
| `tools/test-p11-9-c-2c-fix-e6a-documents-contract-routing.php` | PHP | P11.9 E6A | Documents routing |
| `tools/test-p11-9-c-2c-fix-e6a-no-vehicle-backjump.php` | PHP | P11.9 E6A | No backjump |
| `tools/test-p11-9-c-2c-fix-e6a-regression-freeze.php` | PHP | P11.9 E6A | Regression freeze |
| `tools/test-p11-9-c-2c-fix-e6a-scope-security.php` | PHP | P11.9 E6A | E6A scope |
| `tools/test-p11-9-c-2c-fix-e6a-signature-blocking-checklist.php` | PHP | P11.9 E6A | Signature checklist |
| `tools/test-p11-9-c-2c-fix-e6a-sql-truth-step-state.php` | PHP | P11.9 E6A | SQL truth state |
| `tools/test-p11-9-c-2c-fix-e7-contract-token-security.php` | PHP | P11.9 E7 | Contract token |
| `tools/test-p11-9-c-2c-fix-e7-csrf-invalid-request.php` | PHP | P11.9 E7 | CSRF invalid |
| `tools/test-p11-9-c-2c-fix-e7-customer-contract-page.php` | PHP | P11.9 E7 | Customer contract page |
| `tools/test-p11-9-c-2c-fix-e7-documents-routing.php` | PHP | P11.9 E7 | Documents routing |
| `tools/test-p11-9-c-2c-fix-e7-regression-freeze.php` | PHP | P11.9 E7 | E7 freeze |
| `tools/test-p11-9-c-2c-fix-e7-scope-security.php` | PHP | P11.9 E7 | E7 scope |
| `tools/test-p11-9-c-2c-fix-e7b-contract-flow-regression.php` | PHP | P11.9 E7B | Contract flow |
| `tools/test-p11-9-c-2c-fix-e7b-documents-forms-csrf.php` | PHP | P11.9 E7B | Forms CSRF |
| `tools/test-p11-9-c-2c-fix-e7b-invalid-csrf-recovery.php` | PHP | P11.9 E7B | CSRF recovery |
| `tools/test-p11-9-c-2c-fix-e7b-multitab-csrf.php` | PHP | P11.9 E7B | Multitab CSRF |
| `tools/test-p11-9-c-2c-fix-e7b-scope-security.php` | PHP | P11.9 E7B | E7B scope |
| `tools/test-p11-9-c-2c-fix-e7b-stable-csrf-token.php` | PHP | P11.9 E7B | Stable CSRF |
| `tools/test-p11-9-c-2c-fix-e7d-cartable-page.php` | PHP | P11.9 E7D | Cartable page |
| `tools/test-p11-9-c-2c-fix-e7d-cartable-token-persistence.php` | PHP | P11.9 E7D | Token persistence |
| `tools/test-p11-9-c-2c-fix-e7d-customer-acceptance.php` | PHP | P11.9 E7D | Customer acceptance |
| `tools/test-p11-9-c-2c-fix-e7d-prerequisite-gate.php` | PHP | P11.9 E7D | Prerequisite gate |
| `tools/test-p11-9-c-2c-fix-e7d-scope-security.php` | PHP | P11.9 E7D | E7D scope |
| `tools/test-p11-9-c-2c-fix-e7d-staff-routing.php` | PHP | P11.9 E7D | Staff routing |

**Table F uniform fields:** Reason = P11.9 phase test/diagnostic/fixture; supporting runtime still uncommitted and UAT not signed off.

---

## Cross-Phase Risk Notes

| Risk | Detail |
|------|--------|
| Mixed working tree | PR-02A reception/customer changes coexist with P11.9 OTP/wizard/CSRF/contract work — **do not partial-commit runtime** without phase boundary review |
| OTP surface area | 10+ modified `public_html` OTP files — high sensitivity; owner review required before any runtime commit |
| Calendar scope exception | Docs approved in `ffbc00b`; **runtime helper still uncommitted** — must ship with PR-02A batch only |
| No live secrets in tree | `private/m360-otp-config.php` not present in working tree (correct); example template held for diff review |
| Tests precede commit | Zero `COMMIT_NOW_TEST` — all automated tests bound to held runtime per special rules |

---

## Commit Eligibility

**`NOT_ELIGIBLE_DISPOSITION_AUDIT_ONLY`**

This audit classifies the working tree only. No file disposition changes were applied. Further PR-02A repair work should reference this report before staging any paths.

---

**End of local file disposition audit.**

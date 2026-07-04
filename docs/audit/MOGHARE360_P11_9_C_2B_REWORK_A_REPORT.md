# MOGHARE360 P11.9-C-2B-REWORK-A — Report

**Phase:** P11.9-C-2B-REWORK-A  
**Mode:** Runtime fix + action placement + runtime smoke test (no C-2C, no write actions, no SQL/DB/Auth changes)  
**Date:** 2026-07-04  
**Product:** MOGHARE360 V1 RC  
**Subject:** Fix intake fatal, intake-first detail flow, service classification display, runtime verification

---

## 1. Executive Summary

P11.9-C-2B-REWORK-A addresses the UAT-blocking PHP fatal in `m360_rw_build_intake_file()` and corrects action placement on the online request detail page so reception staff are routed to intake completion before sensitive decisions.

**Runtime fatal:** Fixed — all `m360_rw_build_gate()` call sites now pass 9 arguments; the ninth is `null` when no JobCard exists.

**Browser Validation: PENDING OPERATOR** — automated runtime smoke executed against live DB for request IDs 18 and 20; no operator browser session was performed in this phase.

---

## 2. Problem Statement (from UAT Rejection)

| Issue | Symptom |
|-------|---------|
| Argument count mismatch | `m360_rw_build_gate expects 9 arguments but 8 passed` |
| Broken path | `m360_rw_build_intake_file()` line ~558 — empty gate bootstrap |
| User impact | `erp-reception-intake-file.php?online_request_id=18` and `20` fatals before HTML |
| Test gap | Prior tests called `m360_rw_build_gate(..., null)` directly with 9 args; never exercised `m360_rw_build_intake_file()` |

---

## 3. Changes Applied

### 3.1 Runtime fatal fix

**File:** `public_html/includes/m360-reception-workbench-helper.php`

```php
// Before (8 args — fatal):
$emptyGate = m360_rw_build_gate([], [], [], [], [], [], [], []);

// After (9 args — correct):
$emptyGate = m360_rw_build_gate([], [], null, null, null, [], [], [], null);
```

Successful path at line ~614 unchanged — already passed `$jobcard` as ninth argument.

**Call site inventory (all 9-arg):**

| Location | 9th arg |
|----------|---------|
| `m360_rw_build_intake_file()` empty bootstrap (~558) | `null` |
| `m360_rw_build_intake_file()` success path (~614) | `$jobcard` |
| `tools/test-p11-9-c-2b-rework-a-runtime-intake-smoke.php` | `null` / `$jobcard` as applicable |
| `tools/test-p11-9-c-2b-intake-completion-shell.php` | `null` |
| `tools/test-p11-9-c-2b-fix-a-process-correction.php` | `null` |

### 3.2 Online request detail — action placement

**File:** `public_html/erp-reception-online-request-detail.php`

Removed direct POST forms for:

- پذیرش درخواست (accept)
- رد درخواست (reject)
- تبدیل به کارت کار (convert)
- علامت‌گذاری در حال بررسی (under_review)

Replaced with intake-first CTA:

- Primary action: **تکمیل پرونده پذیرش** → `erp-reception-intake-file.php?online_request_id={id}`
- Guidance: **ابتدا پرونده پذیرش را تکمیل و وضعیت Gate را بررسی کنید.**

Sensitive actions remain on the intake shell where gate state is visible (read-only gate + existing accept handler forms from FIX-A).

### 3.3 Process rules (unchanged from FIX-A, verified)

| Rule | Implementation |
|------|----------------|
| Reject in temporary reception | Allowed on intake shell (`erp-reception-intake-file.php` temp section) |
| Convert to JobCard | Blocked until service path / fault clear and gate `ready_convert` |
| Service classification | Displayed on intake file; labeled **ثبت توسط پذیرشگر**; taxonomy from helper |
| No auto JobCard | No automatic creation; convert only via existing accept handler when gate allows |

**Service taxonomy (reception-filled):**

- کارشناسی و عیب‌یابی → موتور و گیربکس، برق و باتری، زیروبند و تعلیق، مبلمان داخلی، خدمات بدنه، آپشن
- سرویس‌های دوره‌ای
- کارشناسی خرید و فروش

### 3.4 New runtime smoke test

**File:** `tools/test-p11-9-c-2b-rework-a-runtime-intake-smoke.php`

Covers:

- `m360_rw_build_intake_file(false, 0)` — no throw
- Live DB: `m360_rw_build_intake_file($conn, 18)` and `(20)` — no fatal
- 9-arg empty gate call
- Temp gate: convert blocked without service class; temp actions allowed
- Detail page: primary CTA + guidance; no convert/accept/reject submit buttons
- Intake: reject in temp section; service class by receptionist
- Regex guard: no 8-arg `m360_rw_build_gate` call in helper
- PHP lint on intake-file.php

---

## 4. Scope Compliance

| Constraint | Status |
|------------|--------|
| No C-2C started | ✓ |
| No new write actions | ✓ |
| No SQL / DB changes | ✓ |
| No Auth/Login changes | ✓ |
| `staff-auth.php` / `access-control.php` untouched | ✓ |
| No role/permission/workflow/OTP changes | ✓ |
| No auto JobCard creation | ✓ |
| `private/config/secrets` untouched | ✓ |
| No P12 scope | ✓ |

---

## 5. Test Results

| Test | Result |
|------|--------|
| PHP lint — `m360-reception-workbench-helper.php` | PASS |
| PHP lint — `erp-reception-intake-file.php` | PASS |
| PHP lint — `erp-reception-online-request-detail.php` | PASS |
| `test-p11-9-c-2b-rework-a-runtime-intake-smoke.php` | **19/19 PASS** |
| `test-p11-9-c-2b-fix-a-process-correction.php` | **26/26 PASS** |
| `test-p11-9-c-2b-scope-security.php` | **8/8 PASS** |
| `test-p11-9-c-2b-intake-completion-shell.php` | **27/27 PASS** |
| `test-v1-production-signoff.php` | **23/23 PASS** |

### Runtime smoke — live DB (IDs 18, 20)

| Request ID | `m360_rw_build_intake_file()` | Gate status (observed) |
|------------|-------------------------------|------------------------|
| 18 | No fatal | `otp_required` |
| 20 | No fatal | `otp_required` |

---

## 6. Files Changed

| File | Change |
|------|--------|
| `public_html/includes/m360-reception-workbench-helper.php` | Fix 9-arg empty gate call |
| `public_html/erp-reception-online-request-detail.php` | Remove sensitive actions; intake-first CTA |
| `tools/test-p11-9-c-2b-rework-a-runtime-intake-smoke.php` | **New** runtime smoke test |
| `docs/audit/MOGHARE360_P11_9_C_2B_REWORK_A_REPORT.md` | **New** this report |

**Not modified:** `staff-auth.php`, `access-control.php`, SQL migrations, secrets, OTP, workflow handlers (except read-only detail UI).

---

## 7. Browser Validation

**Browser Validation: PENDING OPERATOR**

Operator should verify in browser (staff session):

1. `erp-reception-intake-file.php?online_request_id=18` — loads without fatal
2. `erp-reception-intake-file.php?online_request_id=20` — loads without fatal
3. `erp-reception-online-request-detail.php?request_id=18` — shows only «تکمیل پرونده پذیرش» CTA, not accept/reject/convert/under_review buttons
4. Intake shell shows service classification taxonomy and temp reject when gate allows

---

## 8. Notes / Next Steps

- C-2C (service classification write / persistence) intentionally **not** started.
- Gate status `otp_required` for IDs 18/20 is expected business state, not a runtime error.
- Rework closes the UAT fatal and action-placement gaps; operator browser sign-off remains before production acceptance.

---

**Phase status:** P11.9-C-2B-REWORK-A complete (automated verification). Awaiting operator browser validation.

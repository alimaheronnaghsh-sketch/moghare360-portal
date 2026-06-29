# MOGHARE360 P11.9-A — Operator Runbook

**Role:** OPERATOR / Run Controller  
**Purpose:** Control the dry run without executing workflow on behalf of staff

---

## 1. Operator responsibilities

- Enforce Go/No-Go before start
- Brief all roles on navigation rules
- Maintain 115-step execution log
- Maintain incident register
- Enforce OTP deferral protocol
- Call **STOP** when BLOCKED conditions occur
- Coordinate owner sign-off at phase boundaries

---

## 2. Starting point — Staff Home

Every staff role begins at:

`http://<host>/moghare360/erp-staff-home.php`

After login via `staff-login.php`. Verify:

- Persian role label in identity section
- «کار امروز» cards visible
- Runtime-hold cards show **نیازمند بررسی عملیاتی** (disabled)
- No raw `erp-*.php` filenames in card body (P11.7.1)

---

## 3. Route Map — operational view only

Reference URL:

`erp-route-map.php?view=operational`

Rules:

- Default **نمای عملیاتی** — not technical view for line staff
- Only **قابل ورود** and safe **تشخیصی / مدیریتی** links are active
- Guided/action/API/customer routes are **not** normal navigation
- **فایل موجود** ≠ operational ready

Use Route Map to answer “which boards exist?” — not as primary daily nav (Staff Home is primary).

---

## 4. Manager bridge usage

**OWNER / SYSTEM_ADMIN:** Staff Home → «مرجع عملیاتی One-Day Run»  
**SERVICE_MANAGER:** Staff Home → «مرجع هماهنگی سالن»

Rules:

- Bridge is for **oversight and cross-unit jumps**
- Do not use disabled runtime-hold cards (part-use, payment-tracking)
- Timeline is **guided** — open only with known `jobcard_id`
- No impersonation, no act-as-staff

---

## 5. Logging every step

Use `P11_9_A_115_STEP_EXECUTION_LOG_TEMPLATE.md`.

For each step from P11.9-1 (001–115):

1. Record Step #, role, route, action
2. Record Expected Before / After
3. Record Actual Result
4. Mark PASS / WARNING / BLOCKED / SKIP
5. Add operator note + owner decision if needed

Canonical JobCard ID and number must appear in log header once known.

---

## 6. Handling READY / PARTIAL / WARNING / BLOCKED

| Classification | Operator action |
|----------------|-----------------|
| **READY** | Proceed; log PASS |
| **PARTIAL** | Proceed with brief verbal guide; log WARNING |
| **WARNING** | Proceed if owner accepts; log WARNING + note |
| **BLOCKED** | **STOP** phase; log BLOCKED; incident register |
| **SKIP** | Document skip reason (part-use, payment-tracking, deferred OTP) |
| **BACKLOG** | Do not attempt in dry run |

---

## 7. When to stop the dry run

Stop if:

- Any required role cannot authenticate
- Staff Home or Route Map ops view broken
- Canonical M360-DEMO JobCard lost/unidentifiable
- Staff must open action URL directly to continue
- OTP required but no deferral signed and SMS absent
- Operator cannot determine current step/state
- BLOCKED on core P2–P7 board/detail flow

Pause (not full stop) for WARNING items with owner approval.

---

## 8. Prohibited during run

- Opening `*-action.php` or accept/generate/send URLs directly
- Using `erp-jobcard-part-use.php` or `erp-payment-tracking.php`
- Manual SQL UPDATE/INSERT mid-run to “fix” workflow
- Sharing passwords in chat/log
- Enabling fake production OTP
- Owner impersonating line staff
- Using JobCard ID 1 without verifying it is the agreed M360-DEMO record

---

## 9. Phase handoff script (operator)

At each major phase, announce:

1. Current JobCard number + ID
2. Current status from responsibility strip (if available)
3. Next role and first page
4. Any SKIP/DEFER rules for that phase

Example: *«FINANCE: open estimate board from Staff Home. Payment-tracking is SKIP. Customer estimate OTP is DEFERRED per signed protocol.»*

---

## 10. End of run

1. Complete log through step 115
2. Roll up incidents
3. Owner review on P8 dashboards
4. Record Go/No-Go outcome for **execution phase** (future)
5. Do not auto-run E2E demo scenario — optional manual note only

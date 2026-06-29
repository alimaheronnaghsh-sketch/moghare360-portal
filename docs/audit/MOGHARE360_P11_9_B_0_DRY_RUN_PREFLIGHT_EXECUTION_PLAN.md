# MOGHARE360 P11.9-B-0 — Dry Run Preflight Execution Plan

**Phase:** P11.9-B-0  
**Mode:** REPORT ONLY — no execution in this phase  
**Product:** MOGHARE360 V1 RC  
**Date:** 2026-06-26  
**Precedes:** Manual operator preflight (P11.9-B) and controlled One-Day Run dry run execution  
**Inputs:** P11.9-0, P11.9-1, P11.9-A dry run pack and SQL templates

---

## 1. Executive Summary

P11.9-A delivered the **Dry Run Pack** — operator runbook, provisioning checklists, M360-DEMO JobCard plan, OTP deferral protocol, 115-step log template, Go/No-Go checklist, incident register, manager observation guide, read-only preflight SQL, and guarded M360-DEMO seed template. **Nothing in that pack was executed.**

P11.9-B-0 defines the **controlled execution sequence** for the next manual phase: operator-led **preflight** before the 115-step dry run. This document specifies:

- Ten ordered preflight phases with stop conditions
- Operator responsibilities, tools, inputs, outputs, and evidence capture
- Seven structured output tables for logging results
- A mandatory **user report format** to return after manual preflight
- Go/No-Go criteria aligned with P11.9-1 **CONDITIONAL GO** posture

**This phase does not run SQL, create users, create demo JobCards, execute workflow actions, or change any application, database, Auth, permission, OTP, or P12 scope.**

---

## 2. Scope and Non-Execution Boundary

### In scope (this document only)

- Execution plan definition
- Evidence capture templates
- Stop conditions and Go/No-Go rules
- User report format for post-preflight feedback

### Out of scope — do NOT do in P11.9-B-0

| Forbidden action | Reason |
|------------------|--------|
| Run SQL (preflight or seed) | Operator manual phase only |
| Create staff users or passwords | Access Management in P11.9-B |
| Create M360-DEMO JobCard | UI or guarded seed in P11.9-B |
| Execute workflow actions | Dry run phase after Go |
| Change OTP config or expose secrets | Security boundary |
| Modify application code | Report-only phase |
| Modify database schema or migrations | Out of scope |
| Modify Auth/Login, permissions, roles | Out of scope |
| Modify route maps or action handlers | Out of scope |
| Start P12 scope | Not in V1 RC dry run path |
| Commit secrets | Security boundary |

### Source artifacts (read-only reference)

| Category | Path |
|----------|------|
| Discovery | `docs/audit/MOGHARE360_P11_9_0_ONE_DAY_RUN_DRY_RUN_READINESS_DISCOVERY_REPORT.md` |
| Step map | `docs/audit/MOGHARE360_P11_9_1_ONE_DAY_RUN_MAXIMUM_STEP_MAP_REPORT.md` |
| Pack report | `docs/audit/MOGHARE360_P11_9_A_DRY_RUN_PACK_REPORT.md` |
| Master pack | `docs/dry-run/P11_9_A_ONE_DAY_RUN_DRY_RUN_PACK.md` |
| Runbook | `docs/dry-run/P11_9_A_OPERATOR_RUNBOOK.md` |
| Role checklist | `docs/dry-run/P11_9_A_ROLE_PROVISIONING_CHECKLIST.md` |
| JobCard plan | `docs/dry-run/P11_9_A_M360_DEMO_JOBCARD_PREPARATION_PLAN.md` |
| OTP protocol | `docs/dry-run/P11_9_A_OTP_DEFERRAL_PROTOCOL.md` |
| Step log | `docs/dry-run/P11_9_A_115_STEP_EXECUTION_LOG_TEMPLATE.md` |
| Go/No-Go | `docs/dry-run/P11_9_A_GO_NO_GO_CHECKLIST.md` |
| Manager guide | `docs/dry-run/P11_9_A_MANAGER_OBSERVATION_GUIDE.md` |
| Incident register | `docs/dry-run/P11_9_A_DRY_RUN_INCIDENT_REGISTER_TEMPLATE.md` |
| Preflight SQL | `database/dry-run/P11_9_A_READONLY_PREFLIGHT_CHECK.sql` |
| Seed template | `database/dry-run/P11_9_A_M360_DEMO_JOBCARD_SEED_TEMPLATE.sql` |

---

## 3. Preflight Execution Sequence

Execute phases **in order**. Do not skip ahead to dry run steps 001–115 until Phase 10 is **GO** or **CONDITIONAL GO** with documented conditions.

### Preflight Execution Sequence Table

| Order | Phase | Operator action | Tool / page | Required input | Expected output | Stop condition | Evidence to capture |
|-------|-------|-----------------|-------------|----------------|-----------------|----------------|---------------------|
| 1 | Repository / Git safety | Run git checks; confirm clean working tree and known branch | Terminal: `git status --short`, `git branch -vv`, `git log --oneline -n 5` | Local repo at P11.9-A complete state | Clean tree **or** documented doc-only changes; branch tracks remote | **STOP** if uncommitted runtime/config/secret files (`.env`, OTP config, `public_html/` hotfixes) | Screenshot or paste of git commands output |
| 2 | Local service readiness | Confirm Apache/XAMPP and portal URLs respond | Browser + XAMPP control panel | XAMPP running; base URL `http://localhost:8080/moghare360/` | Portal loads; login pages load; unauthenticated Staff Home redirects to login | **STOP** if local app unavailable | Screenshot of portal home, staff-login, owner-login; note redirect behavior |
| 3 | Read-only SQL preflight | Open SSMS; run preflight script unchanged | SSMS + `P11_9_A_READONLY_PREFLIGHT_CHECK.sql` | Database `MOGHARE360_ERP`; read access | Printed result sets: owner, role counts, demo usernames, M360-DEMO rows, P1–P7 tables, warnings | **STOP** if script fails to run; **STOP** if owner missing; proceed to Phase 4 if roles missing | Full SSMS Messages + result grid export or screenshots |
| 4 | Staff user provisioning | Create missing demo users via Access Management only | `erp-access-management.php` → `erp-access-user-create.php` | Owner login; preflight role gaps | Six demo staff users exist, login-enabled, ACTIVE | **STOP** before dry run if any role cannot be created or cannot login | Per-user: username, role, login test PASS/FAIL; **no password screenshots** |
| 5 | M360-DEMO JobCard readiness | Create or confirm fresh traceable JobCard | Reception UI (preferred) or guarded seed SQL after review | RECEPTION login; JobCard plan values | `M360-DEMO-*` JobCard at `RECEIVED`; ID recorded | **STOP** if no safe demo JobCard can be created | `jobcard_id`, `jobcard_number`, board visibility screenshot |
| 6 | OTP deferral confirmation | Owner + operator sign deferral for customer OTP legs | `P11_9_A_OTP_DEFERRAL_PROTOCOL.md` | P11.9-0 SMS-not-configured state | Signed deferral; deferred steps identified | **STOP** for customer OTP steps if deferral unsigned | Signed deferral table (§7); no OTP secrets |
| 7 | Runtime-hold route confirmation | Brief PARTS/FINANCE/TECH; acknowledge SKIP | Staff Home disabled cards; runbook §runtime-hold | Pack skip rules | Staff acknowledge part-use + payment-tracking SKIP | **STOP** and route to Fix Pack if dry run requires those pages | Skip table (§8); staff initials |
| 8 | Navigation readiness | Smoke-load boards without workflow POST | Staff Home, Route Map ops view, manager bridge, boards | Each role login (spot-check) | Pages load; no BLOCKED on core navigation | **STOP** if Staff Home or Route Map operational view fails | Per-page load screenshot or PASS/FAIL list |
| 9 | Operator pack readiness | Open/print required documents | `docs/dry-run/*` pack files | P11.9-A pack complete | Runbook, log, Go/No-Go, incident register, OTP protocol, manager guide available | **STOP** if materials missing | Checklist tick + file paths noted |
| 10 | Final Go / No-Go | Complete Go/No-Go checklist; owner sign-off | `P11_9_A_GO_NO_GO_CHECKLIST.md` | Phases 1–9 evidence | **GO**, **NO-GO**, or **CONDITIONAL GO** recorded | **NO-GO** blocks dry run; **CONDITIONAL GO** requires written conditions | Completed Go/No-Go table (§11); user report (§12) |

### Phase detail — commands and decisions

#### Phase 1 — Repository / Git safety check

**Purpose:** Confirm current code is committed/pushed and working tree is clean before manual preflight.

**Commands (run from repo root):**

```bash
git status --short
git branch -vv
git log --oneline -n 5
```

**Decision:** If uncommitted **runtime**, **config**, or **secret** files exist → **STOP**. Document-only or audit-report changes may be acceptable if operator confirms they do not affect runtime behavior.

#### Phase 2 — Local service readiness

**Purpose:** Confirm XAMPP/Apache and local URL are available.

**Checks:**

- Apache running in XAMPP
- `http://localhost:8080/moghare360/` loads
- `staff-login.php` loads
- `owner-login.php` loads
- `erp-staff-home.php` redirects to login when unauthenticated

**Decision:** If local app does not load → **STOP**.

#### Phase 3 — Read-only SQL preflight

**Purpose:** Run `database/dry-run/P11_9_A_READONLY_PREFLIGHT_CHECK.sql` in SSMS only.

**Rules:**

- Use database `MOGHARE360_ERP`
- No edits to SQL before execution unless operator explicitly records reason in log
- Copy/screenshot/log all output
- Script contains **no** INSERT/UPDATE/DELETE/MERGE/DDL

**Expected output categories:** owner exists; role counts; demo username existence; M360-DEMO JobCard existence; JobCard ID 1 present but not recommended; P1–P7 table presence; warnings.

**Decision:**

- SQL fails to run → **STOP**
- Owner missing → **STOP**
- Required roles missing → proceed to Phase 4 (provisioning)
- M360-DEMO exists → operator decides reuse vs fresh traceable record (document decision)

#### Phase 4 — Staff user provisioning readiness

**Purpose:** Manual creation through Access Management only.

**Required demo users:** `demo.reception`, `demo.service.manager`, `demo.technician`, `demo.parts`, `demo.finance`, `demo.qc`  
**Owner:** existing owner/admin user

**Rules:** No passwords in repo; no password screenshots; passwords stored outside repo; no raw SQL user creation unless future explicit approval; each user logs in once and confirms Staff Home.

**Decision:** If any role cannot be created or cannot login → **STOP** before dry run.

#### Phase 5 — M360-DEMO JobCard readiness

**Purpose:** Prepare creation of fresh traceable JobCard.

**Preferred:** Reception UI. **Fallback:** guarded seed template after review and `@CONFIRM_CREATE_M360_DEMO = N'CREATE_M360_DEMO'`.

**Rules:** Do not use JobCard ID 1; use prefix `M360-DEMO-`; suggested `M360-DEMO-001`; do not touch real records; record created ID; confirm visible on reception/technical boards.

**Decision:** If no fresh demo JobCard can be created safely → **STOP**.

#### Phase 6 — OTP deferral confirmation

**Purpose:** Confirm customer OTP legs deferred for staff-centric dry run.

**Rules:** Production fake OTP forbidden; do not edit OTP config; do not expose secrets; every deferred OTP step logged in execution log; if SMS configured later, repeat customer legs separately.

**Decision:** If owner/operator does not sign OTP deferral → **STOP** for customer-facing OTP steps (staff path may still be **CONDITIONAL**).

#### Phase 7 — Runtime-hold route confirmation

**Routes skipped:** `erp-jobcard-part-use.php`, `erp-payment-tracking.php`

**Alternatives:** `erp-part-reserve.php`; estimate + final invoice + settlement boards.

**Decision:** If dry run requires runtime-hold pages → **STOP** and switch to Fix Pack.

#### Phase 8 — Navigation readiness

**Check (load only — no workflow POST):** Staff Home; Route Map operational view (`erp-route-map.php?view=operational`); manager reference bridge; reception jobcards board; technical board; QC board; estimate board; final invoice board.

**Decision:** If Staff Home or Route Map operational view fails → **STOP**.

#### Phase 9 — Operator pack readiness

**Required documents open/available:** Operator Runbook; 115-Step Execution Log Template; Go/No-Go Checklist; Incident Register; OTP Deferral Protocol; Manager Observation Guide.

**Decision:** If runbook/log/checklist unavailable → **STOP**.

#### Phase 10 — Final Go / No-Go

See §11.

---

## 4. SQL Preflight Plan

### Execution rules

| Rule | Detail |
|------|--------|
| Tool | SQL Server Management Studio (SSMS) |
| Database | `USE MOGHARE360_ERP;` (script sets this) |
| Script | `database/dry-run/P11_9_A_READONLY_PREFLIGHT_CHECK.sql` |
| Mode | Read-only — SELECT and PRINT only |
| Edits | None unless operator documents reason before run |
| Seed SQL | **Not** part of Phase 3 — seed is Phase 5 fallback only |

### SQL Preflight Output Capture Template

| Check item | Expected result | Actual result | PASS / WARNING / BLOCKED | Screenshot / log reference | Operator note |
|------------|-----------------|---------------|--------------------------|----------------------------|---------------|
| Script executed without error | Messages tab shows preflight header | | | | |
| `core_users` owner row | ≥1 `is_system_owner = 1`, login-enabled | | | | Missing → **BLOCKED** |
| Role counts — RECEPTION | ≥1 active assignment | | | | Missing → **WARNING** → Phase 4 |
| Role counts — SERVICE_MANAGER | ≥1 active assignment | | | | |
| Role counts — TECHNICIAN | ≥1 active assignment | | | | |
| Role counts — PARTS | ≥1 active assignment | | | | |
| Role counts — FINANCE | ≥1 active assignment | | | | |
| Role counts — QC | ≥1 active assignment | | | | |
| Minimum 6 roles check | PASS message or WARNING | | | | |
| `demo.reception` | EXISTS or MISSING | | | | |
| `demo.service.manager` | EXISTS or MISSING | | | | |
| `demo.technician` | EXISTS or MISSING | | | | |
| `demo.parts` | EXISTS or MISSING | | | | |
| `demo.finance` | EXISTS or MISSING | | | | |
| `demo.qc` | EXISTS or MISSING | | | | |
| M360-DEMO JobCard count | 0 or ≥1 with PASS/WARNING message | | | | Reuse vs create decision |
| JobCard ID 1 row | Present with NOT RECOMMENDED note | | | | Informational only |
| P1–P7 tables | All EXISTS | | | | MISSING → **BLOCKED** |
| M360-DEMO customer/vehicle markers | Count reported | | | | |

---

## 5. Demo Staff Provisioning Plan

**Method:** `erp-access-management.php` → `erp-access-user-create.php` only.

### Demo Staff Provisioning Plan Table

| Role | Username | Creation method | Required login test | Staff Home expected | Status to record | Stop condition |
|------|----------|-----------------|---------------------|---------------------|------------------|----------------|
| OWNER | *(existing admin)* | Pre-existing | Login via `owner-login.php` | Product Home or Staff Home if mapped | EXISTS / PASS | **STOP** if owner cannot login |
| RECEPTION | `demo.reception` | Access Management UI | `staff-login.php` → Staff Home | «کار امروز» reception cards; online requests + jobcards cards | CREATED / LOGIN PASS / FAIL | **STOP** if cannot create or login |
| SERVICE_MANAGER | `demo.service.manager` | Access Management UI | Staff login → Staff Home | Coordination bridge; technical board access | CREATED / LOGIN PASS / FAIL | **STOP** if cannot create or login |
| TECHNICIAN | `demo.technician` | Access Management UI | Staff login → Staff Home | Technical cards; part-use card shows runtime_hold disabled | CREATED / LOGIN PASS / FAIL | **STOP** if cannot create or login |
| PARTS | `demo.parts` | Access Management UI | Staff login → Staff Home | Parts cards; can open `erp-part-reserve.php` | CREATED / LOGIN PASS / FAIL | **STOP** if cannot create or login |
| FINANCE | `demo.finance` | Access Management UI | Staff login → Staff Home | Finance cards; payment-tracking disabled | CREATED / LOGIN PASS / FAIL | **STOP** if cannot create or login |
| QC | `demo.qc` | Access Management UI | Staff login → Staff Home | QC board card visible | CREATED / LOGIN PASS / FAIL | **STOP** if cannot create or login |

**Password rules:** Set manually; store outside repo; never paste into ChatGPT report or commit to Git.

---

## 6. M360-DEMO JobCard Creation Plan

### M360-DEMO JobCard Creation Plan Table

| Data item | Required value | Source / method | Validation page | Evidence | Risk |
|-----------|----------------|-----------------|-------------------|----------|------|
| JobCard number | `M360-DEMO-001` (prefix `M360-DEMO-`) | Reception UI **or** guarded seed SQL | `erp-reception-jobcards.php` | Board row screenshot | Duplicate number if reuse without check |
| Customer name | M360 Demo Customer | UI or seed template | JobCard detail strip | Detail screenshot | Touching real customer if wrong code |
| Customer code | `M360-DEMO-CUST-001` | UI or seed | Preflight SQL marker query | SQL output or UI field | Collision with existing code |
| Vehicle | Toyota Camry | UI or seed | JobCard detail | Detail screenshot | — |
| Vehicle code | `M360-DEMO-VEH-001` | UI or seed | Preflight SQL | — | Collision |
| Plate | `M360-DEMO-001` | UI or seed | Detail / vehicle section | — | — |
| Complaint | Dry Run controlled service flow test | UI or seed | Detail | — | — |
| Starting status | `RECEIVED` (P2 entry) | UI or seed | Detail responsibility strip | Strip screenshot | Wrong status blocks P2 flow |
| `jobcard_id` | Record actual ID — **not assumed 1** | System-assigned | All boards use same ID in log header | Log header entry | Using ID 1 invalidates traceability |
| Reception user | `demo.reception` `user_id` | UI or `@OPERATOR_USER_ID` in seed | Detail `reception_user_id` | — | Seed without valid user fails |
| Visibility | Row on reception + technical boards | After create | `erp-reception-jobcards.php`, `erp-technical-board.php` | Board screenshots | Hidden filter/state wrong |

**Reuse decision:** If preflight shows existing `M360-DEMO-*` JobCard, operator may reuse **only if** status is `RECEIVED` (or reset plan documented), number is traceable, and log header records `jobcard_id`. Prefer fresh `M360-DEMO-002` if prior run state is unknown.

---

## 7. OTP Deferral Signoff Plan

**Default (P11.9-1):** Defer customer OTP legs for staff-centric dry run. SMS not configured per P11.9-0.

### OTP Deferral Signoff Table

| Step / phase | OTP needed? | Deferred? | Reason | Operator initials | Owner approval | Repeat later? |
|--------------|-------------|-----------|--------|-------------------|----------------|---------------|
| P1.5 — intake contract sign | Yes if live SMS | YES (default) | SMS not configured; staff-centric run | | | YES — separate customer session if SMS added |
| P4 — estimate approval sign | Yes if live SMS | YES (default) | Same | | | YES |
| P7 — delivery review sign | Yes if live SMS | YES (default) | Same | | | YES |
| API — `api/customer/*-otp.php` | Yes if live | YES (default) | Not exercised in staff run | | | YES |
| Staff P2–P7 boards/actions | No | N/A | Staff path independent of SMS | | N/A | N/A |

**Sign-off block (complete before dry run):**

| Field | Value |
|-------|-------|
| Deferral decision | DEFER ALL CUSTOMER OTP LEGS / LIVE SMS (choose one) |
| Production fake OTP | **FORBIDDEN** — acknowledged |
| OTP config changed | NO |
| Date | |
| Operator | |
| Owner | |

**Decision:** Unsigned deferral → **STOP** for customer OTP steps.

---

## 8. Runtime-Hold Skip Plan

### Runtime-Hold Skip Table

| Route | Why skipped | Alternative path | Affected steps (P11.9-1) | Evidence | Future phase |
|-------|-------------|------------------|--------------------------|----------|--------------|
| `erp-jobcard-part-use.php` | Runtime hold — page not operational | `erp-part-reserve.php`; manual observation if needed | 064 (TECH), 071 (PARTS) | Staff Home card shows **نیازمند بررسی عملیاتی**; SKIP logged | Fix Pack |
| `erp-payment-tracking.php` | Runtime hold — page not operational | Estimate board + final invoice + settlement boards | 077 (FINANCE) | Staff Home disabled card; SKIP logged | Fix Pack |

**Operator briefing:** PARTS and TECH acknowledge part-use SKIP; FINANCE acknowledges payment-tracking SKIP. Log **SKIP** in 115-step execution log for affected steps.

**Decision:** If dry run cannot proceed without these pages → **STOP** → Fix Pack before dry run.

---

## 9. Navigation Readiness Plan

Load pages **without submitting workflow actions**.

| Page | URL pattern | Role to test | PASS criteria |
|------|-------------|--------------|---------------|
| Staff Home | `erp-staff-home.php` | Each of 6 staff roles | Identity label + «کار امروز» cards |
| Route Map operational | `erp-route-map.php?view=operational` | OWNER or OPERATOR | Page loads; protected badges visible |
| Manager reference bridge | Staff Home bridge section | OWNER | Bridge links load (non-hold) |
| Coordination bridge | Staff Home | SERVICE_MANAGER | Bridge visible |
| Reception JobCards | `erp-reception-jobcards.php` | RECEPTION | Board loads; M360-DEMO row visible if created |
| Technical board | `erp-technical-board.php` | TECHNICIAN or SM | Board loads |
| QC board | `erp-qc-board.php` | QC | Board loads |
| Estimate board | `erp-estimate-board.php` | FINANCE | Board loads |
| Final invoice board | `erp-final-invoice-board.php` | FINANCE | Board loads |

**Decision:** Staff Home or Route Map operational view failure → **STOP**.

---

## 10. Operator Evidence Capture Plan

For each preflight phase, operator captures:

| Evidence type | Storage | Rules |
|---------------|---------|-------|
| Git command output | User report §1 | Paste text; no secrets |
| Browser screenshots | Local folder outside repo | No passwords in frame |
| SSMS output | Export or screenshot | Read-only preflight only in Phase 3 |
| Login test results | User report §4 | PASS/FAIL only |
| JobCard identity | Log header + user report §5 | `jobcard_id` + `M360-DEMO-*` number |
| Signed deferral | OTP table §7 | Initials only |
| Go/No-Go | Checklist §11 + user report §9 | Owner + operator names |

**After preflight:** Send user report (§12) to ChatGPT/project channel before starting 115-step dry run.

---

## 11. Go / No-Go Checklist

### Go / No-Go Table

| Item | Required condition | Current status | Decision | Owner / operator note |
|------|-------------------|----------------|----------|----------------------|
| Git safety | Clean tree or doc-only changes; no uncommitted runtime/secrets | | GO / NO-GO | |
| Local app | Portal + logins load | | GO / NO-GO | |
| SQL preflight | Executed; owner exists; P1–P7 tables exist | | GO / NO-GO / WARNING | |
| Owner login | PASS | | GO / NO-GO | |
| Six demo staff logins | All PASS | | GO / NO-GO | |
| M360-DEMO JobCard | Fresh traceable record exists; ID recorded | | GO / NO-GO | |
| OTP deferral | Signed (or live SMS verified) | | GO / NO-GO / CONDITIONAL | |
| Runtime-hold skips | Acknowledged by PARTS/FINANCE/TECH | | GO / NO-GO | |
| Staff Home | Loads for all roles | | GO / NO-GO | |
| Route Map ops view | Loads | | GO / NO-GO | |
| Operator pack | Runbook + log + checklists ready | | GO / NO-GO | |
| No direct action URLs | Team briefed | | GO / NO-GO | |
| No mid-run SQL fixes | Acknowledged | | GO / NO-GO | |

### GO if ALL true

- Repo clean (or acceptable doc-only delta documented)
- Local app available
- Read-only SQL preflight completed
- Owner login confirmed
- Six demo staff users provisioned and login confirmed
- Fresh M360-DEMO JobCard exists
- OTP deferral signed
- Runtime-hold skips acknowledged
- Staff Home + Route Map operational view load
- Operator log ready

### NO-GO if ANY true

- Owner cannot login
- Any required demo role cannot login
- No fresh demo JobCard
- Preflight SQL fails
- Local app unavailable
- OTP decision unresolved
- Action endpoints must be opened directly to continue
- Operator cannot identify step/state

### CONDITIONAL GO

Allowed when staff-centric path is ready but customer OTP legs remain deferred with signed protocol — matches P11.9-1 **CONDITIONAL GO**. Document conditions in user report.

---

## 12. User Report Format

After completing manual preflight (P11.9-B execution — **not** P11.9-B-0), send this exact template:

---

**P11.9-B Preflight Result**

**1. Git status:**

* clean / not clean
* notes:

**2. Local app:**

* localhost works: yes/no
* staff-login works: yes/no
* owner-login works: yes/no

**3. SQL preflight:**

* executed: yes/no
* owner exists: yes/no
* required roles present: yes/no
* demo usernames existing: yes/no
* M360-DEMO exists: yes/no
* warnings:

**4. Staff users:**

* demo.reception login: pass/fail
* demo.service.manager login: pass/fail
* demo.technician login: pass/fail
* demo.parts login: pass/fail
* demo.finance login: pass/fail
* demo.qc login: pass/fail

**5. Demo JobCard:**

* created: yes/no
* method: UI / guarded SQL / not created
* jobcard_id:
* demo code:
* visible in reception board: yes/no

**6. OTP:**

* deferral signed: yes/no
* notes:

**7. Runtime-hold:**

* part-use skipped: yes/no
* payment-tracking skipped: yes/no

**8. Navigation:**

* Staff Home works: yes/no
* Route Map operational view works: yes/no
* manager bridge works: yes/no

**9. Go/No-Go:**

* GO / NO-GO / CONDITIONAL
* blocking issues:
* warnings:
* operator notes:

---

## 13. Final Persian Answers

**1. آیا در P11.9-B باید چیزی ساخته شود؟**  
بله — در فاز **P11.9-B (اجرای دستی Preflight)** باید کاربران دمو، JobCard دمو `M360-DEMO-*` و شواهد Go/No-Go ساخته/ثبت شوند. **P11.9-B-0 فقط برنامه است و چیزی نمی‌سازد.**

**2. آیا SQL باید اجرا شود؟**  
در **P11.9-B-0 خیر**. در **P11.9-B بله** — فقط اسکریپت read-only preflight در SSMS. Seed SQL فقط در صورت نیاز و با تأیید صریح اپراتور.

**3. آیا SQL فقط read-only است یا seed هم داریم؟**  
Preflight **فقط read-only** است. **Seed template** (`P11_9_A_M360_DEMO_JOBCARD_SEED_TEMPLATE.sql`) اختیاری و guarded است — فقط fallback برای JobCard، نه بخش اجباری preflight.

**4. آیا ساخت کاربران باید با UI باشد یا SQL؟**  
**فقط UI** — از `erp-access-management.php`. SQL خام برای کاربر ممنوع مگر تأیید صریح بعدی.

**5. آیا ساخت JobCard باید با UI باشد یا SQL template؟**  
**ترجیح UI** (Reception). SQL template فقط fallback بعد از review و `@CONFIRM_CREATE_M360_DEMO = N'CREATE_M360_DEMO'`.

**6. قبل از Dry Run چه چیزهایی باید STOP کنند؟**  
عدم دسترسی local app؛ شکست SQL preflight؛ نبود owner؛ عدم login هر نقش الزامی؛ نبود JobCard دمو امن؛ عدم امضای deferral OTP؛ نیاز به runtime-hold pages؛ شکست Staff Home یا Route Map ops؛ نبود runbook/log؛ نیاز به باز کردن مستقیم action endpoint.

**7. چه خروجی‌هایی را کاربر باید بعد از اجرای Preflight به ChatGPT گزارش بدهد؟**  
قالب **§12 User Report Format** — وضعیت Git، local app، نتایج SQL، login هر demo user، JobCard (id/code/method)، OTP deferral، runtime-hold skip، navigation، و تصمیم **GO/NO-GO/CONDITIONAL** با blocking issues و warnings.

**8. اگر Preflight پاس شد، فاز بعدی چیست؟**  
**اجرای Dry Run کنترل‌شده** — 115 گام P11.9-1 با Operator Runbook، execution log، incident register؛ نه P12.

**9. اگر Preflight fail شد، فاز بعدی چیست؟**  
**NO-GO** — رفع blocker (دسترسی، کاربر، JobCard، navigation) یا **Fix Pack** برای runtime-hold؛ Dry Run شروع نشود تا Go/No-Go پاس شود.

**10. آیا P12 شروع می‌شود؟**  
**خیر.** Preflight و Dry Run در محدوده V1 RC P2–P7 و P8 oversight هستند؛ P12 خارج از scope است.

---

## 14. Recommended Next Phase

| Outcome | Next phase |
|---------|------------|
| P11.9-B-0 complete (this report) | Operator executes **P11.9-B manual preflight** using §3 sequence |
| Preflight **GO** or **CONDITIONAL GO** | **One-Day Run dry run execution** — 115 steps per P11.9-1 with P11.9-A pack |
| Preflight **NO-GO** | Remediation (users, JobCard, local env) or **Fix Pack** for runtime-hold blockers |
| Incidents during dry run | Log in incident register → Fix Pack backlog |

**Immediate operator action:** Open `P11_9_A_GO_NO_GO_CHECKLIST.md`, run Phase 1 git checks, then proceed through §3 in order. Return §12 user report when preflight completes.

---

P11.9-B-0 defines the controlled dry run preflight execution plan, evidence capture, stop conditions, user report format and Go/No-Go criteria before any SQL execution, staff provisioning, demo JobCard creation, workflow action, code change, permission change, Auth/Login change, OTP config change, or P12 scope.

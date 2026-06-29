# MOGHARE360 P11.9-A — One-Day Run Dry Run Pack (Master)

**Version:** P11.9-A  
**Product:** MOGHARE360 V1 RC  
**Status:** Preparation pack — **does not execute the dry run**

---

## Purpose

Prepare a **controlled One-Day Run dry run** per P11.9-0 and P11.9-1: operator runbook, staff provisioning checklist, M360-DEMO JobCard plan, OTP deferral protocol, 115-step log template, Go/No-Go rules, manager observation guide, incident register, and read-only SQL preflight.

---

## Scope

### This pack **does**

- Document operator-controlled preparation steps
- Provide checklists and log templates
- Provide read-only SQL preflight (`database/dry-run/P11_9_A_READONLY_PREFLIGHT_CHECK.sql`)
- Provide **optional guarded** M360-DEMO seed SQL template for operator review
- Encode P11.9-1 decisions (CONDITIONAL GO, skips, deferrals)

### This pack **does not**

- Execute the dry run
- Create staff users automatically
- Create demo JobCard automatically
- Run workflow actions
- Change Auth/Login, permissions, roles, workflow, APIs, OTP config
- Fix runtime-hold pages (part-use, payment-tracking)

---

## Prerequisites

1. MOGHARE360 V1 RC deployed (XAMPP or staging)
2. Database `MOGHARE360_ERP` accessible
3. P11.7–P11.8 navigation upgrades present
4. Owner can log in
5. Operator assigned (Run Controller)
6. External password storage (not in Git)

---

## Pack contents

| Document | Purpose |
|----------|---------|
| `P11_9_A_OPERATOR_RUNBOOK.md` | How to control the run |
| `P11_9_A_ROLE_PROVISIONING_CHECKLIST.md` | 6 staff + OWNER |
| `P11_9_A_M360_DEMO_JOBCARD_PREPARATION_PLAN.md` | Fresh demo JobCard |
| `P11_9_A_OTP_DEFERRAL_PROTOCOL.md` | Customer OTP deferral logging |
| `P11_9_A_115_STEP_EXECUTION_LOG_TEMPLATE.md` | Step-by-step log |
| `P11_9_A_GO_NO_GO_CHECKLIST.md` | Final gate |
| `P11_9_A_MANAGER_OBSERVATION_GUIDE.md` | Owner oversight |
| `P11_9_A_DRY_RUN_INCIDENT_REGISTER_TEMPLATE.md` | Issues during run |

---

## Roles (minimum)

| Role | Purpose |
|------|---------|
| OWNER / SYSTEM_ADMIN | Oversight, access, Go/No-Go sign-off |
| RECEPTION | P1/P1.5/P2 intake |
| SERVICE_MANAGER | Assign / coordinate |
| TECHNICIAN | Diagnosis + work execution |
| PARTS | Part reserve (part-use SKIP) |
| FINANCE | Estimate + invoice/settlement (payment-tracking SKIP) |
| QC | QC + delivery control |
| CUSTOMER | Optional — defer OTP legs if needed |
| OPERATOR | Run Controller — log, stop rules, briefing |

---

## Demo data requirements

- **Do not use JobCard ID 1** as the canonical dry-run record
- Create **fresh** traceable JobCard: prefix **`M360-DEMO-`**
- Sample customer: **M360 Demo Customer**
- Sample vehicle: **Toyota Camry**, plate **M360-DEMO-001**
- Starting status: **P2 reception entry** (`RECEIVED`) — not mid-workflow

See `P11_9_A_M360_DEMO_JOBCARD_PREPARATION_PLAN.md`.

---

## OTP decision (pack default)

- **Staff-centric dry run:** customer OTP legs may be **deferred** with operator log
- **Production fake OTP:** forbidden
- **Live SMS:** not required for staff path; configure later for full customer legs

See `P11_9_A_OTP_DEFERRAL_PROTOCOL.md`.

---

## Runtime-hold decisions

| Page | Decision |
|------|----------|
| `erp-jobcard-part-use.php` | **SKIP** — use `erp-part-reserve.php`; manual observation if needed |
| `erp-payment-tracking.php` | **SKIP** — verify via estimate + final invoice/settlement only |

---

## One-Day Run phases (12 owner steps → 115 max steps)

1. Customer request / arrival (P1)
2. Reception receives (P1)
3. JobCard create/progress (P2)
4. Contract/signature gate (P1.5)
5. Service manager assign (P3)
6. Technician diagnosis (P3)
7. Parts reserve (SKIP part-use UI)
8. Estimate / finance (SKIP payment-tracking UI)
9. Work execution (P5)
10. QC (P6)
11. Settlement (P7)
12. Delivery / close (P7 — customer legs may defer)

Full step list: `docs/audit/MOGHARE360_P11_9_1_ONE_DAY_RUN_MAXIMUM_STEP_MAP_REPORT.md`

---

## Operator rules

1. Start every staff role from **Staff Home** (`erp-staff-home.php`)
2. Use **Route Map operational view** as reference only (`erp-route-map.php?view=operational`)
3. Board → detail → form action — **never** open `*-action.php` URLs directly
4. **No** runtime-hold routes (part-use, payment-tracking)
5. **No** hidden manual DB fixes during the run
6. Log every step in the 115-step template
7. Log every deferred OTP leg per protocol
8. Stop on BLOCKED; escalate WARNING to owner

---

## Stop conditions (during dry run)

Stop immediately if:

- Required role cannot log in
- Staff Home broken for any role
- No identifiable `M360-DEMO` JobCard
- Operator must open action endpoint directly to proceed
- Core P2–P7 board/detail pages fail to load with errors
- Unauthorized manual SQL/data repair attempted mid-run

---

## Final Go/No-Go rules

See `P11_9_A_GO_NO_GO_CHECKLIST.md`.

**GO** only when preflight + provisioning + demo JobCard + briefing + deferral sign-off complete.

**NO-GO** if any hard gate fails.

---

## Related SQL

1. Run preflight (read-only): `database/dry-run/P11_9_A_READONLY_PREFLIGHT_CHECK.sql`
2. Optional demo seed (after review): `database/dry-run/P11_9_A_M360_DEMO_JOBCARD_SEED_TEMPLATE.sql`

---

## Recommended sequence

1. Read this master pack
2. Run read-only preflight SQL
3. Complete role provisioning checklist
4. Create M360-DEMO JobCard (UI preferred; SQL template optional)
5. Sign OTP deferral decision
6. Complete Go/No-Go checklist
7. Print/open operator runbook + 115-step log
8. Schedule dry run execution (separate phase — **not P11.9-A**)

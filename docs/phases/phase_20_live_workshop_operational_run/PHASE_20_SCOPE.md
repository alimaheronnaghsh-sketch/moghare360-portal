# PHASE 20 — Live Workshop Operational Run — Scope

**Phase:** PHASE 20 — LIVE WORKSHOP OPERATIONAL RUN  
**Status:** Documentation and operational planning only  
**SQL:** No SQL required

---

## Phase Objective

Design and prepare the **controlled live workshop operational run** for MOGHARE360 ERP inside the workshop environment.

---

## Locked Rules

| Rule | Status |
|------|--------|
| Database: **MOGHARE360_ERP** | LOCKED |
| Local laptop server = system of record | LOCKED |
| moghareh360.ir = Mirror Only | LOCKED |
| **UI → Validation Engine → Workflow Engine → Database → Audit Log** | LOCKED |
| **Live run must be controlled** | LOCKED |
| **Manual fallback protocol** for real operational use | LOCKED |
| **Any operational error must be logged** | LOCKED |
| No direct UI→DB / validation / workflow / audit bypass | LOCKED |
| Camera direct only · No upload bypass | LOCKED |
| Contract authorization controls operation | LOCKED |
| Out-of-contract / above-ceiling requires approval | LOCKED |
| No SaaS / portal / accounting / payment gateway | LOCKED |

---

## PHASE 20 Modules

1. Reception Live Use
2. JobCard Live Entry
3. Technician Tablet View
4. QC Live Check
5. Delivery Live Check
6. Daily Error Log
7. Manual Fallback Protocol
8. Day-end Operational Report

---

## Allowed Scope

- `docs/phases/phase_20_live_workshop_operational_run/` (5 files)
- `docs/operations/` (10 files)

---

## Forbidden Scope

- PHP runtime, SQL, schema, `public_html`, auth, config, release
- Modify existing forms; implement live run runtime, dashboards, tablet UI
- Deploy; SaaS, portal, accounting, payment gateway activation
- Commit, push

---

## Phase 20 Constraints

- **PHASE 20 is documentation/operational planning only**
- **No runtime implementation**
- **No PHP creation**
- **No SQL creation**
- **No database modification**
- **No existing form modification**
- **No public portal activation**
- **No production SaaS activation**
- **No official accounting activation**
- **No payment gateway activation**

---

**END OF SCOPE**

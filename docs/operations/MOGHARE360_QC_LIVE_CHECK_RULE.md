# MOGHARE360 — QC Live Check Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## QC Check Requirement

Every JobCard proceeding to delivery must pass **QC live check** — structured pass/fail/rework decision with audit trail.

**QC before delivery** — LOCKED.

---

## Preconditions

| Precondition | Source |
|--------------|--------|
| JobCard state | APPROVED or APPLIED per policy |
| Operations complete | All required operation steps closed |
| Contract scope | Work within authorized services or out-of-contract APPLIED |
| Input photos | 6 complete or exception on file |

---

## Output Photo Dependency

| Rule | Detail |
|------|--------|
| **Output photo dependency** | 8 output photos may be captured at or after QC pass — policy: QC pass authorizes output photo session |
| Camera direct only | Phase 18 |
| Block delivery if output incomplete | Phase 18 output rule |

---

## Final Diagnostic Dependency (If Required)

| Rule | Detail |
|------|--------|
| **Final diagnostic dependency if required** | Owner policy: Final Diagnostic PDF before QC pass or before delivery |
| Stage | `DIAGNOSTIC` / `FINAL` |
| Missing when required | Block QC pass — workflow hold |

---

## Contract / Service Completion Dependency

| Check | Rule |
|-------|------|
| Authorized services | All billed work mapped to contract or approved amendments |
| Ceiling | Finance preview within ceiling or approval on file |
| **Contract authorization controls operation** | QC cannot pass work outside contract without approval |

---

## QC Decision

| Result | Action |
|--------|--------|
| Pass | Allow delivery path |
| Fail | Block delivery; rework operations |
| Rework | Return to technician queue; audit `qc_rework` |

Controlled dropdown — not free text for result enum.

---

## Rework Handling

| Step | Rule |
|------|------|
| 1 | QC records fail reason in notes (free text) |
| 2 | JobCard/workflow returns to in-progress operations |
| 3 | Additional work may trigger out-of-contract approval |
| 4 | Re-QC required after rework |
| 5 | Audit chain preserved |

---

## Audit Requirement

| Event | Detail |
|-------|--------|
| `qc_check_started` | jobcard_id, actor |
| `qc_pass` | timestamp |
| `qc_fail` | reason |
| `qc_rework` | assigned technician |
| `erp_qc_check_history` | Domain history row |

Per `MOGHARE360_WORKFLOW_AUDIT_EVENT_CONTRACT.md`.

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF QC LIVE CHECK RULE**

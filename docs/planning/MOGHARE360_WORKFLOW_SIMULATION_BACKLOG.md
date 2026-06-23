# MOGHARE360 — Workflow Simulation Backlog

**Status:** Simulation backlog only — **No workflow runtime implementation in PHASE 08**

---

## Purpose

Define dry-run workflow simulation cases for future Workflow Engine implementation. Simulations validate transition contract without database writes (or use rollback test transactions in later phases).

---

## Allowed Simulation Cases

| Sim ID | Transition | Entity example | Expected result |
|--------|------------|----------------|-----------------|
| WS-001 | **DRAFT → SUBMITTED** | JobCard | Allow when validation + permission pass |
| WS-002 | **SUBMITTED → UNDER_REVIEW** | JobCard | Allow when reviewer assigned |
| WS-003 | **UNDER_REVIEW → APPROVED** | JobCard | Allow when approve permission |
| WS-004 | **UNDER_REVIEW → REJECTED** | JobCard | Allow with reject reason |
| WS-005 | **APPROVED → APPLIED** | JobCard | Allow when preconditions met |
| WS-006 | **APPLIED → CLOSED** | JobCard | Allow when close permission |
| WS-007 | **DRAFT → CANCELLED** | Purchase request | Allow when cancel permission |
| WS-008 | **SUBMITTED → CANCELLED** | Purchase request | Allow when not in review |

---

## Forbidden Simulation Cases

| Sim ID | Transition | Expected result |
|--------|------------|-----------------|
| WS-F01 | **DRAFT → APPROVED** | Block |
| WS-F02 | **DRAFT → APPLIED** | Block |
| WS-F03 | **SUBMITTED → APPLIED** | Block |
| WS-F04 | **UNDER_REVIEW → APPLIED** | Block |
| WS-F05 | **REJECTED → APPLIED** | Block |
| WS-F06 | **CANCELLED → APPLIED** | Block |
| WS-F07 | **CLOSED → APPLIED** | Block |
| WS-F08 | **CLOSED → any active state** | Block |

---

## Simulation Requirements (Future)

Each simulation must verify:

1. Actor session present
2. Permission gate check
3. Validation Engine pass/fail
4. Workflow Engine authorize/deny
5. Audit event recorded (or simulated log in dry-run mode)

---

## Implementation Note

- Phase 09+ may implement `tools/test-workflow-simulation.php`
- No `app/workflow/` PHP in Phase 08

---

**END OF WORKFLOW SIMULATION BACKLOG**

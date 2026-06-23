# MOGHARE360 — Out-of-Contract Approval Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Principle

**Any out-of-contract operation must require approval** before execution. **No execution before approval.**

---

## Out-of-Contract Operation Definition

Work or cost that falls outside the currently **APPLIED** service contract scope, authorization level, or cost ceiling.

---

## Trigger Conditions

| Trigger | Description |
|---------|-------------|
| **New service not in original contract** | Service catalog item not in authorized services list |
| **Cost above ceiling** | Cumulative preview cost exceeds customer-approved ceiling |
| **Additional part needed** | Part not contemplated in original scope or ceiling |
| **Diagnostic finding changes scope** | Secondary/Final diagnostic requires work outside contract |
| **Delivery delay changes terms** | Storage fees, dates, or handover conditions change |

Any trigger creates an out-of-contract approval request linked to JobCard.

---

## Required Approval Before Execution

| Step | Rule |
|------|------|
| 1 | Technician or service advisor creates approval request (DRAFT) |
| 2 | Scope and cost impact documented — dropdown services + preview amount |
| 3 | SUBMITTED → UNDER_REVIEW |
| 4 | Manager/customer path per authorization level |
| 5 | APPROVED → APPLIED on approval record |
| 6 | Only then may Operation Engine execute new scope |

Validation Engine blocks operation submit until approval state = APPLIED.

---

## Approval States

Aligned with global workflow contract:

| State | Meaning |
|-------|---------|
| **DRAFT** | Request being prepared |
| **SUBMITTED** | Sent for review |
| **UNDER_REVIEW** | Manager/owner reviewing |
| **APPROVED** | Approved — not yet effective on operations |
| **REJECTED** | Denied — return to prior scope |
| **APPLIED** | Effective — operations may proceed |
| **CLOSED** | Request complete — archived |

Illegal transitions rejected by Workflow Engine (E-04).

---

## Customer Acceptance Link

APPROVED → APPLIED typically requires **customer acceptance record** for scope/cost change (except emergency path with documented follow-up).

---

## Audit Requirement

| Event | Detail |
|-------|--------|
| `out_of_contract_requested` | Trigger type, jobcard_id |
| `out_of_contract_submitted` | Actor |
| `out_of_contract_approved` | Approver |
| `out_of_contract_rejected` | Reason |
| `out_of_contract_applied` | Scope effective |
| `out_of_contract_execution_blocked` | Attempt without APPLIED |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF OUT-OF-CONTRACT APPROVAL RULE**

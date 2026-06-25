# MOGHARE360 — Cost Ceiling Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Principle

**Customer-approved cost ceiling** is the maximum spend authorized on contract before additional customer approval is required. **Any cost above customer-approved ceiling must require approval.**

---

## Ceiling Definition

| Field | Rule |
|-------|------|
| **Customer-approved cost ceiling** | Numeric amount on service contract |
| **Currency/amount validation** | Valid positive decimal; IRR default; 2 decimal places policy |
| Currency code | Controlled dropdown — not free text |
| Zero ceiling | Only with Level 1 (inspection) or explicit owner policy |

---

## Ceiling Required Before Paid Work

| Gate | Rule |
|------|------|
| Contract APPLIED | Ceiling field mandatory for Level 2+ |
| Operation execute | Validation Engine verifies ceiling present |
| Part reservation (chargeable) | Block if ceiling missing |
| **Ceiling required before paid work** | LOCKED |

---

## Warning Before Approaching Ceiling

| Threshold | Action |
|-----------|--------|
| 80% of ceiling | UI warning to technician and reception |
| 90% of ceiling | Manager notification (future) |
| 100% of ceiling | Hard block on additional chargeable ops |

Warnings do not replace approval — informational only until block.

---

## Blocking Rule Above Ceiling

| Scenario | Action |
|----------|--------|
| Next operation would exceed ceiling | **Blocking rule above ceiling** — E-04 / contract gate |
| Parts cost + labor + tax preview > ceiling | Block submit |
| **No execution before approval** | Out-of-contract approval workflow required |

---

## Approval Required Above Ceiling

| Step | Requirement |
|------|-------------|
| Trigger | Estimated or actual cumulative cost > ceiling |
| Workflow | Out-of-contract approval — SUBMITTED → APPROVED |
| Customer acceptance | New acceptance record for increased ceiling or scope |
| New ceiling value | Versioned contract amendment |

Per `MOGHARE360_OUT_OF_CONTRACT_APPROVAL_RULE.md`.

---

## Finance Preview Dependency

| Rule | Detail |
|------|--------|
| Running total | Finance **preview** module aggregates labor + parts estimate |
| Not official accounting | Preview only — **no official accounting activation** |
| No payment gateway | No charge/capture — display and gate only |
| Ceiling compare | Preview total vs contract ceiling at Validation Engine |

---

## Audit Requirement

| Event | Detail |
|-------|--------|
| `ceiling_set` | Initial ceiling on contract |
| `ceiling_warning_shown` | 80%/90% threshold |
| `ceiling_breach_blocked` | Operation blocked |
| `ceiling_approval_requested` | Out-of-contract flow started |
| `ceiling_amended` | New ceiling after approval |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF COST CEILING RULE**

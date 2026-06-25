# MOGHARE360 — Delivery Live Check Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Delivery Close Requirement

**Delivery live check** is the final operational gate before JobCard **CLOSED** — confirming customer handover, media evidence, and QC completion.

---

## Preconditions (Dependencies)

### Customer Acceptance Dependency

| Rule | Source |
|------|--------|
| Contract acceptance on file | Phase 19 |
| Delivery acknowledgement | Contract template section — recorded at handover |
| Amendment acceptance | If scope changed during job |

### QC Dependency

| Rule | Source |
|------|--------|
| **QC dependency** | QC pass required — `MOGHARE360_QC_LIVE_CHECK_RULE.md` |
| Rework open | Blocks delivery |

### Output Photo Dependency

| Rule | Source |
|------|--------|
| **8 output photos** | `MOGHARE360_OUTPUT_PHOTO_8_RULE.md` |
| Stage OUTPUT complete | Or manager exception |

### Payment Tracking Preview Dependency Only

| Rule | Detail |
|------|--------|
| **Payment tracking preview dependency only** | Record preview amount / payment method note |
| **No official accounting activation** | LOCKED |
| **No payment gateway** | No card charge, no billing integration |
| Finance preview | Display unpaid/paid preview status for handover checklist |

---

## Delivery Checklist (Controlled)

| Item | Control |
|------|---------|
| Vehicle keys returned | Checkbox enum |
| Customer informed of work done | Checkbox |
| Output photos complete | System verify |
| Final diagnostic (if policy) | System verify |
| Delivery acknowledgement signed/recorded | Acceptance record |

---

## Delivery Acknowledgement

| Requirement | Rule |
|-------------|------|
| **Delivery acknowledgement** | Customer acceptance type: in-person at handover |
| Link to JobCard | Mandatory |
| Timestamp + actor | Reception/delivery staff |
| Contract PDF | Archived version matches delivered scope |

---

## Workflow Transition

| Transition | Gate |
|------------|------|
| APPLIED → CLOSED | All delivery dependencies met |
| Illegal skip | E-04 — workflow block |

---

## Audit Requirement

| Event | Detail |
|-------|--------|
| `delivery_check_started` | jobcard_id |
| `delivery_completed` | actor, timestamp |
| `delivery_blocked` | missing dependency code |
| `erp_delivery_control_history` | History row |

---

## No Bypass

- No delivery close without QC + output photos (unless approved exception)
- No accounting post
- No portal activation

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF DELIVERY LIVE CHECK RULE**

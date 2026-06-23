# MOGHARE360 — 8 Output Photo Rule

**Stage:** OUTPUT  
**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Rule

**Exactly 8 required output photos** per JobCard before delivery close.

Capture method: **Camera direct only** — per `MOGHARE360_CAMERA_ONLY_CAPTURE_RULE.md`.

---

## Suggested Capture Positions

| # | Position | Purpose |
|---|----------|---------|
| 1 | **Front final** | Completed work — front view |
| 2 | **Rear final** | Completed work — rear view |
| 3 | **Left side final** | Completed work — left side |
| 4 | **Right side final** | Completed work — right side |
| 5 | **Dashboard / odometer final** | Post-service mileage / warnings |
| 6 | **Interior final** | Cleaned / restored cabin |
| 7 | **Repaired area / serviced area** | Close-up of primary repair |
| 8 | **Delivery condition confirmation** | Handover-ready overall confirmation |

Each position maps to fixed slot (1–8).

---

## Binding to JobCard

| Requirement | Rule |
|-------------|------|
| Parent | Every photo `jobcard_id` required |
| Stage | `media_stage = OUTPUT` |
| QC link | Output set expected after QC pass policy |
| Orphan media | FORBIDDEN |

---

## Workflow Gate

| Transition | Precondition |
|------------|--------------|
| Delivery close / APPLIED → CLOSED | All 8 OUTPUT slots registered OR approved exception |
| Delivery control form submit | 8 output photos complete (per Critical Forms v2) |

Workflow Engine blocks delivery close without complete OUTPUT set.

---

## Exception Handling with Manager Approval

| Scenario | Process |
|----------|---------|
| Slot not applicable (minor service) | Manager approval with documented reason |
| Stage tag | `EXCEPTION` |
| Audit | `output_photo_exception_approved` |
| Policy | Exception does not waive QC accountability |

---

## Validation Rules

| Check | Result |
|-------|--------|
| Count < 8 | E-07 — block delivery close |
| Count > 8 | E-07 — reject excess |
| QC not passed | E-04 — block OUTPUT registration if policy requires QC first |
| Non-camera source | E-07 |

---

## Audit Requirement

| Event | Detail |
|-------|--------|
| Slot captured | `output_photo_captured` — slot 1–8 |
| Set complete | `output_photo_set_complete` |
| Exception approved | `output_photo_exception_approved` |
| Delivery blocked (incomplete) | `delivery_blocked_missing_output_photos` |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF 8 OUTPUT PHOTO RULE**

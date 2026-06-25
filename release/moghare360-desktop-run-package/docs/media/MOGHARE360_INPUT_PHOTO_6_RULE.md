# MOGHARE360 — 6 Input Photo Rule

**Stage:** INPUT  
**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Rule

**Exactly 6 required input photos** per JobCard before technical workflow may start.

Capture method: **Camera direct only** — per `MOGHARE360_CAMERA_ONLY_CAPTURE_RULE.md`.

---

## Suggested Capture Positions

| # | Position | Purpose |
|---|----------|---------|
| 1 | **Front** | Front exterior condition |
| 2 | **Rear** | Rear exterior condition |
| 3 | **Left side** | Driver-side body condition |
| 4 | **Right side** | Passenger-side body condition |
| 5 | **Dashboard / odometer** | Mileage and warning lights evidence |
| 6 | **Interior / visible condition** | Cabin condition, seats, visible damage |

Each position maps to a fixed slot (1–6). UI shows slot checklist — not free-order upload.

---

## Binding to JobCard

| Requirement | Rule |
|-------------|------|
| Parent | Every photo `jobcard_id` required |
| Stage | `media_stage = INPUT` |
| Orphan media | FORBIDDEN — E-01 / E-07 |
| Vehicle link | Inherited via JobCard → vehicle ref |

---

## Workflow Gate

| Transition | Precondition |
|------------|--------------|
| Start technical operations | All 6 INPUT slots registered OR approved exception |
| SUBMITTED → technical queue | 6 input photos complete (default policy) |

Workflow Engine blocks transition if Validation Engine reports incomplete INPUT set.

---

## Exception Handling with Manager Approval

| Scenario | Process |
|----------|---------|
| Physical obstruction (e.g. cannot photograph rear) | Manager approval workflow |
| Stage tag | `EXCEPTION` with reason code |
| Audit | `media_exception_approved` — approver, reason, missing slot |
| Policy | Minimum 4 of 6 only if exception explicitly granted — default remains 6 |

No silent skip of slots.

---

## Validation Rules

| Check | Result |
|-------|--------|
| Count < 6 | E-07 — block workflow |
| Count > 6 | E-07 — reject excess |
| Non-camera source | E-07 |
| Duplicate slot | Replace only pre-registration; post-registration → correction workflow |

---

## Audit Requirement

| Event | Detail |
|-------|--------|
| Slot captured | `input_photo_captured` — slot 1–6 |
| Set complete | `input_photo_set_complete` |
| Exception requested | `input_photo_exception_request` |
| Exception approved | `input_photo_exception_approved` |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF 6 INPUT PHOTO RULE**

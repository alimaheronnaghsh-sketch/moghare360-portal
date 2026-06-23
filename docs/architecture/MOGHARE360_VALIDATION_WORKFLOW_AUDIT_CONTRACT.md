# MOGHARE360 — Validation / Workflow / Audit Contract

**Status:** Locked architecture contract — Documentation only

---

## Required Flow

All controlled writes must follow:

**UI → Validation Engine → Workflow Engine → Database → Audit Log**

| Stage | Failure action |
|-------|----------------|
| UI | Collect input only — no SQL |
| Validation Engine | Reject → return errors to UI |
| Workflow Engine | Reject illegal transition |
| Database | Persist approved payload |
| Audit Log | Append immutable record |

---

## Validation Engine Contract

Validation is **mandatory** before Workflow Engine and database write.

### Field and Business Rules

| Rule | Scope |
|------|-------|
| **National ID** | Iran algorithm (10-digit check digit) |
| **Mobile** | `09XXXXXXXXX` (11 digits) |
| Persian-only name | Customer display name |
| **VIN** | ISO 3779 |
| **Plate** | Iran plate standard |
| Engine / chassis | Format validation |
| Brand / model / class | Fixed lists, cascading |
| **Required fields** | Per entity schema |
| **Status values** | Enum / dropdown validation |
| **State transitions** | Valid source→target only |
| **Permission guard** | Role + permission key on action |

### Media Rules

| Rule | Requirement |
|------|-------------|
| **Camera direct only** | No file-picker upload path |
| **No upload bypass** | No alternate media ingest |
| Max input images | 6 |
| Max output images | 8 |

### Diagnostics

- Initial PDF, Secondary PDF, Final PDF — gated by workflow state

---

## Workflow Engine Contract

### States

```
DRAFT → SUBMITTED → UNDER_REVIEW → APPROVED → APPLIED → CLOSED
```

| Transition | Requires |
|------------|----------|
| DRAFT → SUBMITTED | Validation pass + submit permission |
| SUBMITTED → UNDER_REVIEW | Reviewer assignment |
| UNDER_REVIEW → APPROVED | Approve permission |
| UNDER_REVIEW → DRAFT | Reject with reason |
| APPROVED → APPLIED | Apply permission + preconditions |
| APPLIED → CLOSED | Close permission + completion checks |

Illegal transitions rejected at Workflow Engine.

---

## Audit Log Contract

Every controlled state/action change must capture:

| Field | Description |
|-------|-------------|
| **actor** | `actor_user_id` from session |
| **timestamp** | Server time, immutable |
| **action** | create / update / transition / reject |
| **old state** | Prior workflow state or field snapshot |
| **new state** | Target workflow state or field snapshot |
| **reference entity** | `entity_type` + `entity_id` |
| **source module** | Canonical domain / module name |

Storage: domain `*_history` tables and/or `core_audit_logs` per audit ownership map.

---

## Database Constraint Relationship

31 CHECK constraints (Phase 03) complement but **do not replace** Validation Engine. Critical validation columns (32, Phase 04) are primarily application-enforced.

---

## Product Boundary

- No bypass of validation for convenience
- No official accounting or payment gateway in validation scope
- Finance validation is preview-only

---

**END OF VALIDATION / WORKFLOW / AUDIT CONTRACT**

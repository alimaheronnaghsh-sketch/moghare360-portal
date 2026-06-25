# MOGHARE360 — Employee Profile Completion Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Employee Profile Completion Purpose

Define **complete internal employee records** for workshop HR readiness — identity, role, contract, training, discipline — without payroll runtime activation in Phase 23.

**HR remains internal administrative readiness** — LOCKED.

---

## Identity Data

| Field | Validation |
|-------|------------|
| Persian name | Phase 17 Persian name rule |
| National ID | Phase 17 National ID rule |
| Employee code | Unique — system or owner-assigned |
| Date of birth | Optional policy |

---

## Contact Data

| Field | Validation |
|-------|------------|
| Mobile | Phase 17 mobile rule |
| Address | Free text — local only |
| Emergency contact | Name + phone |

---

## Role / Position Relation

| Link | Rule |
|------|------|
| **Role/position relation** | Department + position dropdowns |
| Identity module | Links to `core_users` where applicable — read via HR, write via Identity gate |
| Permissions | Not modified in Phase 23 — reference only |

---

## Contract Relation

| Link | Rule |
|------|------|
| **Contract relation** | HR contract type dropdown — Phase 17 |
| Start / end dates | Required for active employees |
| Contract documents | Local HR document store — not domain |

---

## Training / Certification Relation

| Element | Rule |
|---------|------|
| **Training/certification relation** | Course name, date, expiry — dropdown type |
| Technician certification | Flag for assigned operations |
| Expired cert | Warning on assignment — future |

---

## Discipline / Reward Relation

| Element | Rule |
|---------|------|
| **Discipline/reward relation** | Record type dropdown |
| Notes | Free text — manager only |
| Sensitive | HR access boundary |

---

## Document Readiness

| Document | Storage |
|----------|---------|
| ID copy | Local path — not git, not domain |
| Contract scan | Local |
| **Document readiness** | Checklist per employee — complete/incomplete |

Camera direct or owner-approved scan workflow — no domain upload.

---

## Access Boundary

| Role | Access |
|------|--------|
| HR admin | Full profile |
| Manager | Team subset |
| Employee self | Future — not Phase 23 |
| Customer / portal | FORBIDDEN |

---

## Audit Requirement

| Event | Detail |
|-------|--------|
| `employee_profile_created` | employee_id |
| `employee_profile_updated` | field mask |
| `hr_document_registered` | doc type |
| `erp_hr_history` | Domain row |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF EMPLOYEE PROFILE COMPLETION RULE**

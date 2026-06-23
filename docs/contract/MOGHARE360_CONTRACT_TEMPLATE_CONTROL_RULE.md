# MOGHARE360 — Contract Template Control Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Principle

Service contracts use a **controlled template based on existing Codex/reference contract** — workshop legal standard maintained by owner. Staff cannot invent ad-hoc contract terms in free text for binding clauses.

---

## Template Source

| Item | Rule |
|------|--------|
| Base document | Owner **Codex/reference contract** (workshop service agreement standard) |
| ERP template | Structured sections mapped from Codex — not uncontrolled Word uploads |
| Language | Persian primary for customer-facing clauses |
| Changes | **Owner approval for template changes** — version bump required |

---

## Required Contract Sections

| # | Section | Content |
|---|---------|---------|
| 1 | **Customer identity** | Name, national ID, mobile — from validated customer master |
| 2 | **Vehicle identity** | Plate, VIN, brand/model — from validated vehicle master |
| 3 | **Reception condition** | Intake notes, mileage, visible damage summary (reference to input photos) |
| 4 | **Authorized services** | Scoped service list — dropdown/catalog driven |
| 5 | **Cost ceiling** | Customer-approved maximum spend |
| 6 | **Sleep/storage terms** | Explicit storage start, free period, chargeable period, daily fee |
| 7 | **Customer acceptance** | Acceptance type, timestamp, recording staff |
| 8 | **Out-of-contract approval** | Clause acknowledging additional work requires approval |
| 9 | **Delivery acknowledgement** | Handover terms, output photo reference |

Free text limited to reception notes and section-specific remarks — not for replacing structured clauses.

---

## Template Versioning

| Field | Rule |
|-------|--------|
| `template_version` | Semantic version e.g. `1.0.0` |
| Effective date | New JobCards use current version at draft time |
| In-flight contracts | Retain version at creation — no retroactive template swap |
| History | Prior template versions archived locally for legal reference |

---

## Owner Approval for Template Changes

| Step | Requirement |
|------|-------------|
| Propose change | Document diff vs prior Codex section |
| Owner sign-off | Required before `template_version` increment |
| Audit | `contract_template_version_published` |
| ERP rollout | New drafts only — existing contracts unchanged |

---

## Validation Integration

| Check | Error |
|-------|-------|
| Missing required section | E-01 |
| Ceiling missing | E-01 |
| Storage terms missing when vehicle stored | E-01 |
| Template version retired | Block new drafts on old version |

---

## PDF Generation Note

Template renders to PDF in future implementation phase — **no PDF generation in Phase 19**.

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF CONTRACT TEMPLATE CONTROL RULE**

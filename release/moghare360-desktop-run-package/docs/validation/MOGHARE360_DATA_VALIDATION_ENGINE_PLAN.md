# MOGHARE360 — Data Validation Engine Plan

**Database:** MOGHARE360_ERP  
**Status:** Planning baseline — PHASE 17  
**Implementation:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Purpose

The **Data Validation Engine** is the mandatory gate between UI input and all downstream persistence. It enforces field format, required-field, duplicate-risk, media, and cross-domain rules **before** the Workflow Engine or database receive any write request.

**No validation bypass** is permitted at any layer.

---

## Position in Flow

```
UI
 │
 ▼
Validation Engine  ◄── PHASE 17 locks rules here
 │
 ▼
Workflow Engine
 │
 ▼
Database (MOGHARE360_ERP)
 │
 ▼
Audit Log
```

**UI → Validation Engine → Workflow Engine → Database → Audit Log**

---

## Core Principles

| Principle | Rule |
|-----------|------|
| Validation before workflow | State transitions rejected if field validation fails (E-04 blocked upstream) |
| Validation before database write | No row insert/update until validation passes |
| **No direct UI-to-database write** | All mutations through Validation + Workflow services |
| **No validation bypass** | No admin shortcut, hidden route, or debug flag to skip |
| Dropdown-first | Sensitive enums via controlled selectors — not free text |
| Free text scope | Notes and descriptions only |
| Domain ownership | Each module validates its owned fields; cross-domain refs validated jointly |

---

## Validation Engine Responsibilities

| Responsibility | Detail |
|----------------|--------|
| Required field check | E-01 |
| Format validation | E-02 — National ID, mobile, plate, VIN, engine/chassis, Persian name |
| Duplicate risk | E-03 — unique business keys before DB |
| Permission pre-check | Delegates to permission layer; E-05 on fail |
| Cross-domain write guard | E-06 — module cannot write foreign domain master |
| Media rule enforcement | E-07 — camera direct only; no upload bypass |
| Production boundary | E-10 — block SaaS, portal, accounting, payment |

Workflow Engine handles **state transition validation** (E-04) after field validation passes.

---

## Validation Error Categories

Aligned with `docs/architecture/MOGHARE360_VALIDATION_ERROR_POLICY.md`:

| Code | Category | Validation Engine role |
|------|----------|------------------------|
| E-01 | REQUIRED_FIELD_MISSING | Block before workflow |
| E-02 | INVALID_FORMAT | Field validators (Phase 17 rules) |
| E-03 | DUPLICATE_RISK | Pre-write uniqueness check |
| E-04 | INVALID_STATE_TRANSITION | Workflow Engine (after validation pass) |
| E-05 | PERMISSION_DENIED | Permission gate |
| E-06 | CROSS_DOMAIN_WRITE_BLOCKED | Ownership matrix enforcement |
| E-07 | MEDIA_RULE_VIOLATION | Camera/upload rules |
| E-08 | DATABASE_WRITE_BLOCKED | Aggregate reject code |
| E-09 | AUDIT_REQUIRED | Post-write audit failure → rollback |
| E-10 | PRODUCTION_BOUNDARY_BLOCKED | SaaS/portal/accounting/payment |

---

## Domain Ownership Responsibility

Per `docs/architecture/MOGHARE360_DOMAIN_VALIDATION_RESPONSIBILITY_MATRIX.md`:

| Domain | Primary validators |
|--------|-------------------|
| Customer | National ID, mobile, Persian name, channel/class dropdowns |
| Vehicle | Plate, VIN, engine/chassis, brand/model cascade |
| JobCard | Customer/vehicle/contract refs, type dropdown |
| Operation/QC/Delivery | Step enums, QC pass/fail, checklist |
| Inventory/Purchase | Part category, quantity, supplier |
| CRM | Follow-up type, customer/jobcard ref |
| HR | Contract type, employee fields, Persian name |

Each domain's **validation owner** is the same module that owns database writes for that domain.

---

## Phase 17 Validator Modules (Planned)

| Module | Document |
|--------|----------|
| National ID | `MOGHARE360_NATIONAL_ID_VALIDATION_RULE.md` |
| Mobile | `MOGHARE360_MOBILE_VALIDATION_RULE.md` |
| Iranian Plate | `MOGHARE360_IRANIAN_PLATE_VALIDATION_RULE.md` |
| VIN | `MOGHARE360_VIN_VALIDATION_RULE.md` |
| Engine / Chassis | `MOGHARE360_ENGINE_CHASSIS_VALIDATION_RULE.md` |
| Persian Name | `MOGHARE360_PERSIAN_NAME_VALIDATION_RULE.md` |
| Dropdown / Cascade | `MOGHARE360_DROPDOWN_CASCADING_SELECT_RULES.md` |
| Critical Forms v2 | `MOGHARE360_CRITICAL_FORMS_V2_LOCK_PLAN.md` |

---

## Future Runtime Implementation Requirement

When a later phase approves implementation:

1. **Server-side validators** in PHP service layer — client-side hints only; server is authoritative
2. **Single validation entry point** per domain write route
3. **Normalized input** before format check (trim, digit-only extraction, uppercase VIN)
4. **Field-level error response** — Persian RTL messages per error policy
5. **Audit on validation failure** where policy requires (e.g. National ID fail)
6. **No duplicate validator logic** in UI pages — shared validation library
7. **Unit tests** per validator before form lock activation
8. **Forms v2** replace free-text sensitive fields per Critical Forms lock plan

**No runtime implementation in PHASE 17.**

---

## Product Boundary

- Local laptop server = system of record
- moghareh360.ir = Mirror Only
- No production SaaS · No public portal · No official accounting · No payment gateway

---

**END OF DATA VALIDATION ENGINE PLAN**

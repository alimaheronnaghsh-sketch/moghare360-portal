# PHASE 17 — Data Validation Engine & Form Lock — Scope

**Phase:** PHASE 17 — DATA VALIDATION ENGINE AND FORM LOCK  
**Status:** Documentation and planning only  
**SQL:** No SQL required

---

## Phase Objective

Design and lock the **Data Validation Engine** and **Form Lock** rules for MOGHARE360 ERP before operational go-live.

---

## Locked Rules

| Rule | Status |
|------|--------|
| Database: **MOGHARE360_ERP** | LOCKED |
| Local laptop server = system of record | LOCKED |
| moghareh360.ir = Mirror Only | LOCKED |
| **UI → Validation Engine → Workflow Engine → Database → Audit Log** | LOCKED |
| Sensitive fields = dropdowns / structured selectors | LOCKED |
| Free text only for notes/descriptions | LOCKED |
| **No UI may write directly to database** | LOCKED |
| **No validation bypass** | LOCKED |
| **No upload bypass** · **Camera direct only** | LOCKED |
| No production SaaS / portal / accounting / payment gateway | LOCKED |

---

## PHASE 17 Modules

1. National ID Validator
2. Mobile Validator
3. Iranian Plate Validator
4. VIN Validator
5. Engine / Chassis Validator
6. Persian-only Name Validator
7. Dropdown / Cascading Select Engine
8. Critical Forms v2 Lock Plan

---

## Allowed Scope

- `docs/phases/phase_17_data_validation_engine_form_lock/` (5 files)
- `docs/validation/` (10 files)

---

## Forbidden Scope

- PHP runtime, SQL, schema, `public_html`, auth, config, release
- Modify existing form pages; implement validators in runtime
- SaaS, portal, accounting, payment gateway activation
- Commit, push

---

## Phase 17 Constraints

- **PHASE 17 is documentation/planning only**
- **No runtime validator implementation**
- **No PHP creation**
- **No SQL creation**
- **No database modification**
- **No existing form modification**

---

**END OF SCOPE**

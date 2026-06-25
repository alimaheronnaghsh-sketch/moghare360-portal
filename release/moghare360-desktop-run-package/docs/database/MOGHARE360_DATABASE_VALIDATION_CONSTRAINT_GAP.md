# MOGHARE360 — Database Validation Constraint Gap

**Database:** MOGHARE360_ERP  
**Source:** PHASE 04 SSMS read-only discovery + master validation plan  
**Status:** Documentation only

---

## Discovery Metric

| Metric | Value |
|--------|-------|
| Critical validation columns checked | **32** |
| Check constraints (Phase 03) | 31 |

Phase 04 identified 32 columns across MOGHARE360_ERP that are **critical for business validation** (national ID, mobile, VIN, plate, status enums, etc.). Database CHECK constraints (31 total) do not individually map 1:1 to all validation rules.

---

## Risk Analysis

### R-01 — Database Constraints Do Not Cover All Validation Rules

DB enforces structural integrity (PK, FK, some enums). **Business validation** (algorithms, formats, Persian-only names, media capture rules) lives primarily in **Validation Engine** — not yet fully implemented in `app/validation/`.

### R-02 — UI Must Not Directly Write to DB

**No direct database write from UI.** Even where CHECK constraints exist, UI pages must not bypass:

**UI → Validation Engine → Workflow Engine → Database → Audit Log**

### R-03 — Validation Engine Remains Mandatory

Database constraints are a **safety net**, not the primary validation layer. Gap between 32 critical columns and 31 CHECK constraints indicates most rules are application-enforced.

---

## Required Validation Flow

```
UI → Validation Engine → Workflow Engine → Database → Audit Log
```

| Stage | Responsibility |
|-------|----------------|
| UI | Collect input only |
| Validation Engine | Business rules (below) |
| Workflow Engine | State authorization |
| Database | PK, FK, CHECK, DEFAULT |
| Audit Log | Immutable record |

---

## Validation Rules — Engine vs Database Gap

| Rule | Validation Engine | DB CHECK (typical) |
|------|-------------------|-------------------|
| **National ID validation** (Iran algorithm) | Required | Unlikely in CHECK |
| **Mobile validation** (`09XXXXXXXXX`) | Required | Unlikely in CHECK |
| Persian-only name | Required | Unlikely in CHECK |
| **VIN validation** (ISO 3779) | Required | Unlikely in CHECK |
| **Plate validation** (Iran standard) | Required | Unlikely in CHECK |
| Engine/chassis numbers | Required | Unlikely in CHECK |
| Brand/model/class dropdowns | Required | FK or lookup table |
| **Status dropdown/control validation** | Required | Partial — some status CHECK |
| **Camera direct only** | Required (media) | Not in DB |
| **No upload bypass** | Required (media) | Not in DB |
| Diagnostics PDFs (initial/secondary/final) | Required | Not in DB |

---

## Critical Validation Columns (32) — Categories

Phase 04 flagged columns likely including:

- Customer: national_id, mobile, name fields
- Vehicle: plate, vin, engine_no, chassis_no, brand_id, model_id, class_id
- JobCard/Operation: status, workflow_state
- Contract: status, authorization fields
- Payment preview: amount, status (not official accounting)

> Per-column list from user SSMS export to be cross-referenced in Phase 05.

---

## Required Future Action

1. Export 32 critical column definitions from SSMS
2. Map each to Validation Engine rule ID
3. Identify which may safely gain CHECK constraint (status enums only)
4. Confirm Workflow Engine gates status-changing writes on validated columns
5. Never rely on DB alone for National ID, VIN, plate, or media rules

---

## Product Boundary

- No official accounting activation
- No payment gateway/billing/tax integration created
- Finance/payment columns are preview scope only

---

**END OF VALIDATION CONSTRAINT GAP**

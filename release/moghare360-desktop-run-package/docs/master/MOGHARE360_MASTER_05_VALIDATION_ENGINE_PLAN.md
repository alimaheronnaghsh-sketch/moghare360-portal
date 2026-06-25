# MOGHARE360 — Master 05 Validation Engine Plan

**Status:** Planning only — Documentation only  
**SQL:** Not required

---

## Purpose

Define the Validation Engine rules and pipeline. No direct database write from UI. All validated writes flow through engines and audit.

---

## Core Pipeline

```
UI → Validation Engine → Workflow Engine → Database → Audit Log
```

| Stage | Responsibility |
|-------|----------------|
| UI | Collect input; no direct SQL |
| Validation Engine | Schema + business rules |
| Workflow Engine | State authorization |
| Database | Persist approved data |
| Audit Log | Immutable action record |

**Rule:** No direct database write from UI.

---

## Customer Validation

| Field | Rule |
|-------|------|
| National ID | Iran algorithm (10-digit check digit) |
| Mobile | `09XXXXXXXXX` (11 digits, starts with 09) |
| Name | Persian-only name (no Latin in display name field) |

Reject on validation failure before Workflow Engine.

---

## Vehicle Validation

| Field | Rule |
|-------|------|
| Plate | Iran plate standard |
| VIN | ISO 3779 format |
| Engine | Engine number validation |
| Chassis | Chassis number validation |
| Brand | Fixed brand list (dropdown) |
| Model | Cascading model dropdown (depends on brand) |
| Class | Class dropdown |

---

## Media Validation

| Rule | Value |
|------|-------|
| Max input images | 6 |
| Max output images | 8 |
| Capture method | **Camera direct only** |
| Upload bypass | **No upload bypass** |

- No file-picker upload path that bypasses camera capture
- Images stored only after validation + workflow approval where required

---

## Diagnostics Validation

| Document | Stage |
|----------|-------|
| Initial PDF | Service intake / first assessment |
| Secondary PDF | Mid-service diagnostic |
| Final PDF | Completion / QC handoff |

PDFs linked to JobCard workflow state; generation gated by permission.

---

## Validation Engine Interface (Planned)

```
validateCustomer(payload) → ValidationResult
validateVehicle(payload) → ValidationResult
validateMedia(captureMeta) → ValidationResult
validateDiagnostics(pdfMeta) → ValidationResult
```

`ValidationResult`: `{ ok: bool, errors: [], normalized: {} }`

On `ok === false` → return to UI; no DB write.

---

## Product Boundary

- Documentation only
- No Validation Engine PHP implementation in this phase
- Camera direct only / No upload bypass enforced in future implementation

---

**END OF VALIDATION ENGINE PLAN**

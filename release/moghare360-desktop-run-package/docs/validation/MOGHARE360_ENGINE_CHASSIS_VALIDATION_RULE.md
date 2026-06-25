# MOGHARE360 — Engine / Chassis Validation Rule

**Fields:** Engine number, Chassis number  
**Status:** PLANNED_NOT_IMPLEMENTED  
**Error category:** E-02 INVALID_FORMAT, E-03 DUPLICATE_RISK  
**SQL:** No SQL required

---

## Scope

Validates engine and chassis identification numbers on **vehicle** master and **jobcard** vehicle context.

---

## Engine Number Validation Concept

| Aspect | Rule |
|--------|------|
| Purpose | Unique manufacturer engine identifier |
| **Allowed characters** | Alphanumeric `A–Z`, `0–9`, hyphen `-` (policy: normalize hyphens out for compare) |
| **Length range planning** | Minimum 5, maximum 20 characters after normalization |
| Case | Uppercase normalization |
| Empty | Optional if VIN + plate sufficient per form policy; required when brand policy demands |

---

## Chassis Number Validation Concept

| Aspect | Rule |
|--------|------|
| Purpose | Chassis / frame identifier (may differ from VIN on some imports) |
| **Allowed characters** | Same as engine — alphanumeric + optional hyphen |
| **Length range planning** | Minimum 5, maximum 20 characters |
| Relationship to VIN | If chassis equals VIN, dedupe check must not false-positive |

---

## Brand / Model Dependency Note

| Dependency | Rule |
|------------|------|
| Brand dropdown | Some brands impose format hints (e.g. fixed prefix length) |
| Model depends on brand | Cascade select — engine/chassis optional rules per brand table (future reference data) |
| Import vehicles | May allow longer alphanumeric — cap at 20 unless brand rule extends |

Validation engine applies **base rule first**, then **brand-specific overlay** when reference data exists.

---

## Duplicate Risk Check

| Check | Action |
|-------|--------|
| Engine number on another active vehicle | E-03 |
| Chassis number on another active vehicle | E-03 |
| Same engine + different VIN | Flag — possible data entry error |

---

## Usage Context

| Context | Usage |
|---------|-------|
| Vehicle registration form | Engine/chassis capture |
| JobCard | Read-only from vehicle master |
| QC / delivery | Verify against intake record |

---

## Error Message Policy

| Condition | User message (Persian concept) |
|-----------|----------------------------------|
| Invalid format | «شماره موتور/شاسی نامعتبر است» — E-02 |
| Length out of range | «طول شماره موتور/شاسی مجاز نیست» — E-02 |
| Duplicate | «این شماره موتور/شاسی قبلاً ثبت شده است» — E-03 |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF ENGINE / CHASSIS VALIDATION RULE**

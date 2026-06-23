# MOGHARE360 — VIN Validation Rule

**Field:** Vehicle Identification Number (VIN)  
**Status:** PLANNED_NOT_IMPLEMENTED  
**Error category:** E-02 INVALID_FORMAT, E-03 DUPLICATE_RISK  
**SQL:** No SQL required

---

## Scope

Validates VIN for **vehicle registration** and vehicle master records. Aligns with ISO 3779 structure (17 characters).

---

## Format Rules

| Rule | Requirement |
|------|-------------|
| **VIN must be 17 characters** | Exactly 17 after normalization |
| **Uppercase normalization** | Convert `a-z` → `A-Z` before validation |
| **Alphanumeric-only** | Letters A–Z and digits 0–9 only |
| **Forbidden characters: I, O, Q** | Reject if any of `I`, `O`, `Q` present — E-02 |
| No spaces or hyphens in stored value | Strip before length check |

---

## Validation Steps (Planned)

1. Normalize: trim, uppercase, remove separators
2. Length == 17 → else E-02
3. Charset: `[A-HJ-NPR-Z0-9]{17}` → else E-02
4. Optional future: ISO 3779 check digit (position 9) — document as enhancement gate
5. Duplicate check against `erp_vehicles` — E-03

---

## Duplicate Vehicle Risk Check

| Check | Action |
|-------|--------|
| VIN exists on active vehicle | Reject — E-03 |
| VIN reuse on scrapped vehicle | Owner policy — flag for admin review |

---

## Usage Context

| Context | Usage |
|---------|-------|
| Vehicle registration form | Primary VIN capture |
| JobCard vehicle bind | Vehicle must have valid VIN or plate (at least one per policy) |
| Import / warranty lookup | Future integration — not Phase 17 |

---

## Error Message Policy

| Condition | User message (Persian concept) |
|-----------|----------------------------------|
| Empty when required | «شماره VIN الزامی است» — E-01 |
| Invalid length/format | «شماره VIN نامعتبر است (۱۷ کاراکتر)» — E-02 |
| Forbidden characters | «حروف I، O و Q در VIN مجاز نیستند» — E-02 |
| Duplicate | «این VIN قبلاً ثبت شده است» — E-03 |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF VIN VALIDATION RULE**

# MOGHARE360 — Mobile Validation Rule

**Field:** Iranian mobile number  
**Status:** PLANNED_NOT_IMPLEMENTED  
**Error category:** E-02 INVALID_FORMAT, E-03 DUPLICATE_RISK  
**SQL:** No SQL required

---

## Scope

Validates mobile numbers for **customers**, **personnel**, and **contact** records.

---

## Format Rules

| Rule | Requirement |
|------|-------------|
| **Iranian mobile format** | `09XXXXXXXXX` |
| **11-digit requirement** | Exactly 11 digits after normalization |
| **Numeric-only** | Digits only |
| Prefix | Must start with `09` |
| **No spaces/dashes unless normalized** | Accept input with separators only if normalized to 11 digits before validation |

---

## Normalization

1. Strip spaces, dashes, parentheses, `+98` country prefix
2. Convert `98XXXXXXXXXX` (12 digits) → `0XXXXXXXXXX` (11 digits) if valid
3. Convert Persian/Arabic digits to Latin
4. Validate normalized result

---

## Rejected Patterns

| Pattern | Action |
|---------|--------|
| Length ≠ 11 after normalization | E-02 |
| Does not start with `09` | E-02 |
| Non-numeric | E-02 |
| Invalid operator digit (position 3) | E-02 per Iranian mobile ranges policy |

---

## Duplicate Risk Check

| Check | Action |
|-------|--------|
| Same mobile on active customer | E-03 — business policy: one primary mobile per customer master |
| Cross-entity duplicate | Flag for reception review if shared family number policy applies |

---

## Usage Context

| Entity | Usage |
|--------|-------|
| Customer | Primary contact mobile |
| Personnel | HR employee mobile |
| Contact | Secondary phones table where mobile type |

---

## Error Message Policy

| Condition | User message (Persian concept) |
|-----------|----------------------------------|
| Empty required | «شماره موبایل الزامی است» — E-01 |
| Invalid format | «شماره موبایل نامعتبر است (مثال: 09123456789)» — E-02 |
| Duplicate | «این شماره موبایل قبلاً ثبت شده است» — E-03 |

---

## Audit Requirement

- Customer mobile change → `erp_customer_core_history`
- Failed duplicate on submit → `validation_failed` in audit log

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF MOBILE VALIDATION RULE**

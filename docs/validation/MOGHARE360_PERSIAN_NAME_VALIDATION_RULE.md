# MOGHARE360 — Persian Name Validation Rule

**Field:** Persian personal / customer name  
**Status:** PLANNED_NOT_IMPLEMENTED  
**Error category:** E-02 INVALID_FORMAT  
**SQL:** No SQL required

---

## Scope

Validates **Persian-only** names for **customers** and **personnel** (first name, last name, full name fields).

---

## Persian-Only Name Rule

| Rule | Requirement |
|------|-------------|
| **No Latin letters** for Persian customer/personnel names | Reject `A–Z`, `a–z` — E-02 |
| **Allowed Persian characters** | Arabic/Persian script letters (U+0600–U+06FF range, excluding digits) |
| **Allowed spaces** | Single space between name parts; trim leading/trailing |
| Allowed punctuation | None in name fields (hyphen in compound names — future policy gate) |
| Digits | Reject digits in name fields |

---

## Length Planning

| Field | Minimum | Maximum |
|-------|---------|---------|
| First name | 2 characters | 50 characters |
| Last name | 2 characters | 50 characters |
| Full name (if single field) | 3 characters | 100 characters |

After trim; count by Unicode grapheme clusters for Persian script.

---

## Normalization

1. Trim whitespace
2. Collapse multiple internal spaces to single space
3. Reject if Latin script detected
4. Optional: normalize ی/ي and ک/ك variants per owner policy (document at implementation)

---

## Usage Context

| Entity | Fields |
|--------|--------|
| Customer | Customer display name, owner name |
| Personnel | Employee first/last name |
| CRM contact | Person name where Persian |

**Business / legal entity names** may use separate field with different rule — not covered by this Persian personal name rule.

---

## Error Message Policy

| Condition | User message (Persian concept) |
|-----------|----------------------------------|
| Empty required | «نام الزامی است» — E-01 |
| Too short | «نام کوتاه است» — E-02 |
| Latin characters | «نام باید فارسی باشد» — E-02 |
| Invalid characters | «کاراکتر نامعتبر در نام» — E-02 |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF PERSIAN NAME VALIDATION RULE**

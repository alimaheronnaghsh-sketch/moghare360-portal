# MOGHARE360 — Iranian Plate Validation Rule

**Field:** Iranian vehicle license plate  
**Status:** PLANNED_NOT_IMPLEMENTED  
**Error category:** E-02 INVALID_FORMAT, E-03 DUPLICATE_RISK  
**SQL:** No SQL required

---

## Scope

Validates Iranian vehicle plates for **vehicle registration** and **jobcard** vehicle binding. **No free-text plate entry** — structured segmented input only.

---

## Iranian Plate Structure Planning

Standard segmented format (classic):

```
[XX] | [آ-ی] | [XXX] | [XX]
  │       │        │      └── Series / city code (2 digits)
  │       │        └── Three-digit number
  │       └── Persian letter (single character selector)
  └── Province / region code (2 digits)
```

Stored canonical form: normalized concatenation or structured columns per schema (future implementation).

---

## Segmented Plate Input Concept

| Segment | Control type |
|---------|--------------|
| Province code (2 digits) | Numeric segment or province dropdown mapping to code |
| Letter | **Persian letter selector** — dropdown of valid plate letters |
| Three-digit number | Numeric segment (000–999) |
| Series (2 digits) | Numeric segment |

**No free-text plate entry** — user cannot type full plate as single string.

---

## Province / Series Fields

| Field | Rule |
|-------|------|
| Province code | Valid Iranian province code list (controlled reference) |
| Series | 2-digit numeric; validated with province rules |
| Letter | From approved Persian letter set for plates |

---

## Validation Rules

| Rule | Requirement |
|------|-------------|
| All segments required | E-01 if any segment empty |
| Segment format | Each segment matches allowed pattern |
| Complete plate | Assembled plate passes business format check |
| **Duplicate vehicle risk** | Reject if plate exists on another active vehicle — E-03 |

---

## Usage Context

| Context | Usage |
|---------|-------|
| Vehicle registration form | Primary plate capture |
| JobCard | Vehicle ref must have validated plate |
| Customer vehicle bind | Plate uniqueness across fleet |

---

## Error Message Policy

| Condition | User message (Persian concept) |
|-----------|----------------------------------|
| Incomplete segments | «تمام بخش‌های پلاک را تکمیل کنید» — E-01 |
| Invalid segment | «پلاک نامعتبر است» — E-02 |
| Duplicate plate | «این پلاک قبلاً ثبت شده است» — E-03 |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED** — segmented UI and validator in future phase only.

---

**END OF IRANIAN PLATE VALIDATION RULE**

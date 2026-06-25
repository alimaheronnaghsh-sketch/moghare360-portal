# MOGHARE360 — National ID Validation Rule

**Field:** Iranian National ID (کد ملی)  
**Status:** PLANNED_NOT_IMPLEMENTED  
**Error category:** E-02 INVALID_FORMAT, E-03 DUPLICATE_RISK  
**SQL:** No SQL required

---

## Scope

Validates Iranian national identification numbers for **customers** and **personnel** (HR employee records where national ID is collected).

---

## Format Rules

| Rule | Requirement |
|------|-------------|
| **10-digit requirement** | Exactly 10 digits after normalization |
| **Numeric-only** | Digits `0–9` only; reject letters and symbols |
| Input normalization | Strip spaces, dashes, Persian digits → Latin digits before validation |

---

## Rejected Patterns

| Pattern | Action |
|---------|--------|
| Length ≠ 10 | Reject — E-02 |
| Non-numeric characters | Reject — E-02 |
| **Repeated digit rejection** | Reject all-same-digit codes (e.g. `0000000000`, `1111111111`, … `9999999999`) — E-02 |
| Leading zeros invalid context | Valid if checksum passes; all-zero rejected as repeated |

---

## Checksum Algorithm Requirement

Iranian National ID check digit (digit 10):

1. Take digits `d1` … `d9` (positions 1–9)
2. Compute: `S = d1×10 + d2×9 + d3×8 + d4×7 + d5×6 + d6×5 + d7×4 + d8×3 + d9×2`
3. `R = S mod 11`
4. Let `d10` = digit 10:
   - If `R < 2`: require `d10 == R`
   - If `R >= 2`: require `d10 == 11 - R`
5. Fail checksum → E-02

**Do not expose algorithm steps to end user** — show generic format message only.

---

## Usage Context

| Entity | Table / context (reference) |
|--------|----------------------------|
| Customer | `erp_customers` national ID field |
| Personnel | HR employee intake |
| Contact | Where national ID is legally required |

---

## Duplicate Risk Check

| Check | Action |
|-------|--------|
| Existing customer with same national ID | Reject — E-03 |
| Policy | One active customer master per national ID |

---

## Error Message Policy

| Condition | User message (Persian concept) |
|-----------|----------------------------------|
| Empty required | «کد ملی الزامی است» — E-01 |
| Invalid format | «کد ملی نامعتبر است» — E-02 |
| Duplicate | «این کد ملی قبلاً ثبت شده است» — E-03 |

Internal log: `validation_failed`, field `national_id`, code `E-02` or `E-03`.

---

## Audit Requirement

| Event | Requirement |
|-------|-------------|
| Failed validation (optional policy) | Log `validation_failed` with actor, timestamp, field — no national ID in public log if policy restricts |
| Successful customer create/update | `erp_customer_core_history` per domain matrix |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED** — rule locked in Phase 17; no PHP validator, no form change.

---

**END OF NATIONAL ID VALIDATION RULE**

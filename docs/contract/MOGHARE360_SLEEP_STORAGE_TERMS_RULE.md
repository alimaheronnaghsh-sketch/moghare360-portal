# MOGHARE360 — Sleep / Storage Terms Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Principle

**Any sleep/storage term must be explicit** on the service contract. No implicit storage fees or indefinite vehicle retention without documented terms and customer acknowledgement.

---

## Vehicle Sleep / Storage Terms

| Term | Requirement |
|------|-------------|
| Storage reason | Dropdown: awaiting parts, awaiting approval, customer delay, workshop queue, other (note) |
| **Start date/time rule** | Explicit `storage_start_at` — server timestamp when vehicle enters sleep/storage |
| Location | Bay / yard / offsite — controlled selector |
| **Free storage period if any** | Number of days — `0` if none |
| **Chargeable storage period if any** | Begins after free period ends |
| **Daily storage fee concept** | Amount per day in IRR — `0` only if explicitly free |

---

## Customer Acknowledgement

| Requirement | Rule |
|-------------|--------|
| **Customer acknowledgement** | Acceptance record type covering storage clause |
| Contract section | Sleep/storage section must be complete before APPLIED |
| Extension | New acknowledgement if terms change |

Per `MOGHARE360_CUSTOMER_ACCEPTANCE_RECORD_RULE.md`.

---

## Exception Approval

| Scenario | Process |
|----------|---------|
| Waive storage fees | Manager approval + audit |
| Extend free period | Out-of-contract or storage amendment workflow |
| Force release | Owner approval if customer dispute |

---

## Delivery Delay Relation

| Relation | Rule |
|----------|--------|
| Delivery delayed by customer | Storage terms continue per contract |
| Delivery delayed by workshop | Storage fee may pause per owner policy — requires manager flag |
| **Delivery delay changes terms** | Triggers out-of-contract review if fee or date changes |

Links to JobCard CLOSED transition and output photo gate (Phase 18).

---

## Validation Rules

| Check | Error |
|-------|-------|
| Vehicle on premises > 24h without storage terms | E-01 warning → block paid ops |
| Chargeable period without daily fee | E-01 |
| Missing start date | E-01 |

---

## Audit Requirement

| Event | Detail |
|-------|--------|
| `storage_terms_set` | Contract apply |
| `storage_started` | Clock start |
| `storage_fee_accrued` | Daily batch preview (finance preview) |
| `storage_terms_amended` | Approval + new acknowledgement |
| `storage_exception_approved` | Fee waiver |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF SLEEP / STORAGE TERMS RULE**

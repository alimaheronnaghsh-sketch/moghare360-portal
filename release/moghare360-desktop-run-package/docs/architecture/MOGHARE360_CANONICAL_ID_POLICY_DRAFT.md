# MOGHARE360 — Canonical ID Policy (Draft)

**Database:** MOGHARE360_ERP  
**Status:** Draft policy — **not an executable SQL decision**  
**SQL:** No SQL required

---

## Discovery Summary

| Metric | Value |
|--------|-------|
| **Total dual int/bigint logical IDs** | **10** |

---

## Dual-Type Logical ID List

Each name appears as both **`int`** and **`bigint`** across tables in MOGHARE360_ERP:

| Logical ID | Domains affected |
|------------|------------------|
| **customer_id** | Customer, CRM, Finance, JobCard |
| **entity_id** | Audit, Rule, cross-cutting |
| **history_id** | Audit / History |
| **jobcard_id** | JobCard, Operation, Finance |
| **part_id** | Inventory, JobCard usage |
| **purchase_request_id** | Inventory / Purchase |
| **stock_location_id** | Inventory / Stock |
| **stock_movement_id** | Inventory / Stock |
| **supplier_id** | Inventory / Purchase |
| **vehicle_id** | Vehicle, Customer bindings |

---

## Draft Policy Rules

### Structural (Locked Until Owner Approval)

| Rule | Status |
|------|--------|
| **No ALTER ID types yet** | Locked |
| **No rebuild tables** | Locked |
| **No compatibility columns yet** | Locked |

### Application and Contract (Future Implementation)

| Rule | Requirement |
|------|-------------|
| **No implicit ID type assumptions** | Future code must not assume all IDs are int or bigint |
| **DTO/API/module contract must declare expected ID type** | Each endpoint and module contract documents `int` vs `bigint` per field |
| **Future SQL must explicitly handle ID compatibility when approved** | CAST/join only in ChatGPT-approved scripts with documented parent PK type |

---

## Canonical Type Guidance (Draft — Not Executed)

| Domain | New column default (draft) | Existing |
|--------|---------------------------|----------|
| Identity / Access | Document actual | Likely INT |
| Customer / Vehicle | BIGINT for new ERP FKs | Mixed |
| JobCard / Operation | BIGINT | Mixed |
| Inventory / Stock | BIGINT | Mixed |
| Finance Preview | Match JobCard parent | Mixed |
| Audit / History | Match referenced entity | Mixed |

---

## Module Contract ID Declaration (Future Template)

```text
Entity: erp_jobcards.jobcard_id
Canonical type: bigint
FK children must use: bigint
PHP type hint: int|string (64-bit safe)
API JSON: string recommended for bigint
```

---

## Risk Reminder

Mixed ID types break joins, FK adds, API serialization, and reporting if assumed uniform. Phase 05 documented 52 mismatch candidates; this policy addresses the 10 named logical IDs.

---

## Disclaimer

**This is a draft policy, not an executable SQL decision.**

No `ALTER COLUMN`, no shadow columns, no migration scripts authorized by this document.

---

## Product Boundary

- **Do not alter ID types yet**
- No SQL execution

---

**END OF CANONICAL ID POLICY DRAFT**

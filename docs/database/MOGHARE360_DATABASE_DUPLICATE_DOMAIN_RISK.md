# MOGHARE360 — Database Duplicate Domain Risk

**Database:** MOGHARE360_ERP  
**Source:** PHASE 04 SSMS read-only discovery (heuristic grouping)  
**Status:** Documentation only

---

## Discovery Metrics

| Metric | Value |
|--------|-------|
| Duplicate domain candidates | **63** |

### Duplicate Domain Count (Heuristic)

| Domain label | Count |
|--------------|-------|
| Customer | 11 |
| History / Audit | 7 |
| Operation | 7 |
| JobCard | 6 |
| HR | 6 |
| Inventory | 5 |
| Part | 4 |
| CRM | 3 |
| Finance | 3 |
| Payment | 3 |
| Stock | 3 |
| Vehicle | 2 |
| Purchase | 2 |
| Contract | 1 |
| **Total flagged** | **63** |

---

## Important Heuristic Limitation

Duplicate domain grouping was performed by **table-name substring matching**. This is useful for **risk discovery** but is **not final domain ownership**.

### Known False Positives

| Table | Incorrect classification | Actual domain |
|-------|-------------------------|---------------|
| `core_departments` | **Part** (because "departments" contains "part") | Identity / Access — organizational departments |
| `erp_hr_employment_contracts` | **Contract** | **HR employment** contracts — not customer service contracts |

### Ambiguous Cases

- `erp_customer_contracts` vs `erp_hr_employment_contracts` — both contain "contract"
- `erp_inventory_*` vs `erp_stock_*` vs `erp_parts` — inventory domain overlap
- `*_history` vs `*_change_history` vs `core_audit_logs` — audit proliferation

---

## Decision

**Table-name heuristics are useful for risk discovery but not final domain ownership.**

Before any SQL design:

1. Manual review of all 63 candidates
2. Assign **authoritative table** per business entity
3. Mark others as: history, preview, deprecated-hold, or soft-run
4. Document in Phase 05 domain ownership map

---

## Risk Analysis

### R-01 — Parallel Table Families

Customer domain (11 heuristic matches) may include `erp_customers`, `erp_customer_intakes`, `erp_customer_phones`, bindings, relations, and history — legitimate normalization vs duplicate risk depends on ownership map.

### R-02 — Inventory / Stock / Part Split

Inventory (5) + Stock (3) + Part (4) = 12 flagged tables. Risk of writing to wrong stock ledger if ownership unclear.

### R-03 — History / Audit Proliferation

7 History/Audit flagged tables plus `core_audit_logs` — ensure audit strategy is intentional, not duplicate logging paths.

### R-04 — Contract Name Collision

1 Contract-flagged table may be `erp_hr_employment_contracts` (false positive) or actual `erp_customer_contracts` family — **manual disambiguation required**.

---

## Domain Ownership Questions (Phase 05)

| Question | Domains affected |
|----------|------------------|
| Which table is customer master? | Customer (11) |
| Which table is authoritative stock balance? | Inventory, Stock, Part |
| Which contract table is service vs employment? | Contract, HR |
| Which payment table is preview master? | Payment, Finance |
| Which job card table is operational hub? | JobCard, Operation |

---

## Required Future Action

**Manual domain ownership map before SQL design.**

- No new tables in flagged domains until ownership confirmed
- No DROP of "duplicate" candidates without owner approval
- Cross-reference `MOGHARE360_DATABASE_DOMAIN_TABLE_MAP.md` (Phase 02)

---

## Product Boundary

- Documentation only
- No database schema change

---

**END OF DUPLICATE DOMAIN RISK**

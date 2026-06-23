# MOGHARE360 — Ambiguous Table Ownership Review

**Database:** MOGHARE360_ERP  
**Source:** PHASE 05 SSMS read-only discovery  
**Status:** Documentation only — manual confirmation required

---

## Discovery Metric

| Metric | Value |
|--------|-------|
| Ambiguous ownership rows | **38** |

38 tables received multiple domain signals during automated ownership scanning. Each requires **manual domain ownership confirmation before SQL design**.

---

## Heuristic Authority Limitation

- **Table-name heuristics are useful for risk discovery**
- **Table-name heuristics are not final ownership authority**
- **Manual domain ownership confirmation is required before SQL design**

Phase 04 documented 63 duplicate-domain substring matches. Phase 05 refined to **38 ambiguous ownership rows** with proposed owners — still requiring owner approval.

---

## Known False-Positive / Heuristic Warnings

### core_departments

**core_departments appears ambiguous because "departments" contains "part", but proposed owner is Identity / Access / Security.**

| Signal | Misleading match |
|--------|------------------|
| Substring "part" | Inventory / Parts domain |
| Actual function | Organizational departments for staff/RBAC |
| **Proposed owner** | Identity / Access / Security |

### erp_hr_employment_contracts

**erp_hr_employment_contracts appears ambiguous because it contains "contract", but proposed owner is HR.**

| Signal | Misleading match |
|--------|------------------|
| Substring "contract" | Customer service contracts |
| Actual function | Employee employment agreements |
| **Proposed owner** | HR |
| Distinct from | `erp_customer_contracts` (Customer domain) |

---

## Three-Signal Tables (Highest Ambiguity)

Tables matching **3 domain signals** — priority manual review:

| Table | Signals (typical) | Proposed owner | Review question |
|-------|-------------------|----------------|-----------------|
| `erp_customer_vehicle_change_history` | Customer, Vehicle, Audit/History | Audit / History (write); Customer/Vehicle (context) | Who initiates binding change workflow? |
| `erp_finance_part_margin_rules` | Finance, Part/Inventory, Rule | Finance Preview / Payment | Is margin rule finance policy or inventory policy? |
| `erp_jobcard_part_usage_history` | JobCard, Inventory, Audit/History | Audit / History (write); JobCard (context) | Usage history owned by job process or stock ledger? |

---

## Risk Categories

### R-01 — History Tables: Audit-Owned vs Domain-Owned

History tables may be:

| Model | Owner | When |
|-------|-------|------|
| Audit / History central | `Audit / History` | Append-only compliance log |
| Domain operational history | Controlling domain | Domain workflow replays state |

**Risk:** Wrong owner breaks audit queries vs operational dashboards.

Examples: `erp_jobcard_change_history`, `erp_customer_vehicle_change_history`, `erp_jobcard_part_usage_history`

---

### R-02 — Cross-Domain Tables Require Controller Decision

Tables linking two domains need a **primary controller**:

| Table | Domains involved | Controller decision needed |
|-------|------------------|---------------------------|
| `erp_customer_vehicle_bindings` | Customer + Vehicle | Customer intake vs Vehicle registration |
| `erp_jobcard_part_usage` | JobCard + Inventory | JobCard when on active job; Inventory for stock impact |
| `erp_part_reservations` | Inventory + JobCard | Reservation authority |
| `erp_finance_part_margin_rules` | Finance + Inventory | Pricing policy owner |

---

### R-03 — Finance / JobCard / Inventory Overlap

Before any SQL change in these domains:

1. Confirm authoritative cost table (`erp_jobcard_cost_*` → JobCard)
2. Confirm authoritative stock table (`erp_stock_balances` vs `erp_inventory_items`)
3. Confirm payment preview master (`erp_payments` → Finance Preview)
4. **No new parallel tables** in overlap zones

---

## Ambiguous Row Resolution Process

```
1. List 38 ambiguous tables from SSMS export
2. Assign proposed owner (this document + ownership map)
3. Owner / ChatGPT confirms or overrides
4. Lock in MOGHARE360_DOMAIN_OWNERSHIP_MAP.md (approved version)
5. Only then authorize SQL change candidates
```

---

## Product Boundary

- No SQL design until 38 rows reviewed
- No table deletion based on ambiguity
- No official accounting activation

---

**END OF AMBIGUOUS TABLE OWNERSHIP REVIEW**

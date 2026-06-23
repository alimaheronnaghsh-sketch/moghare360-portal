# MOGHARE360 — Cross-Domain FK Review

**Database:** MOGHARE360_ERP  
**Source:** PHASE 05 SSMS read-only discovery  
**Status:** Documentation only

---

## Discovery Metrics

| Classification | Count |
|----------------|-------|
| **CROSS_DOMAIN_FK_REVIEW** | **33** |
| **INTRA_DOMAIN_FK** | **44** |
| Total FK relationships reviewed | 77 |

*Aligns with Phase 03 total foreign keys: 77.*

---

## Interpretation

### INTRA_DOMAIN_FK: 44

**44 FK relationships are within proposed domains** — child and parent tables share the same domain owner per `MOGHARE360_DOMAIN_OWNERSHIP_MAP.md`.

Examples (conceptual):

- `core_user_roles` → `core_users`, `core_roles` (Identity)
- `erp_jobcard_cost_lines` → `erp_jobcard_cost_headers` (JobCard)
- `erp_stock_movements` → `erp_stock_locations` (Inventory)

These are lower risk for ownership disputes but still require cascade and orphan review.

### CROSS_DOMAIN_FK_REVIEW: 33

**33 FK relationships cross proposed domain boundaries** and require review.

Examples (conceptual):

- JobCard → Customer / Vehicle
- Part usage → Parts / JobCard
- Payment preview → JobCard
- Service operations → JobCards
- User audit → core_users

**Cross-domain FK is not automatically wrong** — ERP systems naturally link domains. Each cross-domain FK must have:

| Requirement | Detail |
|-------------|--------|
| Clear business ownership | Which domain controls the relationship lifecycle |
| Workflow responsibility | Which Workflow Engine state authorizes the link |
| Audit responsibility | Which audit table records changes |

---

## Risk Analysis

### R-01 — FK Change Before Ownership Locked

**No FK should be changed until domain ownership is locked.**

Adding, dropping, or altering FKs before Phase 05 ownership approval may wire the wrong domain graph.

### R-02 — Cross-Domain Without Workflow

Cross-domain FK without Workflow Engine gate allows writes that bypass domain controller (e.g. part usage without approved JobCard state).

### R-03 — Empty Tables Mask Orphans

Cross-domain FKs on empty tables (46 empty operational tables, Phase 04) pass integrity checks until real data exposes orphan rows.

---

## Required Future Review (Per Cross-Domain FK)

| Review item | Purpose |
|-------------|---------|
| **FK ownership** | Which domain owns the relationship |
| **Cascade behavior** | ON DELETE / ON UPDATE rules |
| **Delete/update behavior** | Soft delete vs hard delete impact |
| **Workflow authority** | State required before insert/update |
| **Audit responsibility** | Which `*_history` table logs the change |

---

## Review Priority Tiers

| Tier | Cross-domain paths | Priority |
|------|-------------------|----------|
| 1 | Customer ↔ Vehicle ↔ JobCard | Highest — core workshop flow |
| 2 | JobCard ↔ Inventory (part usage, reservations) | High — stock impact |
| 3 | JobCard ↔ Finance Preview (cost, payment) | High — preview only |
| 4 | Identity ↔ all domains (user_id references) | Medium — auth context |
| 5 | CRM ↔ Customer | Medium — follow-up linkage |
| 6 | HR ↔ Identity | Medium — employee-user link |
| 7 | Rule ↔ Operation (approvals) | Medium — workflow |

---

## Good Signal (Phase 04 Carry-Forward)

**Disabled/untrusted foreign keys: 0** — all 77 FKs are trusted and enabled. Cross-domain review is about **correctness and ownership**, not FK trust repair.

---

## Product Boundary

- Documentation only
- No FK ALTER until ownership approved
- No SQL execution

---

**END OF CROSS-DOMAIN FK REVIEW**

# MOGHARE360 — Cross-Domain Interaction Rules

**Database:** MOGHARE360_ERP  
**Source:** Phase 05 FK discovery  
**Status:** Documentation only

---

## FK Inventory

| Classification | Count |
|----------------|-------|
| **CROSS_DOMAIN_FK_REVIEW** | **33** |
| **INTRA_DOMAIN_FK** | **44** |

---

## Interpretation

### INTRA_DOMAIN_FK: 44

Relationships within a single canonical domain owner. Lower ownership dispute risk; still require cascade and workflow review.

### CROSS_DOMAIN_FK_REVIEW: 33

Relationships spanning two or more canonical domains. **Cross-domain FK is not automatically wrong** — ERP systems require cross-links (JobCard → Customer, Part Usage → Parts, etc.).

---

## Cross-Domain FK Requirements

Every cross-domain FK must have documented:

| Requirement | Owner |
|-------------|-------|
| **Domain owner** | Which domain owns the FK relationship lifecycle |
| **Workflow owner** | Which workflow authorizes create/update/delete |
| **Audit owner** | Which history table records changes |
| **Validation owner** | Which Validation Engine rules apply |
| **Delete/update behavior** | Soft vs hard delete; restrict vs cascade |
| **Cascade behavior** | ON DELETE / ON UPDATE policy |

---

## Interaction Patterns (Canonical)

| From domain | To domain | Pattern | Workflow |
|-------------|-----------|---------|----------|
| Customer | Vehicle | Bind vehicle to customer | Customer + Vehicle joint validation |
| Vehicle | JobCard | Job references vehicle | JobCard creation workflow |
| JobCard | Operation | Service on job | Operation tied to jobcard state |
| JobCard | Inventory | Part usage | Inventory service after job approval |
| JobCard | Finance Preview | Cost/payment preview | Post-QC / delivery gate |
| Operation | CRM | Post-delivery follow-up | CLOSED jobcard trigger |
| Identity | All | `user_id` actor context | Session + permission on every write |
| Rule | Operation/Inventory | Approval gates | Rule evaluation before APPROVED |

---

## Forbidden (This Phase)

| Action | Status |
|--------|--------|
| **No FK change is allowed yet** | Locked |
| **No SQL change is allowed yet** | Locked |
| ADD/DROP FK without ownership approval | Forbidden |
| Cross-domain write bypassing service layer | Forbidden |

---

## Review Priority (33 Cross-Domain FKs)

1. Customer ↔ Vehicle ↔ JobCard (core workshop path)
2. JobCard ↔ Inventory (stock impact)
3. JobCard ↔ Finance Preview (preview payment)
4. Identity ↔ operational tables (actor integrity)
5. Rule ↔ Operation (approval path)

---

## Product Boundary

- Documentation only
- No FK ALTER until Phase 07+ with approved SQL

---

**END OF CROSS-DOMAIN INTERACTION RULES**

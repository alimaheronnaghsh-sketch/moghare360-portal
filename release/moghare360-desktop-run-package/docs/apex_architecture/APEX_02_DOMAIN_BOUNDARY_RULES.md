# APEX 02 — Domain Boundary Rules

## Domain Boundary Lock

ApexMahinERP is organized into **eight locked domains**. Each domain owns its aggregates, invariants, and write authority. Domain boundaries are **architectural law** — not implementation suggestions.

---

## Eight Locked Domains

| # | Domain | Primary Responsibility |
|---|--------|------------------------|
| 1 | **Organization Domain** | Tenants, branches, organizational structure |
| 2 | **Identity & Access Domain** | Users, authentication, roles, permissions |
| 3 | **Finance Domain** | Ledger, AR, AP, cash, credit, journal entries |
| 4 | **Procurement Domain** | RFQ, purchase orders, GRN, vendor management, landed cost |
| 5 | **Inventory Domain** | Items, warehouses, stock ledger, reorder policies |
| 6 | **CRM & Marketing Domain** | Leads, campaigns, appointments, source attribution |
| 7 | **HR Domain** | Skills, attendance, technician performance, bonus rules |
| 8 | **Job & Technical Intelligence Domain** | JobCards, QC, delivery, warranty, cases, symptoms, root causes, repair intelligence |

---

## Domain Ownership Principle

- Each domain **owns** its data and business rules.
- Each domain exposes **controlled operations** through service boundaries.
- No domain may assume it can read or mutate another domain’s persistence directly.

---

## Core Boundary Rule

> **No entity is allowed to directly touch another domain’s table.**

Domain interaction must happen **only through service boundaries** — application services, domain events, or published APIs. Never through shared-table shortcuts.

---

## Anti-Corruption Boundary

Each consuming domain must treat external domain contracts through an **anti-corruption layer**:

- Translate external DTOs into local value objects
- Never leak foreign domain table shapes into local code
- Never embed foreign domain SQL in local repositories
- Validate inbound references (IDs, codes) through owning-domain services

This prevents “schema creep” where one domain’s tables become everyone’s lookup tables.

---

## Integration Rules

| Rule | Requirement |
|------|-------------|
| Cross-domain reads | Via published query APIs or read models owned by source domain |
| Cross-domain writes | Via command APIs exposed by owning domain only |
| Synchronous calls | Service-to-service with explicit contracts |
| Asynchronous events | Domain events with versioned payloads |
| Shared database server | Permitted at infrastructure level; **logical separation still mandatory** |

Integration must be **API/service-based**. Direct cross-domain repository access is forbidden.

---

## Physical Schema Implication (Future — Not Phase 0)

When physical schema design begins (after sign-off):

- Tables must be grouped under domain ownership
- Foreign keys across domains require explicit ownership and reference contracts
- Shared lookup tables without an owning domain are forbidden
- Finance tables must not receive operational writes from non-Finance domains

Phase 0 defines **logical** boundaries only. No physical tables are designed in this phase.

---

## Violation Examples (Forbidden)

| Violation | Why Forbidden |
|-----------|---------------|
| Job module UPDATE on inventory stock table | Inventory Domain owns stock mutations |
| CRM module INSERT into ledger | Finance Domain owns journal entries |
| Technical Intelligence direct UPDATE on JobCard status | Job Domain owns operational truth |
| HR module reading user password hash table | Identity Domain owns credentials |

---

## Cursor Statement

Cursor documented domain boundaries only. **Cursor did not decide the next roadmap step.**

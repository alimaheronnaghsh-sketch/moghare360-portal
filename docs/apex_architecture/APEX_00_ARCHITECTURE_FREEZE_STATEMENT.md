# APEX 00 — Architecture Freeze Statement

## Phase

**APEX PHASE 0 — Architecture Freeze & Domain Model Lock**

## Official Product Statement

**ApexMahinERP** is a specialized ERP for the automotive workshop industry with an internal technical knowledge engine and an intelligent international learning network.

ApexMahinERP is an **industrial ERP product**, not a generic software project, prototype dump, or ad-hoc collection of screens tied to unrelated tables.

## Product Identity

| Attribute | Definition |
|-----------|------------|
| Industry | Automotive repair and service workshops |
| Product class | Industrial ERP |
| Differentiator 1 | Internal technical knowledge engine |
| Differentiator 2 | Intelligent international learning network |
| Architecture posture | Domain-separated, API-first, PWA-ready |
| Strategic focus | Specialized, intelligent, scalable |

## What ApexMahinERP Is

- A **domain-separated** enterprise platform for workshop operations
- An **API-first** system designed for web, mobile PWA, and Windows desktop clients
- A product that combines **ERP operations** with **technical intelligence**
- A platform built for **long-term industrial memory** — symptoms, root causes, repair procedures, failure patterns
- A foundation for **cross-workshop learning** in later phases

## What ApexMahinERP Is Not

The following are explicitly **rejected** as product direction:

- Generic, noisy, table-dump ERP with unbounded entities and no domain ownership
- Spreadsheet-style CRUD without operational gates (prepayment, QC, delivery, warranty)
- Monolithic schema where every module reads and writes every other module’s data
- Public SaaS or production activation before architecture and ownership are signed off
- Physical database design before architecture freeze and logical model approval

## Architecture Freeze Declaration

As of **APEX PHASE 0**, the following are **frozen** pending user review and sign-off:

1. **Product statement** — specialized automotive workshop ERP with technical intelligence
2. **MVP scope** — Core ERP, Job & Operations, Technical Intelligence MVP, UX & Platform
3. **Phase 2+ scope** — ML prediction, predictive maintenance, technician recommendation, cross-workshop network
4. **Eight locked domains** — Organization, Identity & Access, Finance, Procurement, Inventory, CRM & Marketing, HR, Job & Technical Intelligence
5. **Domain boundary rule** — no direct cross-domain table access; service boundaries only
6. **Clean restart sequence** — freeze → logical model → sign-off → ownership rules → physical schema (later)

## Phase 0 Deliverable Type

This phase produces **documentation and architecture decisions only**.

- No physical schema
- No SQL
- No migrations
- No runtime application changes

## Signoff Gate

Physical schema design, SQL scripts, and database migrations **must not begin** until:

1. Architecture freeze is approved
2. Logical domain model diagram is completed and approved
3. Data ownership rules are defined and approved

## Cursor Statement

Cursor implemented this document as part of Phase 0 documentation only. **Cursor did not decide the next roadmap step.**

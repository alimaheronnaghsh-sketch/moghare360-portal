# APEX 01 — Product Scope: MVP and Phase 2+

## Product Market Fit Focus

ApexMahinERP targets **automotive repair and service workshops** that need:

- Controlled operational workflow from intake to delivery and warranty
- Integrated finance, inventory, and procurement without operational pollution of the ledger
- Technical memory that improves diagnosis and repair quality over time
- A luxury, responsive experience across web, mobile PWA, and Windows desktop

Product Market Fit is achieved when workshops can run daily operations, measure technician performance, and accumulate reusable technical intelligence — not when every possible ERP feature exists on day one.

---

## MVP Scope (Included)

### 1. Core ERP

| Area | MVP Capability |
|------|----------------|
| Organization | Branch, Role, Permission |
| Finance Kernel | Ledger, AR, AP, Cash, Credit |
| Procurement | Domestic + foreign procurement, landed cost |
| Inventory Engine | Stock control and warehouse operations |
| CRM | Customer relationship with attribution |
| Operational HR | Skill, KPI, Productivity |

### 2. Job & Operations

| Capability | Description |
|------------|-------------|
| JobCard Workflow | End-to-end job lifecycle |
| Prepayment Gate | Financial gate before work proceeds |
| QC Gate | Quality control checkpoint |
| Delivery Gate | Controlled vehicle/job delivery |
| Warranty Loop | Post-delivery warranty handling |

### 3. Technical Intelligence MVP

| Capability | Description |
|------------|-------------|
| Case Structure | Structured technical case records |
| Symptom classification | Classified symptom input |
| Root Cause recording | Documented root cause |
| Repair Steps | Procedure steps per case |
| Used parts | Parts linkage to repair |
| Failure statistics | Frequency-based patterns |
| Simple rule-based suggestion | Ranked probability suggestions |

### 4. UX & Platform

| Capability | Description |
|------------|-------------|
| Luxury UI Design | Premium workshop-grade UX |
| Responsive Web | Full responsive web application |
| Full mobile PWA | Installable progressive web app |
| Windows Desktop Ready | Desktop client readiness |
| API-First Architecture | All clients consume domain APIs |

---

## Phase 2+ Scope (Future — Not MVP)

The following are **planned** but **excluded from MVP implementation**:

| Capability | Phase |
|------------|-------|
| Machine Learning Failure Prediction | Phase 2+ |
| Predictive Maintenance Engine | Phase 2+ |
| Technician Recommendation Engine | Phase 2+ |
| Cross-Workshop Learning Network | Phase 2+ |
| International Technical Intelligence Exchange | Phase 2+ |

---

## Explicitly Excluded (MVP and Phase 0)

| Exclusion | Reason |
|-----------|--------|
| Public SaaS activation | Architecture and ownership not yet signed off |
| Production activation | Clean restart sequence not complete |
| Physical database schema | Precedes architecture sign-off |
| SQL and migrations | Forbidden until ownership rules defined |
| Generic ERP feature sprawl | Conflicts with specialized product focus |
| Cross-domain direct table access | Violates domain boundary rule |
| ML / advanced prediction engines | Phase 2+ only |

---

## Scope Lock Statement

MVP delivers a **complete workshop operations platform** with **foundational technical intelligence**. Phase 2+ extends intelligence with ML, network effects, and international exchange.

No scope item in Phase 2+ may be pulled into MVP without explicit Project Controller approval.

## Cursor Statement

Cursor documented scope only. **Cursor did not decide the next roadmap step.**

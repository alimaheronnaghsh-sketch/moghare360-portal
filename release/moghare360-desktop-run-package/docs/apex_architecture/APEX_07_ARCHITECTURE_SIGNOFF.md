# APEX 07 — Architecture Signoff

## Signoff Status

**PENDING USER REVIEW**

---

## Frozen Items (Phase 0)

### Architecture Freeze Statement

- ApexMahinERP is an industrial ERP product for automotive workshops
- Specialized product with technical knowledge engine and international learning network (Phase 2+)
- Explicit rejection of generic, noisy, table-dump ERP
- API-first, domain-separated, PWA-ready architecture

### Product Scope Lock

| Scope | Status |
|-------|--------|
| MVP — Core ERP, Job & Operations, Technical Intelligence MVP, UX & Platform | Locked in documentation |
| Phase 2+ — ML, predictive maintenance, cross-workshop network | Locked as future scope |
| Public SaaS activation | Excluded |
| Production activation | Excluded |

### Domain Boundary Lock

Eight locked domains with **no direct cross-domain table access**:

1. Organization Domain
2. Identity & Access Domain
3. Finance Domain
4. Procurement Domain
5. Inventory Domain
6. CRM & Marketing Domain
7. HR Domain
8. Job & Technical Intelligence Domain

Service boundaries and anti-corruption layers are mandatory.

### Clean Restart Lock

Ordered sequence enforced:

1. Freeze Architecture
2. Design Logical Domain Model Diagram
3. Approval & Sign-Off
4. Define Data Ownership Rules
5. Start Physical Schema Design

**No tables, no SQL, no physical schema before Steps 1–4 complete and approved.**

---

## Pending Items (Not Phase 0)

| Item | Status |
|------|--------|
| Logical Domain Model Diagram | Pending |
| Data Ownership Matrix (full) | Pending |
| Service Boundary Map | Pending |
| Physical Schema Design | Blocked until sign-off |

---

## Approval Checklist

| Check | Reviewer | Date | Approved |
|-------|----------|------|----------|
| Product statement accurate | | | ☐ |
| MVP scope complete | | | ☐ |
| Phase 2+ scope acceptable | | | ☐ |
| Eight domains correct | | | ☐ |
| Boundary rules acceptable | | | ☐ |
| Clean restart sequence accepted | | | ☐ |
| Preliminary ownership rules acceptable | | | ☐ |

---

## Signoff Authority

Approval requires **Project Controller / User** sign-off before any physical schema work begins.

---

## Cursor Statement

Cursor prepared this signoff document. **Cursor did not decide the next roadmap step.**

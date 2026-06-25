# MOGHARE360 — Read-Only Build Plan

**Status:** Planning baseline — Documentation only  
**Phase:** PHASE 08

---

## First Layer Principle

**The first implementation layer must be read-only.**

All Phase 09+ runtime work (when authorized) begins with inspection and visibility — not writes.

---

## Read-Only Page Rules

| Rule | Requirement |
|------|-------------|
| No insert/update/delete | Read-only pages must not mutate MOGHARE360_ERP |
| No workflow state change | Display states only; no transition buttons that write |
| No auth/permission bypass | Session + permission guards required on every page |
| No customer portal activation | Staff ERP visibility only |
| No SaaS behavior | No billing, subscription, or multi-tenant UI |
| No official accounting/payment/tax | Finance views show PREVIEW_ONLY banners |

---

## Read-Only Build Targets

| Target | Purpose | Source docs |
|--------|---------|-------------|
| **Admin structure overview** | 12 domains, flow diagram, product boundaries | Canonical domain model, master prompt |
| **Domain ownership viewer** | Table→domain map, ambiguous flags | Domain ownership map |
| **Validation rule viewer** | Per-domain validation rules | Validation rule matrix |
| **Workflow transition viewer** | Allowed/forbidden transitions | Workflow state transition contract |
| **Permission gate viewer** | Gate matrix (conceptual) | Permission workflow gate matrix |
| **Audit contract viewer** | Audit fields and required actions | Workflow audit event contract |
| **Database readiness viewer** | PK/FK/empty table stats | Structure health, gap analysis |
| **Module readiness viewer** | Readiness levels per module | Module contract matrix |

---

## Technical Approach (Future Phase 09+)

```
User → existing auth → permission read guard → read-only helper → static/SQL SELECT → RTL view
```

- Helpers live in `public_html/includes/` only when Phase 09 explicitly allows
- SELECT queries: no PII dump; aggregate/count preferred
- Link from existing command center (small safe link pattern from prior phases)

---

## Build Sequence

1. Architecture overview + domain map (foundation)
2. Validation + workflow + permission + audit viewers (contracts)
3. Database risk + module readiness (data posture)
4. Per-module read-only dashboards (module sequence doc)

---

## Forbidden in Read-Only Layer

- Submit forms that POST writes
- Workflow transition buttons
- SQL package execution UI
- Payment gateway or accounting export
- Public customer-facing routes

---

## Product Boundary

- **Read-only build plan** — planning only
- **Do not create PHP yet**

---

**END OF READ-ONLY BUILD PLAN**

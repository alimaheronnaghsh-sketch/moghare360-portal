# MOGHARE360 — Customer Web View Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Principle

**Customer web view is planned only.**  
**No runtime activation in PHASE 22.**  
**No public portal deployment in PHASE 22.**

Customer-facing access is a future capability requiring explicit owner activation after implementation — not part of Phase 22 delivery.

---

## Architecture Boundary

| Rule | Requirement |
|------|-------------|
| **Domain remains Mirror Only** | moghareh360.ir |
| **No data storage on domain** | LOCKED |
| **No business logic on domain** | LOCKED |
| Future access | Approved gateway/tunnel to **local laptop server** read API |
| Auth | Customer-specific token — separate from staff auth |

---

## Future Customer Visible Data (Approved Limited Fields Only)

When activated in a future phase, portal may show **only**:

| Field | Sanitized content |
|-------|-------------------|
| **JobCard status summary** | e.g. «در حال سرویس» — enum labels only |
| **Delivery readiness** | Ready / not ready — no internal QC notes |
| **Approved service summary** | Customer-facing service names from contract |
| **Non-sensitive appointment/reminder info** | Next reminder date, workshop phone |

All data served from local MOGHARE360_ERP — streamed, not persisted on host.

---

## Must NOT Expose

| Data | Status |
|------|--------|
| **Internal notes** | FORBIDDEN |
| **Supplier data** | FORBIDDEN |
| **Employee data** | FORBIDDEN |
| **Cost/profit details** | FORBIDDEN |
| **Official accounting records** | FORBIDDEN |
| **Payment gateway** | FORBIDDEN |
| **Audit/internal security data** | FORBIDDEN |
| Diagnostic PDF full content | Policy gate — summary only if ever |
| Input/output workshop photos | Policy gate — not default |
| Other customers' data | FORBIDDEN — strict customer scope |

**Customer portal must not expose private/internal workshop data** — LOCKED.

---

## Activation Gate

| Requirement | Phase |
|-------------|-------|
| Phase 22 planning | This document |
| Runtime implementation | Future phase |
| Owner sign-off | Required before activation |
| Security review | Network + auth audit |
| E-10 block | Portal write/accounting/payment until approved |

Roadmap lock: public customer portal activation was gated to Phase 22 **approval** — Phase 22 delivers **plan only**, not activation.

---

## Validation / Workflow (Future)

- Read-only customer routes — no master data write
- Complaint submit (future) → Validation → Workflow → local DB → Audit
- No bypass of staff CRM for sensitive actions

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED** — **no public portal pages**, no PHP, no deploy in Phase 22.

---

**END OF CUSTOMER WEB VIEW RULE**

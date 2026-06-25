# MOGHARE360 — Contract Authorization Engine Plan

**Database:** MOGHARE360_ERP  
**Status:** Planning baseline — PHASE 19  
**Implementation:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Purpose

The **Contract and Authorization Engine** governs what workshop operations are permitted for each JobCard based on a signed service contract, customer authorization level, cost ceiling, and storage terms. It prevents unauthorized work, unapproved spending, and ambiguous customer agreements before operational execution.

---

## Contract Role in Workshop Operation

| Role | Detail |
|------|--------|
| Legal/operational boundary | Defines authorized services and limits |
| Authorization gate | Operations Engine checks contract before execute |
| Cost control | Ceiling blocks paid work above approved amount |
| Storage clarity | Sleep/storage terms explicit — no implicit charges |
| Evidence | Customer acceptance + PDF archive for disputes |
| Workflow integration | Contract state drives JobCard and operation transitions |

**Contract must control operation authorization.**

---

## Binding to Customer, Vehicle, and JobCard

| Entity | Binding |
|--------|---------|
| **Customer** | Contract party — identity from customer master |
| **Vehicle** | Subject vehicle — plate/VIN from vehicle master |
| **JobCard** | Operational instance — one active service contract per JobCard path |

No orphan contract — every contract row references all three where applicable.

---

## Contract Before Operational Execution Principle

```
Customer + Vehicle intake
    │
    ▼
Contract drafted (template + terms)
    │
    ▼
Customer acceptance recorded
    │
    ▼
Contract APPLIED ──► JobCard may proceed to paid operations
    │
    ▼
Operations checked against authorization level + ceiling
```

Paid or scope-expanding work **must not execute** without applied contract (or documented emergency path with post-hoc approval).

---

## Authorization Before Out-of-contract Work

| Scenario | Requirement |
|----------|-------------|
| Service not in original contract | Out-of-contract approval workflow |
| Diagnostic changes scope | New approval before additional work |
| Additional parts required | Approval if outside authorized scope |
| **Any out-of-contract operation must require approval** | LOCKED |

---

## Cost Ceiling Control

| Rule | Detail |
|------|--------|
| Customer-approved ceiling on contract | Required before paid work |
| Work estimated above ceiling | Block until approval |
| **Any cost above customer-approved ceiling must require approval** | LOCKED |
| Finance preview | Preview amounts — not official accounting |

Per `MOGHARE360_COST_CEILING_RULE.md`.

---

## Storage / Sleep Term Control

| Rule | Detail |
|------|--------|
| **Any sleep/storage term must be explicit** | LOCKED |
| Free period, chargeable period, daily fee | Documented on contract |
| Customer acknowledgement | Required for storage terms |

Per `MOGHARE360_SLEEP_STORAGE_TERMS_RULE.md`.

---

## Workflow Requirement

**UI → Validation Engine → Workflow Engine → Database → Audit Log**

| Component | Contract role |
|-----------|---------------|
| Validation Engine | Required fields, ceiling format, acceptance evidence |
| Workflow Engine | Contract states; out-of-contract approval states |
| Database | Contract master + JobCard FK |
| Audit Log | Acceptance, approval, ceiling breach attempts |

Per `MOGHARE360_CONTRACT_TO_WORKFLOW_BINDING_RULE.md`.

---

## Audit Requirement

| Event | When |
|-------|------|
| `contract_created` | New contract draft |
| `contract_acceptance_recorded` | Customer acceptance |
| `contract_applied` | Contract active on JobCard |
| `out_of_contract_approval` | Scope/cost exception |
| `ceiling_breach_blocked` | Operation blocked above ceiling |
| `contract_pdf_archived` | PDF registered |

Per `MOGHARE360_WORKFLOW_AUDIT_EVENT_CONTRACT.md`.

---

## Local-Only Storage Principle

| Asset | Storage |
|-------|---------|
| Contract PDF archive | Owner laptop server only |
| Acceptance evidence refs | Local index + optional local attachment |
| **No contract file storage on domain** | moghareh360.ir |
| **No cloud contract storage** | LOCKED |

Per `MOGHARE360_CONTRACT_PDF_ARCHIVE_RULE.md` and Phase 16/18 storage boundaries.

---

## Phase 19 Modules (Planned)

| Module | Document |
|--------|----------|
| Template control | `MOGHARE360_CONTRACT_TEMPLATE_CONTROL_RULE.md` |
| Authorization levels | `MOGHARE360_CUSTOMER_AUTHORIZATION_LEVEL_RULE.md` |
| Cost ceiling | `MOGHARE360_COST_CEILING_RULE.md` |
| Sleep/storage | `MOGHARE360_SLEEP_STORAGE_TERMS_RULE.md` |
| Out-of-contract | `MOGHARE360_OUT_OF_CONTRACT_APPROVAL_RULE.md` |
| Customer acceptance | `MOGHARE360_CUSTOMER_ACCEPTANCE_RECORD_RULE.md` |
| PDF archive | `MOGHARE360_CONTRACT_PDF_ARCHIVE_RULE.md` |
| Workflow binding | `MOGHARE360_CONTRACT_TO_WORKFLOW_BINDING_RULE.md` |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED** — no PHP, no PDF generation, no signature capture, no SQL in Phase 19.

---

## Product Boundary

- No production SaaS · No public portal · No official accounting · No payment gateway
- Finance preview only for cost display

---

**END OF CONTRACT AUTHORIZATION ENGINE PLAN**

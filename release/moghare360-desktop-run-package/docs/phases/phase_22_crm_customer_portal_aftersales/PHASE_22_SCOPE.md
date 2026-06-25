# PHASE 22 — CRM / Customer Portal / After-sales — Scope

**Phase:** PHASE 22 — CRM / CUSTOMER PORTAL / AFTER-SALES  
**Status:** Documentation and planning only  
**SQL:** No SQL required

---

## Phase Objective

Design and lock the **CRM, controlled Customer Portal, and After-sales** layer for MOGHARE360 ERP before final handover.

---

## Locked Rules

| Rule | Status |
|------|--------|
| Database: **MOGHARE360_ERP** | LOCKED |
| Local laptop server = system of record | LOCKED |
| moghareh360.ir = Mirror Only | LOCKED |
| **No customer data / files / business logic on domain** | LOCKED |
| **UI → Validation Engine → Workflow Engine → Database → Audit Log** | LOCKED |
| CRM follow-up binds Customer + JobCard (where applicable) | LOCKED |
| Complaints follow workflow | LOCKED |
| Satisfaction via controlled form rules | LOCKED |
| Customer score = calculated/preview only — no auto financial action | LOCKED |
| **Customer portal planned only** — no runtime activation in Phase 22 | LOCKED |
| Portal must not expose internal/accounting/payment data | LOCKED |
| No SaaS / official accounting / payment gateway | LOCKED |
| No UI→DB / validation / workflow / audit bypass | LOCKED |

---

## PHASE 22 Modules

1. 3-day Follow-up Automation
2. Complaint Handling
3. Customer Satisfaction
4. Customer Score
5. Service Reminder
6. Customer Web View
7. Upsell / Loyalty
8. After-sales Warranty Tracking

---

## Allowed Scope

- `docs/phases/phase_22_crm_customer_portal_aftersales/` (5 files)
- `docs/crm/` (10 files)

---

## Forbidden Scope

- PHP runtime, SQL, schema, `public_html`, auth, config, release
- CRM/portal runtime; public customer portal pages; domain data exposure
- Payment gateway, official accounting, SaaS, deploy
- Commit, push

---

## Phase 22 Constraints

- **PHASE 22 is documentation/planning only**
- **No runtime CRM implementation**
- **No customer portal runtime activation**
- **No PHP creation**
- **No SQL creation**
- **No database modification**
- **No existing form modification**
- **No public portal deployment**
- **No SaaS / official accounting / payment gateway activation**

---

**END OF SCOPE**

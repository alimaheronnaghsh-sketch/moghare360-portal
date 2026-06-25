# MOGHARE360 — CRM / Customer Portal / After-sales Plan

**Database:** MOGHARE360_ERP  
**Status:** Planning baseline — PHASE 22  
**Implementation:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Purpose

The **CRM and after-sales layer** extends workshop operations from delivery through follow-up, complaint resolution, satisfaction measurement, reminders, loyalty, and warranty tracking — while keeping **customer portal as planned-only** until explicit owner activation after Phase 22.

---

## Scope: Delivery to Warranty

```
JobCard CLOSED (delivery complete)
    │
    ├── 3-day follow-up automation
    ├── Customer satisfaction survey
    ├── Service reminders (date/mileage/type)
    │
    ├── Complaints (any time) ──► workflow resolution
    │
    ├── Customer score (preview/classification)
    ├── Upsell / loyalty campaigns (owner-approved)
    │
    └── After-sales warranty tracking
              │
              └── Links to Phase 21 return/defective flow

Customer web view (future) ──► limited read-only via approved gateway
                               NOT activated in Phase 22
```

---

## Dependencies

### Customer Dependency

| Rule | Detail |
|------|--------|
| CRM reads customer master | Customer module owns writes |
| Contact validation | Phase 17 mobile rules for outreach |
| CRM must not edit customer master | E-06 cross-domain block |

### Vehicle Dependency

| Rule | Detail |
|------|--------|
| Follow-up / reminder | Vehicle ref for mileage/service type |
| Warranty | Vehicle + part/service linkage |

### JobCard Dependency

| Rule | Detail |
|------|--------|
| **Follow-up binds to JobCard where applicable** | Post-delivery follow-up requires CLOSED JobCard |
| Complaint | Optional JobCard ref |
| Satisfaction | Tied to delivered JobCard |

### Delivery Dependency

| Rule | Detail |
|------|--------|
| Follow-up clock | Starts at delivery close timestamp |
| Satisfaction timing | After delivery per rule |
| Warranty start | Often delivery date or part install date |

Per Phase 20 delivery rules.

---

## Workflow Requirement

**UI → Validation Engine → Workflow Engine → Database → Audit Log**

| Process | Workflow |
|---------|----------|
| Follow-up | PENDING → CONTACTED / NO_ANSWER / ISSUE_REPORTED → CLOSED |
| Complaint | DRAFT → SUBMITTED → UNDER_REVIEW → RESOLVED → CLOSED |
| Warranty claim | Approval workflow |
| Campaign | Owner-approved before send |

CRM module owns follow-up lifecycle per domain matrix — `crm.followup`, `crm.upsell` permissions.

---

## Audit Requirement

| Target | Events |
|--------|--------|
| `erp_crm_history` | All CRM mutations |
| Audit log | follow-up, complaint, satisfaction, score change, campaign |
| **No audit bypass** | E-09 on failure |

---

## Customer Portal Boundary

| Rule | Status |
|------|--------|
| **Customer portal is planned only in PHASE 22** | LOCKED |
| **No public portal runtime activation in PHASE 22** | LOCKED |
| Future activation | Explicit owner sign-off after implementation phase |
| Data exposed | Approved limited fields only — see `MOGHARE360_CUSTOMER_WEB_VIEW_RULE.md` |
| Must not expose | Internal notes, costs, accounting, payment, staff data |

---

## Mirror-Only Domain Rule

| Rule | Requirement |
|------|-------------|
| moghareh360.ir | Mirror Only — Phase 16 |
| **No customer data storage on domain** | LOCKED |
| **No file storage on domain** | LOCKED |
| **No business logic on domain** | LOCKED |
| Future portal | Gateway to local server — not host-side ERP |

---

## Phase 22 Module Documents

| Module | Document |
|--------|----------|
| 3-day follow-up | `MOGHARE360_3_DAY_FOLLOWUP_AUTOMATION_RULE.md` |
| Complaints | `MOGHARE360_COMPLAINT_HANDLING_RULE.md` |
| Satisfaction | `MOGHARE360_CUSTOMER_SATISFACTION_RULE.md` |
| Customer score | `MOGHARE360_CUSTOMER_SCORE_RULE.md` |
| Service reminder | `MOGHARE360_SERVICE_REMINDER_RULE.md` |
| Customer web view | `MOGHARE360_CUSTOMER_WEB_VIEW_RULE.md` |
| Upsell / loyalty | `MOGHARE360_UPSELL_LOYALTY_RULE.md` |
| Warranty | `MOGHARE360_AFTERSALES_WARRANTY_TRACKING_RULE.md` |

---

## Product Boundary

- No production SaaS · No public portal activation · No official accounting · No payment gateway
- Customer score — no automatic financial/accounting action

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF CRM / CUSTOMER PORTAL / AFTER-SALES PLAN**

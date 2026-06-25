# MOGHARE360 — Complaint Handling Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Complaint Registration Purpose

Structured capture and resolution of customer complaints — **complaint handling must follow workflow** — with full audit and owner visibility.

---

## Complaint Sources

| Source | Channel |
|--------|---------|
| **Phone** | Reception/CRM logs call |
| **In-person** | Workshop visit |
| **Message** | SMS/messaging — evidence note local only |
| **Future controlled customer portal** | Phase 22 planned — not active; sanitized submit only when activated |

Dropdown — not free text source.

---

## Complaint Categories

| Category | Examples |
|----------|----------|
| Service quality | Rework, incomplete work |
| Parts | Wrong part, defective |
| Delay | Delivery/storage delay |
| Billing preview dispute | Preview amount disagreement — not official accounting |
| Staff behavior | Communication |
| Other | Note required |

Controlled dropdown per Critical Forms v2 CRM follow-up patterns.

---

## Complaint Severity

| Level | Response SLA (planning) |
|-------|-------------------------|
| Low | 3 business days |
| Medium | 1 business day |
| High | Same day — manager |
| Critical | Immediate — owner |

---

## Binding

| Entity | Rule |
|--------|------|
| **Customer** | Mandatory `customer_id` |
| **Vehicle** | Required when complaint is vehicle-specific |
| **JobCard** | Required when related to specific job |

No orphan complaints without customer.

---

## Workflow States

| State | Meaning |
|-------|---------|
| DRAFT | Being recorded |
| SUBMITTED | Logged officially |
| UNDER_REVIEW | Manager/owner reviewing |
| IN_PROGRESS | Resolution underway |
| RESOLVED | Fix delivered to customer |
| CLOSED | Customer acknowledged / archived |
| REJECTED | Invalid complaint — documented reason |

**Complaint handling must follow workflow** — no skip to CLOSED.

---

## Owner / Admin Review

| Rule | Detail |
|------|--------|
| High/Critical | Owner notification |
| **Owner/admin review** | Required before RESOLVED for severity ≥ Medium |
| Reopen | CLOSED → IN_PROGRESS with approval |

---

## Resolution Record

| Field | Rule |
|-------|------|
| Resolution type | Dropdown: rework, refund preview note, apology, warranty, other |
| Resolution note | Free text |
| Resolved by | Staff user |
| Customer notified | Checkbox + timestamp |

No official accounting post — preview notes only.

---

## Audit Requirement

| Event | Detail |
|-------|--------|
| `complaint_registered` | category, severity |
| `complaint_escalated` | owner |
| `complaint_resolved` | resolution type |
| `complaint_closed` | actor |
| `erp_crm_history` | Full chain |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF COMPLAINT HANDLING RULE**

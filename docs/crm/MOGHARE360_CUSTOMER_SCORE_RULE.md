# MOGHARE360 — Customer Score Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Customer Score Purpose

Aggregate behavioral and experience signals into a **customer score** and classification for CRM prioritization — **preview/calculation only**, not automated financial or accounting action.

**Customer score must be calculated/previewed only, not used for automatic financial/accounting action** — LOCKED.

---

## Score Inputs

| Input | Weight (planning) |
|-------|-------------------|
| **Visit frequency** | Repeat visits per year |
| **Invoice/payment behavior preview** | Preview paid on time — not official AR |
| **Complaint history** | Count and severity — negative |
| **Satisfaction** | Average ratings — positive |
| **Vehicle class** | Fleet/VIP boost per policy |
| **Loyalty** | Campaign enrollment, tenure |
| **Referral** | Referred new customers — positive |

Weights owner-configurable in future — documented at implementation.

---

## VIP / Loyal / Converting / New Classification

| Class | Planning criteria |
|-------|-------------------|
| **VIP** | High score + fleet/VIP customer class + owner flag |
| **Loyal** | 3+ visits, satisfaction ≥ 4, no open complaints |
| **Converting** | 2nd visit in progress — nurture |
| **New** | First or second interaction |

Classification dropdown on customer CRM view — derived from score bands.

---

## No Automatic Financial / Accounting Action

| Forbidden | Rule |
|-----------|------|
| Auto discount from score | FORBIDDEN without campaign approval |
| Auto credit memo | FORBIDDEN — E-10 |
| Auto payment terms | FORBIDDEN |
| Auto invoice generation | FORBIDDEN until Phase 23 gate |
| Price change on JobCard | FORBIDDEN from score alone |

Score is **advisory** for staff and owner review.

---

## Owner / Admin Review Requirement

| Rule | Detail |
|------|--------|
| VIP assignment | Owner/admin confirms |
| Score override | Manager audit — reason required |
| Classification change | Logged — not silent |
| Periodic recalculation | Nightly preview job (future) |

---

## Audit Requirement

| Event | Detail |
|-------|--------|
| `customer_score_calculated` | score, inputs hash |
| `customer_class_assigned` | class, approver if VIP |
| `customer_score_override` | old, new, reason |
| `financial_action_blocked_from_score` | E-10 if attempted |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF CUSTOMER SCORE RULE**

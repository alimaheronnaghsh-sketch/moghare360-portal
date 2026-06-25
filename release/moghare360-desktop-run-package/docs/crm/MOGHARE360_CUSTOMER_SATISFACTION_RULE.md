# MOGHARE360 — Customer Satisfaction Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Satisfaction Survey Purpose

Measure post-service experience through **controlled form rules** — structured ratings, not free-form-only feedback — to drive CRM quality improvement and complaint triggers.

**Customer satisfaction must be recorded through controlled form rules** — LOCKED.

---

## Timing After Delivery

| Rule | Detail |
|------|--------|
| Primary window | 3–7 days after JobCard CLOSED |
| Link to 3-day follow-up | May capture during follow-up call |
| Late entry | Allowed with reason note — audit |
| Pre-delivery | FORBIDDEN |

---

## Rating Scale Planning

| Dimension | Scale |
|-----------|-------|
| Overall | 1–5 stars (dropdown) |
| Sub-dimensions | 1–5 each |
| N/A option | Per dimension if not applicable |

No free-text-only satisfaction record — ratings mandatory; comments optional.

---

## Rating Dimensions

### Service Advisor Behavior Rating

| Aspect | Measures |
|--------|----------|
| Communication, clarity, professionalism | 1–5 |

### Technical Quality Rating

| Aspect | Measures |
|--------|----------|
| Repair quality, problem resolved | 1–5 |

### Delivery Experience Rating

| Aspect | Measures |
|--------|----------|
| Handover, timeliness, vehicle condition | 1–5 |

---

## Complaint Trigger If Low Score

| Rule | Action |
|------|--------|
| Any dimension ≤ 2 | Auto-flag `low_satisfaction` |
| Overall ≤ 2 | Create complaint draft — severity Medium |
| Overall = 1 | Complaint severity High + manager notify |
| **Complaint trigger if low score** | LOCKED |

---

## Binding

| Field | Requirement |
|-------|-------------|
| `customer_id` | Required |
| `jobcard_id` | Required — delivered job |
| `recorded_by` | Staff user (phone survey) or system (future portal) |
| `recorded_at` | Server timestamp |

---

## Audit Requirement

| Event | Detail |
|-------|--------|
| `satisfaction_recorded` | scores, jobcard_id |
| `satisfaction_low_trigger` | complaint link |
| `erp_crm_history` | Row append |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF CUSTOMER SATISFACTION RULE**

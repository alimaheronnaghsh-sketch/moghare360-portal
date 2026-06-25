# MOGHARE360 — 3-day Follow-up Automation Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Rule

**3-day follow-up after delivery** — CRM contacts every customer within 3 business days of JobCard CLOSED to confirm satisfaction and detect early issues.

---

## Dependencies

### Customer Contact Validation Dependency

| Check | Rule |
|-------|------|
| Mobile on file | Phase 17 validated mobile |
| Preferred contact | Dropdown: mobile / phone |
| Invalid contact | Block auto-queue; flag for reception update |

### JobCard Delivery Dependency

| Check | Rule |
|-------|------|
| JobCard state | **CLOSED** |
| Delivery timestamp | `delivery_completed_at` — follow-up due = +3 business days |
| No follow-up on cancelled jobs | FORBIDDEN |

---

## CRM Responsible User

| Rule | Detail |
|------|--------|
| **CRM responsible user** | Assigned on follow-up create — `assigned_to` |
| Default assignee | CRM role queue |
| Reassignment | Manager audit |

---

## Follow-up Statuses

| Status | Meaning |
|--------|---------|
| **PENDING** | Due; not yet contacted |
| **CONTACTED** | Successful contact — no issue |
| **NO_ANSWER** | Attempted — no response |
| **ISSUE_REPORTED** | Customer reported problem — escalate |
| **CLOSED** | Follow-up complete |

Controlled dropdown — not free text status.

---

## Escalation If Issue Reported

| Step | Action |
|------|--------|
| 1 | Status → ISSUE_REPORTED |
| 2 | Auto-create complaint draft or link existing |
| 3 | Notify service advisor + manager |
| 4 | JobCard may reopen review workflow (policy) |
| 5 | Owner alert if severity high |

---

## Automation Planning (Future)

| Feature | Phase 22 |
|---------|----------|
| Auto-create PENDING row on CLOSED | Planned |
| Due date calculation | +3 business days |
| SMS auto-send | **No** — manual call/message unless owner approves template later |
| **No automatic public messaging without approval** | LOCKED |

---

## Audit Requirement

| Event | Detail |
|-------|--------|
| `followup_created` | jobcard_id, due_date |
| `followup_contacted` | actor, channel |
| `followup_issue_reported` | escalation |
| `followup_closed` | resolution note |
| `erp_crm_history` | Domain row |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF 3-DAY FOLLOW-UP AUTOMATION RULE**

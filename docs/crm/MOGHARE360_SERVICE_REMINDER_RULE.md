# MOGHARE360 — Service Reminder Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Reminder Purpose

Proactive outreach for scheduled maintenance, warranty milestones, and CRM-initiated contact — without spam or unapproved automated public messaging.

---

## Reminder Triggers

| Trigger | Description |
|---------|-------------|
| **Date-based** | Calendar interval since last service (e.g. 6 months) |
| **Mileage-based** | Odometer from last JobCard vs recommended interval |
| **Service type-based** | Oil change, timing belt, inspection per catalog |
| **Warranty-based** | Warranty expiry approaching |
| **Manual CRM decision** | Staff creates one-off reminder |

---

## Customer Contact Validation Dependency

| Check | Rule |
|-------|------|
| Valid mobile | Phase 17 |
| Opt-out flag | Customer consent — do not remind if opted out |
| Invalid contact | Reminder PENDING — fix contact first |

---

## Message / Phone Follow-up Planning

| Channel | Rule |
|---------|--------|
| Phone call | Primary — CRM staff |
| SMS | Template only — **owner approval required** |
| Message app | Manual — evidence note |
| Email | Future — not required Phase 22 |
| **No automatic public messaging without approval** | LOCKED |

---

## Reminder States (Planning)

| State | Meaning |
|-------|---------|
| SCHEDULED | Due in future |
| DUE | Ready for contact |
| CONTACTED | Customer reached |
| BOOKED | Appointment set (future module) |
| DISMISSED | Customer declined — reason |
| CANCELLED | Superseded |

---

## Binding

| Field | Rule |
|-------|------|
| `customer_id` | Required |
| `vehicle_id` | Required for mileage/service type |
| `jobcard_id` | Optional — last service reference |

---

## Audit Requirement

| Event | Detail |
|-------|--------|
| `reminder_scheduled` | trigger type, due_date |
| `reminder_contacted` | channel, actor |
| `reminder_auto_message_blocked` | if unapproved template |
| `erp_crm_history` | Row |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF SERVICE REMINDER RULE**

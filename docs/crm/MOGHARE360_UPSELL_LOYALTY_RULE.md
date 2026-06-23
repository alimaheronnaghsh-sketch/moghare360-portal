# MOGHARE360 — Upsell / Loyalty Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Upsell and Loyalty Purpose

Structured, consent-aware campaigns to suggest additional services and reward loyal customers — without aggressive spam or unapproved automation.

---

## Approved Campaign Concept

| Element | Rule |
|---------|------|
| Campaign record | Name, audience filter, service package ref, date range |
| **Owner/admin approval for campaigns** | Required before active |
| Status | DRAFT → APPROVED → ACTIVE → CLOSED |
| Audience | Score class, vehicle class, manual list |

---

## Service Package Suggestion Planning

| Rule | Detail |
|------|--------|
| Packages | Dropdown from service catalog — bundled maintenance, seasonal offers |
| Suggestion trigger | Post-delivery satisfaction ≥ 4, loyalty class, reminder due |
| JobCard link | Optional — offer on next visit |
| Contract | New services require contract amendment — Phase 19 |

---

## Loyalty Customer Rule

| Rule | Detail |
|------|--------|
| **Loyalty customer** | Loyal/VIP class per customer score |
| Benefits | Priority scheduling (planning), courtesy check — not auto discount |
| Benefit application | Manager approval — no auto price change |
| Tracking | `erp_crm_history` + upsell tables |

---

## VIP Handling

| Rule | Detail |
|------|--------|
| VIP flag | Owner-confirmed |
| Dedicated advisor | Assignment field |
| Escalation | Complaints severity +1 |
| No automatic financial perk | Preview note only |

---

## Customer Consent Consideration

| Rule | Requirement |
|------|-------------|
| Marketing opt-in | Record on customer — dropdown |
| No contact if opted out | Block campaign |
| **No aggressive/spam automation** | Rate limits; max 1 campaign touch per 30 days (planning) |
| Persian RTL templates | Owner-approved text |

---

## Permissions

| Action | Permission |
|--------|------------|
| Create campaign | `crm.upsell` + manager |
| Approve campaign | Owner/admin |
| Log suggestion outcome | `crm.followup` |

---

## Audit Requirement

| Event | Detail |
|-------|--------|
| `campaign_created` | audience |
| `campaign_approved` | owner |
| `upsell_suggested` | customer, package |
| `upsell_declined` / `accepted` | outcome |
| `spam_guard_blocked` | rate limit |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF UPSELL / LOYALTY RULE**

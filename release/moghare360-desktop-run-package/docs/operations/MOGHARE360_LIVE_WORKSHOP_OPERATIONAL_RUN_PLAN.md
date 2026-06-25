# MOGHARE360 — Live Workshop Operational Run Plan

**Database:** MOGHARE360_ERP  
**Status:** Planning baseline — PHASE 20  
**Implementation:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Purpose of Live Operational Run

The **live workshop operational run** is the first controlled use of MOGHARE360 ERP for real workshop traffic on the **local laptop server**. It validates that reception → JobCard → operations → QC → delivery flows work under real conditions while **Phases 16–19 rules remain enforced** and production boundaries (no SaaS, portal, accounting, payment) stay closed.

**Live run must be controlled** — not open-ended production without owner oversight.

---

## Scope of Controlled Workshop Use

| In scope | Out of scope |
|----------|--------------|
| Single workshop site (owner laptop server) | moghareh360.ir ERP hosting |
| LAN/WiFi devices only (default) | Public customer portal |
| Real customers and vehicles | Official accounting posting |
| Finance **preview** for payment tracking | Payment gateway / billing / tax |
| Phased staff roles per runbook | SaaS multi-tenant activation |
| Manual fallback when system down | Validation/workflow bypass |

---

## Roles

| Role | Live run responsibility |
|------|-------------------------|
| **Reception** | Customer/vehicle intake, contract initiation, input photos, JobCard open |
| **Service advisor** | Contract acceptance, scope communication, out-of-contract requests |
| **Technician** | Assigned JobCard view, operation status, camera capture (future UI) |
| **QC** | QC live check before delivery |
| **Delivery** | Delivery close, output photos, handover acknowledgement |
| **CRM/admin** | Follow-up, blocked JobCard review, error log review |
| **Owner/admin** | Go/no-go, fallback approval, day-end sign-off, ceiling/contract exceptions |

---

## Daily Operational Flow

```
08:00  Server health check (local) — owner/admin
       │
08:30  Reception opens — intake per RECEPTION_LIVE_USE_RULE
       │
       ├── Customer validate (Phase 17)
       ├── Vehicle validate (Phase 17)
       ├── Contract draft + acceptance (Phase 19)
       ├── 6 input photos (Phase 18)
       └── JobCard live entry (JOBCARD_LIVE_ENTRY_RULE)
       │
Day    Technicians — tablet view (read-focused per TECHNICIAN_TABLET_VIEW_RULE)
       Operations — contract + ceiling gates
       │
       QC   — QC_LIVE_CHECK_RULE
       │
       Delivery — DELIVERY_LIVE_CHECK_RULE
       │
17:00  Daily error log complete — DAILY_ERROR_LOG_RULE
       Day-end operational report — DAY_END_OPERATIONAL_REPORT_RULE
       Owner sign-off
```

**UI → Validation Engine → Workflow Engine → Database → Audit Log** on every write.

---

## Required Preconditions

| # | Precondition | Source phase |
|---|--------------|--------------|
| 1 | Network architecture locked — local server system of record | Phase 16 |
| 2 | Validation rules documented | Phase 17 |
| 3 | Media rules documented (6 input / 8 output) | Phase 18 |
| 4 | Contract authorization documented | Phase 19 |
| 5 | SQL Server MOGHARE360_ERP reachable | Local ops |
| 6 | Apache/PHP local runtime stable | Local ops |
| 7 | Staff auth and roles assigned | Existing auth — not modified in Phase 20 |
| 8 | Manual fallback forms printed / accessible | MANUAL_FALLBACK_PROTOCOL |
| 9 | Owner go-live authorization | Owner decision — future execution gate |
| 10 | No SaaS / portal / accounting / payment activation | LOCKED |

---

## Manual Fallback Rule

**Any real operational use must have manual fallback protocol.**

When laptop server or ERP unavailable:

- Switch to paper fallback per `MOGHARE360_MANUAL_FALLBACK_PROTOCOL.md`
- Manager approval required
- Later system entry mandatory — **no permanent bypass**
- Log as `manual_fallback_usage` in daily error log

---

## Error Log Rule

**Any operational error must be logged.**

All validation blocks, workflow blocks, media failures, contract issues, and network outages recorded same day per `MOGHARE360_DAILY_ERROR_LOG_RULE.md`.

---

## Day-End Report Rule

Owner/admin receives day-end operational report per `MOGHARE360_DAY_END_OPERATIONAL_REPORT_RULE.md` — vehicles, JobCards, blocks, QC, fallback cases, next-day actions.

---

## Bypass Forbidden

| Bypass | Status |
|--------|--------|
| Direct UI→database | FORBIDDEN |
| Validation bypass | FORBIDDEN |
| Workflow bypass | FORBIDDEN |
| Audit bypass | FORBIDDEN |
| Upload bypass | FORBIDDEN |
| Contract authorization skip | FORBIDDEN |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED** — operational runbook only; no live run runtime, dashboards, or tablet UI in Phase 20.

---

## Product Boundary

- moghareh360.ir = Mirror Only
- No production SaaS · No public portal · No official accounting · No payment gateway

---

**END OF LIVE WORKSHOP OPERATIONAL RUN PLAN**

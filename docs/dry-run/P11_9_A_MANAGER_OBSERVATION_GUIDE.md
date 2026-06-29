# MOGHARE360 P11.9-A — Manager Observation Guide

**Audience:** OWNER / SYSTEM_ADMIN (optional SERVICE_MANAGER for coordination)

---

## 1. Purpose

Read-only oversight during dry run — **not** line execution.

---

## 2. Observation entry points

| Tool | Route | Use |
|------|-------|-----|
| Staff Home Manager Bridge | `erp-staff-home.php` | Cross-unit board refs |
| Route Map (ops) | `erp-route-map.php?view=operational` | Safe route inventory |
| Management dashboard | `erp-management-dashboard.php` | Pipeline overview |
| Owner control center | `erp-owner-control-center.php` | Risk/control list |
| Operational KPI | `erp-operational-kpi-dashboard.php` | KPI read-only |
| Bottleneck monitor | `erp-bottleneck-monitor.php` | Queue bottlenecks |
| Financial summary | `erp-financial-control-summary.php` | Financial read-only |
| JobCard timeline | `erp-jobcard-timeline.php?jobcard_id=` | Audit trail (guided) |
| Access management | `erp-access-management.php` | Pre-run only ideally |

---

## 3. What manager can see

- JobCard on boards (same `M360-DEMO` ID as staff)
- Status labels on responsibility strips (P2–P7 details)
- P8 aggregates (if SQL views applied)
- Bridge links to all major boards
- Route safety badges on Route Map

---

## 4. What manager must NOT do

- **No impersonation** / act-as-staff
- **No manager override engine** (not in V1)
- **No** direct POST to action endpoints
- **No** runtime-hold pages (part-use, payment-tracking)
- **No** manual SQL fixes during run
- **No** changing permissions/roles mid-run
- **No** enabling fake OTP

---

## 5. Phase observation checklist

| Phase | Manager checks |
|-------|----------------|
| Pre-run | Access readiness, Go/No-Go signed |
| P1/P2 | JobCard exists with M360-DEMO prefix |
| P1.5 | Contract gate visible; OTP deferral logged if applicable |
| P3 | Technician assignment visible on technical detail strip |
| P4/P7 FIN | Estimate + settlement progress (payment-tracking skipped) |
| P5 | Work execution board movement |
| P6 | QC + delivery readiness fields |
| P7 | Close/settlement; customer leg deferral if applicable |
| Post | Review incident register + execution log |

---

## 6. SERVICE_MANAGER coordination bridge

Lighter bridge — coordination refs only:

- Reception jobcards, contracts
- Part reserve (not part-use)
- Estimate / invoice boards
- Payment tracking ref **disabled** (runtime hold)

Use for cross-unit awareness, not admin diagnostics.

---

## 7. Escalation

Manager escalates to **STOP** when operator marks BLOCKED or core P2–P7 UI fails.

Owner sign-off captured in execution log «Owner Decision» column.

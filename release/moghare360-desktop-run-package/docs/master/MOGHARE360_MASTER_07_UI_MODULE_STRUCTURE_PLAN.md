# MOGHARE360 — Master 07 UI Module Structure Plan

**Status:** Planning only — Documentation only  
**SQL:** Not required — no UI files created in this phase

---

## Purpose

Define the UI module structure for web ERP, tablet/mobile workshop modes, and desktop admin. No frontend files are created in the MASTER EXECUTION PACK phase.

---

## Web ERP Modules (Desktop / Browser)

| Module | Primary users | Key screens |
|--------|---------------|-------------|
| Customer | Reception, admin | Intake, search, profile |
| Vehicle | Reception, admin | Register, bind, plate/VIN |
| JobCard | Workshop lead | Create, status, operations |
| CRM | Sales, follow-up | Follow-up, satisfaction |
| Inventory | Parts desk | Stock, reserve, purchase request |
| Finance Preview | Admin | Payment preview, reports (not official accounting) |
| HR | Admin | Employee, contract, attendance |
| Admin Panel | Platform owner | Roles, settings, audit dashboards |

All modules: RTL Persian, Moghareh Motors branding (Phase 12.5).

---

## Tablet / Mobile Mode

| Interface | Purpose |
|-----------|---------|
| Mechanic interface | Job execution, operation checklist |
| Workshop execution | Active jobcards, time on bay |
| QC system | Quality checks, pass/fail |
| Inventory usage view | Part consumption on job |

Constraints:

- **Camera direct only** for media capture
- **No upload bypass**
- Touch-first layout; same auth/session stack

---

## Desktop Admin Mode

| Capability | Scope |
|------------|-------|
| Full admin control | Users, permissions (read existing matrix) |
| Financial overview | Finance Preview dashboards only |
| Reporting system | KPI, operation, CRM reports |

No official accounting UI. No tax invoice screens.

---

## Responsive UI Boundary

| Breakpoint | Behavior |
|------------|----------|
| Desktop (≥1024px) | Full module nav, multi-column |
| Tablet (768–1023px) | Workshop mode default; collapsible nav |
| Mobile (<768px) | Mechanic/QC focused; limited admin |

Single codebase; CSS modules per phase pattern (`moghare360-ui-*.css`).

---

## UI Write Path

No direct database write from UI pages.

```
UI form → POST submit-* → Validation Engine → Workflow Engine → DB → Audit
```

---

## Existing Phase UI Reference (Read-Only)

Phases 1–15 delivered audit/read dashboards under `erp-*.php`. Future construction extends modules per this plan without rewriting forbidden auth files.

---

## Product Boundary

- Documentation only
- No UI files created in this phase
- No public customer portal activation
- No production SaaS activation

---

**END OF UI MODULE STRUCTURE PLAN**

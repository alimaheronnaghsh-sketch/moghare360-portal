# MOGHARE360 — Server Health Dashboard Plan

**Status:** Future plan only — **no implementation in PHASE 16**  
**SQL:** No SQL required

---

## Purpose

Provide owner with a single local view of laptop server and ERP runtime health before and during workshop go-live. **Planning document only** — no PHP pages, no SQL, no deployment in Phase 16.

---

## Planned Dashboard Indicators

| Indicator | Description |
|-----------|-------------|
| **Server online/offline** | Laptop reachable on LAN |
| **SQL Server reachable** | `.\SQLEXPRESS` / MOGHARE360_ERP connection OK |
| **PHP / XAMPP reachable** | Apache responds on configured port (8080) |
| **Disk space** | Free space on data and backup volumes |
| **Backup status** | Last successful backup timestamp vs policy |
| **Last audit timestamp** | Last security/structure audit run |
| **Network mode** | `local_only` / `tunnel_admin` / `mirror_public` |
| **Mirror mode** | moghareh360.ir mirror status (static/gateway/offline) |

---

## Data Sources (Future Implementation)

| Signal | Source |
|--------|--------|
| SQL ping | Local connection test script |
| Apache ping | HTTP HEAD to local base URL |
| Disk | WMI / PowerShell / PHP `disk_free_space` |
| Backup | Backup log file or SQL backup history |
| Audit | `docs/` audit timestamps or DB audit table |
| Network mode | Config flag in `private/erp-config.php` (future) |

---

## Access

- **Local only** — dashboard on owner laptop or admin LAN device
- Not published to moghareh360.ir
- Not public internet

---

## UI Concept (Future)

```
┌─────────────────────────────────────────┐
│ MOGHARE360 Server Health                │
├─────────────────────────────────────────┤
│ ● Server        ONLINE                  │
│ ● SQL Server    REACHABLE               │
│ ● Apache/PHP    REACHABLE               │
│ ● Disk          42% free                │
│ ● Backup        2026-06-22 23:00 OK     │
│ ● Last audit    2026-06-20              │
│ ● Network       local_only              │
│ ● Mirror        static_only             │
└─────────────────────────────────────────┘
```

---

## Phase 16 Boundary

| Item | Status |
|------|--------|
| Dashboard specification | This document |
| PHP implementation | NOT in Phase 16 |
| SQL schema for health | NOT in Phase 16 |
| Deploy to production | NOT in Phase 16 |

Implementation may align with Phase 20 (Live Workshop Operational Run) or earlier owner tooling phase.

---

## Relation to Network Architecture

Supports **owner-controlled server principle** and **risk of laptop downtime** monitoring from laptop server architecture doc.

---

**END OF SERVER HEALTH DASHBOARD PLAN**

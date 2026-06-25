# MOGHARE360 — Laptop Server Network Architecture

**Database:** MOGHARE360_ERP  
**Status:** Planning baseline — PHASE 16  
**SQL:** No SQL required

---

## Overview

MOGHARE360 runs as a **local laptop-server-based ERP**. The **laptop server is the system of record**. All workshop business data, media, and operational logic reside on the owner's controlled local machine — not on moghareh360.ir or any cloud host.

---

## Laptop Server Role

| Responsibility | Detail |
|----------------|--------|
| **System of record** | Single authoritative runtime for ERP |
| Hardware | Owner laptop (DESKTOP-U1P34B8 or successor) |
| Uptime | Workshop hours + owner maintenance windows |
| Physical security | Owner-controlled premises |
| **Owner-controlled server principle** | Only owner authorizes network exposure, backups, updates |

---

## SQL Server Local Role

| Property | Value |
|----------|-------|
| Instance | `.\SQLEXPRESS` |
| Database | **MOGHARE360_ERP** |
| Data location | Local disk only |
| **No cloud database** | LOCKED |
| Access | Local applications + authorized LAN/tunnel clients only |

All business tables (96), seeds, and operational rows remain on local SQL Server.

---

## XAMPP / PHP Local Role

| Property | Value |
|----------|-------|
| Stack | Apache + PHP (XAMPP) |
| Deploy path | `C:\xampp\htdocs\moghare360` |
| URL (local) | `http://localhost:8080/moghare360/` |
| Config | `private/erp-config.php` — local only, never on domain |
| Logic | Full ERP business logic runs **locally** |

---

## LAN / WiFi Access Concept

```
[Reception PC] ──┐
[Admin laptop] ──┼── WiFi/LAN ──► [Owner laptop server]
[Tech tablet]  ──┘                      │
                                        ├── Apache/PHP
                                        └── SQL Server
```

- Workshop devices access server via **local network** or **controlled secure tunnel** (see VPN plan)
- No requirement for public internet exposure for daily operations
- Router may restrict inbound ports to server

---

## Backup Responsibility

| Item | Owner responsibility |
|------|---------------------|
| SQL Server backups | Local disk / owner external drive |
| `private/erp-config.php` | Secured locally, not in repo |
| Media / diagnostic PDFs | Local filesystem paths |
| Backup schedule | Per Phase 14 deployment docs — owner executes |
| **Backup must be owner-controlled** | No cloud backup without explicit future approval |

---

## Risk: Laptop Downtime

| Risk | Mitigation (planned) |
|------|---------------------|
| Server offline = workshop ERP offline | UPS; scheduled maintenance windows |
| Disk failure | Regular local backups; disk health monitoring |
| Single point of failure | Accepted for Pre-Go-Live; no cloud failover in Phase 16 |
| Theft/damage | Physical security; encrypted backups offsite (owner policy) |

---

## Required Minimum Server Controls

| Control | Requirement |
|---------|-------------|
| Windows updates | Owner-maintained patch level |
| SQL Server service | Auto-start; password-protected sa/instance |
| Apache/PHP | Non-default port if exposed (8080 documented) |
| Firewall | Block unnecessary inbound from internet |
| Antivirus | Owner standard |
| Disk space monitoring | Planned in server health dashboard |
| Session timeout | Existing auth stack (not modified in Phase 16) |

---

## Architecture Flow (Local)

**UI → Validation Engine → Workflow Engine → Database → Audit Log**

All engines execute on laptop server against local MOGHARE360_ERP.

---

## Product Boundary

- **No production deployment yet** in Phase 16
- **No data on domain**

---

**END OF LAPTOP SERVER NETWORK ARCHITECTURE**

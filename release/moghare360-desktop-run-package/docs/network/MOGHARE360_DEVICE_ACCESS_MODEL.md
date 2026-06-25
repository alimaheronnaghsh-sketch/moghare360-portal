# MOGHARE360 — Device Access Model

**Status:** Planning baseline — PHASE 16  
**SQL:** No SQL required

---

## Overview

Workshop devices access MOGHARE360 **only** through the **owner laptop server** via **local network** or **controlled secure tunnel**. No anonymous public access.

---

## Device Classes

| Device class | Role | Typical access |
|--------------|------|----------------|
| **Owner laptop server** | System of record; hosts PHP + SQL Server | Direct local |
| **Admin devices** | Full ERP admin, config oversight, reports | LAN or VPN |
| **Reception devices** | Intake, customer lookup, jobcard open | LAN |
| **Technician tablets** | Operation engine, media capture, status updates | LAN or WiFi |
| **QC / delivery devices** | QC checks, delivery handover | LAN |
| **CRM / admin devices** | CRM, follow-up, internal comms | LAN or VPN |

---

## Access Paths

### Primary: Local Network (LAN / WiFi)

```
Device ──► Workshop WiFi/LAN ──► http://<server-ip>:8080/moghare360/
```

- Preferred for daily operations
- No internet exposure required
- Router DHCP reserves server IP (recommended)

### Secondary: Controlled Secure Tunnel

- VPN or secure tunnel to owner network (see HTTPS/VPN plan)
- For remote owner/admin access only — not public customer access
- **No anonymous public access**

---

## Authentication

- Existing staff auth stack (not modified in Phase 16)
- Role-based access per `access-control.php` patterns
- Session timeout enforced locally

---

## Forbidden Access Patterns

| Pattern | Status |
|---------|--------|
| Anonymous public ERP URL | FORBIDDEN |
| Customer self-service portal | **No customer portal until PHASE 22 approval** |
| Official accounting module | **No accounting access until PHASE 23 approval** |
| Payment gateway | FORBIDDEN |
| Direct SQL from devices | FORBIDDEN (apps use PHP API/pages only) |
| moghareh360.ir as ERP entry | FORBIDDEN (mirror only) |

---

## Device Security Minimums

| Control | Requirement |
|---------|-------------|
| Workshop WiFi | WPA2/WPA3; guest network isolated from server |
| Tablets | Screen lock; no personal cloud sync of workshop photos |
| Admin laptops | Encrypted disk recommended |
| Shared reception PC | Auto-lock; staff logout on shift end |

---

## Network Mode Summary

| Mode | Use |
|------|-----|
| `local_only` | LAN/WiFi only — default for go-live |
| `tunnel_admin` | Owner remote admin via VPN |
| `mirror_public` | moghareh360.ir static face only — no ERP |

---

## Phase 16 Status

- Access model documented only
- No device enrollment implementation
- No MDM deployment

---

**END OF DEVICE ACCESS MODEL**

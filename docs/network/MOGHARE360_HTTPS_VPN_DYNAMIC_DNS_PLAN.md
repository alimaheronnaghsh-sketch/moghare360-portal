# MOGHARE360 — HTTPS / VPN / Dynamic DNS Plan

**Status:** Planning only — PHASE 16  
**SQL:** No SQL required  
**Deploy:** **No production deploy in PHASE 16**

---

## HTTPS Requirement

| Context | Requirement |
|---------|-------------|
| Local LAN (HTTP) | Acceptable for workshop-only Pre-Go-Live if network is trusted |
| Remote admin access | **HTTPS required** for any tunnel-exposed endpoint |
| moghareh360.ir public face | **HTTPS required** (host SSL / Let's Encrypt) |
| Credential transmission | Never over plain HTTP across internet |

Local dev URL `http://localhost:8080/moghare360/` remains HTTP on loopback only.

---

## VPN or Secure Tunnel Option

**Recommended safe approach for remote access:**

| Option | Description | Risk |
|--------|-------------|------|
| **WireGuard / OpenVPN** | Owner connects to home/workshop router VPN | Low if keys managed locally |
| **Tailscale / ZeroTier** | Mesh VPN without port forward | Low; no credentials in repo |
| **SSH tunnel** | Owner SSH to server, port forward | Medium; key hygiene required |
| **Direct port forward** | Router forwards 443/8080 to laptop | **High — port exposure risk** |

**Recommendation:** Prefer VPN/mesh over exposing Apache directly to internet.

---

## Dynamic DNS Option

If workshop has dynamic public IP:

| Item | Plan |
|------|------|
| DDNS provider | Owner account (e.g. No-IP, DuckDNS) — **not in repository** |
| Hostname | Points to router public IP |
| Use | VPN endpoint or mirror domain only — not raw ERP |
| Credentials | **No credentials in repository** |

---

## Router / Firewall Considerations

| Rule | Action |
|------|--------|
| Default deny inbound | Block all WAN → LAN except approved |
| Port 1433 (SQL Server) | **Never expose** to internet |
| Port 8080 (Apache) | LAN only unless behind VPN |
| Port 443 | moghareh360.ir mirror static only |
| UPnP | Disable on router |
| Admin panel | Change default router password |

---

## Port Exposure Risk

```
INTERNET ──► open port 8080 ──► XAMPP ──► MOGHARE360_ERP
                    ▲
              HIGH RISK — avoid
```

Exposing local ERP to internet without VPN + hardening is **not approved** in Phase 16.

---

## Recommended Safe Approach (Summary)

1. **Daily ops:** LAN/WiFi only — no inbound ports
2. **Remote owner:** VPN or Tailscale to LAN, then local URL
3. **Public web:** moghareh360.ir static mirror only — no ERP backend on host
4. **Secrets:** `private/erp-config.php`, VPN keys, DDNS tokens — **local only, never in git**
5. **Phase 16:** Document only — **no production deploy**

---

## moghareh360.ir SSL

- Host-provided SSL or Let's Encrypt on mirror pages
- SSL does not imply ERP is hosted on domain
- Termination at host for static content only

---

## Implementation Gate

| Action | Phase |
|--------|-------|
| Document VPN/DNS/HTTPS plan | PHASE 16 ✅ |
| Configure router VPN | Future phase (owner execution) |
| Deploy mirror SSL pages | Future phase (after approval) |
| Expose ERP via tunnel | Future phase (explicit approval) |

---

**END OF HTTPS / VPN / DYNAMIC DNS PLAN**

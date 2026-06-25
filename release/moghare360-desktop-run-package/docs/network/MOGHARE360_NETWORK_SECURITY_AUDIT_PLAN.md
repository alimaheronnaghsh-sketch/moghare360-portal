# MOGHARE360 — Network Security Audit Plan

**Status:** Planning baseline — PHASE 16  
**SQL:** No SQL required  
**Execution:** Audit checklist for future owner/Cursor phase — not executed in Phase 16

---

## Purpose

Systematic review of network, host, and configuration exposure before workshop go-live and before any mirror gateway activation.

---

## Audit Domains

### 1. Open Port Review

| Check | Action |
|-------|--------|
| Router WAN port scan | Document open ports |
| Windows firewall inbound rules | List allow rules for 8080, 1433, 80, 443 |
| SQL Server TCP 1433 | Must be LAN-only or disabled externally |
| Apache 8080 | Must not be WAN-exposed without VPN |

### 2. Host Cleanup Review

| Check | Action |
|-------|--------|
| moghareh360.ir file inventory | List all PHP, SQL dumps, uploads |
| Old portal / cPanel remnants | Identify legacy apps |
| Host MySQL databases | Confirm no MOGHARE360_ERP copy |
| See | `MOGHARE360_HOST_CLEANUP_MIRROR_ONLY_CONVERSION_PLAN.md` |

### 3. Credential Exposure Review

| Check | Action |
|-------|--------|
| Git history | No `erp-config.php`, passwords, API keys |
| `public_html` | No hardcoded DB passwords |
| `.env` / config backups on host | Remove or secure |
| **No credentials in repository** | LOCKED |

### 4. Config Exposure Review

| Check | Action |
|-------|--------|
| `config.php` / `config.example.php` | No real secrets in repo |
| `private/erp-config.php` | Local only; not on host |
| Directory listing | Disabled on Apache |
| Error display | Off in production |

### 5. Backup Exposure Review

| Check | Action |
|-------|--------|
| Backup file location | Not web-accessible |
| Backup encryption | Owner policy |
| Offsite backup | Encrypted if used |
| Git | No `.bak` / `.sql` dumps committed |

### 6. Public Access Review

| Check | Action |
|-------|--------|
| Anonymous ERP URLs | None on internet |
| Customer portal | Not active until Phase 22 |
| Directory indexes | Disabled |
| **No anonymous public access** | LOCKED |

### 7. VPN / Tunnel Review

| Check | Action |
|-------|--------|
| VPN software | Updated; MFA if available |
| Tunnel ACLs | Admin devices only |
| Split tunneling | Document policy |
| See | `MOGHARE360_HTTPS_VPN_DYNAMIC_DNS_PLAN.md` |

### 8. Mirror-Only Enforcement Review

| Check | Action |
|-------|--------|
| Host stores business data | Must be NONE |
| Host stores uploaded files | Must be NONE |
| Host runs business logic | Must be NONE |
| Host database | Must be NONE for MOGHARE360 |
| **moghareh360.ir is Mirror Only** | Verify |

---

## Audit Output (Future)

| Deliverable | Location |
|-------------|----------|
| Audit report | `docs/security/` (future phase) |
| Remediation list | Owner + Cursor approved phases |
| Sign-off | Before mirror gateway activation |

---

## Phase 16 Status

- Audit **plan** documented
- Audit **execution** not in Phase 16
- No host changes in Phase 16

---

## Cadence (Recommended)

| When | Trigger |
|------|---------|
| Pre go-live | Full audit |
| Quarterly | Port + credential review |
| After host change | Mirror-only re-verification |
| After VPN change | Tunnel review |

---

**END OF NETWORK SECURITY AUDIT PLAN**

# MOGHARE360 — Mirror Domain Architecture

**Domain:** moghareh360.ir  
**Status:** Mirror Only — LOCKED  
**Phase:** PHASE 16

---

## Role Definition

**moghareh360.ir is Mirror Only.**

The domain is **not** the system of record. It must **not** replace or duplicate the local laptop ERP runtime.

---

## Forbidden on Domain

| Rule | Status |
|------|--------|
| **Domain must not store business data** | LOCKED |
| **Domain must not store uploaded files** | LOCKED |
| **Domain must not contain business logic** | LOCKED |
| **No database on host** | LOCKED |
| **No customer data on host** | LOCKED |
| **No host-side customer data** | LOCKED |

---

## Permitted (After Future Approval Only)

| Use | Scope |
|-----|-------|
| Static marketing / brand pages | Display only |
| Controlled interface/gateway | Reverse proxy or link to local — **after explicit approval** |
| Status placeholder | "ERP runs locally" messaging |
| SSL termination | HTTPS for public face only |

**Domain can only expose controlled interface/gateway after approval** — not in Phase 16.

---

## Architecture Diagram

```
                    INTERNET
                        │
                        ▼
              ┌─────────────────┐
              │  moghareh360.ir  │  Mirror Only
              │  (no DB, no data)│
              │  static/gateway  │
              └────────┬────────┘
                       │ (optional tunnel — future)
                       ▼
              ┌─────────────────┐
              │ Laptop server    │  SYSTEM OF RECORD
              │ PHP + SQL Server │
              │ MOGHARE360_ERP   │
              └─────────────────┘
```

---

## What Mirror Is NOT

- Not production SaaS — **No production SaaS activation**
- Not customer portal — **No public customer portal activation** (until Phase 22)
- Not accounting host — **No official accounting activation** (until Phase 23)
- Not payment processor — **No payment gateway/billing/tax integration**
- Not file CDN for workshop photos or diagnostic PDFs

---

## Sync / Gateway Concept (Future — Not Phase 16)

One-way or request/response gateway only:

- Mirror may **request** sanitized status from local (owner-approved tunnel)
- Mirror must **never** persist MOGHARE360_ERP rows
- No two-way database sync

---

## Relation to Local Server

| Asset | Local server | moghareh360.ir |
|-------|--------------|----------------|
| MOGHARE360_ERP | Yes | No |
| Business logic (PHP) | Yes | No |
| Customer PII | Yes | No |
| Media / PDFs | Yes | No |
| Marketing HTML | Optional copy | Yes (static) |

---

## Phase 16 Status

- **No deployment in PHASE 16**
- **No deletion on host in PHASE 16**
- Architecture lock documentation only

---

**END OF MIRROR DOMAIN ARCHITECTURE**

# MOGHARE360 — Master 09 Mirror Domain Plan

**Status:** Planning only — Documentation only  
**SQL:** Not required

---

## Purpose

Define the role and boundaries of the mirror domain **moghareh360.ir**. Mirror only — no business processing, no database, no customer portal activation in current product boundary.

---

## Domain

| Property | Value |
|----------|-------|
| Domain | `moghareh360.ir` |
| Role | Mirror only |
| Primary ERP | Local laptop server |

---

## What Mirror Is NOT

| Forbidden on mirror | Reason |
|---------------------|--------|
| Database | No business data storage |
| Business data storage | Data residency on local server only |
| Primary processing | ERP runs locally |
| Customer portal activation | No public customer portal activation |
| Auth/login for staff ERP | Staff ERP stays local |
| Payment gateway | No payment gateway/billing/tax integration created |
| Official accounting | No official accounting activation |

---

## Safe Display-Only Boundary

Permitted future mirror content (concept only — **not implemented**):

- Static marketing / brand pages
- Product information (no live ERP)
- Placeholder status (“system local-only”)

**No business data on domain mirror.**

All dynamic ERP operations remain on local server.

---

## Future Sync / Mirror Concept (Not Implemented)

Conceptual one-way or read-only sync for public-safe content only:

```
Local ERP (authoritative)
  → (future) sanitized static export
    → moghareh360.ir (display cache)
```

Rules for any future implementation:

- No PII, no jobcards, no financial data
- No two-way sync of operational state
- Owner approval required before activation

**This MASTER pack does not implement sync or mirror deployment.**

---

## Security Alignment

- No cloud storage for ERP data
- Mirror must not expose `private/erp-config.php` or API keys
- CSRF and session rules apply only to local ERP, not mirror static site

---

## Product Boundary

- Documentation only
- Mirror concept only, not implemented
- No public customer portal activation
- No production SaaS activation

---

**END OF MIRROR DOMAIN PLAN**

# MOGHARE360 — Local Server & Mirror Domain Final Decision

**Status:** LOCKED — Executive decision  
**Date:** 2026-06-23  
**Database:** MOGHARE360_ERP

---

## System of Record

| Component | Decision |
|-----------|----------|
| **Local laptop server** | **System of record** |
| SQL Server | **MOGHARE360_ERP** on `.\SQLEXPRESS` — business data stored **locally** |
| PHP backend | Runs **locally** (`http://localhost:8080/moghare360/`) |
| **All data lives only on local laptop server** | LOCKED |

---

## Mirror Domain: moghareh360.ir

| Rule | Status |
|------|--------|
| **moghareh360.ir is Mirror Only** | LOCKED |
| **No data storage on domain** | LOCKED |
| **No file storage on domain** | LOCKED |
| **No business logic on domain** | LOCKED |
| No database on mirror host | LOCKED |
| No primary ERP processing on mirror | LOCKED |

---

## Forbidden Cloud / Host Patterns

| Pattern | Status |
|---------|--------|
| **No cloud database** | LOCKED |
| **No host-side customer data** | LOCKED |
| Cloud file storage for ERP media | FORBIDDEN |
| SaaS multi-tenant on public host | FORBIDDEN — **No production SaaS activation** |

---

## Network Architecture (PHASE 16 Preview)

```
[Workshop staff] → Local laptop (Apache + PHP + SQL Server + files)
                        │
                        │ (future: static/marketing mirror only)
                        ▼
                 moghareh360.ir (Mirror Only — no business data)
```

---

## Operational Implications

1. Workshop operations (PHASE 20+) run entirely on local server
2. Backups remain local per deployment plan (Phase 14)
3. Release packages exclude `private/` secrets and real PII
4. Domain mirror may show brand/static content only when PHASE 16 authorizes

---

## Architecture & Media (Unchanged)

**UI → Validation Engine → Workflow Engine → Database → Audit Log**

**Camera direct only** · **No upload bypass**

---

## Product Activation Boundaries

- **No public customer portal activation** until **PHASE 22 approval**
- **No official accounting activation** until **PHASE 23 approval**
- **No payment gateway/billing/tax integration**

---

## Relation to Prior Docs

- Supersedes exploratory mirror concepts in `docs/master/MOGHARE360_MASTER_09_MIRROR_DOMAIN_PLAN.md` with this **final locked decision**
- Aligns with Phase 05 domain ownership and Phase 08 go-live priority

---

**END OF LOCAL SERVER & MIRROR DOMAIN FINAL DECISION**

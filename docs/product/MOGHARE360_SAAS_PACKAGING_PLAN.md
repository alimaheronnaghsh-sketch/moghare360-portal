# MOGHARE360 SaaS Packaging Plan

## Concept

Future SaaS would package MOGHARE360 as hosted Repair Shop OS with optional multi-branch tenant isolation. **Not active in Phase 10.**

## Future Tenant Model

- Tenant = workshop organization (company)
- Branch = physical location under tenant
- Data isolation at schema or tenant_id layer (design only — not implemented)

## Staged Activation

1. Internal ERP (Phases 1–7) — **DONE**
2. Business Ready + Reporting (Phase 9) — **DONE**
3. Commercial Demo (Phase 10) — **DONE (demo only)**
4. Pilot single-tenant hosted — **FUTURE**
5. Multi-tenant SaaS — **FUTURE + explicit approval**

## Security Requirements (Future)

- Tenant-scoped auth context
- No cross-tenant data leakage
- Audit logging per tenant
- Secrets outside web root

## Migration Requirements (Future)

- Export/import per tenant
- Non-destructive upgrades
- Rollback plan per release

## Explicit Note

**SaaS is NOT active in Phase 10.** No billing, subscription engine, or tenant provisioning.

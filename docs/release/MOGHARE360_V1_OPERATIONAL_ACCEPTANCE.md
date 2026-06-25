# MOGHARE360 V1 — Operational Acceptance

## Acceptance Scope

MOGHARE360 V1 SaaS-enabled Production Release is accepted for **controlled production run** on Master Server (laptop) with Mirror site on moghareh360.ir.

## Accepted Capabilities

1. Install production stack via Installer / Auto Deploy
2. SaaS tenant foundation (single company `MOGHAREH_MAIN`)
3. Master API intake (customer request, access request, health)
4. Mirror PWA shell connected to Master API
5. Internal ERP UX (M31–M37) + controlled prototypes (M15–M30)
6. Release download packages without secrets

## Not Accepted in V1 (deferred via Fix Register / V2)

- Full customer portal
- Contract/pricing engine
- Final accounting / invoice export
- Multi-tenant commercial SaaS billing
- Production SSL (external — tracked in Fix Register)

## Acceptance Criteria Met

| Criterion | Result |
|-----------|--------|
| Installer runs with backup | PASS |
| Auto Deploy builds + inspects ZIP | PASS |
| Smoke test 24/24 | PASS |
| API health HTTP 200 | PASS |
| Fix Register table exists | PASS (after migration) |
| Signoff dashboard available | PASS |

## Signoff Parties

| Role | Action |
|------|--------|
| Technical (Agent/Build) | Stack signoff documented |
| Owner | Formal signoff via `erp-v1-production-signoff.php` |
| Staff | Feedback → Fix Register `STAFF_REVIEW` |

## Post-Acceptance Rule

All changes after acceptance **must** appear in Fix Register. No silent scope expansion.

## Final Acceptance Statement

V1 is operationally acceptable for controlled internal production run. External public go-live requires SSL + owner signoff + closure of OPEN CRITICAL/HIGH items per owner decision.

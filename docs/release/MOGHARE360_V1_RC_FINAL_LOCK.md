# MOGHARE360 V1 — RC Final Lock

## RC Lock Statement

MOGHARE360 V1 Release Candidate is **locked** as of P11. This RC includes operational workflow phases P1 through P10 plus release hardening, final audit, local demo package controls, and owner presentation lock (P11).

## Included Phases

P1, P1.5, P2, P3, P4, P5, P6, P7, P8, P9, P10, P11

## No Further Feature Build Rule

After P11 lock, **no new workflow domains** are in scope for V1:

- No accounting voucher / ledger
- No payment gateway / bank / official tax
- No SaaS / multi-company
- No HR / CRM / full purchase-inventory
- No production deploy from this package

## Next Allowed Actions

1. **Demo** — owner and internal presentations using DEMO data
2. **Bugfix** — defects in existing P1–P11 scope only
3. **Packaging** — local demo zip via approved PowerShell script
4. **Documentation** — release and demo docs updates
5. **Owner signoff** — formal acceptance of V1 RC boundaries
6. **OTP provider config** — bugfix patches to `m360-otp-helper` / private OTP config only (P11.1+)
7. **IPPanel CLI diagnostic** — temporary troubleshooting tools only (P11.2)
8. **Online test gate hardening** — secure domain-to-laptop bridge, HMAC intake, cPanel templates (P11.3)

## P11.3 Online Test Gate

- Allowed: secure bridge config, HMAC API, P1 intake adapter, masked logs, readiness page, cPanel placeholder templates
- Not allowed: P12 scope, workflow mutation, Auth/Login changes, production deployment claims

## Verification Pages

- `erp-rc-final-audit.php`
- `erp-local-demo-package.php`
- `erp-owner-presentation-lock.php`
- `erp-rc-final-checklist.php`
- `erp-online-test-readiness.php`

## Migration P11

`database/migrations/P11_rc_final_audit_package_lock.sql` — comment-only, no schema change.

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
9. **Owner access management UI** — professional staff access console over SQL Server identity (P11.4)

## P11.4 Access Management UI

- **Primary path:** `erp-access-management.php` and related P11.4 pages — not JSON editing
- Allowed: controlled INSERT/UPDATE on `core_users`, `core_user_roles`, `erp_company_users`, `core_staff_profiles`, audit/history
- Fallback only: `private/production-users.json` + PowerShell import
- Not allowed: Auth/Login core changes, permission seed edits, legacy MySQL staff auth, P12 scope

## Verification Pages

- `erp-rc-final-audit.php`
- `erp-local-demo-package.php`
- `erp-owner-presentation-lock.php`
- `erp-rc-final-checklist.php`
- `erp-online-test-readiness.php`
- `erp-access-management.php`

## Migration P11

`database/migrations/P11_rc_final_audit_package_lock.sql` — comment-only, no schema change.

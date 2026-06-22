# PHASE 7 — Repo Gap Review

## Legacy / Foundation Tables Reviewed
- `JobCard`, `Services`, `Payments` — legacy tables exist; **not modified**
- `core_v0_*` org/role seeds include `hr` module and `hr_staff` role — foundation only; **not modified**
- No pre-existing `erp_hr_*` operational tables found before Phase 7

## Prior Phase Tables (read-only reference)
- `erp_operation_cases`, `erp_operation_service_steps` — Phase 2; HR does not bind technician to `employee_id`
- `erp_finance_history`, payment/invoice tables — Phase 5; no payroll payment integration
- `erp_crm_*` — Phase 6; independent from HR

## Helpers Reused (unchanged)
- `erp-auth-context.php`
- `erp-permission-guard.php`
- `erp-csrf.php`
- `erp-config-loader.php` (via auth context)

## New Non-Sensitive Helper
- `erp-hr-helper.php` — DB, CSRF wrappers, attendance/payroll calculation, history insert

## Decision
- Build new `erp_hr_*` extension tables; no DROP/RENAME of existing structures
- HR module is independent; technician board link is navigation-only

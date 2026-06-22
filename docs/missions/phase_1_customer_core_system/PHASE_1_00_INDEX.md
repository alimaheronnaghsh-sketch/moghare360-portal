# PHASE 1 — Customer Core System Index

Status: **PENDING USER TEST**

## Built Files

### SQL
- `public_html/sql/sqlserver/phase_1_customer_core_system.sql`

### PHP Pages
- `public_html/erp-customer-core-dashboard.php`
- `public_html/erp-customer-entry.php`
- `public_html/submit-customer-entry.php`
- `public_html/erp-customer-contract-create.php`
- `public_html/submit-customer-contract.php`
- `public_html/erp-customer-profile.php`
- `public_html/erp-vehicle-binding.php`
- `public_html/submit-vehicle-binding.php`

### Helper
- `public_html/includes/erp-customer-core-helper.php`

### CSS
- `public_html/assets/moghare360-ui/moghare360-customer-core.css`

### Test Tool
- `tools/test-phase-1-customer-core.php`

## Browser URLs

Base: `http://localhost:8080/moghare360/`

| Page | URL |
|------|-----|
| Dashboard | `erp-customer-core-dashboard.php` |
| Customer Entry | `erp-customer-entry.php` |
| Contract Create | `erp-customer-contract-create.php` |
| Customer Profile | `erp-customer-profile.php` |
| Vehicle Binding | `erp-vehicle-binding.php` |

## Repo Review Summary (Part 1)

- **DB connection:** ODBC via `erp_auth_create_local_odbc_connection()` in `includes/erp-auth-context.php`; config loader at `includes/erp-config-loader.php` (not modified).
- **Auth:** `erp-auth-context.php` + local test fallback user `10001`.
- **CSRF:** `includes/erp-csrf-helper.php` with purpose-based tokens.
- **Permission:** `includes/erp-permission-guard.php` with placeholder actions for Phase 1.
- **Legacy tables:** `erp_customers`, `erp_vehicles` (Mission 15); optional `Customers_v2`, `CustomerPhones_v2`, `Vehicles`, `JobCard`, `Payments` checked at runtime.
- **Patterns followed:** Mission 15–28 controlled create / read-only list structure; Soft Run RTL CSS tokens.

## Docs

- `PHASE_1_01_CUSTOMER_CORE_SCOPE.md`
- `PHASE_1_02_SQL_FOUNDATION.md`
- `PHASE_1_03_CUSTOMER_ENTRY_ENGINE.md`
- `PHASE_1_04_CONTRACT_ENGINE.md`
- `PHASE_1_05_CUSTOMER_PROFILE.md`
- `PHASE_1_06_VEHICLE_BINDING.md`
- `PHASE_1_90_TEST_RESULT.md`
- `PHASE_1_99_SIGNOFF.md`

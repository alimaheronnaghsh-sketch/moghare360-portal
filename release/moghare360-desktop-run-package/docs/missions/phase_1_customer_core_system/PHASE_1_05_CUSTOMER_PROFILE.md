# PHASE 1 — Customer Profile

## Page

`erp-customer-profile.php` — **read-only**

## Search Filters (GET)

- `customer_id` (optional)
- `intake_id` (optional)
- `mobile` (optional)

If no filter provided, shows search form only.

## Sections

1. Intake records from `erp_customer_intakes`
2. Legacy customer from `Customers_v2` (if exists)
3. Phones from `CustomerPhones_v2` (if exists)
4. Vehicle bindings from `erp_customer_vehicle_bindings`
5. Contracts from `erp_customer_contracts`
6. JobCards from `erp_jobcards` or `JobCard` (if exists)
7. Financial preview from `erp_payments` or `Payments` — count/summary only
8. CRM placeholder — future module

## Rules

- No INSERT / UPDATE / DELETE
- Read-only badge displayed
- Missing legacy tables: section shows "در دسترس نیست"
- Permission: `customer.core.profile.view`
- No customer login / portal

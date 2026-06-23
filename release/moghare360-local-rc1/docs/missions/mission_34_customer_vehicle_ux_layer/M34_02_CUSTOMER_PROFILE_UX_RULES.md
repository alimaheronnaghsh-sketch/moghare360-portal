# Customer Profile UX Rules

## Page
`erp-customer-detail-ux.php`

## ID Resolution
- `?customer_id=` from querystring
- Default: first customer in erp_customers if missing/invalid

## Sections
1. Customer profile card
2. Phone chips (erp_customer_phones if exists)
3. Linked vehicles via relations
4. JobCard history
5. Payment summary (aggregate read-only)
6. Service history timeline (jobcard_change_history)
7. Action panel links

## Guard
- customer.vehicle.view (M15 placeholder)

## Final Profile Decision
Single customer operational view with cross-links to vehicle and jobcard UX.

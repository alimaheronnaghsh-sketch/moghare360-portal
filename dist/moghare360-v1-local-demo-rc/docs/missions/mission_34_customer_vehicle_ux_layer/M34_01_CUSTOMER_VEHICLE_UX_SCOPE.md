# Customer Vehicle UX Scope

## In Scope
- Workbench with KPI, search, customer/vehicle/relation lists
- Customer detail UX (profile, phones, vehicles, jobcards, payment summary)
- Vehicle detail UX (profile, owners, jobcards, service ops, summaries)
- Create UX guide (reception flow, mock forms, M15 link)
- Read-only data helper + CSS

## Out of Scope
- SQL schema / migration
- DB write
- Auth / permission change
- Legacy portal change
- Production deploy

## Tables Used (read-only)
- dbo.erp_customers
- dbo.erp_customer_phones
- dbo.erp_vehicles
- dbo.erp_customer_vehicle_relations
- dbo.erp_jobcards
- dbo.erp_jobcard_change_history
- dbo.erp_service_operations
- dbo.erp_jobcard_part_usage
- dbo.erp_payments
- dbo.erp_qc_checks

## Final Scope Decision
Mission 34 = customer/vehicle UX wrapper with safe table checks.

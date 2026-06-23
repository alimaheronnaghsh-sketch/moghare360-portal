# Service Operation UX Scope

## In Scope
- Service operation workbench (list + KPI)
- Service operation detail UX
- Status board (kanban visualization, no drag/drop)
- Technician workflow readiness page
- Read-only data helper + CSS

## Out of Scope
- SQL schema / migration
- DB write
- Service operation status write
- Assignment write
- Auth / permission change
- Finance / inventory / QC / delivery write from UX pages

## Tables Used (read-only)
- dbo.erp_service_operations
- dbo.erp_service_operation_change_history
- dbo.erp_jobcards
- dbo.erp_customers / erp_vehicles (join)
- dbo.erp_jobcard_part_usage
- dbo.erp_purchase_requests
- dbo.erp_payments
- dbo.erp_qc_checks
- dbo.erp_delivery_controls

## Final Scope Decision
Mission 35 = service operation UX wrapper with progress visualization only.

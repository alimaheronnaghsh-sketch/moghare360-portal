# Finance Preview UX Scope

## In Scope
- Finance preview workbench
- JobCard financial preview
- Payment preview detail
- Invoice preview mock (disabled actions)
- Read-only data helper + CSS

## Out of Scope
- SQL schema / migration
- DB write / payment write
- Invoice finalization
- Accounting export
- Supplier payment
- Tax logic
- Delivery dependency / unlock
- Purchase write / stock write

## Tables Used (read-only)
- dbo.erp_payments
- dbo.erp_payment_history
- dbo.erp_jobcards
- dbo.erp_service_operations
- dbo.erp_jobcard_part_usage
- dbo.erp_purchase_requests
- dbo.erp_customers / erp_vehicles

## Final Scope Decision
Mission 36 = financial preview display layer only.

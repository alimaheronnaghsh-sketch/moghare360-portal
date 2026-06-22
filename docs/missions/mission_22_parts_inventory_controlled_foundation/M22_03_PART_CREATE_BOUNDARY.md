# Mission 22 - Part Create Boundary

## Write Rules
- POST only, CSRF, Auth Context, Permission Guard (parts.create)
- Controlled transaction, rollback on failure

## Validation
- part_code required, unique
- part_name required
- unit_of_measure required (default PCS)

## Insert Scope
- dbo.erp_parts only
- No erp_stock_movements insert
- No finance, purchase, JobCard writes

## Identity Retrieval
Fetch part_id by part_code after INSERT.
No SCOPE_IDENTITY / OUTPUT INSERTED / @@IDENTITY / IDENT_CURRENT.

## Success Message
Part Created OK

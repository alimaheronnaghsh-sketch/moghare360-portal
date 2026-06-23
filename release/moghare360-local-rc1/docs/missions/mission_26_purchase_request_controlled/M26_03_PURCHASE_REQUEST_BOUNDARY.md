# Mission 26 - Purchase Request Boundary

## In Scope
- Create purchase request linked to JobCard
- Optional service_operation_id and part_id
- Initial status DRAFT or SUBMITTED only
- History row PURCHASE_REQUEST_CREATED
- Read-only list and detail pages

## Out of Scope (Locked)
- No supplier payment
- No finance write (AP, ledger, journal)
- No stock receipt (RECEIPT movement)
- No automatic approval
- No purchase order execution
- No accounting export
- No invoice write
- No payment write
- No delivery write
- No approve / reject / cancel UI in Mission 26

## supplier_id
Always NULL on create. Placeholder per Mission 25.

## estimated_unit_cost
Informational only. No finance side effect.

## Forbidden Files
No change to config.php, config.example.php, staff-auth.php, access-control.php, Customer Portal, legacy inventory PHP.

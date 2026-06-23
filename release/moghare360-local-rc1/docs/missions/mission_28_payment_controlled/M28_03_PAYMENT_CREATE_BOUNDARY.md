# Mission 28 - Payment Create Boundary

## In Scope
- Create payment linked to active JobCard
- payment_type: ADVANCE, PARTIAL, FULL
- payment_method: CASH, CARD, BANK_TRANSFER, POS, OTHER
- payment_status: RECEIVED only on create
- History: PAYMENT_RECEIVED, old_status NULL, new_status RECEIVED
- customer_id denormalized from JobCard

## Out of Scope (Locked)
- No invoice finalization
- No accounting export
- No supplier payment
- No tax logic
- No delivery dependency / release
- No purchase write
- No stock write
- No DRAFT / CANCELLED / REVERSED actions in M28 create
- No balance column write

## Forbidden Files
No change to config.php, config.example.php, staff-auth.php, access-control.php, Customer Portal, legacy inventory PHP.

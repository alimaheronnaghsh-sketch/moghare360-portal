# Mission 26 - Implementation Plan

## Deliverables
1. SQL: `erp_purchase_requests` + `erp_purchase_request_history`
2. `erp-purchase-request-create.php` — create + history in transaction
3. `erp-purchase-request-readonly-list.php` — read-only list
4. `erp-purchase-request-detail.php` — read-only detail + history
5. `tools/test-erp-purchase-request-foundation.php` — CLI read-only validation

## Create Flow
1. Auth Context (user_id = 10001)
2. Permission Guard (`purchase.request.create`)
3. CSRF validation
4. Validate jobcard_id (active), optional service_operation_id (same jobcard), optional part_id
5. Validate requested_part_name, requested_quantity > 0, status DRAFT or SUBMITTED
6. Transaction: INSERT request + INSERT history (PURCHASE_REQUEST_CREATED)
7. Composite lookup for purchase_request_id (no SCOPE_IDENTITY)
8. Commit

## Explicitly Out of Scope
- Approve / Reject / Cancel actions
- Supplier selection
- Finance posting
- Stock RECEIPT movement
- Purchase order execution

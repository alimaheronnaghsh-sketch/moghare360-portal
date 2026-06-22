# PHASE 4 — Stock Movement History

Page: `public_html/erp-stock-movement-history.php` (read-only)

## Source Table

Resolved via `inventory_movements_table()`:
- `erp_stock_movements` (Phase 4 schema)
- `erp_inventory_stock_movements` (M22 extension)

## Filters (GET)

- `inventory_item_id`
- `movement_type`
- `operation_case_id`
- `purchase_request_id`

## Movement Types

`INITIAL_BALANCE`, `RESERVATION`, `USAGE`, `RELEASE`, `PURCHASE_REQUEST`, `PENDING_RECEIVE`, `RECEIVE`, `ADJUSTMENT`

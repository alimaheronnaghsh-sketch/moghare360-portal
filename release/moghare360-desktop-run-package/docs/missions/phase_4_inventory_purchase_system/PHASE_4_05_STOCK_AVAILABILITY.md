# PHASE 4 — Stock Availability

Page: `public_html/erp-stock-board.php` (read-only)

## Columns

`item_code`, `item_name`, `available_qty`, `reserved_qty`, `pending_receive_qty`, `available_to_reserve`

## Badges

| Badge | Condition |
|-------|-----------|
| AVAILABLE | free stock above min |
| LOW_STOCK | free ≤ min_stock_qty |
| OUT_OF_STOCK | free ≤ 0 |
| PENDING_RECEIVE | pending_receive_qty > 0 |

Filter: `item_name` (GET)

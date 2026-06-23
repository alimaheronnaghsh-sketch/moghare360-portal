# PHASE 4 — Part Reservation

Pages:
- `public_html/erp-part-reserve.php` — form
- `public_html/submit-part-reserve.php` — controlled write

## Flow

1. User selects `inventory_item_id` and `requested_qty`
2. Page shows `available_to_reserve` before submit
3. Submit calculates availability:
   - Full stock → `RESERVED`, `reserved_qty = requested_qty`
   - Partial → `PARTIALLY_RESERVED`
   - None → `PENDING`
4. Updates `erp_stock_balances.reserved_qty` (no `available_qty` deduction)
5. Inserts `RESERVATION` stock movement and history

CSRF: `inventory_part_reserve`

Optional links: `operation_case_id`, `service_step_id`, `rule_decision_id`

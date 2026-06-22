# Mission 22 - Stock Read-Only Boundary

## Purpose
Read-only stock on-hand view from movement ledger aggregation.

## Rules
- SELECT only on erp-part-readonly-list.php and erp-stock-readonly-list.php
- No POST handlers
- No INSERT/UPDATE/DELETE

## Stock Query
quantity_on_hand = SUM of movements with signed CASE by movement_type:
- SEED, RECEIPT, RETURN, ADJUSTMENT add quantity
- ISSUE, REVERSAL subtract quantity

## Mission 22 Boundary
- No stock movement create from PHP
- No ISSUE movements in M22 prototype or SQL seed
- No stock consumption or deduction

## Columns Displayed
part_id, part_code, part_name, stock_location_id, location_code, quantity_on_hand

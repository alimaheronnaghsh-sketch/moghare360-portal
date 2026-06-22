# PHASE 3 — Inventory Decision Trigger

## Function

`rule_engine_check_inventory_decision($connection, $partId, $partCode, $partName, $requestedQty)`

## Read-Only Stock Estimate

From `erp_stock_movements` when available:

- IN: RECEIPT, RETURN, ADJUSTMENT, SEED
- OUT: ISSUE, REVERSAL

**No stock deduction in Phase 3.**

## Outcomes

| Situation | inventory_decision | next_action |
|-----------|-------------------|-------------|
| Qty unknown | UNKNOWN | MANUAL_CHECK |
| available ≥ requested | AVAILABLE | RESERVE_PART |
| available < requested | PURCHASE_REQUIRED | CREATE_PURCHASE_REQUEST |

Stored in `erp_inventory_rule_requests` via `rule_engine_create_inventory_request_if_needed()`.

## Test Console

Enable inventory check with part_id/part_code or `run_inventory` flag.

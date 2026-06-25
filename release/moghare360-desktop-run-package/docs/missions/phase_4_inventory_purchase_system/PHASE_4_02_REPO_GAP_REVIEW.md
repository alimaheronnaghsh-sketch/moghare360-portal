# PHASE 4 — Repo Gap Review

## Foundation Tables (pre-existing)

| Table | Status | Notes |
|-------|--------|-------|
| `dbo.erp_parts` | EXISTS (M22) | Read-only in Phase 4 catalog; not modified |
| `dbo.erp_stock_locations` | EXISTS (M22) | Reused for seeds MAIN / PENDING |
| `dbo.erp_stock_movements` | EXISTS (M22) | Legacy INT schema; Phase 4 uses `erp_inventory_stock_movements` extension when no `inventory_item_id` column |
| `dbo.erp_purchase_requests` | EXISTS (M26) | Jobcard-focused; Phase 4 uses `erp_inventory_purchase_requests` extension when no `request_code` column |
| `JobCard`, `Payments`, `Vehicles`, `Customers_v2`, `CustomerPhones_v2` | Foundation | Referenced indirectly via operation cases |

## Phase 1–3 Tables (verified at runtime)

- Phase 1: `erp_customer_intakes`, `erp_customer_contracts`, `erp_customer_vehicle_bindings`
- Phase 2: `erp_operation_cases`, `erp_operation_service_steps`, `erp_operation_history`
- Phase 3: `erp_rule_definitions`, `erp_rule_decisions`, `erp_inventory_rule_requests`, `erp_rule_audit_history`

## Helpers Reused (not modified)

- `erp-config-loader.php`, `erp-auth-context.php`, `erp-csrf.php`, `erp-permission-check.php`
- `erp-workflow-engine.php`, `erp-rule-engine.php`, `erp-operation-engine-helper.php`, `erp-customer-core-helper.php`

## New Phase 4 Artifacts

- SQL: `phase_4_inventory_purchase_system.sql`
- Helper: `erp-inventory-purchase-helper.php`
- Tables: `erp_inventory_items`, `erp_stock_balances`, `erp_part_reservations`, `erp_suppliers`, `erp_inventory_purchase_history`
- Extension tables when legacy conflicts: `erp_inventory_purchase_requests`, `erp_inventory_stock_movements`

## Duplicates Avoided

- No DROP/RENAME on `erp_parts` or legacy M22/M26 tables
- M26 `erp-purchase-request-create.php` replaced by Phase 4 form + `submit-purchase-request.php` (extension table path)

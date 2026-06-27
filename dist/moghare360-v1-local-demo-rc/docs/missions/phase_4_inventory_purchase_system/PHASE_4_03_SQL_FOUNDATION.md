# PHASE 4 — SQL Foundation

Script: `public_html/sql/sqlserver/phase_4_inventory_purchase_system.sql`

## Tables

1. `dbo.erp_inventory_items` — catalog items
2. `dbo.erp_stock_locations` — created only if M22 table missing; seeds MAIN / PENDING
3. `dbo.erp_stock_balances` — available / reserved / pending_receive per item
4. `dbo.erp_part_reservations` — controlled reservations
5. `dbo.erp_suppliers` — supplier foundation; seed INTERNAL-MANUAL
6. `dbo.erp_purchase_requests` OR `dbo.erp_inventory_purchase_requests` — purchase lifecycle
7. `dbo.erp_stock_movements` OR `dbo.erp_inventory_stock_movements` — movement audit
8. `dbo.erp_inventory_purchase_history` — entity history

## Rules

- Idempotent `IF OBJECT_ID IS NULL` / `IF NOT EXISTS`
- No DROP, no RENAME, no `USE database`
- Execute manually in SSMS on `moghare360_ERP`

## PHP Table Resolution

- `inventory_purchase_table()` → extension table when M26 legacy lacks `request_code`
- `inventory_movements_table()` → extension table when M22 legacy lacks `inventory_item_id`

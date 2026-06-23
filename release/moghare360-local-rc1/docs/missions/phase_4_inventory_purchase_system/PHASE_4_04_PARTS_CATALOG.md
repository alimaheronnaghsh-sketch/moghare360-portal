# PHASE 4 — Parts Catalog

Page: `public_html/erp-parts-catalog.php`

## Features

- Lists `erp_inventory_items` (top 100)
- Read-only legacy `erp_parts` section when table exists
- Controlled POST (self) to add new inventory item
- CSRF purpose: `inventory_catalog_create`
- Auto-generates `item_code` if omitted
- Creates empty `erp_stock_balances` row for new item

## Fields

`item_name`, `item_code`, `item_category`, `brand`, `compatible_vehicle`, `unit_name`, `min_stock_qty`, `notes`

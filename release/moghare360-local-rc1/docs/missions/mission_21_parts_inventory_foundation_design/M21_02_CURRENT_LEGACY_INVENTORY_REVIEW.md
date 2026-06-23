# Current Legacy Inventory Review

## Purpose
This document records a read-only review of legacy inventory structures in the project.
No legacy table, file, or database is modified in Mission 21.

## Mission 21 Rule
Legacy inventory may be reviewed conceptually only.
No migration, reuse, or alteration is executed in Mission 21.

## Review Method
Static inspection of repository SQL scripts and PHP inventory modules.
No live database connection was required for this design lock.

## Legacy Context Summary

### Separate Database Reference
SQL Server core scripts reference **moghare360_StockCenter** as a separate database.
Core ERP scripts explicitly state they do not alter moghare360_StockCenter.

**Decision:** New ERP parts foundation (`erp_parts`, etc.) is designed in **moghare360_ERP** independently.

### MySQL Legacy / Staging Tables (StockCenter Pattern)
Found in `public_html/sql/patch_stockcenter_inventory.sql`, `patch_inventory_location_warehouses.sql`, and `MOTHER_RUN_ALL_IN_ORDER.sql`:

| Table | Role |
|-------|------|
| `inventory_items_staging` | Staged item master with codes, warehouse fields, quantity, pricing |
| `inventory_movements_staging` | Staged inbound/outbound movements with `related_jobcard` text reference |
| `inventory_warehouses` | Warehouse master |
| `inventory_categories` | Category master |
| `inventory_units` | Unit of measure master |
| `inventory_item_qualities` | Quality level master |
| `inventory_locations` | Warehouse floor/row/rack/section location codes |

**Key legacy fields (items):** item_name, technical_code, oem_code, internal_code, barcode, warehouse_code, quantity, purchase_price_rial, workflow_status, sync_status.

**Key legacy fields (movements):** movement_type, inventory_item_id, quantity, source_location, destination_location, related_jobcard, movement_note.

### Legacy PHP Operational Pages (Do Not Modify)
Found under `public_html/`:
- `staff-inventory.php` and related staff-inventory-*.php pages
- `inventory-controlled-helpers.php`
- `inventory-helpers.php`
- API sync endpoints referencing `inventory_items_staging`

These use legacy staff auth and MySQL PDO patterns — separate from Mission 08+ ERP ODBC prototype chain.

### Portal / Workflow SQL (MySQL)
`public_html/sql/erp_jobcard_workflow_v1.sql` includes:
- `portal_jobcard_parts_requests` — portal-side parts request table linked to jobcard_id

This is portal workflow context, not dbo.erp_jobcards foundation.

### SQL Server ERP Inventory Tables
No `dbo.erp_parts`, `dbo.erp_stock_locations`, or `dbo.erp_stock_movements` tables exist in Mission 17 / 20 SQL Server foundation scripts at time of Mission 21.

Existing SQL Server inventory-related permissions in `core_v0_06_seed_roles_permissions.sql`:
- `inventory.view`, `inventory.inbound`, `inventory.outbound`, `inventory.price`

These are core RBAC seeds for legacy operational inventory roles — not the new `parts.*` / `stock.*` permission keys locked in M21_08.

## Structural Gap vs New ERP Design

| Legacy | New ERP Plan (Mission 21) |
|--------|---------------------------|
| `inventory_items_staging` (MySQL) | `dbo.erp_parts` (SQL Server) |
| `inventory_locations` + warehouse fields | `dbo.erp_stock_locations` |
| `inventory_movements_staging` | `dbo.erp_stock_movements` |
| `related_jobcard` as VARCHAR text | Future typed FK to `dbo.erp_jobcards` + `dbo.erp_service_operations` |
| Staging + sync_status workflow | Controlled ERP transaction + audit/history |

## Migration / Reuse Boundary
No migration from legacy inventory is authorized in Mission 21.

If exact production legacy row counts, constraints, or StockCenter live schema differ from repository scripts:

**Legacy inventory requires read-only structure inspection before migration or reuse.**

## Forbidden Legacy Changes (Locked)
Mission 21 must not change:
- Any `inventory_*` legacy table
- `staff-inventory*.php` and inventory helper files
- `config.php`, `config.example.php`, `staff-auth.php`, `access-control.php`
- Customer Portal files
- moghare360_StockCenter database objects

## Final Review Decision
Legacy inventory remains untouched.
ERP Parts / Inventory foundation must be designed cleanly in moghare360_ERP with explicit deferral of migration and consumption to later missions.

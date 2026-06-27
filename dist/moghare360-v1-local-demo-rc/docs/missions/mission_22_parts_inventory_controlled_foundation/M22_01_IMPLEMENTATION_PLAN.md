# Mission 22 - Implementation Plan

## Deliverables

### 1. SQL Foundation
- `mission_22_parts_inventory_foundation.sql`
- Tables: erp_parts, erp_stock_locations, erp_stock_movements
- Controlled seed: MAIN location only
- No movement seed, no consumption

### 2. Part Create Page
- Auth + CSRF + Permission Guard (parts.create)
- INSERT dbo.erp_parts only
- Duplicate part_code check
- Success: Part Created OK

### 3. Parts Read-Only List
- parts.list guard
- SELECT part_id, part_code, part_name, brand, category, unit_of_measure, is_active, created_at

### 4. Stock Read-Only List
- stock.view guard
- Aggregated quantity_on_hand from movements (read-only)

### 5. CLI Test
- Table existence, MAIN location, stock query, no ISSUE movements

## Forbidden
No stock consumption, JobCard usage, finance, purchase, legacy changes.

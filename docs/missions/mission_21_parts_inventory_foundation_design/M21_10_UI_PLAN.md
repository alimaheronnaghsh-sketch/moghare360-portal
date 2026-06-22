# UI Plan

## Purpose
This document defines the future UI plan for Parts / Inventory foundation.

## Mission 21 Boundary
No UI file is created in Mission 21.

## Future UI Pages (Mission 22)

| File | Purpose |
|------|---------|
| public_html/erp-part-create.php | Controlled create for part master |
| public_html/erp-part-readonly-list.php | Read-only parts catalog list |
| public_html/erp-stock-readonly-list.php | Read-only stock / movement summary list |

## Create Page Sections (Future — erp-part-create.php)
1. part_code (auto-generated or manual per M22 charter)
2. part_name (required)
3. brand, manufacturer (optional)
4. oem_number, aftermarket_number (optional)
5. category (optional)
6. unit_of_measure (required)
7. CSRF token
8. Create result with part_id

## Parts List Page (Future)
Display:
- part_id
- part_code
- part_name
- brand
- category
- unit_of_measure
- is_active
- created_at

## Stock Read-Only List Page (Future)
Display aggregated or recent movement summary:
- part_code / part_name
- stock_location_code
- computed on-hand quantity (read model)
- last movement_type
- last movement_at

No write actions on this page in Mission 22.

## Permission Boundary (Future)
- parts.create on create POST
- parts.list on parts list
- stock.view on stock read-only list
- Auth Context on all pages
- CSRF on all POST writes

## Explicit Non-Goals (Mission 22 UI)
- No stock ISSUE form
- No JobCard part consumption UI
- No purchase request form
- No finance / invoice panels
- No legacy staff-inventory page modification

## Mission 21 Boundary
UI is planned only.
No PHP operational file is created.

## Final UI Decision
Three-page Mission 22 prototype: part create, parts list, stock read-only list.

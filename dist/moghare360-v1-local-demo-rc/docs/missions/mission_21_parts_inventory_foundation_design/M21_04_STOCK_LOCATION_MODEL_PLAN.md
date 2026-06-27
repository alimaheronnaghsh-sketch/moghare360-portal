# Stock Location Model Plan

## Purpose
This document defines the planned Stock Location foundation data model.

## Important Rule
Mission 21 does not create or execute SQL.
This is a plan only.

## Proposed Table (Future — Mission 22+)

### dbo.erp_stock_locations
Warehouse / storage location master for ERP stock.

## Proposed Fields: dbo.erp_stock_locations

| Field | Type (Suggested) | Nullable | Notes |
|-------|------------------|----------|-------|
| stock_location_id | INT | No | Primary key, IDENTITY |
| location_code | NVARCHAR(60) | No | Unique location code |
| location_name | NVARCHAR(150) | No | Human-readable name |
| location_type | NVARCHAR(30) | No | e.g. WAREHOUSE, SHELF, BIN, STAGING |
| is_active | BIT | No | Soft lifecycle; default 1 |
| created_at | DATETIME2(3) | No | Row creation timestamp |

### Suggested location_type Values (Future)
- WAREHOUSE — main storage area
- SHELF — shelf within warehouse
- BIN — bin / slot
- STAGING — temporary staging (issue prep, receipt staging)
- QUARANTINE — blocked stock (future QC integration)

### Minimum Required Fields for Soft Run (Future)
Required on create:
- location_code
- location_name
- location_type
- is_active

## Suggested Constraints (Future SQL)
- Primary key on stock_location_id
- Unique constraint on location_code
- Index on location_type
- Index on is_active
- CHECK constraint on location_type enum (as defined in Mission 22 SQL charter)
- No physical DELETE — use is_active = 0

## Relationship to Stock Movement
- Every `erp_stock_movements` row references one `stock_location_id`
- Location does not store quantity directly in initial design

## Legacy Mapping Note (Read-Only)
Legacy MySQL uses `inventory_warehouses` + `inventory_locations` with composite location_code.
ERP design consolidates to `erp_stock_locations` with explicit `location_type`.
No automatic migration mapping is defined in Mission 21.

## Mission 21 Boundary
Plan only. No table created. No location row created.

## Final Data Model Decision
Location master as standalone table; quantity derived from movement ledger only.

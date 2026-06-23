# SQL Implementation Plan

## Purpose
This document prepares the future SQL implementation plan for Parts / Inventory foundation.

## Critical Rule (Locked)

**No SQL execution in Mission 21.**

**SQL implementation deferred to Mission 22.**

Mission 21 does not create SQL files.
Mission 21 does not execute SQL.
Mission 21 does not change the database.

## Future SQL Scope (Mission 22 — Indicative)
Mission 22 may create:

- dbo.erp_parts
- dbo.erp_stock_locations
- dbo.erp_stock_movements (structure; writes limited per M22 charter)

Optional future history table (Mission 22+ if needed):
- dbo.erp_part_change_history (part master audit — to be confirmed in M22)

## Suggested Table: dbo.erp_parts
Planned fields (see M21_03):
- part_id, part_code, part_name, brand, manufacturer, oem_number, aftermarket_number, category, unit_of_measure, is_active, created_by_user_id, created_at, updated_at

## Suggested Table: dbo.erp_stock_locations
Planned fields (see M21_04):
- stock_location_id, location_code, location_name, location_type, is_active, created_at

## Suggested Table: dbo.erp_stock_movements
Planned fields (see M21_05):
- stock_movement_id, part_id, stock_location_id, movement_type, quantity, reference_type, reference_id, movement_note, created_by_user_id, created_at

## Required SQL Controls (Future)
- primary keys on all tables
- foreign keys: part_id, stock_location_id on movements
- unique constraint on part_code, location_code
- CHECK on movement_type and location_type
- indexes on lookup columns
- no destructive migration
- no legacy table alteration
- idempotent IF NOT EXISTS pattern (per Mission 17 / 20 convention)

## Identity Retrieval (Future)
Follow locked pattern:
- No OUTPUT INSERTED / SCOPE_IDENTITY / @@IDENTITY / IDENT_CURRENT
- INSERT + fetch by unique business key (part_code, location_code)

## Mission 21 Boundary
Plan only.

| Action | Mission 21 |
|--------|------------|
| SQL file creation | Not allowed |
| SQL execution | Not allowed |
| Database change | Not allowed |
| Table creation | Deferred to Mission 22 |

## Final SQL Decision
Mission 21 locks three-table plan only.
All SQL creation and execution explicitly deferred to Mission 22.

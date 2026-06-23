# Part Master Data Model Plan

## Purpose
This document defines the planned Part Master foundation data model.

## Important Rule
Mission 21 does not create or execute SQL.
This is a plan only.

## Proposed Table (Future — Mission 22+)

### dbo.erp_parts
Canonical ERP part / item master for repair shop operations.

## Proposed Fields: dbo.erp_parts

| Field | Type (Suggested) | Nullable | Notes |
|-------|------------------|----------|-------|
| part_id | INT | No | Primary key, IDENTITY |
| part_code | NVARCHAR(60) | No | Unique business code |
| part_name | NVARCHAR(200) | No | Display name |
| brand | NVARCHAR(100) | Yes | Brand label |
| manufacturer | NVARCHAR(150) | Yes | Manufacturer name |
| oem_number | NVARCHAR(120) | Yes | OEM reference |
| aftermarket_number | NVARCHAR(120) | Yes | Aftermarket reference |
| category | NVARCHAR(100) | Yes | Part category |
| unit_of_measure | NVARCHAR(30) | No | e.g. PCS, SET, LITER |
| is_active | BIT | No | Soft lifecycle; default 1 |
| created_by_user_id | INT | No | Auth Context user |
| created_at | DATETIME2(3) | No | Row creation timestamp |
| updated_at | DATETIME2(3) | Yes | Last update timestamp |

### Minimum Required Fields for Soft Run (Future)
Required on create:
- part_code
- part_name
- unit_of_measure
- is_active
- created_by_user_id

Optional on create:
- brand, manufacturer, oem_number, aftermarket_number, category

## Suggested Constraints (Future SQL)
- Primary key on part_id
- Unique constraint on part_code
- Index on part_name
- Index on oem_number, aftermarket_number (filtered or full)
- Index on category
- Index on is_active
- No physical DELETE policy — use is_active = 0

## Identity Retrieval Rule (Future)
Follow locked pattern from M15/M17/M18/M20:
- Do not use OUTPUT INSERTED
- Do not use SCOPE_IDENTITY()
- Do not use @@IDENTITY
- Do not use IDENT_CURRENT
- Approved pattern: INSERT + fetch by generated unique `part_code`

## Suggested Part Code Format (Future)
`PT-[YYYYMMDDHHMMSS]-[random 4 digits]` or controlled manual code with uniqueness validation.

## Relationship to Stock
- `erp_parts` is master data only
- On-hand quantity lives in movement ledger (`erp_stock_movements`), not on part row directly in initial design

## Mission 21 Boundary
Plan only. No table created. No part row created.

## Final Data Model Decision
Single part master table with unique part_code and soft active lifecycle.

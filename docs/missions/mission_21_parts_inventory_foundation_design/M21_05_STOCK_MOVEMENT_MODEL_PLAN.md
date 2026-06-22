# Stock Movement Model Plan

## Purpose
This document defines the planned Stock Movement ledger data model.

## Important Rule
Mission 21 does not create or execute SQL.
This is a plan only.

## Proposed Table (Future — Mission 22+ for read-only seed; Mission 23+ for controlled writes)

### dbo.erp_stock_movements
Append-style stock movement ledger. Source of truth for on-hand quantity calculation.

## Proposed Fields: dbo.erp_stock_movements

| Field | Type (Suggested) | Nullable | Notes |
|-------|------------------|----------|-------|
| stock_movement_id | INT | No | Primary key, IDENTITY |
| part_id | INT | No | FK → dbo.erp_parts |
| stock_location_id | INT | No | FK → dbo.erp_stock_locations |
| movement_type | NVARCHAR(30) | No | See movement types below |
| quantity | DECIMAL(18,4) | No | Signed or unsigned per type policy in M22+ |
| reference_type | NVARCHAR(50) | Yes | e.g. JOBCARD, SERVICE_OPERATION, PURCHASE_REQUEST, MANUAL |
| reference_id | INT | Yes | Typed reference id when applicable |
| movement_note | NVARCHAR(1000) | Yes | Operator note |
| created_by_user_id | INT | No | Auth Context user |
| created_at | DATETIME2(3) | No | Movement timestamp |

## Suggested Movement Types (Locked)

| movement_type | Meaning |
|---------------|---------|
| SEED | Initial stock seed for prototype / opening balance |
| RECEIPT | Stock received into location |
| ISSUE | Stock issued out (future JobCard consumption) |
| RETURN | Stock returned to location |
| ADJUSTMENT | Controlled manual adjustment |
| REVERSAL | Reversal of a prior movement (linked in future mission) |

## Quantity Rules (Design — Future)
- On-hand per part per location = SUM(movements) per signed policy
- Stock must not go negative (enforced at write time in Mission 23+)
- No silent stock change — every movement requires audit trail
- Physical DELETE of movement rows forbidden — use REVERSAL

## Reference Fields (Future)
`reference_type` + `reference_id` link movements to:
- JobCard part usage (Mission 24+)
- Service Operation (preferred)
- Purchase receipt (Mission 25+)
- Manual adjustment document (future)

## Suggested Constraints (Future SQL)
- Primary key on stock_movement_id
- Foreign key: part_id → dbo.erp_parts
- Foreign key: stock_location_id → dbo.erp_stock_locations
- CHECK constraint on movement_type
- Index on (part_id, stock_location_id)
- Index on movement_type
- Index on (reference_type, reference_id)
- Index on created_at

## Mission 21 / 22 Boundary
- Mission 21: design only
- Mission 22: may create table structure; controlled writes limited to part master + read-only stock list (no ISSUE in M22 per testing plan)
- Mission 23: stock deduction design
- Mission 24: ISSUE prototype for JobCard / Service Operation

## Mission 21 Boundary
Plan only. No movement row created. No stock write.

## Final Data Model Decision
Append-only movement ledger with six movement types; negative stock forbidden at application layer in future missions.

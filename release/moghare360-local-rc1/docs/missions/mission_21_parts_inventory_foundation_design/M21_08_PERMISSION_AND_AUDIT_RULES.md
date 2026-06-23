# Permission and Audit Rules

## Purpose
This document defines required permission and audit rules for future Parts / Inventory implementation.

## Required Security Layers (Locked)
Any future Parts / Inventory write must use:
- Auth Context
- Permission Guard
- CSRF (for browser POST writes)
- Controlled transaction
- Audit / history strategy
- Safe error handling
- **No silent stock change**

## Suggested Permission Actions (Future)

| Permission Key | Purpose |
|----------------|---------|
| parts.create | Create part master record |
| parts.view | View single part detail |
| parts.list | List parts |
| stock.view | View stock levels / movement read models |
| stock.movement.view | View movement ledger |
| stock.movement.create | Create stock movement (RECEIPT, SEED, etc.) |
| jobcard.part.use | Issue / return parts on JobCard / Service Operation (Mission 24+) |
| purchase.request.create | Create purchase request when stock unavailable (Mission 25+) |

## Permission Rules by Mission (Locked)

### Mission 22 (Indicative)
May use:
- parts.create, parts.view, parts.list
- stock.view (read-only aggregated list)

Must not use in writes:
- jobcard.part.use
- stock.movement.create for ISSUE
- purchase.request.create

### Mission 24+ (Indicative)
- jobcard.part.use required for ISSUE / RETURN tied to JobCard / Service Operation

### Mission 25+ (Indicative)
- purchase.request.create for out-of-stock purchase path

## Platform Owner Prototype Rule
Local controlled prototypes may use placeholder permission fallback where real permissions are not registered:
- user_id = 10001
- owner / system_admin context
- local prototype only

## Audit Rules (Locked)
Every future write must:
1. Authenticate via Auth Context
2. Enforce Permission Guard for action key
3. Validate CSRF on POST
4. Begin controlled transaction
5. Write business row(s)
6. Write audit / history row(s)
7. Commit or rollback atomically

### Stock Movement Audit
Every `erp_stock_movements` insert must be traceable to:
- created_by_user_id
- created_at
- movement_type
- part_id, stock_location_id, quantity
- reference_type / reference_id when applicable

No UPDATE or DELETE of movement rows — REVERSAL movement type in future mission.

## Forbidden Changes (Locked from M18/M20)
No authorized change to:
- config.php
- config.example.php
- staff-auth.php
- access-control.php
- Customer Portal files
- Legacy inventory operational files

## Forbidden Operations (Mission 21)
- No role assignment
- No direct permission mutation
- No stock movement write
- No purchase write
- No finance write

## Mission 21 Boundary
Permission and audit rules are documented only.
No permission registration is performed.
No audit tables are created.

## Final Permission Decision
Eight permission keys locked; full write stack required for all future inventory operations; silent stock change explicitly forbidden.

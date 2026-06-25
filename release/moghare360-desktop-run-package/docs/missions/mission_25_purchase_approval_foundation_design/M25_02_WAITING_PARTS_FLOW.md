# Waiting Parts Flow

## Purpose
This document locks the Waiting Parts flow from operational need to purchase request.

## Mission 25 Boundary
Flow documented only. No workflow engine or purchase writes in Mission 25.

## Flow Overview (Locked)

```
Service Operation or JobCard needs a part
  → Stock check: quantity_on_hand insufficient
  → Purchase Request created
  → request_status = DRAFT or SUBMITTED
  → Approval performed in future mission (not Mission 25)
  → Real purchase NOT executed in Mission 25
  → Stock receipt / goods inbound designed in future mission (not Mission 25)
```

## Stage Definitions

### 1. Part Need Identified
- Shop floor identifies missing part for JobCard work
- May correlate with Service Operation status `WAITING_PARTS`
- Mission 24 usage flow rejects ISSUE when stock insufficient — triggers purchase path

### 2. Stock Check Failed
- `quantity_on_hand` from movement ledger < required quantity
- No silent override in Mission 25/26 without explicit permission (future)

### 3. Purchase Request Created (Future — Mission 26)
- Operator creates `erp_purchase_requests` row
- Initial status: **DRAFT** or **SUBMITTED** (per M26 charter)
- Links: `jobcard_id` required; `service_operation_id` optional; `part_id` optional

### 4. DRAFT / SUBMITTED
- DRAFT: editable by creator or owner (see M25_04)
- SUBMITTED: awaits approval; not editable without policy (future)

### 5. Approval (Future — Post M26)
- Approver transitions SUBMITTED → APPROVED or REJECTED
- **No automatic approval** in Mission 25 or initial Mission 26 scope
- APPROVED does **not** create stock receipt
- APPROVED does **not** create finance payment

### 6. Real Purchase (Blocked in M25)
- ORDERED status reserved for future purchase execution mission
- No PO transmission, no supplier portal integration in M25

### 7. Stock Receipt (Future Mission)
- RECEIVED status and RECEIPT movement deferred
- CLOSED after receipt and operational closure (future)

## JobCard / Service Operation Interaction
- Purchase Request must reference valid `jobcard_id`
- `service_operation_id` when set must belong to same JobCard
- Purchase Request does not auto-resolve `WAITING_PARTS` in Mission 25 design lock

## Mission 25 Boundary
No purchase request rows created. No approval engine. No receipt.

## Final Flow Decision
Insufficient stock → purchase request (DRAFT/SUBMITTED) → approval later → purchase execution and receipt in future missions only.

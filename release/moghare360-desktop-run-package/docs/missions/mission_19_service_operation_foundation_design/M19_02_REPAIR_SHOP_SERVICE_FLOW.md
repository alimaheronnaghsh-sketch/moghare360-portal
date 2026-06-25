# Repair Shop Service Flow

## Purpose
This document locks the repair shop service flow from JobCard reception through Service Operation completion.

## Flow Overview (Locked)

```
JobCard RECEIVED
  → Service Operation Created
  → Assigned to technician placeholder
  → Work starts
  → Waiting parts (if needed)
  → Done
  → Later QC (future mission)
  → Later Delivery (future mission)
```

## Stage Definitions

### 1. JobCard RECEIVED
- JobCard exists with status RECEIVED (or equivalent active intake state).
- Customer, vehicle, and reception data are already locked on JobCard.
- Service Operation creation is authorized only against an existing active JobCard.

### 2. Service Operation Created
- One or more Service Operations may be created under the same JobCard.
- Initial Service Operation status = DRAFT (see M19_06).
- Creation writes history action SERVICE_OPERATION_CREATED (Mission 20).

### 3. Assigned to Technician Placeholder
- `assigned_to_user_id` may be set to a nullable user reference.
- Real technician selection rules are deferred to a future mission after HR / Skill / Role design.
- Status may transition to ASSIGNED when assignment is recorded (Mission 20+).

### 4. Work Starts
- Status transitions to IN_PROGRESS when work begins.
- No Inventory, Finance, QC, or Delivery side effects in Mission 19 or initial Mission 20 scope.

### 5. Waiting Parts (If Needed)
- Status may transition to WAITING_PARTS when work is blocked by parts.
- Parts request / inventory integration is a future mission.
- WAITING_PARTS is a status placeholder only in this design phase.

### 6. Done
- Status transitions to DONE when service work is complete from shop floor perspective.
- QC and Delivery are not triggered by DONE in Mission 19 or initial Mission 20 scope.

### 7. Later QC (Future Mission)
- QC review, pass/fail, and QC_REJECTED handling are future missions.
- QC_REJECTED is reserved in the status model for future use.

### 8. Later Delivery (Future Mission)
- Vehicle handover and delivery settlement are future missions.
- No Delivery write in Mission 19 or initial Mission 20 scope.

## Alternate Paths (Reserved)

### CANCELLED
- Service Operation may be cancelled without physical delete.
- Cancellation does not delete the JobCard.
- Cancellation history must be audited in Mission 20+.

### QC_REJECTED
- Reserved for future QC mission.
- May return work to IN_PROGRESS or WAITING_PARTS in a future workflow definition.

## JobCard Status Interaction (Locked)
- Service Operation flow does not directly change JobCard status in Mission 19.
- JobCard status coordination is deferred to a future mission.

## Mission 19 Boundary
Flow is documented only.
No workflow engine is implemented.
No status transition code is created.

## Final Flow Decision
Repair shop service flow starts at JobCard RECEIVED, progresses through Service Operation lifecycle, and defers QC and Delivery to future missions.

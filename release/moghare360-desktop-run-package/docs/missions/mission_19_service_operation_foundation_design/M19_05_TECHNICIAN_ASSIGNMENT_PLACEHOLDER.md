# Technician Assignment Placeholder

## Purpose
This document locks the placeholder design for technician assignment on Service Operation.

## Mission 19 Rule (Locked)
Mission 19 designs assignment as a placeholder only.
No real technician selection workflow is implemented.

## Field Design (Locked)

### assigned_to_user_id
- Type: nullable user reference
- Required: No
- Default on create: NULL
- Meaning: optional assignee for the service work item

## Placeholder Behavior (Design)

### On Create
- Service Operation may be created without assignee.
- assigned_to_user_id may remain NULL until a future assignment action.

### On Assign (Future — Mission 20+)
- Assignment requires permission: service.operation.assign
- Assignment must write history with appropriate action_code (e.g. SERVICE_OPERATION_ASSIGNED)
- Status may transition DRAFT → ASSIGNED when assignment is recorded

## Deferred to Future Mission
Real technician selection is completed only after:
- HR foundation design
- Skill model design
- Role / technician role design
- Workshop capacity rules (if any)

Until then:
- Any valid internal user_id may be used as a prototype placeholder in controlled local testing (Mission 20), subject to Permission Guard
- No skill matching
- No shift / availability check
- No labor rate linkage

## Validation Placeholder (Future)
Minimum Mission 20 validation:
- If assigned_to_user_id is provided, it must reference an existing active user
- If NULL, create is still valid

## UI Placeholder (Future — See M19_09)
- Create form: optional technician dropdown or text user_id field for prototype
- Detail page: show assignee name if set, otherwise "Unassigned"
- Full technician picker UI deferred to post-HR/Skill mission

## Mission 19 Boundary
No assignment UI.
No assignment API.
No user lookup query is implemented.
No HR or Skill tables are designed in Mission 19.

## Final Placeholder Decision
assigned_to_user_id is nullable; real technician assignment rules are deferred; Mission 19 locks the field and permission hook only.

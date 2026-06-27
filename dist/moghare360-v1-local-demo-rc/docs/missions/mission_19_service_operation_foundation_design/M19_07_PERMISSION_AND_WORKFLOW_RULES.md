# Permission and Workflow Rules

## Purpose
This document defines required security, permission, and workflow rules for future Service Operation implementation.

## Required Security Layers (Locked)
Any future Service Operation write must use:
- Auth Context
- Permission Guard
- CSRF (for browser POST writes)
- Controlled transaction (Mission 20)
- Audit / history strategy (Mission 20)
- Safe error handling

## Suggested Permission Actions (Future)
Future permission keys:

| Permission Key | Purpose |
|----------------|---------|
| service.operation.create | Create Service Operation under JobCard |
| service.operation.view | View single Service Operation detail |
| service.operation.list | List Service Operations |
| service.operation.assign | Set or change assigned_to_user_id |
| service.operation.status.change | Transition service_status |

## Permission Rules (Locked)

### Create
- Requires Auth Context
- Requires Permission Guard for service.operation.create
- Requires CSRF on POST create

### View / List
- Requires Auth Context
- Requires service.operation.view or service.operation.list as appropriate

### Assign
- Requires service.operation.assign
- Must write history on assignment change

### Status Change
- Requires service.operation.status.change
- Must write history with old_status and new_status

## Workflow Rules (Locked)

### Create Workflow
1. Authenticate via Auth Context
2. Enforce service.operation.create via Permission Guard
3. Validate CSRF token
4. Validate jobcard_id (exists, active)
5. Begin controlled transaction (Mission 20)
6. INSERT into erp_service_operations
7. INSERT history row SERVICE_OPERATION_CREATED
8. Commit transaction or rollback on failure

### Assign Workflow (Future)
1. Auth Context + Permission Guard (service.operation.assign)
2. CSRF for POST
3. Transaction
4. UPDATE assigned_to_user_id
5. History row with action_code SERVICE_OPERATION_ASSIGNED
6. Optional status transition to ASSIGNED

### Status Change Workflow (Future)
1. Auth Context + Permission Guard (service.operation.status.change)
2. CSRF for POST
3. Transaction
4. Validate allowed transition (see M19_06)
5. UPDATE service_status
6. History row with old_status, new_status, action_code SERVICE_OPERATION_STATUS_CHANGED

## Mission 20 Write Rules (Locked)
Every write in Mission 20 must:
- Use a controlled transaction
- Write audit / history on success
- Roll back on failure
- Not write Inventory, Finance, QC, Delivery, or Invoice

## Platform Owner Prototype Rule
Local controlled prototypes may use placeholder permission fallback only where real permissions are not yet registered:
- user_id = 10001
- owner / system_admin context
- local prototype only

## Forbidden Workflow Actions (Mission 19 and Initial Mission 20)
Must not implement:
- Inventory reservation or issue
- Finance posting
- QC checklist execution
- Delivery handover
- Invoice generation
- JobCard status auto-transition
- Physical delete
- Permission / role mutation
- Tenant implementation

## Forbidden File Changes (Locked from M18)
No authorized change to:
- config.php
- config.example.php
- staff-auth.php
- access-control.php
- Customer Portal files
- Legacy operational files

## Audit / History Rule (Locked)
Service Operation creation must write at least one history row:
- action_code = SERVICE_OPERATION_CREATED

Status changes must write:
- old_status
- new_status
- changed_by_user_id
- changed_at

## Mission 19 Boundary
Permission and workflow rules are documented only.
No permission registration is performed.
No workflow code is created.

## Final Permission Decision
Service Operation writes follow the same controlled pattern as JobCard (M17); five permission keys locked; Mission 20 enforces transaction + history on every write.

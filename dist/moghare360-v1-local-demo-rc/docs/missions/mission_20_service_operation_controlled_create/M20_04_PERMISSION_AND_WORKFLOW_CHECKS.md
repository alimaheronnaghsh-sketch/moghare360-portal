# Mission 20 - Permission and Workflow Checks

## Purpose
This document defines permission and workflow checks for Mission 20 Service Operation prototype.

## Required Security Layers
All Service Operation writes use:
- Auth Context (user_id = 10001 prototype)
- Permission Guard
- CSRF (create POST)
- controlled transaction
- audit / history on create

## Permission Actions

| Page | Action Key | Placeholder (Owner Fallback) |
|------|------------|------------------------------|
| Create POST | service.operation.create | placeholder_service_operation_create |
| List GET | service.operation.list | placeholder_service_operation_list |
| Detail GET | service.operation.view | placeholder_service_operation_view |

Reserved for future missions (not used in M20 writes):
- service.operation.assign
- service.operation.status.change

## Platform Owner Prototype Rule
Local controlled prototype allows:
- user_id = 10001
- owner + system_admin context
- placeholder permission fallback when real permissions not registered

## Workflow Checks on Create
1. Auth Context resolves user_id 10001
2. Permission Guard allows service.operation.create
3. CSRF token validated
4. JobCard validated (exists, ACTIVE)
5. Form validated (title, status)
6. Transaction: INSERT operation → INSERT history → COMMIT

## Read-Only Pages
- List: service.operation.list guard, SELECT only
- Detail: service.operation.view guard, SELECT only
- No POST handlers on list or detail pages

## Forbidden Changes
Mission 20 confirms no change to:
- config.php
- config.example.php
- staff-auth.php
- access-control.php
- Customer Portal files
- Legacy operational files

## Forbidden Operations
- No role assignment
- No direct permission mutation
- No production deploy
- No Inventory / Finance / QC / Delivery / Invoice write

## Audit Rule
Create must write history with action_code SERVICE_OPERATION_CREATED.

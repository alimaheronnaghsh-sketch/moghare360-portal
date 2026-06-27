# Mission 17 - Permission and Workflow Checks

## Purpose
This document defines permission and workflow checks for Mission 17.

## Required Permission Actions
- jobcard.create
- jobcard.view
- jobcard.list

## Placeholder Owner Fallback
If real permissions are not yet registered:
- allow only platform owner user_id 10001
- show PLACEHOLDER or PLACEHOLDER_OWNER_ALLOWED in prototype output
- local prototype only

## Required Security Layers
- Auth Context
- Permission Guard
- CSRF for create POST
- controlled transaction
- audit / history strategy
- safe error handling

## Allowed Workflow in Mission 17
- create JobCard with initial status DRAFT or RECEIVED only
- write JOBCARD_CREATED history
- write JOBCARD_RECEIVED history when status is RECEIVED

## Forbidden Workflow in Mission 17
- status transition beyond initial DRAFT / RECEIVED
- workflow transition engine
- approval workflow
- service workflow
- parts workflow
- finance workflow
- delivery workflow

## Audit / History Checks
Create must write at least:
- JOBCARD_CREATED

If status is RECEIVED:
- JOBCARD_RECEIVED

## Forbidden Mutations
- no core_user_roles write
- no access request workflow write
- no role assignment
- no permission mutation
- no tenant implementation

## Final Decision
Mission 17 follows the same controlled security pattern proven in Mission 15.

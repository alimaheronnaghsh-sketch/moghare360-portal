# Permission and Workflow Rules

## Purpose
This document defines required security and workflow rules for future JobCard implementation.

## Required Security Layers
Any future JobCard write must use:
- Auth Context
- Permission Guard
- CSRF
- controlled transaction
- audit / history strategy
- safe error handling

## Suggested Permission Actions
Future action keys may include:

- jobcard.create
- jobcard.view
- jobcard.list
- jobcard.update
- jobcard.status.transition
- jobcard.cancel

## Mission 17 Permission Rule
Mission 17 may use placeholder permissions with Platform Owner fallback only if real permissions are not yet registered.

Allowed prototype fallback:
- user_id = 10001
- owner/system_admin context
- local prototype only

## Workflow Boundary
Mission 17 may create initial JobCard status only.

Mission 17 must not implement status transitions.

## Audit / History Rule
JobCard creation must write at least one history row:
- JOBCARD_CREATED

If status is RECEIVED:
- JOBCARD_RECEIVED

## Forbidden Workflow Actions
Mission 17 must not implement:
- approval workflow
- service workflow
- parts workflow
- finance workflow
- delivery workflow

## Final Permission Decision
JobCard write requires the same controlled pattern proven in Mission 15.

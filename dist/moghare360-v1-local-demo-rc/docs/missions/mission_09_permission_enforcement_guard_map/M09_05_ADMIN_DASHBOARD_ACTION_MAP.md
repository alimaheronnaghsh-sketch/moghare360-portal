# Admin Dashboard Action Map

## Purpose
This document maps read-only Admin and Dashboard actions to future Permission Guard requirements.

## Action Map

| Action Key | Mode | Required Permission Future Placeholder | CSRF Required | Write Allowed | Assignment Allowed |
|---|---|---|---|---|---|
| admin.dashboard.view | Read-only | admin.dashboard.view | No | No | No |
| admin.bootstrap.status.view | Read-only | admin.bootstrap.status.view | No | No | No |
| admin.workflow.viewer.view | Read-only | access.request.view_all | No | No | No |
| admin.auth.context.test.view | Read-only | admin.auth.context.test.view | No | No | No |
| admin.foundation.lock.view | Read-only | admin.foundation.lock.view | No | No | No |

## Read-Only Rule
Read-only admin views:
- do not require CSRF
- must not write
- must not assign roles
- must not modify permissions
- must not change workflow state

## Future Production Note
Some placeholder permissions may not exist yet.

They must not be created in Mission 9.

## Mission 9 Boundary
This is a planning map only.

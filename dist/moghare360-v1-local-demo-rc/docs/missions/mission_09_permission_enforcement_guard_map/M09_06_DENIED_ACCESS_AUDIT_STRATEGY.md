# Denied Access Audit Strategy

## Purpose
This document defines the future strategy for handling denied permission checks.

## Access Denied Behavior
When a user is not allowed to perform an action:

- stop the action
- show safe Access Denied message
- do not expose SQL errors
- do not expose stack traces
- do not expose permission internals beyond what is safe
- do not execute the target action

## Safe Message
Recommended user-facing message:

Access denied. You do not have permission to perform this action.

## Future Denied Audit Fields
Future denied audit may record:

- actor_user_id
- permission_key
- action_key
- target_entity
- target_id
- ip_address placeholder
- user_agent placeholder
- occurred_at

## Audit Boundary
No audit insert implementation in Mission 9.

Only strategy.

## Future Rule
Denied access audit must be designed before production Permission Guard is enabled.

## Mission 9 Boundary
No database table is created.
No INSERT is performed.
No audit write is performed.

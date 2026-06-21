# Permission Context Design

Project: MOGHARE360 ERP
Mission: Mission 7
Document Type: Permission Context Design
Scope: Design Documentation Only

## Purpose
Permission Context defines how runtime permission checks should work after Auth Context is approved.

## Permission Source
Permissions should be derived from active roles.

## Role Permissions Lock
Current locked role permission count:

- role_permissions = 162

## Permission Keys
Permission checks should use stable permission keys.

## Runtime Permission Cache Placeholder
Future Auth Context may cache permission keys during a request to avoid repeated queries.

## Future Function Shape
Future permission checking may use:

can_user_perform_action(user_id, permission_key)

or PHP helper shape:

erp_auth_can(permission_key)

## No Permission Modification
Mission 7 does not create permissions.
Mission 7 does not modify permissions.
Mission 7 does not change role_permissions.

## Mission 7 Boundary
Permission Context is design-only in Mission 7.

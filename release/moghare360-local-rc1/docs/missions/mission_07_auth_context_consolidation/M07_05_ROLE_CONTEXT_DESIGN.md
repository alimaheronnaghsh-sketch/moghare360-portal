# Role Context Design

Project: MOGHARE360 ERP
Mission: Mission 7
Document Type: Role Context Design
Scope: Design Documentation Only

## Purpose
Role Context defines how the system understands the authenticated user's active roles.

## Role Source
Future Role Context should read roles from:

- core_user_roles

## Active Role Filter
Only active roles should be considered for runtime authorization.

## Temporary Role Awareness
Temporary roles must respect:
- effective_from
- expires_at
- active state

## Locked Platform Roles
Current Platform Owner has:

- owner
- system_admin

## System Owner Role
The owner role is allowed for controlled setup authority.

## System Admin Role
The system_admin role supports admin prototype execution.

## No Direct Role Assignment
Mission 7 does not assign roles.

## Real Assignment Boundary
Real Assignment remains deferred after Mission 7.

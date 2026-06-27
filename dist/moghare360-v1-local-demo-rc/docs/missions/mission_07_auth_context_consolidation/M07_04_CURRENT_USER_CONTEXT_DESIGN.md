# Current User Context Design

Project: MOGHARE360 ERP
Mission: Mission 7
Document Type: Current User Context Design
Scope: Design Documentation Only

## Purpose
Current User Context defines the trusted runtime identity shape for ERP admin pages.

## Future Current User Fields
The future current user context should expose:

- current_user_id
- username
- full_name
- is_system_owner
- is_login_enabled
- lifecycle_state
- active roles
- active permissions

## current_user_id
The current_user_id must come from Auth Context and must not be guessed by pages.

## username
The username should be loaded from the authenticated user record.

## full_name
full_name should be available for display and audit context.

## is_system_owner
is_system_owner identifies Platform Owner authority but must not become a permanent production bypass.

## is_login_enabled
is_login_enabled must be checked before allowing active login access.

## lifecycle_state
lifecycle_state must be checked in future production hardening.

## Active Roles
Active roles must be loaded through Role Context.

## Active Permissions
Active permissions must be loaded through Permission Context.

## Mission 7 Boundary
No user query helper is created in Mission 7.

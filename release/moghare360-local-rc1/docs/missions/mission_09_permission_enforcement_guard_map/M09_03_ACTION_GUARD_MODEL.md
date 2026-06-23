# Action Guard Model

## Purpose
The Action Guard Model defines the standard control structure that every future ERP action must pass through.

## Guard Fields
Each protected action must define:

- Action Key
- Required Permission
- Required Role optional
- Actor User
- Target Entity
- Target ID optional
- Current State
- Allowed Transition
- CSRF Required
- Audit Required
- Deny Behavior

## Action Key
A stable action identifier used by the application.

Example:
access.request.approve

## Required Permission
A permission key that must exist in the user's active permission set.

Example:
access.request.approve

## Required Role Optional
Some actions may require a special role in addition to permission.

Default:
Permission should be enough unless future policy requires role restriction.

## Actor User
The authenticated user from Auth Context.

## Target Entity
The entity being acted on.

Examples:
- core_access_requests
- core_access_request_items
- admin_dashboard

## Current State
Workflow actions must check the current state before allowing transitions.

## Allowed Transition
Workflow transitions must be explicitly allowed.

Example:
UNDER_REVIEW -> APPROVED

## CSRF Required
Write actions require CSRF protection.

Read-only actions do not require CSRF.

## Audit Required
Write actions require future audit.
Denied attempts should have future audit strategy.

## Deny Behavior
Denied access must:
- stop execution
- show safe access denied message
- avoid sensitive technical detail
- optionally record future audit

## Core Rules
No action should run without guard.

No write should run without:
- Auth
- Permission
- CSRF
- Audit

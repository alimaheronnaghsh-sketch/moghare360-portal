# Phase 1A Access Request Create UI Plan

## Project
MOGHARE360 ERP

## Phase
Phase 1A

## Purpose
Plan the first controlled write-enabled ERP UI for creating access requests.

This document is planning-only.

No UI is created in this step.

No runtime behavior is changed in this step.

## Current Completed Foundation

The following Phase 1A security foundation is complete for local prototype scope:

- ERP Config Loader
- ERP Admin Login Prototype
- ERP Admin Logout Prototype
- ERP Auth Helper
- ERP Permission Helper
- Protected Read-Only ERP Admin Area
- ERP Audit Helper
- ERP Audit Write Completion Review
- ERP CSRF Helper
- ERP CSRF Protection Completion Review

## First Write UI Scope

The first planned write-enabled ERP UI will be:

- Access Request Create UI

Future candidate file:

- erp-access-request-create.php

This file is not created in this step.

## Main Boundary

No write-enabled UI may be created unless it uses:

- ERP Auth Helper
- ERP Permission Helper
- ERP CSRF Helper
- ERP Audit Helper
- Safe input validation
- Controlled database write boundary
- Safe error handling

## Intended Business Purpose

The Access Request Create UI will allow an authorized ERP user to create a controlled access request for a user access lifecycle process.

The request may later be reviewed, approved, rejected, or audited according to the ERP access governance workflow.

## Future Protected Page Requirements

The future page must:

- require active ERP login
- require allowed ERP role or permission
- reject unauthenticated access
- reject unauthorized access
- use CSRF token generation on GET form display
- use CSRF token validation on POST submit
- validate all submitted inputs
- insert only into the approved access request table
- write a safe audit event after successful creation
- never expose raw SQL errors
- never expose stack traces
- never expose config secrets
- never expose password_hash
- never expose CSRF token internals
- never use old portal login
- never use old portal session keys

## Required Future Dependencies

The future Access Request Create UI must use:

- includes/erp-config-loader.php
- includes/erp-auth-helper.php
- includes/erp-permission-helper.php
- includes/erp-csrf-helper.php
- includes/erp-audit-helper.php

The future page must not use:

- staff-auth.php
- access-control.php
- config.php
- config.example.php
- old portal login session keys

## Future Database Discovery Required

Before creating the UI, the project must discover the exact access request table and columns.

The future discovery must identify:

- table name
- primary key
- required columns
- nullable columns
- default values
- foreign key relationships
- approval workflow fields
- audit-related fields
- created_at or requested_at field
- status field
- requester field
- subject user field if applicable
- requested role or permission field if applicable

No SQL modification is approved in this plan.

## Future Candidate Tables

Possible table names to investigate:

- dbo.core_access_requests
- dbo.core_user_access_requests
- dbo.core_access_lifecycle_requests
- dbo.core_approval_requests

The exact table must be discovered before implementation.

## Future Input Fields

The future form may include only approved fields after database discovery.

Possible future fields:

- request_type
- subject_user_id
- requested_role_code
- requested_permission_code
- reason
- priority
- requested_start_date
- requested_end_date

Final fields must depend on actual database schema.

## Future Validation Rules

The future submit handler must validate:

- request_type is allowed
- subject_user_id is numeric if used
- requested_role_code exists if used
- requested_permission_code exists if used
- reason length is limited
- priority is allowed if used
- dates are valid if used
- date range is valid if used
- required fields are not empty
- current user is allowed to create the request
- duplicate active request is handled if applicable

## Future Audit Requirement

Successful creation must write a safe audit event.

Potential audit action:

- ERP_ACCESS_REQUEST_CREATED

Failed validation may be audited only after explicit approval.

The audit details must not include:

- password
- password_hash
- CSRF token
- session token
- database password
- config secrets
- SQL errors
- stack trace

## Future Success Behavior

After successful creation, the UI should show a safe message:

- ERP access request created.

It should not expose internal IDs unless explicitly approved later.

## Future Failure Behavior

Validation failure should show safe user-facing validation messages.

Security failure should show only generic safe messages.

Database failure should show only:

- ERP request could not be completed.

Raw database errors must not be displayed.

## Future Test Requirements

After implementation, the project must test:

- unauthenticated access is blocked
- unauthorized access is blocked
- authorized GET loads form
- CSRF hidden input exists
- POST without CSRF fails
- POST with invalid CSRF fails
- POST with valid CSRF continues
- invalid input fails safely
- valid input creates exactly one access request
- successful creation writes audit event
- raw SQL errors are not displayed
- secrets are not displayed
- no old portal auth dependency exists

## Not Approved In This Step

The following are not approved now:

- Creating erp-access-request-create.php
- Creating Access Request Create UI
- Creating any form
- Creating any submit handler
- Performing database writes
- Modifying SQL files
- Creating migrations
- Creating users
- Assigning roles
- Modifying permissions
- Modifying auth helper
- Modifying permission helper
- Modifying CSRF helper
- Modifying audit helper
- Modifying dashboard
- Production deployment

## Final Decision

This document only approves planning the first controlled write-enabled ERP UI.

No write-enabled UI is created.

No runtime behavior is changed.

The required next step is:

- Access Request Table Discovery

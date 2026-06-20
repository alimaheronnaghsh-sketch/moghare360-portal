# Phase 2 Access Request Workflow Transition Browser Action Plan

Project: MOGHARE360 ERP  
Phase: Phase 2  
Document Type: Execution Plan  
Status: Design Only  
Implementation Status: Not Started  

## 1. Purpose

This document defines the first controlled browser-based workflow transition plan for MOGHARE360 ERP.

The first real workflow transition must be implemented only after this plan is reviewed and committed.

## 2. Current System State

Current real phase:

    Core ERP Foundation + Controlled Admin Prototype

Confirmed facts:

    role_permission_count = 162
    core_access_requests = 2
    D01 - D19 = OK
    Overall Status = OK

Latest confirmed commit:

    8de2ae5 Fix ERP admin dashboard D16 access request count

## 3. Scope

The first workflow transition will be limited to Access Request only.

Target transition:

    DRAFT -> SUBMITTED

No other workflow is included in this phase.

Out of scope:

- JobCard workflow
- Customer workflow
- Inventory workflow
- Tenant creation
- User creation
- Role assignment
- Permission changes
- Production login replacement

## 4. Browser Action Rule

The transition must happen only through a browser form.

Required execution chain:

    Browser Form
    -> CSRF Validation
    -> Auth Check
    -> Permission Check
    -> Workflow Engine
    -> Audit / History
    -> State Update

No UI file may directly update database tables.

## 5. Required Layers

Before any state update, the following checks must be enforced:

| Layer | Required |
|---|---|
| Session/Auth Context | Yes |
| CSRF Token | Yes |
| Permission Check | Yes |
| Workflow Transition Validation | Yes |
| Audit Insert | Yes |
| History Insert | Yes |
| State Update | Yes |

## 6. Target Data Object

Target table:

    dbo.core_access_requests

Target state column:

    request_state

Initial state:

    DRAFT

Next state:

    SUBMITTED

## 7. Planned Future Files

These files may be created later, but not in this phase:

    public_html/erp-access-request-transition.php
    includes/erp-auth-context.php
    includes/erp-csrf.php
    includes/erp-permission-check.php
    includes/erp-workflow-engine.php

This document does not create or modify those files.

## 8. Permission Rule

The future transition must require a permission similar to:

    access_request.submit

If the permission does not exist yet, it must not be created directly during implementation.

Permission creation must be planned separately.

## 9. Audit Rule

Every successful transition must create audit/history records.

Minimum required audit data:

    actor_user_id
    request_id
    old_state
    new_state
    action_code
    created_at
    source_ip
    user_agent

## 10. Failure Rule

If any required check fails, the transition must stop.

Failure cases:

    Missing session
    Invalid CSRF token
    Missing permission
    Invalid current state
    Invalid target state
    Database error
    Audit failure
    History failure

No partial state update is allowed.

## 11. Safety Rules

This phase is design-only.

This phase must not change:

- Login logic
- Config files
- Users
- Roles
- Permissions
- Workflow state
- Tenant data
- Customer portal files
- Inventory files
- Legacy files
- SQL schema
- Runtime behavior

## 12. Final Decision

The first controlled write action in MOGHARE360 ERP will be:

    Access Request Workflow Transition: DRAFT -> SUBMITTED

Implementation is not allowed until this plan is reviewed, tested as a document-only change, committed, and pushed.

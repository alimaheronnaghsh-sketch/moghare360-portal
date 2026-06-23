# Phase 2 Controlled Prototype Implementation Plan

Project: MOGHARE360 ERP  
Phase: Phase 2  
Document Type: Implementation Plan  
Status: Planning Only  
Implementation Status: Not Started  

## 1. Purpose

This document defines the controlled prototype implementation plan for the first browser-based workflow write action in MOGHARE360 ERP.

This is still a planning document.

No implementation is approved by this document alone.

## 2. Approved Direction

Approved workflow area:

    Access Request Workflow

Approved first transition:

    DRAFT -> SUBMITTED

Approved execution type:

    Browser-based controlled transition

Approved write scope:

    dbo.core_access_requests.request_state

## 3. Current Confirmed System State

Current real phase:

    Core ERP Foundation + Controlled Admin Prototype

Confirmed facts:

    role_permission_count = 162
    core_access_requests = 2
    D01 - D19 = OK
    Overall Status = OK

## 4. Required Execution Chain

The prototype must follow this chain:

    Browser Form
    -> CSRF Validation
    -> Auth Check
    -> Permission Check
    -> Workflow Engine
    -> Audit / History
    -> State Update

No UI file may directly update database tables.

## 5. Prototype Boundary

The prototype must be limited to:

    Access Request DRAFT -> SUBMITTED

The prototype must not include:

- JobCard workflow
- Customer workflow
- Inventory workflow
- Tenant creation
- User creation
- Role creation
- Role assignment
- Permission creation
- Production login replacement

## 6. Future Planned Files

The following files may be created during the implementation phase only after this plan is reviewed, committed, and pushed:

    public_html/erp-access-request-transition.php
    includes/erp-auth-context.php
    includes/erp-csrf.php
    includes/erp-permission-check.php
    includes/erp-workflow-engine.php

This planning document does not create those files.

## 7. Files That Must Not Be Modified

The implementation phase must not modify:

    staff-auth.php
    access-control.php
    staff-login.php
    config.php
    config.example.php
    customer portal files
    inventory files
    legacy files

## 8. Auth Context Requirement

The prototype must have a single controlled auth context.

Minimum required values:

    current_user_id
    username
    is_system_owner
    active_roles
    active_permissions
    tenant_context

For the first prototype:

    tenant_context = PLATFORM_DEFAULT

## 9. CSRF Requirement

The browser form must include a CSRF token.

The POST handler must reject the request if:

    CSRF token is missing
    CSRF token is invalid
    Session is missing
    Session is expired

## 10. Permission Requirement

The transition must require permission validation.

Target future permission concept:

    access_request.submit

If this permission does not already exist, it must not be created during the first implementation.

The first implementation must document the missing permission dependency before enabling the transition.

## 11. Workflow Engine Requirement

The workflow engine must validate:

    entity_type = ACCESS_REQUEST
    current_state = DRAFT
    target_state = SUBMITTED
    transition_code = SUBMIT

Invalid transitions must stop before any database update.

## 12. Audit and History Requirement

Every successful transition must write audit/history records.

Minimum required data:

    actor_user_id
    entity_type
    entity_id
    old_state
    new_state
    action_code
    created_at
    source_ip
    user_agent

If audit/history write fails, the transition must stop and rollback.

## 13. Transaction Rule

The future implementation must use one database transaction.

The transaction must include:

    workflow validation
    audit insert
    history insert
    state update

If any step fails:

    rollback transaction

No partial write is allowed.

## 14. Browser Test Requirement

The prototype must be tested from the browser only.

Required browser tests:

    Open transition page
    Confirm session/auth context is active
    Confirm CSRF token exists
    Submit DRAFT request
    Confirm transition result
    Confirm request_state changed to SUBMITTED
    Confirm audit/history rows were created
    Confirm invalid resubmit is blocked

## 15. SQL Verification Requirement

After browser test, SQL verification must confirm:

    request_state = SUBMITTED
    audit row exists
    history row exists
    no unrelated table changed

SQL verification must be read-only SELECT only.

## 16. Rollback Rule

Rollback must be planned before implementation.

Rollback must define:

    how to return request_state to DRAFT
    how to preserve audit/history truth
    how to document rollback
    how to avoid silent data cleanup

No rollback SQL is approved in this document.

## 17. Safety Blockers

Implementation must not start if any of these are missing:

    Auth context plan
    CSRF plan
    Permission check plan
    Workflow engine plan
    Audit/history plan
    Browser test plan
    Rollback plan

## 18. Final Decision

The next implementation phase may only begin after this plan is reviewed, committed, and pushed.

This document approves planning for the controlled prototype only.

It does not approve PHP implementation yet.

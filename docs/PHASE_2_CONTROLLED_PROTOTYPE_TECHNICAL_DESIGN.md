# Phase 2 Controlled Prototype Technical Design

Project: MOGHARE360 ERP  
Phase: Phase 2  
Document Type: Technical Design  
Status: Design Only  
Implementation Status: Not Started  

## 1. Purpose

This document defines the technical design for the first controlled browser-based workflow prototype in MOGHARE360 ERP.

This design is not an implementation.

No PHP, SQL, user, role, permission, tenant, or workflow write change is approved by this document alone.

## 2. Approved Prototype Scope

Approved workflow area:

    Access Request Workflow

Approved first transition:

    DRAFT -> SUBMITTED

Approved entity:

    dbo.core_access_requests

Approved state column:

    request_state

Approved execution type:

    Browser-based controlled transition

## 3. Current Confirmed System State

Current real phase:

    Core ERP Foundation + Controlled Admin Prototype

Confirmed facts:

    role_permission_count = 162
    core_access_requests = 2
    D01 - D19 = OK
    Overall Status = OK

## 4. Technical Boundary

The prototype must be isolated from legacy login and portal logic.

Files that must not be modified:

    staff-auth.php
    access-control.php
    staff-login.php
    config.php
    config.example.php
    customer portal files
    inventory files
    legacy files

The prototype must use new ERP-specific files only.

## 5. Planned Future Files

The implementation phase may create these files only after this technical design is reviewed, committed, and pushed:

    public_html/erp-access-request-transition.php
    includes/erp-auth-context.php
    includes/erp-csrf.php
    includes/erp-permission-check.php
    includes/erp-workflow-engine.php

No file is created by this design document.

## 6. Planned Browser Page

Planned file:

    public_html/erp-access-request-transition.php

Planned responsibility:

    Render controlled browser form
    Load current auth context
    Create CSRF token
    Show eligible DRAFT access requests
    Submit transition request by POST
    Show result message
    Never directly update database tables

Allowed methods:

    GET
    POST

GET responsibility:

    Display form and request state

POST responsibility:

    Submit requested transition into controlled execution chain

## 7. Planned Auth Context Layer

Planned file:

    includes/erp-auth-context.php

Planned functions:

    erp_auth_get_current_context()
    erp_auth_require_current_user()
    erp_auth_is_system_owner(array $context)
    erp_auth_get_user_roles(array $context)
    erp_auth_get_user_permissions(array $context)

Minimum context fields:

    current_user_id
    username
    full_name
    is_system_owner
    active_roles
    active_permissions
    tenant_context

First prototype tenant context:

    PLATFORM_DEFAULT

Temporary prototype actor:

    user_id = 10001
    username = mahin.paradigm.owner

## 8. Planned CSRF Layer

Planned file:

    includes/erp-csrf.php

Planned functions:

    erp_csrf_create_token(string $form_key)
    erp_csrf_validate_token(string $form_key, string $token)
    erp_csrf_require_valid_token(string $form_key, string $token)

Required behavior:

    Token must be stored in session
    Token must be included in POST form
    Missing token must block transition
    Invalid token must block transition
    Expired session must block transition

Form key:

    access_request_submit

## 9. Planned Permission Check Layer

Planned file:

    includes/erp-permission-check.php

Planned functions:

    erp_permission_user_has(array $context, string $permission_key)
    erp_permission_require(array $context, string $permission_key)

Target permission concept:

    access_request.submit

Important rule:

    If permission access_request.submit does not exist yet, implementation must stop before enabling real transition.

No permission may be created silently during implementation.

## 10. Planned Workflow Engine Layer

Planned file:

    includes/erp-workflow-engine.php

Planned functions:

    erp_workflow_can_transition(string $entity_type, string $current_state, string $target_state)
    erp_workflow_require_transition(string $entity_type, string $current_state, string $target_state)
    erp_workflow_transition_access_request_submit(int $request_id, array $context)

Target entity type:

    ACCESS_REQUEST

Allowed transition:

    DRAFT -> SUBMITTED

Transition code:

    SUBMIT

Invalid transitions must stop before any database update.

## 11. Planned Execution Chain

The final implementation must execute in this order:

    Browser POST
    -> Session/Auth Context Load
    -> CSRF Validation
    -> Permission Validation
    -> Load Access Request
    -> Validate current_state = DRAFT
    -> Validate target_state = SUBMITTED
    -> Begin SQL Transaction
    -> Insert Audit Row
    -> Insert History Row
    -> Update core_access_requests.request_state
    -> Commit SQL Transaction
    -> Show Browser Result

No step may be skipped.

## 12. Planned Transaction Boundary

The SQL transaction must include:

    audit insert
    history insert
    state update

If any step fails:

    rollback transaction

No partial write is allowed.

## 13. Planned Audit Data

Every successful transition must record:

    actor_user_id
    entity_type
    entity_id
    old_state
    new_state
    action_code
    source_ip
    user_agent
    created_at

Action code:

    ACCESS_REQUEST_SUBMIT

## 14. Planned History Data

Every successful transition must record:

    request_id
    user_id
    old_state
    new_state
    change_reason
    changed_at
    changed_by

Change reason:

    Browser controlled workflow transition: DRAFT -> SUBMITTED

## 15. Planned Browser Test

The implementation test must confirm:

    Page opens in browser
    Auth context loads
    CSRF token exists
    DRAFT request is visible
    Submit button is visible only for eligible request
    POST succeeds for valid DRAFT request
    D16 remains OK after transition if dashboard expected count is unchanged
    request_state changes from DRAFT to SUBMITTED
    audit/history rows are created
    second submit attempt is blocked

## 16. Planned Read-Only SQL Verification

SQL verification after browser test must use SELECT only.

Required checks:

    Confirm target request_state = SUBMITTED
    Confirm audit row exists
    Confirm history row exists
    Confirm no unrelated user changed
    Confirm no role changed
    Confirm no permission changed
    Confirm no tenant changed

No verification SQL may perform INSERT, UPDATE, DELETE, MERGE, DROP, ALTER, or TRUNCATE.

## 17. Planned Rollback Documentation Rule

Rollback must not silently erase history.

If rollback is needed, it must be documented as a new controlled change.

Rollback must define:

    old_state
    rollback_state
    rollback_reason
    actor_user_id
    audit preservation rule
    history preservation rule

No rollback SQL is approved in this design.

## 18. Implementation Blockers

Implementation must not begin if any of these are missing:

    Confirmed target DRAFT request
    Confirmed auth context strategy
    Confirmed CSRF strategy
    Confirmed permission strategy
    Confirmed workflow engine strategy
    Confirmed audit/history table structure
    Confirmed rollback approach
    Confirmed browser test checklist

## 19. Final Technical Decision

The controlled prototype may be designed around new ERP-specific files only.

The first controlled write action remains:

    Access Request Workflow Transition: DRAFT -> SUBMITTED

Implementation is still blocked until this technical design is reviewed, committed, pushed, and signed off.

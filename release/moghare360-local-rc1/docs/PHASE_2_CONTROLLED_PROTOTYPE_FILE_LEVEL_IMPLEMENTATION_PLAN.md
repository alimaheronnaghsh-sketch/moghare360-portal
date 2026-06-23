# Phase 2 Controlled Prototype File-Level Implementation Plan

Project: MOGHARE360 ERP  
Phase: Phase 2  
Document Type: File-Level Implementation Plan  
Status: Planning Only  
Implementation Status: Not Started  

## 1. Purpose

This document defines the exact file-level implementation plan for the first controlled browser-based workflow prototype in MOGHARE360 ERP.

This is still a planning document.

No PHP implementation is approved by this document alone.

## 2. Approved Baseline

Approved source document:

    docs/PHASE_2_CONTROLLED_PROTOTYPE_TECHNICAL_DESIGN.md

Approved sign-off document:

    docs/PHASE_2_CONTROLLED_PROTOTYPE_TECHNICAL_DESIGN_SIGNOFF.md

Approved workflow area:

    Access Request Workflow

Approved first transition:

    DRAFT -> SUBMITTED

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

## 4. Implementation Boundary

The future implementation must create new ERP-specific files only.

The future implementation may create these files:

    public_html/erp-access-request-transition.php
    includes/erp-auth-context.php
    includes/erp-csrf.php
    includes/erp-permission-check.php
    includes/erp-workflow-engine.php

The future implementation must not modify:

    staff-auth.php
    access-control.php
    staff-login.php
    config.php
    config.example.php
    customer portal files
    inventory files
    legacy files

## 5. File 1: Browser Transition Page

Planned file:

    public_html/erp-access-request-transition.php

Purpose:

    Provide the browser interface for controlled Access Request transition.

Responsibilities:

    Start session
    Load ERP auth context
    Load CSRF helper
    Load permission helper
    Load workflow engine
    Render GET form
    Process POST request
    Show controlled success or failure message
    Never directly update database tables outside the workflow engine

Allowed methods:

    GET
    POST

GET behavior:

    Show eligible Access Requests
    Show current request_state
    Show submit button only for DRAFT request
    Include CSRF token

POST behavior:

    Validate CSRF token
    Validate auth context
    Validate permission
    Call workflow engine
    Show result message

## 6. File 2: ERP Auth Context

Planned file:

    includes/erp-auth-context.php

Purpose:

    Provide a single ERP-specific current user context for the controlled prototype.

Planned functions:

    erp_auth_start_session_if_needed()
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

Temporary prototype actor:

    user_id = 10001
    username = mahin.paradigm.owner

Tenant context for first prototype:

    PLATFORM_DEFAULT

Important rule:

    This file must not replace legacy login.

## 7. File 3: ERP CSRF Helper

Planned file:

    includes/erp-csrf.php

Purpose:

    Protect the browser POST transition request.

Planned functions:

    erp_csrf_create_token(string $form_key)
    erp_csrf_validate_token(string $form_key, string $token)
    erp_csrf_require_valid_token(string $form_key, string $token)

Form key:

    access_request_submit

Required behavior:

    Token must be stored in session
    Token must be rendered in hidden form field
    Missing token must block transition
    Invalid token must block transition
    Expired session must block transition

## 8. File 4: ERP Permission Check

Planned file:

    includes/erp-permission-check.php

Purpose:

    Validate whether the current ERP user can perform the requested workflow action.

Planned functions:

    erp_permission_user_has(array $context, string $permission_key)
    erp_permission_require(array $context, string $permission_key)

Target permission concept:

    access_request.submit

Important rule:

    If access_request.submit does not exist yet, implementation must not silently create it.

Prototype fallback rule:

    Platform Owner user_id 10001 may be used only as temporary prototype actor if clearly documented in implementation result.

## 9. File 5: ERP Workflow Engine

Planned file:

    includes/erp-workflow-engine.php

Purpose:

    Own the controlled workflow transition logic.

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

Action code:

    ACCESS_REQUEST_SUBMIT

Important rule:

    The browser page must call the workflow engine.
    The browser page must not directly update request_state.

## 10. Required POST Flow

The future POST flow must execute in this exact order:

    Start session
    Load auth context
    Validate current user
    Validate CSRF token
    Validate permission
    Read request_id from POST
    Load access request from dbo.core_access_requests
    Confirm current request_state = DRAFT
    Confirm target request_state = SUBMITTED
    Begin SQL transaction
    Insert audit row
    Insert history row
    Update dbo.core_access_requests.request_state
    Commit SQL transaction
    Show success message

If any step fails:

    Stop execution
    Rollback if transaction started
    Show safe failure message
    Do not partially update database

## 11. Required SQL Transaction Boundary

The transaction must include:

    Audit insert
    History insert
    State update

The transaction must not include:

    User creation
    Role creation
    Permission creation
    Tenant creation
    Config change
    Schema change

## 12. Required Audit Behavior

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

Required action code:

    ACCESS_REQUEST_SUBMIT

## 13. Required History Behavior

Every successful transition must record:

    request_id
    user_id
    old_state
    new_state
    change_reason
    changed_at
    changed_by

Required change reason:

    Browser controlled workflow transition: DRAFT -> SUBMITTED

## 14. Required Browser Test Sequence

The future implementation must be tested in browser using this sequence:

    Open erp-access-request-transition.php
    Confirm page loads
    Confirm auth context shows Platform Owner
    Confirm CSRF token exists
    Confirm DRAFT request is visible
    Submit DRAFT request
    Confirm success message
    Refresh dashboard
    Confirm dashboard still loads
    Confirm second submit attempt is blocked

## 15. Required Read-Only SQL Verification

After browser test, verify using SELECT only:

    Target request_state = SUBMITTED
    Audit row exists
    History row exists
    No user was created
    No role was created
    No permission was created
    No tenant was created
    No schema changed

No verification SQL may use:

    INSERT
    UPDATE
    DELETE
    MERGE
    DROP
    ALTER
    TRUNCATE

## 16. Required Runtime Copy Rule

If the implementation creates files under repository path, runtime copy must be handled explicitly.

Current local runtime folder:

    C:\xampp\htdocs\moghare360

Current repository folder:

    C:\Users\User\Documents\GitHub\alimaheronnaghsh-sketch\moghare360-portal

Current local URL standard for now:

    http://localhost:8080/moghare360/

Future naming standard:

    moghareh360

## 17. Commit Boundary

The future implementation should be committed in small safe steps.

Recommended commit sequence:

    Commit 1: Create ERP auth context helper
    Commit 2: Create ERP CSRF helper
    Commit 3: Create ERP permission check helper
    Commit 4: Create ERP workflow engine helper
    Commit 5: Create browser transition page
    Commit 6: Add browser test result document

No commit may mix unrelated legacy changes.

## 18. Implementation Blockers

Implementation must not start if any of these are missing:

    Confirmed DRAFT access request
    Confirmed table structure for audit/history
    Confirmed permission strategy
    Confirmed transaction strategy
    Confirmed runtime copy path
    Confirmed browser test checklist
    Confirmed rollback documentation rule

## 19. Final Decision

The next approved step after this document is:

    File-Level Implementation Plan Sign-Off

PHP implementation remains blocked until this file-level plan is reviewed, committed, pushed, and signed off.

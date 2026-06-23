# Phase 2 Controlled Prototype File-Level Implementation Plan Sign-Off

Project: MOGHARE360 ERP  
Phase: Phase 2  
Document Type: Sign-Off  
Status: Approved for First Controlled PHP Helper Implementation  
Implementation Status: Not Started  

## 1. Sign-Off Purpose

This document confirms that the Phase 2 Controlled Prototype File-Level Implementation Plan has been reviewed and accepted.

This sign-off approves moving to the first controlled PHP helper implementation step.

This sign-off does not approve the full workflow transition page yet.

## 2. Approved Source Document

Approved document:

    docs/PHASE_2_CONTROLLED_PROTOTYPE_FILE_LEVEL_IMPLEMENTATION_PLAN.md

Approved prototype scope:

    Access Request Workflow

Approved first transition:

    DRAFT -> SUBMITTED

Approved execution type:

    Browser-based controlled transition

## 3. Confirmed Current System State

Current real phase:

    Core ERP Foundation + Controlled Admin Prototype

Confirmed facts:

    role_permission_count = 162
    core_access_requests = 2
    D01 - D19 = OK
    Overall Status = OK

## 4. Approved Future File Set

The controlled prototype may eventually create these new ERP-specific files:

    public_html/erp-access-request-transition.php
    includes/erp-auth-context.php
    includes/erp-csrf.php
    includes/erp-permission-check.php
    includes/erp-workflow-engine.php

## 5. Approved First Implementation Step

The first approved implementation step after this sign-off is:

    Create ERP Auth Context Helper

Approved first file:

    includes/erp-auth-context.php

This first implementation step must be safe and isolated.

## 6. Files Still Not Approved for Creation Yet

The following files are not approved for creation in the first implementation step:

    public_html/erp-access-request-transition.php
    includes/erp-csrf.php
    includes/erp-permission-check.php
    includes/erp-workflow-engine.php

These files require separate step-by-step approval.

## 7. Files That Must Not Be Modified

The first implementation step must not modify:

    staff-auth.php
    access-control.php
    staff-login.php
    config.php
    config.example.php
    customer portal files
    inventory files
    legacy files
    existing dashboard files

## 8. Approved Auth Context Helper Boundary

The first helper may define:

    erp_auth_start_session_if_needed()
    erp_auth_get_current_context()
    erp_auth_require_current_user()
    erp_auth_is_system_owner(array $context)
    erp_auth_get_user_roles(array $context)
    erp_auth_get_user_permissions(array $context)

The helper must not:

    replace legacy login
    create users
    assign roles
    change permissions
    write workflow state
    update database tables

## 9. Prototype Actor Rule

Temporary prototype actor:

    user_id = 10001
    username = mahin.paradigm.owner

Tenant context for first prototype:

    PLATFORM_DEFAULT

This is temporary for controlled prototype only.

## 10. Safety Rules

The first implementation must remain read-only.

Allowed operations:

    session read/write
    context array creation
    function definition
    safe error handling

Not allowed operations:

    INSERT
    UPDATE
    DELETE
    MERGE
    DROP
    ALTER
    TRUNCATE
    Login replacement
    Config change
    Role assignment
    Permission creation
    Tenant creation

## 11. Required Test After First Implementation

After creating includes/erp-auth-context.php, the implementation must be tested by syntax check only.

Required test:

    php -l includes/erp-auth-context.php

No browser workflow test is required for the auth helper alone.

## 12. Commit Boundary

The first implementation commit must include only:

    includes/erp-auth-context.php

No other file may be included in that commit.

## 13. Final Sign-Off Decision

The Phase 2 Controlled Prototype File-Level Implementation Plan is approved.

The next approved step is only:

    Create includes/erp-auth-context.php

Full workflow implementation remains blocked.

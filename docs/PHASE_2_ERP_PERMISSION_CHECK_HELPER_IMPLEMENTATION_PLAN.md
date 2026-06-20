# Phase 2 ERP Permission Check Helper Implementation Plan

Project: MOGHARE360 ERP  
Phase: Phase 2  
Document Type: Implementation Plan  
Status: Planning Only  
Implementation Status: Not Started  

## 1. Purpose

This document defines the implementation plan for the ERP Permission Check Helper in MOGHARE360 Phase 2 controlled prototype.

This is a planning document only.

No PHP implementation is approved by this document alone.

## 2. Approved Previous Helpers

Completed helpers:

    includes/erp-auth-context.php
    includes/erp-csrf.php

Confirmed status:

    Syntax tests passed
    No login replacement
    No database write
    No config change
    No user, role, permission, tenant, or workflow change

## 3. Next Planned Helper

Planned file:

    includes/erp-permission-check.php

Purpose:

    Provide isolated permission validation for controlled ERP prototype actions.

This helper will be used later by:

    public_html/erp-access-request-transition.php

The browser page is not approved for creation yet.

## 4. Implementation Boundary

The Permission Check Helper may only validate permissions from the ERP auth context array.

Allowed behavior:

    Read active_permissions from context
    Read active_roles from context
    Check requested permission key
    Allow temporary Platform Owner fallback
    Throw safe exception on missing permission

Not allowed behavior:

    Database connection
    INSERT
    UPDATE
    DELETE
    MERGE
    DROP
    ALTER
    TRUNCATE
    Login replacement
    User creation
    Role assignment
    Permission creation
    Workflow state change
    Tenant change

## 5. Planned Functions

The future helper may define:

    erp_permission_user_has(array $context, string $permission_key)
    erp_permission_require(array $context, string $permission_key)

## 6. Target Permission Concept

The first target permission concept is:

    access_request.submit

If this permission does not exist in the database yet, it must not be silently created.

Permission creation must be planned separately.

## 7. Prototype Fallback Rule

Temporary prototype actor:

    user_id = 10001
    username = mahin.paradigm.owner

Temporary rule:

    Platform Owner may pass permission checks during the controlled prototype only.

This fallback must be clearly documented in the PHP helper.

This fallback is not production authorization.

## 8. Failure Rule

The helper must reject:

    Empty permission key
    Missing current_user_id
    Invalid context
    Missing permission
    Non-owner user without permission

Failure must throw:

    RuntimeException

No HTML output is required from the helper.

## 9. Safety Rule

The helper must be isolated.

It may require:

    includes/erp-auth-context.php

It must not require:

    staff-auth.php
    access-control.php
    staff-login.php
    config.php
    config.example.php
    database connection

## 10. Required Test

After implementation, syntax check must run:

    php -l includes/erp-permission-check.php

Expected result:

    No syntax errors detected in includes/erp-permission-check.php

## 11. Commit Boundary

The future implementation commit must include only:

    includes/erp-permission-check.php

No other file may be included in that implementation commit.

## 12. Final Decision

The next approved implementation step after this plan and sign-off will be:

    Create includes/erp-permission-check.php

Full browser workflow implementation remains blocked.

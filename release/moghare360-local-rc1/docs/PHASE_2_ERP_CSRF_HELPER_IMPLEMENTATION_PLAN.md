# Phase 2 ERP CSRF Helper Implementation Plan

Project: MOGHARE360 ERP  
Phase: Phase 2  
Document Type: Implementation Plan  
Status: Planning Only  
Implementation Status: Not Started  

## 1. Purpose

This document defines the implementation plan for the ERP CSRF Helper in MOGHARE360 Phase 2 controlled prototype.

This is a planning document only.

No PHP implementation is approved by this document alone.

## 2. Approved Previous Step

Completed helper:

    includes/erp-auth-context.php

Completed test document:

    docs/PHASE_2_ERP_AUTH_CONTEXT_HELPER_TEST_RESULT.md

Confirmed status:

    Syntax test passed
    No login replacement
    No database write
    No config change
    No user, role, permission, tenant, or workflow change

## 3. Next Planned Helper

Planned file:

    includes/erp-csrf.php

Purpose:

    Provide CSRF token creation and validation for controlled browser POST actions.

This helper will be used later by:

    public_html/erp-access-request-transition.php

The browser page is not approved for creation yet.

## 4. Implementation Boundary

The CSRF helper may only provide session-based token functions.

Allowed behavior:

    Start session if needed
    Create CSRF token
    Store token in session
    Validate submitted token
    Throw safe exception on missing or invalid token

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

    erp_csrf_start_session_if_needed()
    erp_csrf_create_token(string $form_key)
    erp_csrf_validate_token(string $form_key, string $token)
    erp_csrf_require_valid_token(string $form_key, string $token)

## 6. Planned Form Key

The first approved form key is:

    access_request_submit

No other form key is required in this step.

## 7. Token Rule

Token generation must use:

    random_bytes()

Token storage must use:

    $_SESSION

Token comparison must use:

    hash_equals()

## 8. Failure Rule

The helper must reject:

    Missing form key
    Empty form key
    Missing token
    Empty token
    Missing session token
    Invalid token

Failure must throw:

    RuntimeException

No HTML output is required from the helper.

## 9. Safety Rule

The helper must be isolated.

It must not require:

    staff-auth.php
    access-control.php
    staff-login.php
    config.php
    config.example.php
    database connection

## 10. Required Test

After implementation, syntax check must run:

    php -l includes/erp-csrf.php

Expected result:

    No syntax errors detected in includes/erp-csrf.php

## 11. Commit Boundary

The future implementation commit must include only:

    includes/erp-csrf.php

No other file may be included in that implementation commit.

## 12. Final Decision

The next approved implementation step after this plan and sign-off will be:

    Create includes/erp-csrf.php

Full browser workflow implementation remains blocked.

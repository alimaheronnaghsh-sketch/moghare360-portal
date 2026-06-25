# Phase 2 ERP CSRF Helper Implementation Plan Sign-Off

Project: MOGHARE360 ERP  
Phase: Phase 2  
Document Type: Sign-Off  
Status: Approved for ERP CSRF Helper Implementation  
Implementation Status: Not Started  

## 1. Sign-Off Purpose

This document confirms that the Phase 2 ERP CSRF Helper Implementation Plan has been reviewed and accepted.

This sign-off approves moving to the isolated ERP CSRF Helper implementation step.

This sign-off does not approve browser workflow implementation yet.

## 2. Approved Source Document

Approved document:

    docs/PHASE_2_ERP_CSRF_HELPER_IMPLEMENTATION_PLAN.md

Approved next implementation file:

    includes/erp-csrf.php

## 3. Confirmed Previous Helper

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

## 4. Approved CSRF Helper Boundary

The ERP CSRF Helper may only provide:

    session start helper
    token creation
    token storage in session
    token validation
    safe exception on missing or invalid token

## 5. Approved Planned Functions

The next implementation may define:

    erp_csrf_start_session_if_needed()
    erp_csrf_create_token(string $form_key)
    erp_csrf_validate_token(string $form_key, string $token)
    erp_csrf_require_valid_token(string $form_key, string $token)

## 6. Approved Form Key

The first approved form key is:

    access_request_submit

## 7. Approved Token Rules

Token generation must use:

    random_bytes()

Token storage must use:

    $_SESSION

Token comparison must use:

    hash_equals()

Failure must throw:

    RuntimeException

## 8. Not Approved in This Sign-Off

This sign-off does not approve:

- Browser transition page
- Permission helper
- Workflow engine
- Login replacement
- Config changes
- User creation
- Role creation
- Role assignment
- Permission creation
- Tenant creation
- Workflow state change
- SQL schema change
- Database write operation

## 9. Files That Must Not Be Modified

The next implementation step must not modify:

    staff-auth.php
    access-control.php
    staff-login.php
    config.php
    config.example.php
    customer portal files
    inventory files
    legacy files
    existing dashboard files
    includes/erp-auth-context.php

## 10. Required Test After Implementation

After creating includes/erp-csrf.php, run:

    php -l includes/erp-csrf.php

Expected result:

    No syntax errors detected in includes/erp-csrf.php

## 11. Commit Boundary

The implementation commit must include only:

    includes/erp-csrf.php

No other file may be included.

## 12. Final Sign-Off Decision

The Phase 2 ERP CSRF Helper Implementation Plan is approved.

The next approved step is only:

    Create includes/erp-csrf.php

Full browser workflow implementation remains blocked.

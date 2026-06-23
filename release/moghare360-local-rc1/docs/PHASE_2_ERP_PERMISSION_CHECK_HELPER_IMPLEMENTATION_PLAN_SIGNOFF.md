# Phase 2 ERP Permission Check Helper Implementation Plan Sign-Off

Project: MOGHARE360 ERP  
Phase: Phase 2  
Document Type: Sign-Off  
Status: Approved for ERP Permission Check Helper Implementation  
Implementation Status: Not Started  

## 1. Sign-Off Purpose

This document confirms that the Phase 2 ERP Permission Check Helper Implementation Plan has been reviewed and accepted.

This sign-off approves moving to the isolated ERP Permission Check Helper implementation step.

This sign-off does not approve browser workflow implementation yet.

## 2. Approved Source Document

Approved document:

    docs/PHASE_2_ERP_PERMISSION_CHECK_HELPER_IMPLEMENTATION_PLAN.md

Approved next implementation file:

    includes/erp-permission-check.php

## 3. Confirmed Previous Helpers

Completed helpers:

    includes/erp-auth-context.php
    includes/erp-csrf.php

Confirmed status:

    Syntax tests passed
    No login replacement
    No database write
    No config change
    No user, role, permission, tenant, or workflow change

## 4. Approved Permission Helper Boundary

The ERP Permission Check Helper may only validate permissions from the ERP auth context array.

Allowed behavior:

    Read active_permissions from context
    Read active_roles from context
    Check requested permission key
    Allow temporary Platform Owner fallback
    Throw safe exception on missing permission

## 5. Approved Planned Functions

The next implementation may define:

    erp_permission_user_has(array $context, string $permission_key)
    erp_permission_require(array $context, string $permission_key)

## 6. Approved Permission Concept

The first target permission concept is:

    access_request.submit

If this permission does not exist in the database yet, it must not be silently created.

Permission creation must be planned separately.

## 7. Approved Prototype Fallback

Temporary prototype actor:

    user_id = 10001
    username = mahin.paradigm.owner

Temporary rule:

    Platform Owner may pass permission checks during the controlled prototype only.

This fallback is not production authorization.

## 8. Not Approved in This Sign-Off

This sign-off does not approve:

- Browser transition page
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
    includes/erp-csrf.php

## 10. Required Test After Implementation

After creating includes/erp-permission-check.php, run:

    php -l includes/erp-permission-check.php

Expected result:

    No syntax errors detected in includes/erp-permission-check.php

## 11. Commit Boundary

The implementation commit must include only:

    includes/erp-permission-check.php

No other file may be included.

## 12. Final Sign-Off Decision

The Phase 2 ERP Permission Check Helper Implementation Plan is approved.

The next approved step is only:

    Create includes/erp-permission-check.php

Full browser workflow implementation remains blocked.

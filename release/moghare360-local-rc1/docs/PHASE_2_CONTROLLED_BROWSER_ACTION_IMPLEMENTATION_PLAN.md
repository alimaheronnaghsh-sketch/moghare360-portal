# Phase 2 Controlled Browser Action Implementation Plan

Project: MOGHARE360 ERP
Phase: Phase 2
Document Type: Implementation Plan
Status: Planning Only
Implementation Status: Not Started

## 1. Purpose

This document defines the implementation plan for the first controlled browser action in MOGHARE360 Phase 2.

This is a planning document only.

No PHP browser page implementation is approved by this document alone.

## 2. Completed Isolated Helpers

Completed Phase 2 isolated helpers:

```
includes/erp-auth-context.php
includes/erp-csrf.php
includes/erp-permission-check.php
includes/erp-workflow-engine.php
```

Confirmed status:

```
Syntax tests passed
No login replacement
No database write
No config change
No user, role, permission, tenant, or workflow state change
```

## 3. Planned Browser Action File

Planned file:

```
public_html/erp-access-request-transition.php
```

Purpose:

```
Provide the first controlled browser action prototype for Access Request workflow transition.
```

This page must not be created until this plan receives sign-off.

## 4. First Controlled Action Scope

The first controlled browser action scope is:

```
Entity: access_request
Transition: DRAFT -> SUBMITTED
Permission concept: access_request.submit
```

No other entity, workflow, transition, permission, or browser action is approved in this plan.

## 5. Required Execution Chain

The future browser action must follow this chain:

```
Browser Form
CSRF Validation
Auth Context Check
Permission Check
Workflow Transition Validation
Audit / History Planning Boundary
State Update Planning Boundary
```

Important:

```
This implementation plan does not approve database state update yet.
This implementation plan does not approve audit/history database write yet.
```

## 6. Approved Future Page Behavior

The future page may:

```
Load isolated ERP helper files
Display a controlled HTML form
Generate CSRF token for the form
Accept POST request for one transition action
Validate CSRF token
Load temporary ERP auth context
Require current user context
Require access_request.submit permission
Validate access_request DRAFT -> SUBMITTED transition
Return a safe success preview/result
```

The future page must remain a controlled prototype.

## 7. Not Approved Yet

This plan does not approve:

* Direct database update
* Workflow state update in SQL Server
* Audit table insert
* History table insert
* Access request row mutation
* Permission creation
* Login replacement
* Production authorization
* Multi-step workflow processing
* Customer portal change
* Inventory module change
* Legacy file change

## 8. Required Helper Usage

The future page may require:

```
includes/erp-auth-context.php
includes/erp-csrf.php
includes/erp-permission-check.php
includes/erp-workflow-engine.php
```

The future page must not require:

```
staff-auth.php
access-control.php
staff-login.php
config.php
config.example.php
database connection
```

## 9. Required Safety Rules

The future page must:

```
Use POST for transition action
Use CSRF token validation
Use auth context validation
Use permission validation
Use workflow transition validation
Escape HTML output
Avoid raw user-controlled output
Throw or display safe error messages only
Avoid database connection
Avoid database write operation
```

## 10. Required Form Key

The future page must use this CSRF form key:

```
access_request_submit
```

## 11. Required Permission Key

The future page must check this permission concept:

```
access_request.submit
```

If this permission does not exist in the database yet, it must not be silently created.

Temporary Platform Owner fallback remains controlled prototype only.

## 12. Required Workflow Validation

The future page must validate only:

```
Entity: access_request
From state: DRAFT
To state: SUBMITTED
```

No other transition may be executed or previewed.

## 13. Required Test After Future Implementation

After creating public_html/erp-access-request-transition.php, run:

```
php -l public_html/erp-access-request-transition.php
```

Expected result:

```
No syntax errors detected in public_html/erp-access-request-transition.php
```

## 14. Runtime Copy Rule

After implementation and syntax test, the file may later be copied to local runtime only after explicit instruction.

Potential runtime target:

```
C:\xampp\htdocs\moghare360\erp-access-request-transition.php
```

Runtime copy is not approved by this plan alone.

## 15. Commit Boundary

The future implementation commit must include only:

```
public_html/erp-access-request-transition.php
```

No other file may be included in that implementation commit.

## 16. Final Decision

The next approved step after this plan is:

```
Create sign-off document for this Controlled Browser Action Implementation Plan
```

The browser action page remains blocked until sign-off is completed.

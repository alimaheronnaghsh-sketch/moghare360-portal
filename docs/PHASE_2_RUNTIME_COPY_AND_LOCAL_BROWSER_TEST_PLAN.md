# Phase 2 Runtime Copy and Local Browser Test Plan

Project: MOGHARE360 ERP
Phase: Phase 2
Document Type: Runtime Copy and Local Browser Test Plan
Status: Planning Only
Implementation Status: Not Started

## 1. Purpose

This document defines the controlled plan for copying the first Phase 2 browser action prototype to the local XAMPP runtime and testing it in the browser.

This is a planning document only.

No runtime copy is approved by this document alone.

## 2. Approved Source File

Approved source file:

```
public_html/erp-access-request-transition.php
```

The source file has already been created, syntax tested, committed, and pushed.

Confirmed syntax test:

```
php -l public_html/erp-access-request-transition.php
```

Result:

```
No syntax errors detected in public_html/erp-access-request-transition.php
```

## 3. Runtime Copy Target

Planned runtime target:

```
C:\xampp\htdocs\moghare360\erp-access-request-transition.php
```

Runtime copy is not performed in this step.

Runtime copy must wait for a separate sign-off document.

## 4. Required Runtime Helper Files

The browser action depends on these helper files:

```
includes/erp-auth-context.php
includes/erp-csrf.php
includes/erp-permission-check.php
includes/erp-workflow-engine.php
```

For local runtime execution, these helper files must be available under:

```
C:\xampp\htdocs\moghare360\includes\
```

The runtime helper copy status must be checked before browser testing.

No helper copy is approved by this document alone.

## 5. Planned Local URL

Planned local test URL:

```
http://localhost:8080/moghare360/erp-access-request-transition.php
```

This URL must not be tested until runtime copy is approved and completed.

## 6. Planned Runtime Copy Command

After sign-off only, the planned copy command for the browser action file may be:

```
Copy-Item "C:\Users\User\Documents\GitHub\alimaheronnaghsh-sketch\moghare360-portal\public_html\erp-access-request-transition.php" "C:\xampp\htdocs\moghare360\erp-access-request-transition.php" -Force
```

This command must not be executed during this planning step.

## 7. Planned Runtime Helper Verification

Before browser testing, the following runtime files must exist:

```
C:\xampp\htdocs\moghare360\includes\erp-auth-context.php
C:\xampp\htdocs\moghare360\includes\erp-csrf.php
C:\xampp\htdocs\moghare360\includes\erp-permission-check.php
C:\xampp\htdocs\moghare360\includes\erp-workflow-engine.php
```

If any helper is missing, helper runtime copy must be planned and approved before browser testing.

## 8. Planned Browser Test Scope

The local browser test may verify:

```
Page loads successfully
Controlled prototype boundary message is visible
Current prototype actor is displayed
Controlled transition table is displayed
CSRF form is rendered
Preview button is displayed
POST request validates CSRF
Auth context is available
Permission check passes for temporary Platform Owner fallback
Workflow validation approves DRAFT -> SUBMITTED
Success preview is displayed
Database update remains blocked
Audit/history write remains blocked
```

## 9. Expected Success Message

After clicking the preview button, the expected success message is:

```
Controlled transition preview approved. No database state was changed.
```

Expected blocked confirmations:

```
Database Update: Blocked
Audit / History Write: Blocked
```

## 10. Not Approved in This Plan

This plan does not approve:

* Runtime copy
* Helper runtime copy
* Database connection
* Database update
* Workflow state update in SQL Server
* Audit table insert
* History table insert
* Access request row mutation
* Permission creation
* Login replacement
* Production authorization
* Customer portal change
* Inventory module change
* Legacy file change
* SQL schema change

## 11. Required Safety Rules During Future Test

During future local browser testing:

```
Do not edit runtime files manually
Do not edit Git source files during the test
Do not connect the page to database
Do not add SQL queries
Do not add INSERT, UPDATE, DELETE, MERGE, DROP, ALTER, or TRUNCATE
Do not create audit/history writes
Do not modify login or config files
Do not test unrelated portal pages
```

## 12. Required Test Evidence After Future Runtime Test

After the future runtime test, the result document must record:

```
Runtime files copied
Local URL tested
Browser load result
POST preview result
Success or error message
Confirmation that database remained unchanged
Confirmation that no audit/history write occurred
Confirmation that no SQL was introduced
Confirmation that no unrelated file was changed
```

## 13. Commit Boundary

This planning commit must include only:

```
docs/PHASE_2_RUNTIME_COPY_AND_LOCAL_BROWSER_TEST_PLAN.md
```

No runtime file may be included.

No PHP file may be changed.

## 14. Final Decision

The next approved step after this plan is:

```
Create sign-off document for Runtime Copy and Local Browser Test Plan
```

Runtime copy remains blocked until that sign-off is completed.

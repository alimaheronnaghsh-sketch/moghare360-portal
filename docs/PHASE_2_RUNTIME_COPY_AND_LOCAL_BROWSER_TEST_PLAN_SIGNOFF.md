# Phase 2 Runtime Copy and Local Browser Test Plan Sign-Off

Project: MOGHARE360 ERP
Phase: Phase 2
Document Type: Sign-Off
Status: Approved for Controlled Runtime Copy and Local Browser Test
Implementation Status: Not Started

## 1. Sign-Off Purpose

This document confirms that the Phase 2 Runtime Copy and Local Browser Test Plan has been reviewed and accepted.

This sign-off approves moving to the controlled runtime copy and local browser test step.

This sign-off does not approve database workflow state update.

This sign-off does not approve audit/history database write.

This sign-off does not approve production workflow implementation.

## 2. Approved Source Document

Approved document:

```
docs/PHASE_2_RUNTIME_COPY_AND_LOCAL_BROWSER_TEST_PLAN.md
```

Approved source file:

```
public_html/erp-access-request-transition.php
```

Approved runtime target:

```
C:\xampp\htdocs\moghare360\erp-access-request-transition.php
```

Approved local test URL:

```
http://localhost:8080/moghare360/erp-access-request-transition.php
```

## 3. Confirmed Completed Components

Completed isolated helpers:

```
includes/erp-auth-context.php
includes/erp-csrf.php
includes/erp-permission-check.php
includes/erp-workflow-engine.php
```

Completed controlled browser action:

```
public_html/erp-access-request-transition.php
```

Confirmed status:

```
Syntax tests passed
No login replacement
No database write
No config change
No user, role, permission, tenant, or workflow state change
No audit/history write
No runtime copy yet
```

## 4. Approved Runtime Copy Scope

The next execution step may copy only this browser action file:

```
Source:
C:\Users\User\Documents\GitHub\alimaheronnaghsh-sketch\moghare360-portal\public_html\erp-access-request-transition.php

Target:
C:\xampp\htdocs\moghare360\erp-access-request-transition.php
```

No other source file may be copied without separate instruction.

## 5. Required Runtime Helper Verification

Before local browser testing, the following runtime helper files must exist:

```
C:\xampp\htdocs\moghare360\includes\erp-auth-context.php
C:\xampp\htdocs\moghare360\includes\erp-csrf.php
C:\xampp\htdocs\moghare360\includes\erp-permission-check.php
C:\xampp\htdocs\moghare360\includes\erp-workflow-engine.php
```

If any helper file is missing, browser testing must stop.

Missing helper runtime copy must be handled as a separate controlled step.

## 6. Approved Runtime Copy Command

The next controlled execution step may run:

```
Copy-Item "C:\Users\User\Documents\GitHub\alimaheronnaghsh-sketch\moghare360-portal\public_html\erp-access-request-transition.php" "C:\xampp\htdocs\moghare360\erp-access-request-transition.php" -Force
```

This command may be executed only after this sign-off document is committed and pushed.

## 7. Approved Local Browser Test Scope

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

## 8. Expected Browser Test Result

After opening:

```
http://localhost:8080/moghare360/erp-access-request-transition.php
```

The page should load without PHP fatal error.

After clicking the preview button, the expected success message is:

```
Controlled transition preview approved. No database state was changed.
```

Expected blocked confirmations:

```
Database Update: Blocked
Audit / History Write: Blocked
```

## 9. Not Approved in This Sign-Off

This sign-off does not approve:

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
* Any runtime copy other than the approved browser action file

## 10. Required Safety Rules During Runtime Test

During the next runtime test step:

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

## 11. Required Test Evidence After Runtime Test

After the next runtime test, the result document must record:

```
Runtime file copied
Runtime helper file availability
Local URL tested
Browser load result
POST preview result
Success or error message
Confirmation that database remained unchanged
Confirmation that no audit/history write occurred
Confirmation that no SQL was introduced
Confirmation that no unrelated file was changed
```

## 12. Commit Boundary

This sign-off commit must include only:

```
docs/PHASE_2_RUNTIME_COPY_AND_LOCAL_BROWSER_TEST_PLAN_SIGNOFF.md
```

No runtime file may be included.

No PHP file may be changed.

## 13. Final Sign-Off Decision

The Phase 2 Runtime Copy and Local Browser Test Plan is approved.

The next approved execution step is only:

```
Verify required runtime helper files
Copy public_html/erp-access-request-transition.php to C:\xampp\htdocs\moghare360
Test http://localhost:8080/moghare360/erp-access-request-transition.php locally
```

Database workflow state update remains blocked.

Audit/history database write remains blocked.

Production workflow implementation remains blocked.

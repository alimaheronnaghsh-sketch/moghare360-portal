# Phase 2 Runtime Helper Copy Plan Sign-Off

Project: MOGHARE360 ERP
Phase: Phase 2
Document Type: Sign-Off
Status: Approved for Controlled Runtime Helper Copy
Implementation Status: Not Started

## 1. Sign-Off Purpose

This document confirms that the Phase 2 Runtime Helper Copy Plan has been reviewed and accepted.

This sign-off approves moving to the controlled runtime helper copy step.

This sign-off does not approve browser action runtime copy yet.

This sign-off does not approve local browser test yet.

This sign-off does not approve database workflow state update.

This sign-off does not approve audit/history database write.

## 2. Approved Source Document

Approved document:

```
docs/PHASE_2_RUNTIME_HELPER_COPY_PLAN.md
```

## 3. Runtime Helper Check Result

Runtime root checked:

```
C:\xampp\htdocs\moghare360
```

Required runtime helper folder:

```
C:\xampp\htdocs\moghare360\includes
```

Runtime check result:

```
MISSING: C:\xampp\htdocs\moghare360\includes\erp-auth-context.php
MISSING: C:\xampp\htdocs\moghare360\includes\erp-csrf.php
MISSING: C:\xampp\htdocs\moghare360\includes\erp-permission-check.php
OK:      C:\xampp\htdocs\moghare360\includes\erp-workflow-engine.php
```

Because helper files were missing, browser action runtime copy was stopped.

Browser test was not performed.

## 4. Approved Runtime Helper Copy Scope

The next execution step may copy only these missing helper files:

```
includes/erp-auth-context.php
includes/erp-csrf.php
includes/erp-permission-check.php
```

No browser action file may be copied in this helper copy step.

No other helper file may be copied unless explicitly instructed.

The existing runtime helper file must not be modified in this step:

```
C:\xampp\htdocs\moghare360\includes\erp-workflow-engine.php
```

## 5. Approved Source Helper Files

Approved source files:

```
C:\Users\User\Documents\GitHub\alimaheronnaghsh-sketch\moghare360-portal\includes\erp-auth-context.php
C:\Users\User\Documents\GitHub\alimaheronnaghsh-sketch\moghare360-portal\includes\erp-csrf.php
C:\Users\User\Documents\GitHub\alimaheronnaghsh-sketch\moghare360-portal\includes\erp-permission-check.php
```

## 6. Approved Runtime Helper Targets

Approved runtime targets:

```
C:\xampp\htdocs\moghare360\includes\erp-auth-context.php
C:\xampp\htdocs\moghare360\includes\erp-csrf.php
C:\xampp\htdocs\moghare360\includes\erp-permission-check.php
```

## 7. Approved Runtime Helper Copy Commands

After this sign-off document is committed and pushed, the next controlled execution step may run:

```
Copy-Item "C:\Users\User\Documents\GitHub\alimaheronnaghsh-sketch\moghare360-portal\includes\erp-auth-context.php" "C:\xampp\htdocs\moghare360\includes\erp-auth-context.php" -Force

Copy-Item "C:\Users\User\Documents\GitHub\alimaheronnaghsh-sketch\moghare360-portal\includes\erp-csrf.php" "C:\xampp\htdocs\moghare360\includes\erp-csrf.php" -Force

Copy-Item "C:\Users\User\Documents\GitHub\alimaheronnaghsh-sketch\moghare360-portal\includes\erp-permission-check.php" "C:\xampp\htdocs\moghare360\includes\erp-permission-check.php" -Force
```

## 8. Required Runtime Helper Verification After Copy

After the controlled helper copy, the following runtime helper files must exist:

```
C:\xampp\htdocs\moghare360\includes\erp-auth-context.php
C:\xampp\htdocs\moghare360\includes\erp-csrf.php
C:\xampp\htdocs\moghare360\includes\erp-permission-check.php
C:\xampp\htdocs\moghare360\includes\erp-workflow-engine.php
```

## 9. Required Syntax Checks After Copy

After the controlled helper copy, syntax checks must run:

```
php -l C:\xampp\htdocs\moghare360\includes\erp-auth-context.php
php -l C:\xampp\htdocs\moghare360\includes\erp-csrf.php
php -l C:\xampp\htdocs\moghare360\includes\erp-permission-check.php
php -l C:\xampp\htdocs\moghare360\includes\erp-workflow-engine.php
```

Expected result for each file:

```
No syntax errors detected
```

## 10. Not Approved in This Sign-Off

This sign-off does not approve:

* Browser action runtime copy
* Local browser test
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
* Any source PHP modification
* Any runtime file edit beyond the approved helper copy

## 11. Required Safety Rules During Runtime Helper Copy

During the next runtime helper copy step:

```
Do not edit runtime files manually
Do not edit Git source files during the runtime copy
Do not connect any helper to database
Do not add SQL queries
Do not add INSERT, UPDATE, DELETE, MERGE, DROP, ALTER, or TRUNCATE
Do not create audit/history writes
Do not modify login or config files
Do not test unrelated portal pages
Do not copy the browser action file yet
```

## 12. Required Evidence After Runtime Helper Copy

After the next runtime helper copy, the result document must record:

```
Runtime helper files copied
Runtime helper files verified
Syntax test result for copied helpers
Confirmation that browser action copy was still not performed
Confirmation that browser test was still not performed
Confirmation that no SQL was introduced
Confirmation that no database write occurred
Confirmation that no unrelated file was changed
```

## 13. Commit Boundary

This sign-off commit must include only:

```
docs/PHASE_2_RUNTIME_HELPER_COPY_PLAN_SIGNOFF.md
```

No runtime file may be included.

No PHP file may be changed.

## 14. Final Sign-Off Decision

The Phase 2 Runtime Helper Copy Plan is approved.

The next approved execution step is only:

```
Copy missing helper files to C:\xampp\htdocs\moghare360\includes
Verify all required runtime helper files exist
Run php -l on all required runtime helper files
```

Browser action runtime copy remains blocked until helper copy is completed and verified.

Local browser test remains blocked.

Database workflow state update remains blocked.

Audit/history database write remains blocked.

Production workflow implementation remains blocked.

# Phase 2 Runtime Helper Copy Plan

Project: MOGHARE360 ERP
Phase: Phase 2
Document Type: Runtime Helper Copy Plan
Status: Planning Only
Implementation Status: Not Started

## 1. Purpose

This document defines the controlled plan for copying the missing Phase 2 ERP helper files to the local XAMPP runtime.

This is a planning document only.

No runtime copy is approved by this document alone.

## 2. Runtime Helper Check Result

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

## 3. Approved Source Helper Files

The missing helper files already exist in Git source:

```
C:\Users\User\Documents\GitHub\alimaheronnaghsh-sketch\moghare360-portal\includes\erp-auth-context.php
C:\Users\User\Documents\GitHub\alimaheronnaghsh-sketch\moghare360-portal\includes\erp-csrf.php
C:\Users\User\Documents\GitHub\alimaheronnaghsh-sketch\moghare360-portal\includes\erp-permission-check.php
```

These files were previously created, syntax tested, committed, and pushed.

## 4. Planned Runtime Helper Targets

The planned runtime helper copy targets are:

```
C:\xampp\htdocs\moghare360\includes\erp-auth-context.php
C:\xampp\htdocs\moghare360\includes\erp-csrf.php
C:\xampp\htdocs\moghare360\includes\erp-permission-check.php
```

The already existing file must not be modified in this helper copy step unless explicitly required:

```
C:\xampp\htdocs\moghare360\includes\erp-workflow-engine.php
```

## 5. Planned Runtime Helper Copy Commands

After sign-off only, the planned copy commands may be:

```
Copy-Item "C:\Users\User\Documents\GitHub\alimaheronnaghsh-sketch\moghare360-portal\includes\erp-auth-context.php" "C:\xampp\htdocs\moghare360\includes\erp-auth-context.php" -Force

Copy-Item "C:\Users\User\Documents\GitHub\alimaheronnaghsh-sketch\moghare360-portal\includes\erp-csrf.php" "C:\xampp\htdocs\moghare360\includes\erp-csrf.php" -Force

Copy-Item "C:\Users\User\Documents\GitHub\alimaheronnaghsh-sketch\moghare360-portal\includes\erp-permission-check.php" "C:\xampp\htdocs\moghare360\includes\erp-permission-check.php" -Force
```

These commands must not be executed during this planning step.

## 6. Required Runtime Helper Verification After Future Copy

After future helper copy, these files must exist:

```
C:\xampp\htdocs\moghare360\includes\erp-auth-context.php
C:\xampp\htdocs\moghare360\includes\erp-csrf.php
C:\xampp\htdocs\moghare360\includes\erp-permission-check.php
C:\xampp\htdocs\moghare360\includes\erp-workflow-engine.php
```

If all helper files exist, the next controlled step may retry copying:

```
public_html/erp-access-request-transition.php
```

to:

```
C:\xampp\htdocs\moghare360\erp-access-request-transition.php
```

## 7. Required Syntax Checks After Future Copy

After future helper runtime copy, syntax checks may run:

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

## 8. Not Approved in This Plan

This plan does not approve:

* Runtime helper copy
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

## 9. Required Safety Rules

During future runtime helper copy:

```
Do not edit runtime files manually
Do not edit Git source files during the runtime copy
Do not connect any helper to database
Do not add SQL queries
Do not add INSERT, UPDATE, DELETE, MERGE, DROP, ALTER, or TRUNCATE
Do not create audit/history writes
Do not modify login or config files
Do not test unrelated portal pages
```

## 10. Required Evidence After Future Helper Copy

After the future helper runtime copy, the result document must record:

```
Runtime helper files copied
Runtime helper files verified
Syntax test result for copied helpers
Confirmation that browser action copy was still controlled separately
Confirmation that no SQL was introduced
Confirmation that no database write occurred
Confirmation that no unrelated file was changed
```

## 11. Commit Boundary

This planning commit must include only:

```
docs/PHASE_2_RUNTIME_HELPER_COPY_PLAN.md
```

No runtime file may be included.

No PHP file may be changed.

## 12. Final Decision

The next approved step after this plan is:

```
Create sign-off document for Runtime Helper Copy Plan
```

Runtime helper copy remains blocked until that sign-off is completed.

Browser action runtime copy remains blocked until required helpers are present.

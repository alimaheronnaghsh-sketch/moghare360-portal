# Mission 17 - Testing Plan

## Purpose
This document defines the test plan for Mission 17.

## Tests

### 1. PHP Syntax Test
Commands:
```powershell
C:\xampp\php\php.exe -l public_html\erp-jobcard-create.php
C:\xampp\php\php.exe -l public_html\erp-jobcard-readonly-list.php
C:\xampp\php\php.exe -l public_html\erp-jobcard-detail.php
C:\xampp\php\php.exe -l tools\test-erp-jobcard-foundation.php
```

Expected:
- No syntax errors

### 2. SQL Execution Confirmation
Script:
- public_html/sql/sqlserver/mission_17_jobcard_foundation.sql

Expected:
- Executed manually in SSMS
- dbo.erp_jobcards created
- dbo.erp_jobcard_change_history created
- No destructive migration

### 3. CLI Foundation Test
Command:
```powershell
C:\xampp\php\php.exe tools\test-erp-jobcard-foundation.php
```

Expected:
- Overall: OK

### 4. Browser Create Test
URL:
- http://localhost:8080/moghare360/erp-jobcard-create.php

Expected:
- Auth Context loaded
- Permission Guard loaded
- CSRF required
- Controlled POST create succeeds
- Overall Status = OK

### 5. Browser Read-Only List Test
URL:
- http://localhost:8080/moghare360/erp-jobcard-readonly-list.php

Expected:
- Created JobCard visible
- Overall Status = OK

### 6. Browser Detail Test
URL:
- http://localhost:8080/moghare360/erp-jobcard-detail.php

Expected:
- JobCard header visible
- Customer and vehicle summary visible
- Reception data visible
- History timeline visible
- Overall Status = OK

### 7. History / Audit Test
Expected after create:
- JOBCARD_CREATED
- JOBCARD_RECEIVED when status is RECEIVED
- changed_by_user_id = 10001

### 8. Forbidden Scope Check
Expected:
- No Service Operation created
- No Inventory write
- No Finance write
- No Delivery write
- No Customer Portal change
- No legacy file change
- No forbidden files changed

## Final Test Result
Update M17_90_TEST_RESULT.md after all tests pass.

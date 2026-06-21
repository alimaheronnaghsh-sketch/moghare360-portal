# Browser Test Plan

## Browser URL
http://localhost:8080/moghare360/erp-auth-permission-workflow-readonly-test.php

## Purpose
Validate Auth Context, Permission Guard, and Workflow Read-Only data together in a browser read-only integration page.

## Expected Visible Results
- PHP version displayed
- ODBC extension status displayed
- Connection status = OK
- user_id = 10001
- username = mahin.paradigm.owner
- roles = owner, system_admin
- permissions count > 0
- guard access.request.approve = OK
- guard access.request.apply = OK
- request_id = 4
- request_number = AR-20260620-084634-10001
- request_type = ROLE_GRANT
- request_state = APPLIED
- workflow timeline status = COMPLETE
- ACCESS_REQUEST_SUBMITTED = OK
- ACCESS_REQUEST_UNDER_REVIEW = OK
- ACCESS_REQUEST_APPROVED = OK
- ACCESS_REQUEST_APPLIED = OK
- core_user_roles count = 2
- Real Assignment = NOT PERFORMED
- No write performed = OK
- Overall Status = OK

## Security Requirements
- meta noindex, nofollow
- warning banner visible
- no form
- no write
- no secret display
- no password_hash display

## CLI Companion Test
```powershell
C:\xampp\php\php.exe C:\Users\User\Documents\GitHub\alimaheronnaghsh-sketch\moghare360-portal\tools\test-erp-auth-permission-workflow-integration.php
```

## Mission 12 Boundary
Browser page is read-only integration validation only.
No workflow action is executed.

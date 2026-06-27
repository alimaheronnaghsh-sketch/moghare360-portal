# Mission 11 Testing Plan

## Purpose
Define validation for Mission 11 Access Denied handler prototype.

## 1. CLI Simulation Test
Command:
```powershell
C:\xampp\php\php.exe C:\Users\User\Documents\GitHub\alimaheronnaghsh-sketch\moghare360-portal\tools\test-erp-access-denied-handler.php
```

Expected:
- Mode: SIMULATION_ONLY
- actor_user_id = 10001
- action_key = admin.dashboard.view
- permission_key = placeholder_admin_dashboard_view
- decision = DENIED
- safe message = OK
- event shape = OK
- audit write = NOT PERFORMED
- No sensitive error exposed = OK
- Overall: OK

## 2. Browser Read-Only Simulation Test
URL:
http://localhost:8080/moghare360/erp-access-denied-readonly-test.php

Expected:
- test mode = SIMULATION_ONLY
- event shape = OK
- audit write = NOT PERFORMED
- No sensitive error exposed = OK
- Overall Status = OK

## 3. Event Shape Validation
Confirm denied event includes all required fields and decision = DENIED.

## 4. Safe Message Validation
Confirm safe message equals:

Access denied. You do not have permission to perform this action.

## 5. No Sensitive Error Validation
Confirm simulation output does not expose:
- SQLSTATE
- stack trace
- password_hash
- internal exception details
- ODBC internals

## 6. No Forbidden File Modification Test
Confirm Mission 11 does not modify:
- staff-auth.php
- access-control.php
- config.php
- Customer Portal files
- workflow write handlers
- permission tables

## Mission 11 Boundary
Mission 11 creates no runnable audit write path.
These are simulation validation rules only.

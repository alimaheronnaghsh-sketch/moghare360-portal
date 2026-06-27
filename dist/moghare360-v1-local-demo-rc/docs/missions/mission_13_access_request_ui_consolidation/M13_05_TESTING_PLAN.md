# Mission 13 Testing Plan

## 1. PHP Syntax Test
Command:
```powershell
C:\xampp\php\php.exe -l C:\Users\User\Documents\GitHub\alimaheronnaghsh-sketch\moghare360-portal\public_html\erp-access-request-admin.php
```

Expected:
- No syntax errors

## 2. Browser Admin UI Test
URL:
http://localhost:8080/moghare360/erp-access-request-admin.php

Expected:
- warning banner visible
- auth/permission summary visible
- request list visible
- default selected request_id = 4 visible
- Overall Status = OK

Detail URL:
http://localhost:8080/moghare360/erp-access-request-admin.php?request_id=4

## 3. Read-Only Boundary Test
Confirm:
- no form element
- no POST handling
- no submit/review/approve/apply links
- no secret display
- no password_hash display

## 4. Request List Visible
Confirm list query returns all access request rows ordered by request_id DESC.

## 5. Request Detail Visible
Confirm request_id 4 detail, items, approvals, and timeline load.

## 6. APPLIED State-Only Visible
Confirm state-only warning section displays:
- APPLIED = State-Only
- Real Assignment = NOT PERFORMED

## 7. Timeline Visible
For request_id = 4 confirm Timeline status = COMPLETE when required history types exist.

## 8. No Write Test
Confirm page performs SELECT only and displays No write performed = OK.

## 9. Forbidden File Test
Confirm Mission 13 does not modify:
- staff-auth.php
- access-control.php
- config.php
- workflow transition pages
- Mission 5 viewer
- helper files from Missions 8/10/11

## Mission 13 Boundary
Browser test required for signoff.
No CLI test file is created in Mission 13.

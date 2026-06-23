# Mission 15 Testing Plan

## 1. PHP Syntax Test
Commands:
```powershell
C:\xampp\php\php.exe -l C:\Users\User\Documents\GitHub\alimaheronnaghsh-sketch\moghare360-portal\public_html\erp-customer-vehicle-create.php
C:\xampp\php\php.exe -l C:\Users\User\Documents\GitHub\alimaheronnaghsh-sketch\moghare360-portal\public_html\erp-customer-vehicle-readonly-list.php
C:\xampp\php\php.exe -l C:\Users\User\Documents\GitHub\alimaheronnaghsh-sketch\moghare360-portal\tools\test-erp-customer-vehicle-foundation.php
```

Expected:
- No syntax errors

## 2. SQL Execution Confirmation
Execute in SSMS:
public_html/sql/sqlserver/mission_15_customer_vehicle_foundation.sql

Expected:
- Mission 15 Customer Vehicle SQL foundation script completed.

## 3. CLI Foundation Test
Command:
```powershell
C:\xampp\php\php.exe C:\Users\User\Documents\GitHub\alimaheronnaghsh-sketch\moghare360-portal\tools\test-erp-customer-vehicle-foundation.php
```

Expected:
- all Mission 15 tables = OK
- guard customer.vehicle.create = OK or PLACEHOLDER_OWNER_ALLOWED
- guard customer.vehicle.view = OK or PLACEHOLDER_OWNER_ALLOWED
- Overall: OK

## 4. Browser Create Test
URL:
http://localhost:8080/moghare360/erp-customer-vehicle-create.php

Expected after valid POST:
- Created Customer ID visible
- Created Vehicle ID visible
- Created Relation ID visible
- customer_code visible
- vehicle_code visible
- Audit/History = RECORDED
- Overall Status = OK

## 5. Browser Read-Only List Test
URL:
http://localhost:8080/moghare360/erp-customer-vehicle-readonly-list.php

Expected:
- recent relations visible
- Overall Status = OK
- no form
- no write

## 6. History/Audit Test
After one successful create, confirm rows exist in:
- dbo.erp_customer_vehicle_change_history

Expected change types:
- CUSTOMER_CREATED
- CUSTOMER_PHONE_CREATED
- VEHICLE_CREATED
- CUSTOMER_VEHICLE_RELATION_CREATED

## 7. Forbidden File Check
Confirm no changes to:
- Customer Portal
- legacy customer files
- staff-auth.php
- access-control.php
- config.php
- config.example.php
- access request workflow pages

## Mission 15 Boundary
User must run SQL manually before CLI/browser tests can fully pass.

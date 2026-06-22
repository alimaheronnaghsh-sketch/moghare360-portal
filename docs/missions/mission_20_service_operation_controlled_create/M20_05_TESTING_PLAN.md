# Mission 20 - Testing Plan

## Purpose
This document defines the test plan for Mission 20 Service Operation implementation.

## Prerequisites
1. Mission 17 JobCard foundation tables exist
2. JobCard jobcard_id = 1 exists (or update test expectation)
3. Execute mission_20_service_operation_foundation.sql in SSMS manually

## Test Sequence

### 1. SQL Object Existence
Validate:
- dbo.erp_service_operations
- dbo.erp_service_operation_change_history
- columns, PKs, FKs to dbo.erp_jobcards, status CHECK

### 2. CLI Foundation Test
Run:
```
php tools/test-erp-service-operation-foundation.php
```

Expected (after SQL + browser create):
- tables OK
- jobcard_id 1 OK
- Service Operation for jobcard_id 1 OK
- history SERVICE_OPERATION_CREATED OK
- guards OK
- Overall OK

### 3. Browser Create Test
Open:
- public_html/erp-service-operation-create.php

Create Service Operation for JobCard ID = 1:
- service_title required
- service_status ASSIGNED or IN_PROGRESS
- confirm message: Service Operation Created OK

### 4. List Page Test
Open:
- public_html/erp-service-operation-readonly-list.php

Confirm row shows jobcard_id, service_operation_id, service_title, service_status, created_at.

### 5. Detail Page Test
Open:
- public_html/erp-service-operation-detail.php?service_operation_id={id}

Confirm operation details and history timeline with SERVICE_OPERATION_CREATED.

### 6. Boundary Tests
Confirm no writes to:
- Inventory
- Finance
- QC
- Delivery
- Invoice

### 7. Forbidden File Check
Confirm no changes to config, staff-auth, access-control, Customer Portal, legacy files.

## Test Result Document
Record results in M20_90_TEST_RESULT.md after user execution.

## Current Status
PENDING USER TEST

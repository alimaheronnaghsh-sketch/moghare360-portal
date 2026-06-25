# Mission 20 - Implementation Plan

## Purpose
This document defines the Mission 20 implementation plan for the Service Operation Controlled Create Prototype.

## Deliverables

### 1. SQL Foundation Script
File:
- public_html/sql/sqlserver/mission_20_service_operation_foundation.sql

Creates if missing:
- dbo.erp_service_operations
- dbo.erp_service_operation_change_history

Includes:
- primary keys
- foreign keys to dbo.erp_jobcards
- service_status CHECK constraint
- safe indexes
- no DROP
- no TRUNCATE
- no destructive migration

Execution:
- Manual SSMS only
- Not auto-run from PHP

### 2. Controlled Create Page
File:
- public_html/erp-service-operation-create.php

Features:
- Auth Context
- Permission Guard action service.operation.create
- local Mission 20 CSRF wrapper
- POST only
- transaction create
- history row SERVICE_OPERATION_CREATED
- identity fetch by composite lookup after INSERT (no SCOPE_IDENTITY)
- accepts JobCard ID = 1 or any active JobCard
- initial status ASSIGNED or IN_PROGRESS only
- success message: Service Operation Created OK

### 3. Read-Only List Page
File:
- public_html/erp-service-operation-readonly-list.php

Features:
- SELECT only
- Permission Guard action service.operation.list
- shows jobcard_id, service_operation_id, service_title, service_status, created_at
- detail links

### 4. Read-Only Detail Page
File:
- public_html/erp-service-operation-detail.php

Features:
- SELECT only
- Permission Guard action service.operation.view
- Service Operation header, JobCard summary, description, history timeline

### 5. CLI Foundation Test
File:
- tools/test-erp-service-operation-foundation.php

Features:
- read-only checks
- table existence
- JobCard jobcard_id = 1 check
- Service Operation and history checks (PENDING until browser create)
- permission guard checks
- no writes

## Security Requirements
- user_id 10001 only for prototype pages
- Permission Guard required
- CSRF required for create POST
- controlled transaction for create
- safe error handling
- no secret exposure
- no OUTPUT INSERTED / SCOPE_IDENTITY / @@IDENTITY / IDENT_CURRENT

## Forbidden Scope
Mission 20 must not write Inventory, Finance, QC, Delivery, or Invoice tables.

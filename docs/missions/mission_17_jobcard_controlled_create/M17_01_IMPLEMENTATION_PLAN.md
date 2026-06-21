# Mission 17 - Implementation Plan

## Purpose
This document defines the Mission 17 implementation plan for the JobCard Controlled Create Prototype.

## Deliverables

### 1. SQL Foundation Script
File:
- public_html/sql/sqlserver/mission_17_jobcard_foundation.sql

Creates if missing:
- dbo.erp_jobcards
- dbo.erp_jobcard_change_history

Includes:
- primary keys
- foreign keys to Customer / Vehicle foundation
- safe indexes
- no DROP
- no TRUNCATE
- no destructive migration

Execution:
- Manual SSMS only
- Not auto-run from PHP

### 2. Controlled Create Page
File:
- public_html/erp-jobcard-create.php

Features:
- Auth Context
- Permission Guard action jobcard.create
- local Mission 17 CSRF wrapper
- POST only
- transaction create
- history rows JOBCARD_CREATED and JOBCARD_RECEIVED when applicable
- identity fetch by jobcard_number after INSERT
- safe local owner diagnostics on localhost POST failure

### 3. Read-Only List Page
File:
- public_html/erp-jobcard-readonly-list.php

Features:
- SELECT only
- Permission Guard action jobcard.list
- recent JobCards with customer and vehicle summary
- detail links

### 4. Read-Only Detail Page
File:
- public_html/erp-jobcard-detail.php

Features:
- SELECT only
- Permission Guard action jobcard.view
- JobCard header, customer summary, vehicle summary, reception data, history timeline

### 5. CLI Foundation Test
File:
- tools/test-erp-jobcard-foundation.php

Features:
- read-only checks
- table existence
- Customer / Vehicle foundation checks
- relation_id 1 check
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

## Mission 17 Boundary
No Service Operation, Inventory, Finance, Delivery, Customer Portal, legacy, config, login, tenant, or workflow scope.

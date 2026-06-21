# Mission 15 Implementation Plan

## Purpose
Mission 15 implements the first controlled ERP Customer / Vehicle foundation prototype after Mission 14 design lock.

## Components

### 1. SQL Foundation Script
File:
- public_html/sql/sqlserver/mission_15_customer_vehicle_foundation.sql

Creates idempotent foundation tables:
- dbo.erp_customers
- dbo.erp_customer_phones
- dbo.erp_vehicles
- dbo.erp_customer_vehicle_relations
- dbo.erp_customer_vehicle_change_history

Includes safe non-unique indexes.
No DROP. No TRUNCATE. No legacy table modification.

### 2. Controlled Create Page
File:
- public_html/erp-customer-vehicle-create.php

Requirements:
- Auth Context
- Permission Guard with placeholder action customer.vehicle.create
- CSRF on POST
- ODBC transaction
- history rows on success
- safe error handling

### 3. Read-Only List Page
File:
- public_html/erp-customer-vehicle-readonly-list.php

Requirements:
- SELECT only
- no form
- no POST
- Permission Guard placeholder action customer.vehicle.view

### 4. CLI Foundation Test
File:
- tools/test-erp-customer-vehicle-foundation.php

Requirements:
- read-only
- table existence checks
- auth/guard checks
- record counts only
- no write by test

## Security Requirements
- platform owner user_id 10001 allowed for local prototype placeholder actions
- all other users blocked safely
- no secret display
- no SQL error exposure on create page
- rollback on create failure

## Mission 15 Boundary
Prototype only.
Not production deploy.

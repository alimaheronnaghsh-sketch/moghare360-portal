# SQL Implementation Plan

## Purpose
This document prepares the future SQL implementation plan for Customer and Vehicle foundation.

## Important Rule
Mission 14 does not create or execute SQL.

## Future SQL Scope
A future SQL mission may create:

- ERP customer table
- customer phone/contact table
- vehicle table
- customer vehicle relation table
- indexes
- constraints
- audit/history placeholders

## Suggested Future Tables
Possible table names:

- dbo.erp_customers
- dbo.erp_customer_phones
- dbo.erp_vehicles
- dbo.erp_customer_vehicle_relations

Final names must be approved before SQL execution.

## Required SQL Controls
Future SQL must include:
- primary keys
- created_at
- updated_at
- created_by_user_id
- updated_by_user_id
- lifecycle_state
- indexes for search fields
- no destructive migration

## Future Write Boundary
Any future Create/Update must use:
- Auth Context
- Permission Guard
- CSRF
- Audit or history strategy

## Mission 14 Boundary
Plan only.
No SQL file is created.
No SQL is executed.

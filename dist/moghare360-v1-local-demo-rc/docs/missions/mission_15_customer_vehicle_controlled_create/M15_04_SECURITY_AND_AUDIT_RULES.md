# Security and Audit Rules

## Auth Context
Create and list pages use Mission 8 Auth Context helper for:
- current user id
- username
- roles
- permissions count

## Permission Guard
Mission 15 uses placeholder actions:
- customer.vehicle.create
- customer.vehicle.view

For local prototype:
- user_id 10001 may proceed when guard result is OK or PLACEHOLDER_OWNER_ALLOWED
- all other users are blocked safely

## CSRF
Create page POST requires valid CSRF token.

## Controlled Transaction
Create page uses ODBC transaction for all inserts.
Partial writes must not remain after failure.

## History Rows Required
On successful create, history rows must be inserted for:
- CUSTOMER_CREATED
- CUSTOMER_PHONE_CREATED
- VEHICLE_CREATED
- CUSTOMER_VEHICLE_RELATION_CREATED

## No Legacy Write
Mission 15 must not modify:
- Customer Portal files
- legacy customer pages
- staff-auth.php
- access-control.php
- config.php
- config.example.php

## No Customer Login
Mission 15 does not create customer login or customer portal access.

## No Production Deploy
Mission 15 is local controlled prototype only.

## Forbidden Mutations
Mission 15 must not perform:
- core_user_roles write
- access request workflow write
- role assignment
- permission mutation
- tenant implementation
- legacy migration

## Mission 15 Boundary
Security and audit rules apply to prototype pages only.

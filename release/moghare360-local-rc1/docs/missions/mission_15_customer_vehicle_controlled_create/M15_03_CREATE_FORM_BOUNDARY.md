# Create Form Boundary

## Purpose
Define the controlled create boundary for Mission 15.

## POST Only
Create page accepts POST only for write path.
GET must not perform writes.

## CSRF Required
Create page requires:
- includes/erp-csrf.php
- erp_csrf_create_token()
- erp_csrf_require_valid_token() on POST

## Auth Required
Create page requires:
- Auth Context start
- current user load
- platform owner user_id 10001 for local prototype

## Permission Guard Required
Create page requires:
- action key customer.vehicle.create
- placeholder permission allowed only for user 10001 in Mission 15 prototype
- all other users blocked safely

## Validation Rules
Required:
- full_name
- primary_mobile
- brand
- model
- at least one of plate_number or vin

Optional:
- national_id
- customer_type default PERSON
- city
- address
- customer_notes
- production_year integer if provided
- mileage integer if provided
- color
- vehicle_notes

## Transaction Rule
Create flow uses ODBC transaction:
- BEGIN via odbc_autocommit(false)
- insert customer
- insert customer phone
- insert vehicle
- insert relation
- insert history rows
- COMMIT on success

## Rollback Rule
On any failure:
- rollback transaction
- restore autocommit
- show safe generic error only

## Safe Error Rule
Create page must not expose:
- SQL errors
- stack traces
- ODBC internals
- password_hash or secrets

## No Auto Sample Data
Mission 15 must not auto-create sample records.
Write occurs only after valid POST + CSRF + Auth + Permission Guard.

## Mission 15 Boundary
Controlled create prototype only.

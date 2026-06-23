# Customer / Vehicle Link Rules

## Purpose
This document defines how JobCard must connect to Customer and Vehicle foundation records.

## Required Link
A JobCard must reference:
- customer_id
- vehicle_id

## Preferred Link
A JobCard should also reference:
- relation_id

if an active Customer / Vehicle relation exists.

## Data Source
Mission 17 must use records from:
- dbo.erp_customers
- dbo.erp_vehicles
- dbo.erp_customer_vehicle_relations

## Active Relation Rule
Preferred relation:
- relation_type = OWNER
- lifecycle_state = ACTIVE

## Validation Rule
Mission 17 must not allow JobCard creation for:
- missing customer_id
- missing vehicle_id
- non-existing customer
- non-existing vehicle

## Soft Run Rule
For Soft Run, JobCard creation should use the test Customer / Vehicle created in Mission 15 if available.

## Forbidden Rule
Mission 17 must not create Customer or Vehicle records.

Customer / Vehicle creation belongs to Mission 15.
JobCard creation must only link existing records.

## Final Link Decision
JobCard is dependent on Customer / Vehicle foundation and must not duplicate customer or vehicle identity data.

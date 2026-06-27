# SQL Implementation Plan

## Purpose
This document prepares the future SQL implementation plan for JobCard foundation.

## Important Rule
Mission 16 does not create or execute SQL.

## Future SQL Scope
Mission 17 may create:

- dbo.erp_jobcards
- dbo.erp_jobcard_change_history

## Suggested Table: dbo.erp_jobcards
Planned fields:
- jobcard_id
- jobcard_number
- customer_id
- vehicle_id
- relation_id
- reception_user_id
- assigned_team_id
- jobcard_status
- reception_at
- promised_at
- intake_mileage
- fuel_level
- customer_complaint
- requested_services_summary
- initial_vehicle_condition
- internal_notes
- priority_level
- lifecycle_state
- created_at
- updated_at
- created_by_user_id
- updated_by_user_id

## Suggested Table: dbo.erp_jobcard_change_history
Planned fields:
- history_id
- jobcard_id
- change_type
- previous_status
- new_status
- change_summary
- changed_by_user_id
- changed_at

## Required SQL Controls
Future SQL must include:
- primary keys
- foreign keys to Customer / Vehicle foundation
- created_at
- updated_at
- created_by_user_id
- updated_by_user_id
- lifecycle_state
- status field
- indexes for customer_id, vehicle_id, jobcard_number, jobcard_status
- no destructive migration

## Mission 16 Boundary
Plan only.
No SQL file is created.
No SQL is executed.

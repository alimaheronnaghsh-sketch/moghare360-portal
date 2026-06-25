# JobCard Data Model Plan

## Purpose
This document defines the planned JobCard foundation data model.

## Proposed JobCard Entity
A JobCard represents one controlled internal service reception record for one vehicle and one customer.

## Proposed Fields
Suggested JobCard fields:

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

## Suggested History Entity
JobCard history should track:

- history_id
- jobcard_id
- change_type
- previous_status
- new_status
- change_summary
- changed_by_user_id
- changed_at

## Suggested Number Format
JobCard number format for Soft Run:

JC-[YYYYMMDDHHMMSS]-[random 4 digits]

## Minimum Required Fields for Soft Run
Required:
- customer_id
- vehicle_id
- relation_id if available
- reception_user_id
- jobcard_status
- reception_at
- customer_complaint or requested_services_summary
- created_by_user_id

## Mission 16 Boundary
This is design only.
No table is created.

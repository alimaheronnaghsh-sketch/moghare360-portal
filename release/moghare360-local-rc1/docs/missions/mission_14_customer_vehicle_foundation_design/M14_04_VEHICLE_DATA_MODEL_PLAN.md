# Vehicle Data Model Plan

## Purpose
This document defines the planned Vehicle foundation for MOGHARE360 ERP.

## Proposed Vehicle Entity
Vehicle should represent a serviceable car/motor vehicle inside the ERP.

## Proposed Fields
Suggested Vehicle fields:

- vehicle_id
- vehicle_code
- plate_number
- vin
- chassis_number
- engine_number
- brand
- model
- trim
- production_year
- color
- mileage
- fuel_type
- transmission_type
- body_type
- lifecycle_state
- notes
- created_at
- updated_at
- created_by_user_id
- updated_by_user_id

## Lifecycle State
Possible values:
- ACTIVE
- INACTIVE
- SOLD
- UNKNOWN

## Soft Run Minimum Fields
Minimum required Vehicle fields for Soft Run:
- plate_number or vin
- brand
- model
- production_year optional
- mileage optional

## Identity Rules
Vehicle identity should prioritize:
1. VIN if available
2. Plate number if VIN is not available
3. Chassis number if needed

## Mission 14 Boundary
This is a design plan only.
No SQL is executed.

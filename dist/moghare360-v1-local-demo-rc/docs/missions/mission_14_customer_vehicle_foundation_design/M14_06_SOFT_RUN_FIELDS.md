# Soft Run Fields

## Purpose
This document defines the minimum fields required for Soft Run at Moghare Motors.

## Customer Required Fields
Required:
- full_name
- primary_mobile

Recommended:
- national_id
- customer_type
- address
- notes

## Vehicle Required Fields
Required:
- brand
- model
- plate_number or vin

Recommended:
- production_year
- mileage
- color
- chassis_number
- engine_number

## Relationship Required Fields
Required:
- customer_id
- vehicle_id
- relation_type = OWNER
- lifecycle_state = ACTIVE

## Soft Run Form Sections
The future form should include:

1. Customer information
2. Contact information
3. Vehicle information
4. Ownership / relationship information
5. Internal notes

## Data Quality Rule
Soft Run can allow some optional fields, but must not allow a vehicle record with no usable identity.

## Mission 14 Boundary
No form is created in Mission 14.

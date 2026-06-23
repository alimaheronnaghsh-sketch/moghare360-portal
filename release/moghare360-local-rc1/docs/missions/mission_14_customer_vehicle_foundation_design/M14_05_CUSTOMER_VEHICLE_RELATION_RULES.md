# Customer Vehicle Relation Rules

## Purpose
This document defines the relationship rules between Customers and Vehicles.

## Relationship Need
A customer may have multiple vehicles.
A vehicle may have different owners over time.

## Proposed Relationship Entity
CustomerVehicleRelation or VehicleOwnership should track:

- relation_id
- customer_id
- vehicle_id
- relation_type
- is_primary_owner
- valid_from
- valid_to
- lifecycle_state
- notes
- created_at
- updated_at

## Relation Types
Possible values:
- OWNER
- DRIVER
- COMPANY_CONTACT
- FLEET_MANAGER
- AUTHORIZED_PERSON

## Ownership History
Vehicle ownership should support history.

A vehicle should not lose past owner data when ownership changes.

## Soft Run Rule
For Soft Run, one active OWNER relation is enough.

## Data Integrity Rule
A JobCard in later missions should reference:
- customer_id
- vehicle_id
- active customer-vehicle relation if available

## Mission 14 Boundary
No relationship table is created in Mission 14.
This is design only.

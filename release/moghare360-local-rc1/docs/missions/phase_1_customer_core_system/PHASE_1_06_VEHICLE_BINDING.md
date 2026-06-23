# PHASE 1 — Vehicle Binding

## Pages

- Form: `erp-vehicle-binding.php`
- Write: `submit-vehicle-binding.php`

## Fields

`customer_id`, `intake_id`, `vehicle_id`, `relationship_type`, `license_plate`, `vin`, `brand`, `model`, `model_year`, `color`, `mileage_km`, `notes`

## Relationship Types

`OWNER`, `DRIVER`, `REPRESENTATIVE`, `FLEET_CONTACT`, `PREVIOUS_OWNER`

## Duplicate Check

- `erp_customer_vehicle_bindings` — license_plate, vin
- `Vehicles` legacy table (read-only, if exists)

Note: duplicate does not block insert; warning recorded in history.

## Photo Metadata

For each selected type (`FRONT`, `REAR`, `LEFT`, `RIGHT`, `INTERIOR`, `ODOMETER`, `DAMAGE`):

- Insert into `erp_vehicle_photo_records`
- `storage_status = PLACEHOLDER`
- No real file upload

## Security

- CSRF purpose: `customer_core_vehicle_binding`
- Permission: `customer.core.vehicle.binding.create`

## History

`action_type = VEHICLE_BINDING_CREATE`

## Redirect

`erp-customer-core-dashboard.php?phase1=vehicle_binding_ok`

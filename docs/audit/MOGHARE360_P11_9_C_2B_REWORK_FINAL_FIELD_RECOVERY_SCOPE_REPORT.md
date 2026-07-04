# MOGHARE360 P11.9-C-2B-REWORK-FINAL-FIELD-RECOVERY — Scope Report

**Phase:** P11.9-C-2B-REWORK-FINAL-FIELD-RECOVERY  
**Date:** 2026-07-04  
**Decision:** **CONTINUE** — read-only field recovery; no DB/Auth/OTP bypass.

---

## 1. Existing field sources (code inspection)

| Field | Sources found |
|-------|----------------|
| Customer name | `online_request.customer_name`, payload `customer_name`, `erp_customers.full_name`, intake |
| Mobile | `online_request.mobile`, payload, customer, intake |
| OTP | `m360_online_req_payload_otp_verified()` — payload `otp_verified=1` or column `otp_verified=1` only |
| Plate | `vehicle_plate` column, payload `vehicle_plate/plate/plate_display/plate_number/car_plate`, `plate_parts`, `erp_vehicles.plate_number`, intake `license_plate` |
| VIN/chassis | payload `vin/chassis/chassis_no/vehicle_vin`, `erp_vehicles.vin` |
| Brand/model | payload `brand/vehicle_brand`, `model/vehicle_model`, ERP vehicle row |
| Mileage | payload `odometer_km/mileage/odometer/km/intake_mileage`, jobcard `odometer/intake_mileage` |
| Fuel | payload `fuel_level/intake_fuel_level/fuel`, jobcard `fuel_level` |
| Belongings/damage | payload keys + C-2C if absent |
| Service class | payload `reception_service_*` (C-2C write) |
| Photos | `erp_vehicle_photo_records`, `erp_jobcard_media`, payload `photos` |
| Contracts | `erp_intake_contracts` |

## 2–3. Prior implementations

- **Plate/VIN/brand:** `api/customer/request.php`, `m360_reception_build_vehicle_clean()`, online request columns
- **Mileage/fuel:** customer form payload + `erp_jobcards.intake_mileage/fuel_level/odometer`
- **OTP:** `m360-online-request-helper.php` — data-driven only

## 4. Display without DB changes

All recovered fields above — read from existing columns/payload/ERP rows.

## 5. Truly C-2C write

Service classification save, belongings/damage write if absent, expert review, final confirmation write, operational photo/diag upload from intake.

## 6. Overstated labels corrected

Removed generic `M360_RW_PHASE_NEXT_LABEL_FA` append on gate missing; per-field precise labels.

## 7. Scope confirmation

No DB schema, Auth, permission, workflow, or OTP bypass required.

**Scope gate: CONTINUE**
